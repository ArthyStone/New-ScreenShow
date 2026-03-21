<?php
declare(strict_types=1);

namespace App\Core;
class View {
    public static function render(string $view, array $data = [], bool $usesLayout = true): void {
        $viewFile = __DIR__ . '/../Views/pages/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View $view introuvable");
        }
        extract($data);

        // Capture le contenu de la vue dans $content
        ob_start();
        require $viewFile;
        $content = ob_get_clean(); // $content contient maintenant le HTML de la vue $viewFile
        if(!$usesLayout) {
            echo $content;
            return;
        }

        $title = ucfirst($view) ?? "ScreenShow"; // $view est le nom de la page, ucfirst met first letter in uppercase

        // Inclut le layout principal
        require __DIR__ . '/../Views/layout/layout.php';
    }
}
