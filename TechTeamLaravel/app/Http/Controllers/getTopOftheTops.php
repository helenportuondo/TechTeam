<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DateTime;
header('Content-Type: application/json');



class getTopOftheTops extends Controller
{
    public function fetchData(Request $request){
    	 header('Content-Type: application/json');
        function requestAccessToken($clientId, $clientSecret){
            // URL de solicitud de token de acceso
            $accessTokenUrl = "https://id.twitch.tv/oauth2/token?client_id={$clientId}&client_secret={$clientSecret}&grant_type=client_credentials";
    
            // Realizar la solicitud HTTP para obtener el token de acceso
            $response = Http::post($accessTokenUrl);
    
            // Verificar si la solicitud fue exitosa y devolver el token de acceso
            if ($response->ok()) {
                return $response['access_token'];
            } else {
                return null;
            }
        }
        function fetchTwitchData($url, $clientId, $accessToken){
            $response = Http::withHeaders([
                'Client-ID' => $clientId,
                'Authorization' => 'Bearer ' . $accessToken
            ])->get($url);
    
            // Verificar si la solicitud fue exitosa y devolver los datos
            if ($response->ok()) {
                return $response->json();
            } else {
                return null;
            }
        }
    
        function insertGames($gamesResponse){
            // Insertar los juegos en la base de datos
            foreach ($gamesResponse['data'] as $game) {
                // Verificar si el juego ya existe en la base de datos antes de insertarlo
                $existingGame = DB::table('Game')->where('game_id', $game['id'])->first();
    
                if (!$existingGame) {
                    DB::table('Game')->insert([
                        'game_id' => $game['id'],
                        'game_name' => $game['name'],
                        'last_update' => now()
                    ]);
                } else {
                    // Si el juego ya existe, puedes optar por actualizar su información si es necesario
                    DB::table('Game')
                        ->where('game_id', $game['id'])
                        ->update([
                            'game_name' => $game['name'],
                            'last_update' => now()
                        ]);
                }
            }
        }
    
         function fetchAndInsertVideos($gamesResponse, $clientId, $accessToken){
            // Obtener y insertar los videos para cada juego
            foreach ($gamesResponse['data'] as $game) {
                $videosUrl = "https://api.twitch.tv/helix/videos?game_id={$game['id']}&sort=views&first=40";
    
                $videosResponse = fetchTwitchData($videosUrl, $clientId, $accessToken);
    
                if ($videosResponse) {
                    insertVideos($videosResponse, $game['id']);
                } else {
                    echo "No se encontraron videos para el juego con ID: {$game['id']}<br>";
                }
            }
        }
    
         function insertVideos($videosResponse, $id){
            // Insertar los videos en la base de datos
            foreach ($videosResponse['data'] as $video) {
                // Verificar si el video ya existe en la base de datos antes de insertarlo
                $existingVideo = DB::table('Video')->where('id', $video['id'])->first();
    
                if (!$existingVideo) {
                    DB::table('Video')->insert([
                        'id' => $video['id'],
                        'user_id' => $video['user_id'],
                        'user_name' => $video['user_name'],
                        'title' => $video['title'],
                        'created_at' => $video['created_at'],
                        'view_count' => $video['view_count'],
                        'duration' => $video['duration'],
                        'game_id' => $id
                    ]);
                } else {
                    // Si el video ya existe, puedes optar por actualizar su información si es necesario
                    DB::table('Video')
                        ->where('id', $video['id'])
                        ->update([
                            'user_id' => $video['user_id'],
                            'user_name' => $video['user_name'],
                            'title' => $video['title'],
                            'created_at' => $video['created_at'],
                            'view_count' => $video['view_count'],
                            'duration' => $video['duration'],
                            'game_id' => $id
                        ]);
                }
            }
        }
    
        function updateGames($gamesResponse){
            // Actualizar los juegos en la base de datos
            foreach ($gamesResponse['data'] as $game) {
                Game::where('game_id', $game['id'])->update([
                    'game_name' => $game['name'],
                    'last_update' => now()
                ]);
            }
        }
    
         function fetchAndUpdateVideos($gamesResponse, $clientId, $accessToken){
            // Obtener y actualizar los videos para cada juego
            foreach ($gamesResponse['data'] as $game) {
                $videosUrl = "https://api.twitch.tv/helix/videos?game_id={$game['id']}&sort=views&first=40";
    
                $videosResponse = fetchTwitchData($videosUrl, $clientId, $accessToken);
    
                if ($videosResponse) {
                    Video::where('game_id', $game['id'])->delete(); // Eliminar los videos antiguos
    
                    insertVideos($videosResponse);
                } else {
                    echo "No se encontraron videos para el juego con ID: {$game['id']}<br>";
                }
            }
        }
        
        function conexion() {
            try {
                // Utilizamos las configuraciones de base de datos definidas en el archivo .env
                $connection = DB::connection()->getPdo();
                return $connection;
            } catch (\Exception $e) {
                // Manejar cualquier error de conexión
                die("Error de conexión: " . $e->getMessage());
            }
        }
	    header('Content-Type: application/json');
        // Obtener el número de juegos en la base de datos
        $conection = conexion();
        $games = DB::table('Game')->get();
        $gameCount = DB::table('Game')->count();

        // URL de la API de Twitch para obtener los juegos más vistos
        $gamesTwitchUrl = 'https://api.twitch.tv/helix/games/top?first=3';

        $clientId = 'szp2ugo2j6edjt8ytdak5n2n3hjkq3'; // Reemplaza con tu ID de cliente de Twitch
        $clientSecret = '07gk0kbwwzpuw2uqdzy1bjnsz9k32k'; // Reemplaza con tu secreto de cliente de Twitch

        // Solicitar un token de acceso
        $accessToken = requestAccessToken($clientId, $clientSecret);
	
        if ($gameCount == 0 && isset($accessToken)) { // No hay datos en la base de datos
            // Obtener los juegos más vistos de Twitch
            $gamesResponse = fetchTwitchData($gamesTwitchUrl, $clientId, $accessToken);

            if ($gamesResponse) {
                // Insertar los juegos en la base de datos
                insertGames($gamesResponse);

                // Obtener los videos para cada juego
                fetchAndInsertVideos($gamesResponse, $clientId, $accessToken);
                $results = DB::table('Video as v')
                ->select(
                'v.game_id',
                'g.game_name',
                'v.user_name',
                DB::raw('total_videos.total_videos AS total_videos'),
                DB::raw('total_views.total_views AS total_views'),
                'v.title AS most_viewed_title',
                'v.view_count AS most_viewed_views',
                'v.duration AS most_viewed_duration',
                'v.created_at AS most_viewed_created_at'
                )
                ->join('Game as g', 'v.game_id', '=', 'g.game_id')
                ->join(DB::raw('(SELECT game_id, MAX(view_count) AS max_view_count FROM Video GROUP BY game_id) AS max_views_per_game'), function ($join) {
                $join->on('v.game_id', '=', 'max_views_per_game.game_id')
                    ->on('v.view_count', '=', 'max_views_per_game.max_view_count');
                })
                ->join(DB::raw('(SELECT game_id, user_name, COUNT(*) AS total_videos FROM Video GROUP BY game_id, user_name) AS total_videos'), function ($join) {
                $join->on('v.game_id', '=', 'total_videos.game_id')
                    ->on('v.user_name', '=', 'total_videos.user_name');
                })
                ->join(DB::raw('(SELECT game_id, user_name, SUM(view_count) AS total_views FROM Video GROUP BY game_id, user_name) AS total_views'), function ($join) {
                $join->on('v.game_id', '=', 'total_views.game_id')
                    ->on('v.user_name', '=', 'total_views.user_name');
                })
                ->get();

                $data = [];
                foreach ($results as $row) {
                    // Creamos un array asociativo para cada fila de resultado
                    $rowData = [
                    "game_id" => $row->game_id,
                    "game_name" => $row->game_name,
                    "user_name" => $row->user_name,
                    "total_videos" => $row->total_videos,
                    "total_views" => $row->total_views,
                    "most_viewed_title" => $row->most_viewed_title,
                    "most_viewed_views" => $row->most_viewed_views,
                    "most_viewed_duration" => $row->most_viewed_duration,
                    "most_viewed_created_at" => $row->most_viewed_created_at
                    ];

                    // Agregamos el array asociativo al array principal
                    $data[] = $rowData;
                }
            }   
            // Imprimimos los datos en formato JSON
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } elseif ($gameCount > 0 && isset($accessToken)) { // Hay datos en la base de datos
            // Obtener los juegos más vistos de Twitch
            $gamesResponse = fetchTwitchData($gamesTwitchUrl, $clientId, $accessToken);
            if (isset($gamesResponse['data'])) {
            	$array = array();
            	$games = array_slice($gamesResponse['data'], 0, 3);
            }
	    	$results = DB::table('Game')->pluck('game_id')->toArray();

	    	$ids = array_map(function ($game) {
			return $game['id'];
	    	}, $games);

	        // Eliminar juegos y videos que no están presentes en los juegos obtenidos de Twitch
	    	foreach ($results as $res) {
                if (!in_array($res, $ids)) {
                    DB::table('Video')->where('game_id', $res)->delete();
                    DB::table('Game')->where('game_id', $res)->delete();
                }
	   	    }
	   	    foreach ($games as $game) {
                $id = $game['id'];
                    $name = $game['name'];
                $existingGame = DB::table('Game')->where('game_id', $id)->first();
                if (!$existingGame) {
                        DB::table('Game')->insert([
                        'game_id' => $game['id'],
                        'game_name' => $game['name'],
                        'last_update' => now()
                        ]);
                    $videosUrl = "https://api.twitch.tv/helix/videos?game_id={$game['id']}&sort=views&first=40";
                    $videosResponse = fetchTwitchData($videosUrl, $clientId, $accessToken);
                    insertVideos($videosResponse, $id);
                    $results = DB::table('Video as v')
                    ->select(
                    'v.game_id',
                    'g.game_name',
                    'v.user_name',
                    DB::raw('total_videos.total_videos AS total_videos'),
                    DB::raw('total_views.total_views AS total_views'),
                    'v.title AS most_viewed_title',
                    'v.view_count AS most_viewed_views',
                    'v.duration AS most_viewed_duration',
                    'v.created_at AS most_viewed_created_at'
                    )
                    ->join('Game as g', 'v.game_id', '=', 'g.game_id')
                    ->join(DB::raw('(SELECT game_id, MAX(view_count) AS max_view_count FROM Video GROUP BY game_id) AS max_views_per_game'), function ($join) {
                    $join->on('v.game_id', '=', 'max_views_per_game.game_id')
                        ->on('v.view_count', '=', 'max_views_per_game.max_view_count');
                    })
                    ->join(DB::raw('(SELECT game_id, user_name, COUNT(*) AS total_videos FROM Video GROUP BY game_id, user_name) AS total_videos'), function ($join) {
                    $join->on('v.game_id', '=', 'total_videos.game_id')
                        ->on('v.user_name', '=', 'total_videos.user_name');
                    })
                    ->join(DB::raw('(SELECT game_id, user_name, SUM(view_count) AS total_views FROM Video GROUP BY game_id, user_name) AS total_views'), function ($join) {
                    $join->on('v.game_id', '=', 'total_views.game_id')
                        ->on('v.user_name', '=', 'total_views.user_name');
                    })
                    ->get();

                    $data = [];
                    foreach ($results as $row) {
                        // Creamos un array asociativo para cada fila de resultado
                        $rowData = [
                        "game_id" => $row->game_id,
                        "game_name" => $row->game_name,
                        "user_name" => $row->user_name,
                        "total_videos" => $row->total_videos,
                        "total_views" => $row->total_views,
                        "most_viewed_title" => $row->most_viewed_title,
                        "most_viewed_views" => $row->most_viewed_views,
                        "most_viewed_duration" => $row->most_viewed_duration,
                        "most_viewed_created_at" => $row->most_viewed_created_at
                        ];

                        // Agregamos el array asociativo al array principal
                        $data[] = $rowData;
                        array_push($array, $rowData);
                    }
                        
	            } else {
	                $results = DB::table('Video as v')
                    ->select(
                    'v.game_id',
                    'g.game_name',
                    'v.user_name',
                    DB::raw('total_videos.total_videos AS total_videos'),
                    DB::raw('total_views.total_views AS total_views'),
                    'v.title AS most_viewed_title',
                    'v.view_count AS most_viewed_views',
                    'v.duration AS most_viewed_duration',
                    'v.created_at AS most_viewed_created_at'
                    )
                    ->join('Game as g', 'v.game_id', '=', 'g.game_id')
                    ->join(DB::raw('(SELECT game_id, MAX(view_count) AS max_view_count FROM Video GROUP BY game_id) AS max_views_per_game'), function ($join) {
                    $join->on('v.game_id', '=', 'max_views_per_game.game_id')
                        ->on('v.view_count', '=', 'max_views_per_game.max_view_count');
                    })
                    ->join(DB::raw('(SELECT game_id, user_name, COUNT(*) AS total_videos FROM Video GROUP BY game_id, user_name) AS total_videos'), function ($join) {
                    $join->on('v.game_id', '=', 'total_videos.game_id')
                        ->on('v.user_name', '=', 'total_videos.user_name');
                    })
                    ->join(DB::raw('(SELECT game_id, user_name, SUM(view_count) AS total_views FROM Video GROUP BY game_id, user_name) AS total_views'), function ($join) {
                    $join->on('v.game_id', '=', 'total_views.game_id')
                        ->on('v.user_name', '=', 'total_views.user_name');
                    })
                    ->get();

                    $data = [];
                    foreach ($results as $row) {
                        // Creamos un array asociativo para cada fila de resultado
                        $rowData = [
                        "game_id" => $row->game_id,
                        "game_name" => $row->game_name,
                        "user_name" => $row->user_name,
                        "total_videos" => $row->total_videos,
                        "total_views" => $row->total_views,
                        "most_viewed_title" => $row->most_viewed_title,
                        "most_viewed_views" => $row->most_viewed_views,
                        "most_viewed_duration" => $row->most_viewed_duration,
                        "most_viewed_created_at" => $row->most_viewed_created_at
                        ];

                        // Agregamos el array asociativo al array principal
                        $data[] = $rowData;
                        array_push($array, $rowData);
                    }
			    }
    	}
        echo json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            die('Error solicitando el token de acceso');
        }
    

    }

   	 
}

