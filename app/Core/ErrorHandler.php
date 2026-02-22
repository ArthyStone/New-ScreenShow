<?php
declare(strict_types=1);

namespace App\Core;

class ErrorHandler {
    public static function register(): void {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(\Throwable $e): void {
        http_response_code(500);
        echo "Erreur interne du serveur.";
        echo "<pre>";
        echo $e;
        exit;
        // En production : log fichier
        error_log($e->getMessage());
    }

    public static function handleError(int $severity, string $message, string $file, int $line): void {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}