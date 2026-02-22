<?php

namespace App\Avatar;

interface AvatarGeneratorInterface
{
    public function generate(string $inputPath, string $theme): string;
}
