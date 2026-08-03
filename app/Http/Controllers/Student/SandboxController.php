<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\Submission;
use App\Services\Security\CSourceSecurityValidator;
use App\Services\Security\SecurityAuditLogger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SandboxController extends Controller
{
    /**
     * URL base de la API de Judge0.
     */
    private string $judge0Url;

    /**
     * ID del lenguaje en Judge0 para C (GCC).
     */
    private int $languageId = 50;

    public function __construct()
    {
        $this->judge0Url = config('services.judge0.url', env('JUDGE0_API_URL', 'https://judge0-ce.p.rapidapi.com'));
    }

    /**
     * Muestra la vista del Sandbox (Editor de código)
     */
    public function index()
    {
        return inertia('Student/Sandbox');
    }

    /**
     * Compila y evalúa un reto lógico usando la API de Judge0
     */
    public function compile(Request $request)
    {
        $request->validate([
            'quiz_question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'source_code' => ['required', 'string', 'max:' . CSourceSecurityValidator::MAX_CODE_LENGTH],
        ]);

        $studentCode = $request->input('source_code');

        // Security check on submitted student code
        $securityCheck = CSourceSecurityValidator::validate($studentCode);
        if (!$securityCheck['isValid']) {
            SecurityAuditLogger::logViolation(
                'STUDENT_SANDBOX_SECURITY_BLOCK',
                'Intento de envío de código con instrucciones no autorizadas.',
                [
                    'question_id' => $request->quiz_question_id,
                    'rule' => $securityCheck['rule'],
                    'reason' => $securityCheck['reason'],
                ],
                $request
            );

            return response()->json([
                'status' => 'error',
                'results' => [
                    [
                        'status' => 'error',
                        'message' => 'Violación de directiva de seguridad.',
                        'details' => $securityCheck['reason']
                    ]
                ]
            ], 422);
        }

        $question = QuizQuestion::with('testCases')->findOrFail($request->quiz_question_id);
        
        if ($question->type !== 'logical') {
            return response()->json(['error' => 'Esta pregunta no es un reto de código.'], 400);
        }

        $testCases = $question->testCases;
        $allPassed = true;
        $results = [];

        $judge0Key = config('services.judge0.key', env('JUDGE0_API_KEY', ''));

        foreach ($testCases as $testCase) {
            $mergedCode = $this->mergeCode($studentCode, $question->boilerplate_code);

            $payload = [
                'language_id' => $this->languageId,
                'source_code' => $mergedCode,
                'stdin' => (string) $testCase->input,
            ];

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-RapidAPI-Host' => 'judge0-ce.p.rapidapi.com',
                    'X-RapidAPI-Key' => $judge0Key,
                ])->timeout(10)->post("{$this->judge0Url}/submissions?base64_encoded=false&wait=true", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    $stdout = trim($data['stdout'] ?? '');
                    $stderr = trim($data['stderr'] ?? '');
                    $compileOutput = trim($data['compile_output'] ?? '');

                    if (!empty($compileOutput) || !empty($stderr)) {
                        $allPassed = false;
                        $results[] = [
                            'status' => 'error',
                            'message' => 'Error de compilación o ejecución.',
                            'details' => Str::limit($compileOutput ?: $stderr, 2000)
                        ];
                        break;
                    }

                    $expected = trim($testCase->expected_output);
                    if ($stdout === $expected) {
                        $results[] = [
                            'status' => 'passed',
                            'input' => $testCase->is_hidden ? 'Oculto' : $testCase->input,
                            'output' => $stdout
                        ];
                    } else {
                        $allPassed = false;
                        $results[] = [
                            'status' => 'failed',
                            'expected' => $testCase->is_hidden ? 'Oculto' : $expected,
                            'actual' => Str::limit($stdout, 1000),
                            'input' => $testCase->is_hidden ? 'Oculto' : $testCase->input
                        ];
                    }
                } else {
                    return response()->json(['error' => 'No fue posible evaluar la prueba en este momento. Inténtelo más tarde.'], 502);
                }

            } catch (Exception $e) {
                SecurityAuditLogger::logViolation(
                    'JUDGE0_API_EXCEPTION',
                    'Fallo de conexión o respuesta inválida de Judge0.',
                    ['exception' => $e->getMessage()],
                    $request
                );

                return response()->json(['error' => 'Error de comunicación con el motor de evaluación.'], 500);
            }
        }

        $finalStatus = $allPassed ? 'passed' : 'failed';

        // Registrar el envío (Submission) en la base de datos
        $submission = Submission::create([
            'user_id' => Auth::id(),
            'quiz_question_id' => $question->id,
            'submitted_code' => $studentCode,
            'status' => $finalStatus,
            'compiler_output' => json_encode($results)
        ]);

        // Gamificación: Otorgar puntos si el estudiante aprueba y es su primer acierto
        if ($finalStatus === 'passed') {
            $user = Auth::user();
            $alreadyPassed = Submission::where('user_id', $user->id)
                ->where('quiz_question_id', $question->id)
                ->where('status', 'passed')
                ->where('id', '!=', $submission->id)
                ->exists();

            if (!$alreadyPassed && $question->quiz) {
                $points = rand(27, 32);
                $user->increment('points', $points);
                
                // Marcar la lección como completada en la tabla pivote
                if ($question->quiz->lesson_id) {
                    $user->lessons()->syncWithoutDetaching([
                        $question->quiz->lesson_id => ['is_completed' => true]
                    ]);
                }
            }
        }

        return response()->json([
            'status' => $finalStatus,
            'results' => $results
        ]);
    }

    /**
     * Helper para fusionar el código del estudiante con la plantilla.
     */
    private function mergeCode(string $studentCode, ?string $boilerplate): string
    {
        if (empty($boilerplate)) {
            return $studentCode;
        }

        $marker = '// __STUDENT_CODE__';
        if (str_contains($boilerplate, $marker)) {
            return str_replace($marker, $studentCode, $boilerplate);
        }

        return $studentCode . "\n" . $boilerplate;
    }
}
