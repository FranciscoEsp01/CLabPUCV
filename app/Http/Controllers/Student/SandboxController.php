<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\QuizQuestion;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Exception;

class SandboxController extends Controller
{
    /**
     * URL base de la API de Judge0.
     * En producción, esto debería estar en una variable de entorno (ej. env('JUDGE0_API_URL')).
     */
    private $judge0Url = 'https://judge0-ce.p.rapidapi.com';

    /**
     * ID del lenguaje en Judge0 para C (GCC). Puede variar según la versión de Judge0.
     */
    private $languageId = 50;

    /**
     * Muestra la vista del Sandbox (Editor de código)
     */
    public function index()
    {
        // En Inertia.js (Vue) se renderizará el componente del editor.
        // Aquí podríamos cargar un desafío de prueba o permitir código libre.
        return inertia('Student/Sandbox');
    }

    /**
     * Compila y evalúa un reto lógico usando la API de Judge0
     */
    public function compile(Request $request)
    {
        $request->validate([
            'quiz_question_id' => 'required|exists:quiz_questions,id',
            'source_code' => 'required|string',
        ]);

        $question = QuizQuestion::with('testCases')->findOrFail($request->quiz_question_id);
        
        if ($question->type !== 'logical') {
            return response()->json(['error' => 'Esta pregunta no es un reto de código.'], 400);
        }

        $studentCode = $request->source_code;
        $testCases = $question->testCases;
        
        $allPassed = true;
        $results = [];

        foreach ($testCases as $testCase) {
            // Se puede inyectar el código del estudiante dentro del boilerplate o enviar directamente.
            // Para ANSI C, si el estudiante solo hizo la función, se fusiona:
            $mergedCode = $this->mergeCode($studentCode, $question->boilerplate_code);

            // Preparar el payload para Judge0
            $payload = [
                'language_id' => $this->languageId,
                'source_code' => $mergedCode,
                'stdin' => $testCase->input,
            ];

            try {
                // Realizar petición a Judge0
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    // Importante: Configura tus API Keys en el .env
                    'X-RapidAPI-Host' => 'judge0-ce.p.rapidapi.com',
                    'X-RapidAPI-Key' => env('JUDGE0_API_KEY', 'default_key_here')
                ])->post("{$this->judge0Url}/submissions?base64_encoded=false&wait=true", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Judge0 devuelve el stdout o stderr dependiendo de si compiló bien o no.
                    $stdout = trim($data['stdout'] ?? '');
                    $stderr = trim($data['stderr'] ?? '');
                    $compileOutput = trim($data['compile_output'] ?? '');

                    // Comprobar si hubo un error de compilación
                    if (!empty($compileOutput) || !empty($stderr)) {
                        $allPassed = false;
                        $results[] = [
                            'status' => 'error',
                            'message' => 'Error de compilación o ejecución.',
                            'details' => $compileOutput ?: $stderr
                        ];
                        break; // Detener evaluación al primer error grave
                    }

                    // Verificar contra la salida esperada
                    $expected = trim($testCase->expected_output);
                    if ($stdout === $expected) {
                        $results[] = ['status' => 'passed', 'input' => $testCase->input, 'output' => $stdout];
                    } else {
                        $allPassed = false;
                        $results[] = [
                            'status' => 'failed',
                            'expected' => $expected,
                            'actual' => $stdout,
                            'input' => $testCase->is_hidden ? 'Oculto' : $testCase->input
                        ];
                    }
                } else {
                    return response()->json(['error' => 'Error al comunicarse con el servidor de compilación.'], 500);
                }

            } catch (Exception $e) {
                return response()->json(['error' => 'Excepción del servidor: ' . $e->getMessage()], 500);
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
     * Función helper para fusionar el código del estudiante con la plantilla del profesor.
     * Ejemplo: Insertar el código del estudiante donde exista un marcador // __STUDENT_CODE__
     */
    private function mergeCode($studentCode, $boilerplate)
    {
        if (empty($boilerplate)) {
            return $studentCode;
        }

        $marker = '// __STUDENT_CODE__';
        if (strpos($boilerplate, $marker) !== false) {
            return str_replace($marker, $studentCode, $boilerplate);
        }

        // Si no hay marcador, por defecto se concatena.
        return $studentCode . "\n" . $boilerplate;
    }
}
