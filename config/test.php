<?php
$path = __DIR__ . '/config/midgarapp-a2e246d71083.json';

if (file_exists($path)) {
    echo "Le fichier existe !\n";
    $content = file_get_contents($path);
    if ($content) {
        echo "Le fichier est lisible par PHP.\n";
    } else {
        echo "Le fichier existe mais n’est pas lisible.\n";
    }
} else {
    echo "Le fichier n’existe pas à cet emplacement.\n";
}
