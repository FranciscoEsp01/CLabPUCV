<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class AiTutorController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $userMessage = $request->input('message');
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return response()->json(['reply' => 'Error: OPENAI_API_KEY no está configurada.'], 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un tutor de lenguaje C amistoso. Ayudas a los estudiantes con su código en C. Mantén tus respuestas claras, concisas y en español.'],
                    ['role' => 'user', 'content' => $userMessage]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content');
                return response()->json(['reply' => trim($reply)]);
            }

            return response()->json(['reply' => 'Hubo un error comunicándose con OpenAI.'], 500);

        } catch (\Exception $e) {
            return response()->json(['reply' => 'Error de conexión: ' . $e->getMessage()], 500);
        }
    }
}
