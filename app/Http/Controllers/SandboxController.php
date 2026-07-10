<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SandboxController extends Controller
{
    public function execute(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');

        // Create a unique temporary directory for this execution
        $uniqueId = Str::random(10);
        $tempDir = storage_path('app/sandbox/' . $uniqueId);
        
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $sourceFile = $tempDir . '/main.c';
        $executableFile = $tempDir . '/main';

        // Write code to file
        File::put($sourceFile, $code);

        // Compile
        $compileProcess = new Process(['gcc', $sourceFile, '-o', $executableFile]);
        $compileProcess->setTimeout(10);
        $compileProcess->run();

        if (!$compileProcess->isSuccessful()) {
            // Compilation failed
            $output = $compileProcess->getErrorOutput();
            // Clean up
            File::deleteDirectory($tempDir);
            return response()->json(['output' => "Error de compilación:\n" . $output]);
        }

        // Execution
        $runProcess = new Process([$executableFile]);
        $runProcess->setTimeout(5); // 5 seconds timeout to prevent infinite loops
        $runProcess->run();

        $output = $runProcess->getOutput();
        $error = $runProcess->getErrorOutput();

        // Clean up
        File::deleteDirectory($tempDir);

        if (!$runProcess->isSuccessful() && empty($output)) {
             return response()->json(['output' => "Error de ejecución:\n" . $error]);
        }

        $finalOutput = $output . $error;
        return response()->json(['output' => $finalOutput . "\n\n[Proceso terminado con código " . $runProcess->getExitCode() . "]"]);
    }
}
