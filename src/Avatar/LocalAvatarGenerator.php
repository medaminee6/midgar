<?php

namespace App\Avatar;

class LocalAvatarGenerator implements AvatarGeneratorInterface
{
    public function generate(string $inputPath, string $theme): string
    {
        $output = 'uploads/avatars/avatar_' . uniqid() . '.png';
        $outputPath = 'public/' . $output;

        // Fonctions globales GD avec \
        $img = \imagecreatefromstring(\file_get_contents($inputPath));

        match ($theme) {
            'anime'     => $this->anime($img),
            'fantasy'   => $this->fantasy($img),
            'cyberpunk' => $this->cyberpunk($img),
            'dark'      => \imagefilter($img, IMG_FILTER_GRAYSCALE),
            default     => null,
        };

        \imagepng($img, $outputPath);
        \imagedestroy($img);

        return '/' . $output;
    }

    private function anime($img): void
    {
        \imagefilter($img, IMG_FILTER_SMOOTH, 7);
        \imagefilter($img, IMG_FILTER_CONTRAST, -20);
        \imagefilter($img, IMG_FILTER_COLORIZE, 35, 25, 45);
    }

    private function fantasy($img): void
    {
        \imagefilter($img, IMG_FILTER_CONTRAST, -30);
        \imagefilter($img, IMG_FILTER_BRIGHTNESS, 15);
        \imagefilter($img, IMG_FILTER_COLORIZE, 60, 30, 10);
    }

    private function cyberpunk($img): void
    {
        \imagefilter($img, IMG_FILTER_COLORIZE, 80, 0, 120);
        \imagefilter($img, IMG_FILTER_CONTRAST, -25);
    }
}
