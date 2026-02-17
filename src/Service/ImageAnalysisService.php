<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageAnalysisService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $apiModel;
    private string $apiProvider;
    private ?string $googleApiKey = null;
    private ?string $anthropicApiKey = null;

    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        string $apiModel,
        string $apiProvider = 'openai'
    ) {
        $this->httpClient = $httpClient;
        // Store the primary API key
        $this->apiKey = $apiKey;
        $this->apiModel = $apiModel ?: 'gpt-4o';
        $this->apiProvider = $apiProvider ?: 'openai';
    }

    public function setGoogleApiKey(string $key): void
    {
        $this->googleApiKey = $key;
    }

    public function setAnthropicApiKey(string $key): void
    {
        $this->anthropicApiKey = $key;
    }

    private function getApiKeyForProvider(): string
    {
        return match ($this->apiProvider) {
            'google' => $this->googleApiKey ?: $this->apiKey,
            'anthropic' => $this->anthropicApiKey ?: $this->apiKey,
            default => $this->apiKey,
        };
    }

    /**
     * Safely get JSON response, handling HTML errors
     */
    private function getJsonResponse($response): array
    {
        try {
            return $response->toArray();
        } catch (\Throwable $e) {
            // Response is not JSON - might be HTML error page
            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? 'unknown';

            // Try to get some content for debugging
            $content = '';
            try {
                $content = substr($response->getContent(false), 0, 500);
            } catch (\Throwable $e2) {
                $content = 'Unable to get content';
            }

            throw new \RuntimeException(
                "Erreur HTTP {$statusCode}: Réponse invalide du serveur. " .
                "Type de contenu: {$contentType}. " .
                "Contenu: {$content}"
            );
        }
    }

    /* ============================================================
     *  PUBLIC API
     * ============================================================
     */

    public function analyzeImage(
        string $imagePath,
        string $entityType = 'oeuvre',
        string $language = 'fr'
    ): array {
        if (!file_exists($imagePath)) {
            throw new \InvalidArgumentException('Image file not found');
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        return match ($this->apiProvider) {
            'google' => $this->analyzeWithGoogle($imageData, $mimeType, $entityType, $language),
            'anthropic' => $this->analyzeWithAnthropic($imageData, $mimeType, $entityType, $language),
            default => $this->analyzeWithOpenAI($imageData, $mimeType, $entityType, $language),
        };
    }

    public function analyzeFromBase64(
        string $base64ImageData,
        string $mimeType,
        string $entityType = 'oeuvre',
        string $language = 'fr'
    ): array {
        return match ($this->apiProvider) {
            'google' => $this->analyzeWithGoogle($base64ImageData, $mimeType, $entityType, $language),
            'anthropic' => $this->analyzeWithAnthropic($base64ImageData, $mimeType, $entityType, $language),
            default => $this->analyzeWithOpenAI($base64ImageData, $mimeType, $entityType, $language),
        };
    }

    public function isConfigured(): bool
    {
        $key = $this->getApiKeyForProvider();
        return !empty($key);
    }

    public function getApiProvider(): string
    {
        return $this->apiProvider;
    }

    /* ============================================================
     *  OPENAI IMPLEMENTATION (RESPONSES API)
     * ============================================================
     */

    private function analyzeWithOpenAI(string $imageData, string $mimeType, string $entityType, string $language): array
    {
        $apiKey = $this->getApiKeyForProvider();
        $languagePrompt = $language === 'fr' ? 'en français' : 'in English';

        $contextPrompt = match ($entityType) {
            'artefact' => 'Tu es un expert en analyse d\'artefacts magiques et d\'objets fantastiques.',
            default => 'Tu es un expert en analyse d\'œuvres d\'art et d\'illustrations.',
        };

        $prompt = <<<PROMPT
{$contextPrompt}

Analyse cette image et génère exactement 3 descriptions détaillées et différentes les unes des autres {$languagePrompt}.

Chaque description doit inclure (quand applicable) :
- Le sujet principal et sa composition
- Les couleurs dominantes
- Les matériaux ou médiums utilisés
- Le style artistique
- Les détails visuels importants
- L'ambiance générale
- L'état de conservation (pour les œuvres)
- Les symboles ou éléments distinctifs

Les descriptions doivent être substantielles (minimum 150 mots chacune).

Format attendu (séparez les descriptions par "---") :
DESCRIPTION 1 : [Titre]
[Contenu de la description]
---
DESCRIPTION 2 : [Titre]
[Contenu de la description]
---
DESCRIPTION 3 : [Titre]
[Contenu de la description]
PROMPT;

        try {
            // Use Chat Completions API (older but more reliable)
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->apiModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$imageData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 2000,
                ],
                'timeout' => 60,
            ]);

            $data = $this->getJsonResponse($response);

            // Debug: log the raw response
            if (empty($data['choices'])) {
                throw new \RuntimeException('Réponse OpenAI invalide ou vide. Response: ' . json_encode($data));
            }

            $text = $data['choices'][0]['message']['content'] ?? '';

            if (empty($text)) {
                throw new \RuntimeException('Réponse OpenAI vide après analyse.');
            }

            return $this->parseDescriptions($text);

        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur OpenAI: ' . $e->getMessage());
        }
    }

    /* ============================================================
     *  GOOGLE GEMINI IMPLEMENTATION
     * ============================================================
     */

    private function analyzeWithGoogle(string $imageData, string $mimeType, string $entityType, string $language): array
    {
        $apiKey = $this->getApiKeyForProvider();
        $languagePrompt = $language === 'fr' ? 'en français' : 'in English';

        $contextPrompt = match ($entityType) {
            'artefact' => 'Tu es un expert en analyse d\'artefacts magiques et d\'objets fantastiques.',
            default => 'Tu es un expert en analyse d\'œuvres d\'art et d\'illustrations.',
        };

        $prompt = <<<PROMPT
{$contextPrompt}

Analyse cette image et génère exactement 3 descriptions détaillées et différentes les unes des autres {$languagePrompt}.

Chaque description doit inclure (quand applicable) :
- Le sujet principal et sa composition
- Les couleurs dominantes
- Les matériaux ou médiums utilisés
- Le style artistique
- Les détails visuels importants
- L'ambiance générale
- L'état de conservation (pour les œuvres)
- Les symboles ou éléments distinctifs

Les descriptions doivent être substantielles (minimum 150 mots chacune).
PROMPT;

        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->apiModel . ':generateContent?key=' . $apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429) {
                // Rate limit - wait longer and retry multiple times
                for ($i = 0; $i < 5; $i++) {
                    $waitTime = 30 + ($i * 20); // 30, 50, 70, 90, 110 seconds
                    sleep($waitTime);
                    $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->apiModel . ':generateContent?key=' . $apiKey, [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt],
                                        [
                                            'inline_data' => [
                                                'mime_type' => $mimeType,
                                                'data' => $imageData,
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'temperature' => 0.7,
                                'maxOutputTokens' => 2048,
                            ]
                        ],
                        'timeout' => 120,
                    ]);

                    $statusCode = $response->getStatusCode();
                    if ($statusCode !== 429) {
                        break;
                    }
                }
            }

            $data = $this->getJsonResponse($response);

            if (isset($data['error'])) {
                throw new \RuntimeException('Erreur Google Gemini: ' . $data['error']['message']);
            }

            if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \RuntimeException('Réponse Google Gemini invalide ou vide.');
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            return $this->parseDescriptions($text);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, '429') || str_contains($errorMsg, 'rate limit')) {
                throw new \RuntimeException('Limite de taux Gemini dépassée (429). Le système réessaiera automatiquement jusqu\'à 5 fois (attente totale ~6 min).', 0, $e);
            }
            throw new \RuntimeException('Erreur Google Gemini: ' . $errorMsg);
        }
    }

    /* ============================================================
     *  ANTHROPIC CLAUDE IMPLEMENTATION
     * ============================================================
     */

    private function analyzeWithAnthropic(string $imageData, string $mimeType, string $entityType, string $language): array
    {
        $apiKey = $this->getApiKeyForProvider();
        $languagePrompt = $language === 'fr' ? 'en français' : 'in English';

        $prompt = <<<PROMPT
Tu es un expert en analyse d'artefacts et d'œuvres d'art.

Analyse cette image et génère exactement 3 descriptions détaillées et différentes les unes des autres {$languagePrompt}.

Chaque description doit inclure (quand applicable) :
- Le sujet principal et sa composition
- Les couleurs dominantes
- Les matériaux ou médiums utilisés
- Le style artistique
- Les détails visuels importants
- L'ambiance générale

Les descriptions doivent être substantielles (minimum 150 mots chacune).

Format attendu (séparez les descriptions par "---") :
DESCRIPTION 1 : [Titre]
[Contenu de la description]
---
DESCRIPTION 2 : [Titre]
[Contenu de la description]
---
DESCRIPTION 3 : [Titre]
[Contenu de la description]
PROMPT;

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->apiModel,
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mimeType,
                                        'data' => $imageData,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $this->getJsonResponse($response);

            if ($statusCode !== 200) {
                $errorMsg = $data['error']['message'] ?? json_encode($data);
                throw new \RuntimeException("Erreur Anthropic (HTTP {$statusCode}): {$errorMsg}");
            }

            if (isset($data['error'])) {
                throw new \RuntimeException('Erreur Anthropic: ' . $data['error']['message']);
            }

            if (empty($data['content'][0]['text'])) {
                throw new \RuntimeException('Réponse Anthropic invalide ou vide.');
            }

            $text = $data['content'][0]['text'];
            return $this->parseDescriptions($text);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();

            // Try to extract more details from the response
            if (str_contains($errorMsg, '400')) {
                // Try to get more details
                if (isset($data) && !empty($data)) {
                    $detail = $data['error']['message'] ?? json_encode($data);
                    throw new \RuntimeException("Erreur Anthropic 400: {$detail}");
                }
                throw new \RuntimeException('Erreur Anthropic 400: Vérifiez le format de la requête ou la clé API.');
            }
            throw new \RuntimeException('Erreur Anthropic: ' . $errorMsg);
        }
    }


    /* ============================================================
     *  RESPONSE PARSING (ANTI-HTML / ANTI-CRASH)
     * ============================================================
     */

    private function parseOpenAIResponse($response): array
    {
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders(false);
        $contentType = $headers['content-type'][0] ?? '';
        $raw = $response->getContent(false);

        if (!str_contains($contentType, 'application/json')) {
            throw new \RuntimeException(
                "OpenAI returned non-JSON response (HTTP {$statusCode})"
            );
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON received from OpenAI');
        }

        if (
            !isset($data['output'][0]['content'][0]['text'])
        ) {
            throw new \RuntimeException('Unexpected OpenAI response structure');
        }

        $text = $data['output'][0]['content'][0]['text'];

        return $this->parseDescriptions($text);
    }

    /* ============================================================
     *  DESCRIPTION PARSER
     * ============================================================
     */

    private function parseDescriptions(string $text): array
    {
        $descriptions = [];
        $parts = preg_split('/---/', $text);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) < 50) {
                continue;
            }

            if (preg_match('/DESCRIPTION\s*(\d+)\s*:\s*(.+?)\n(.*)/si', $part, $m)) {
                $descriptions[] = [
                    'title' => trim($m[2]),
                    'content' => trim($m[3]),
                ];
            } else {
                $descriptions[] = [
                    'title' => 'Description',
                    'content' => $part,
                ];
            }
        }

        if (empty($descriptions)) {
            $descriptions[] = [
                'title' => 'Analyse de l’image',
                'content' => $text,
            ];
        }

        return $descriptions;
    }
}
