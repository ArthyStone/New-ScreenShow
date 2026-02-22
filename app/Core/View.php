<?php
declare(strict_types=1);

namespace App\Core;
class View {
    public static function render(string $view, array $data = []): void {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View {$view} introuvable");
        }

        extract($data, EXTR_SKIP);

        require $viewFile;
    }
}
