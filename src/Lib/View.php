<?php

class View
{
    public static function render(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../Views/' . $name . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
            return;
        }

        echo "Vue introuvable: $name";
    }
}
