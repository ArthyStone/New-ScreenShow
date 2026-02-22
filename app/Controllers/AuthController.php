<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\UserModel;

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /*
    |--------------------------------------------------------------------------
    | Redirection vers Twitch
    |--------------------------------------------------------------------------
    */
    public function redirectToTwitch(): void
    {
        $clientId = $_ENV['TWITCH_CLIENT_ID'];
        $redirectUri = $_ENV['TWITCH_REDIRECT_URI'];

        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);

        $url = "https://id.twitch.tv/oauth2/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => '',
            'state' => $state
        ]);

        header("Location: $url");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Callback Twitch
    |--------------------------------------------------------------------------
    */
    public function handleTwitchCallback(): void
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (!$code || $state !== Session::get('oauth_state')) {
            http_response_code(400);
            exit('Requête invalide.');
        }

        // Échanger le code contre un token
        $tokenResponse = $this->getAccessToken($code);

        if (!$tokenResponse) {
            http_response_code(500);
            exit('Erreur token.');
        }

        $userData = $this->getTwitchUser($tokenResponse['access_token']);

        if (!$userData) {
            http_response_code(500);
            exit('Erreur récupération utilisateur.');
        }

        // Vérifier si utilisateur existe en base
        $user = $this->userModel->findByTwitchId($userData['id']);

        if (!$user) {
            $user = $this->userModel->createFromTwitch($userData);
        }

        Session::set('user_id', (string) $user['id']);
        Session::set('permissions', $user['permissions'] ?? []);

        header('Location: /dashboard');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    public function logout(): void {
        Session::destroy();
        header('Location: /login');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privés
    |--------------------------------------------------------------------------
    */

    private function getAccessToken(string $code): ?array {
        $response = file_get_contents('https://id.twitch.tv/oauth2/token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded",
                'content' => http_build_query([
                    'client_id' => $_ENV['TWITCH_CLIENT_ID'],
                    'client_secret' => $_ENV['TWITCH_CLIENT_SECRET'],
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $_ENV['TWITCH_REDIRECT_URI']
                ])
            ]
        ]));

        return $response ? json_decode($response, true) : null;
    }

    private function getTwitchUser(string $accessToken): ?array
    {
        $response = file_get_contents('https://api.twitch.tv/helix/users', false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Authorization: Bearer $accessToken",
                    "Client-Id: " . $_ENV['TWITCH_CLIENT_ID']
                ]
            ]
        ]));

        $data = $response ? json_decode($response, true) : null;

        return $data['data'][0] ?? null;
    }
}