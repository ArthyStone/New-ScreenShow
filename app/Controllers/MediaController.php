<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\UserModel;
use App\Models\MediaModel;

class MediaController {

    // ── Constantes ───────────────────────────────────────────────
    private const ALLOWED_MIME = [
        'image/jpeg', 'image/jpg', 'image/svg+xml',
        'image/png', 'image/gif', 'video/mp4',
    ];
    private const ALLOWED_EXT  = ['jpeg', 'jpg', 'svg', 'png', 'gif', 'mp4'];
    private const MAX_SIZE      = 100 * 1024 * 1024; // 100 Mo
    private const UPLOAD_DIR    = __DIR__ . '/../../public/resources/medias/pending/';

    // ─────────────────────────────────────────────────────────────

    public function add(): void {
        $twitchId = Session::get('user_id');
        if (!$twitchId) {
            $this->json(['error' => 'Non authentifié.'], 401);
            return;
        }

        // Récupération de l'auteur
        $userModel = new UserModel();
        $user      = $userModel->findByTwitchId((string) $twitchId);
        if (!$user) {
            $this->json(['error' => 'Utilisateur introuvable.'], 404);
            return;
        }

        // Vérification du fichier uploadé
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['file']['error'] ?? -1;
            $this->json(['error' => "Erreur d'upload (code $code)."], 400);
            return;
        }

        $file    = $_FILES['file'];
        $tmpPath = $file['tmp_name'];
        $size    = $file['size'];
        $mime    = mime_content_type($tmpPath);

        // Validation taille
        if ($size > self::MAX_SIZE) {
            $this->json(['error' => "Fichier trop lourd (max 100 Mo)."], 400);
            return;
        }

        // Validation extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            $this->json(['error' => "Extension « .$ext » non autorisée."], 400);
            return;
        }

        // Validation MIME (double sécurité)
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $this->json(['error' => "Type MIME « $mime » non autorisé."], 400);
            return;
        }

        // Nom et tags issus du FormData
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $name = pathinfo($file['name'], PATHINFO_FILENAME);
        }

        $tagsRaw = $_POST['tags'] ?? '[]';
        $tags    = json_decode($tagsRaw, true);
        if (!is_array($tags)) {
            $tags = [];
        }
        $tags = array_values(array_unique(array_filter(
            array_map(fn(string $t) => trim($t), $tags),
            fn(string $t) => $t !== ''
        )));

        // Type
        $type = ($ext === 'mp4') ? 'video' : 'image';

        // Insertion en base d'abord — on a besoin de l'_id pour nommer le fichier
        $mediaModel = new MediaModel();
        $media = $mediaModel->create(
            type:   $type,
            name:   $name,
            tags:   $tags,
            author: [
                'username' => $user['username'],
                'twitchId' => (string) $twitchId,
            ]
        );

        $mediaId = (string) $media['_id'];

        // Déplacement du fichier — nommé d'après l'_id MongoDB
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $filename = $mediaId . '.' . $ext;
        $destPath = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            // Le fichier n'a pas pu être déplacé : on supprime le document pour rester cohérent
            $mediaModel->delete($mediaId);
            $this->json(['error' => "Impossible de déplacer le fichier."], 500);
            return;
        }

        // Réponse
        $this->json(['success' => true, 'id' => $mediaId], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}