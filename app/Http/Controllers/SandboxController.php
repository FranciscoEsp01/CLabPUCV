<?php

namespace App\Http\Controllers;

use App\Services\Security\CSourceSecurityValidator;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class SandboxController extends Controller
{
    /**
     * Maximum allowed execution output size in characters.
     */
    protected const MAX_OUTPUT_LENGTH = 25000;

    /**
     * Maximum execution time in seconds.
     */
    protected const EXECUTION_TIMEOUT_SECONDS = 3;

    /**
     * Maximum compilation time in seconds.
     */
    protected const COMPILATION_TIMEOUT_SECONDS = 5;

    public function execute(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:' . CSourceSecurityValidator::MAX_CODE_LENGTH],
            'stdin' => ['nullable', 'string', 'max:50000'],
        ]);

        $code = $validated['code'];
        $stdin = $validated['stdin'] ?? '';

        // 1. Static Security Analysis (AST/Token validation)
        $securityCheck = CSourceSecurityValidator::validate($code);
        if (!$securityCheck['isValid']) {
            SecurityAuditLogger::logViolation(
                'SANDBOX_SECURITY_BLOCK',
                'Intento de ejecución de código C con instrucciones o cabeceras restringidas.',
                [
                    'rule' => $securityCheck['rule'],
                    'reason' => $securityCheck['reason'],
                    'code_snippet' => Str::limit($code, 200),
                ],
                $request
            );

            return response()->json([
                'output' => "⚠️ [BLOQUEO DE SEGURIDAD]\n\n" . $securityCheck['reason'] . "\n\nPor favor, utiliza únicamente librerías estándar de C y funciones seguras."
            ], 422);
        }

        // 2. Create isolated temporary directory
        $uniqueId = Str::random(24);
        $tempDir = storage_path('app/sandbox/' . $uniqueId);

        try {
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0700, true);
            }

            $sourceFile = $tempDir . '/main.c';
            $executableFile = $tempDir . '/main';

            // Write sanitized source file
            File::put($sourceFile, $code);

            // 3. Isolated environment - empty env except minimal safe PATH
            $safeEnv = [
                'PATH' => '/usr/bin:/bin:/usr/local/bin',
                'LC_ALL' => 'C.UTF-8',
                'TMPDIR' => $tempDir,
            ];

            // 4. Compile with security hardening flags
            $compileCommand = [
                'gcc',
                '-O2',
                '-Wall',
                '-fstack-protector-strong',
                '-fno-asm',
                '-Werror=implicit-function-declaration',
                $sourceFile,
                '-o',
                $executableFile,
                '-lm', // link math library safely
            ];

            $compileProcess = new Process($compileCommand, $tempDir, $safeEnv);
            $compileProcess->setTimeout(self::COMPILATION_TIMEOUT_SECONDS);
            $compileProcess->run();

            if (!$compileProcess->isSuccessful()) {
                $compilationError = $compileProcess->getErrorOutput() ?: $compileProcess->getOutput();
                return response()->json([
                    'output' => "Error de compilación:\n" . Str::limit($compilationError, self::MAX_OUTPUT_LENGTH)
                ]);
            }

            // 5. Execute compiled binary in isolated process
            $runProcess = new Process([$executableFile], $tempDir, $safeEnv);
            $runProcess->setTimeout(self::EXECUTION_TIMEOUT_SECONDS);
            if (!empty($stdin)) {
                $runProcess->setInput($stdin);
            }
            $runProcess->run();

            $stdout = $runProcess->getOutput();
            $stderr = $runProcess->getErrorOutput();
            $exitCode = $runProcess->getExitCode();

            $fullOutput = $stdout . ($stderr ? "\n" . $stderr : '');

            // Truncate output if too long to prevent client/server memory exhaustion
            if (mb_strlen($fullOutput) > self::MAX_OUTPUT_LENGTH) {
                $fullOutput = mb_substr($fullOutput, 0, self::MAX_OUTPUT_LENGTH) . "\n\n[... Salida truncada por límite de tamaño de seguridad ...]";
            }

            if (!$runProcess->isSuccessful() && empty($stdout)) {
                return response()->json([
                    'output' => "Error de ejecución (código {$exitCode}):\n" . $fullOutput
                ]);
            }

            return response()->json([
                'output' => $fullOutput . "\n\n[Proceso finalizado con código {$exitCode}]"
            ]);

        } catch (Throwable $e) {
            SecurityAuditLogger::logViolation(
                'SANDBOX_RUNTIME_EXCEPTION',
                'Excepción durante la ejecución del sandbox C.',
                [
                    'error_message' => $e->getMessage(),
                ],
                $request
            );

            return response()->json([
                'output' => "Error interno al ejecutar el código. El proceso excedió el tiempo límite (" . self::EXECUTION_TIMEOUT_SECONDS . "s) o fue cancelado por el sistema de seguridad."
            ], 500);

        } finally {
            // Guaranteed cleanup of temp files
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }
}
