<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class getStreams extends Controller
{
    public function getLiveStreams()
    {
        // Link de la API para obtener streams en vivo
        $liveStreamsUrl = 'https://api.twitch.tv/helix/streams';

        // Datos de autenticación
        $clientId = 'szp2ugo2j6edjt8ytdak5n2n3hjkq3';
        $clientSecret = '07gk0kbwwzpuw2uqdzy1bjnsz9k32k';
        $accessTokenUrl = "https://id.twitch.tv/oauth2/token";

        // Introducimos los datos para la solicitud del token
        $response = Http::asForm()->post($accessTokenUrl, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
        }

        $responseData = $response->json();

        if (isset($responseData['access_token'])) {
            $accessToken = $responseData['access_token'];

            // Headers para la solicitud de streams en vivo
            $headers = [
                'Client-ID' => $clientId,
                'Authorization' => 'Bearer ' . $accessToken
            ];

            // Solicitud a la API para obtener streams en vivo
            $liveStreamsResponse = Http::withHeaders($headers)->get($liveStreamsUrl);

            if ($liveStreamsResponse->failed()) {
                return response()->json(['error' => 'Error al obtener los datos de streams en vivo'], 500);
            }

            $liveStreamsData = $liveStreamsResponse->json();

            // Crear array para los streams en vivo
            $formattedStreams = [];

            // Formatear datos de streams en vivo
            if (isset($liveStreamsData['data'])) {
                foreach ($liveStreamsData['data'] as $stream) {
                    $formattedStreams[] = [
                        'title' => $stream['title'],
                        'user_name' => $stream['user_name']
                    ];
                }
            }

            // Devolver los datos de streams en vivo formateados en JSON
            return response()->json($formattedStreams, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
        }
    }
}
