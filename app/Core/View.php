<?php
declare(strict_types=1);

namespace App\Core;
class View {
    public static function render(string $view, array $data = []): void {
        $viewFile = __DIR__ . '/../Views/pages/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View $view introuvable");
        }
        extract($data);

        // Capture le contenu de la vue dans $content
        ob_start();
        require $viewFile;
        $title = ucfirst($view) ?? "ScreenShow";
        $content = ob_get_clean();

        // Inclut le layout principal
        require __DIR__ . '/../Views/layout/layout.php';
    }
}
