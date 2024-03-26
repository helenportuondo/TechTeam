<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class getUsers extends Controller
{
    public function getUserInfo(Request $request)
    {
        // Verifica si se proporciona el parámetro 'id' en la URL
        if ($request->has('id')) {
            $userId = $request->input('id');

            // Link de la API para obtener datos del usuario
            $userInfoUrl = "https://api.twitch.tv/helix/users?id={$userId}";

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

                // Headers para la solicitud de datos del usuario
                $headers = [
                    'Client-ID' => $clientId,
                    'Authorization' => 'Bearer ' . $accessToken
                ];

                // Solicitud a la API para obtener datos del usuario
                $userInfoResponse = Http::withHeaders($headers)->get($userInfoUrl);

                if ($userInfoResponse->failed()) {
                    return response()->json(['error' => 'Error al obtener los datos del usuario'], 500);
                }

                $userData = $userInfoResponse->json();

                // Devolver los datos del usuario en formato JSON
                return response()->json($userData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }
        } else {
            return response()->json(['error' => 'Se requiere el parámetro "id" en la URL'], 400);
        }
    }
}

