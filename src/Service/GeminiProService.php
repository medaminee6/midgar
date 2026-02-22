<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Google\Auth\Credentials\ServiceAccountCredentials;

class GeminiProService
{
    private HttpClientInterface $client;
    private string $projectId;
    private string $serviceAccountPath;

    public function __construct(HttpClientInterface $client, string $projectId, string $serviceAccountPath)
    {
        $this->client = $client;
        $this->projectId = $projectId;
        $this->serviceAccountPath = $serviceAccountPath;
    }

    private function getAccessToken(): string
    {
        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];

        $creds = new ServiceAccountCredentials(
            $scopes,
            json_decode(file_get_contents($this->serviceAccountPath), true)
        );

        $token = $creds->fetchAuthToken();
        if (!isset($token['access_token'])) {
            throw new \Exception('Impossible de récupérer le token depuis le JSON du compte de service');
        }
        return $token['access_token'];
    }

    public function generateAvatar(string $imagePath, string $theme): string
    {
        $accessToken = $this->getAccessToken();
        $imageBase64 = base64_encode(file_get_contents($imagePath));

        $prompts = [
            'mage' => 'Fantasy mage avatar, digital art, detailed, epic lighting',
            'elfe' => 'Elven fantasy avatar, mystical forest, digital painting',
            'guerrier' => 'Fantasy warrior avatar, armor, epic illustration'
        ];

        $prompt = $prompts[$theme] ?? $prompts['mage'];

        $url = sprintf(
            'https://us-central1-aiplatform.googleapis.com/v1/projects/%s/locations/us-central1/publishers/google/models/gemini-3-pro-image:predict',
            $this->projectId
        );

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'instances' => [[
                    'prompt' => $prompt,
                    'image'  => $imageBase64,
                    'size'   => '1024x1024'
                ]]
            ]
        ]);

        $data = $response->toArray(false);

        // Debug si l'image n'est pas reçue
        if (!isset($data['predictions'][0]['image'])) {
            throw new \Exception('Gemini n’a retourné aucune image. Réponse brute : ' . json_encode($data));
        }

        // Peut être juste image ou image.bytesBase64Encoded
        $avatarBase64 = $data['predictions'][0]['image']['bytesBase64Encoded'] ?? $data['predictions'][0]['image'] ?? null;
        if (!$avatarBase64) {
            throw new \Exception('Gemini n’a retourné aucune image valide.');
        }

        $avatarPath = 'uploads/avatars/avatar_' . time() . '.png';
        file_put_contents('public/' . $avatarPath, base64_decode($avatarBase64));

        return '/' . $avatarPath;
    }
}
