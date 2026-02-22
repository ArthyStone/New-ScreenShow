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
            'state' => $state,
            'force_verify' => 'true' // Toujours demander à l'utilisateur de se reconnecter, pour éviter des boucles de connexion et surtout pour permettre de changer de compte facilement
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
        Session::set('user_id', (string) $user['twitchId'] ?? '0');
        Session::set('user_name', (string) $user['username'] ?? 'Utilisateur');
        Session::set('user_pfp', (string) $user['twitchPFP'] ?? 'https://i.pinimg.com/170x/1d/ec/e2/1dece2c8357bdd7cee3b15036344faf5.jpg');
        Session::set('user_tickets', (string) $user['tickets'] ?? 0);
        Session::set('permissions', $user['perms'] ?? []);

        header('Location: /infos');
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