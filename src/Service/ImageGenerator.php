<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageGenerator
{
    public function __construct(
        private HttpClientInterface $client,
        private string $hfToken
    ) {}

    public function generate(string $prompt): string
    {
        $response = $this->client->request(
            'POST',
            'https://router.huggingface.co/v1/text-to-image',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->hfToken,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    "model" => "stabilityai/stable-diffusion-3.5-large",
                    "inputs" => $prompt,
                    "parameters" => [
                        "width" => 512,
                        "height" => 512
                    ]
                ])
            ]
        );

        $imageData = $response->getContent();

        $filename = uniqid('ai_') . '.png';
        $path = 'public/uploads/' . $filename;

        file_put_contents($path, $imageData);

        return '/uploads/' . $filename;
    }
}