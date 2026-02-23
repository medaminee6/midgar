<?php

namespace App\Service;

use App\Entity\Universe;

class UniverseStoryGenerator
{
    public function generate(
        Universe $universe,
        ?int $seed = null,
        array $characterContext = [],
        string $mode = 'intro',
        string $previousText = ''
    ): array
    {
        $seed = $seed ?? random_int(1, 9999999);
        $apiKey = trim((string) ($_SERVER['OPENROUTER_API_KEY'] ?? $_ENV['OPENROUTER_API_KEY'] ?? ''));
        $mode = $this->normalizeMode($mode);
        $previousText = trim($previousText);

        if ($apiKey !== '') {
            $story = $this->generateWithOpenRouter($universe, $seed, $apiKey, $characterContext, $mode, $previousText);
            if ($story) {
                return [
                    'story' => $story,
                    'source' => 'openrouter',
                    'seed' => $seed,
                ];
            }
        }

        return [
            'story' => $this->generateFallbackStory($universe, $seed, $characterContext, $mode, $previousText),
            'source' => 'fallback',
            'seed' => $seed,
        ];
    }

    private function generateWithOpenRouter(
        Universe $universe,
        int $seed,
        string $apiKey,
        array $characterContext = [],
        string $mode = 'intro',
        string $previousText = ''
    ): ?string
    {
        $model = trim((string) ($_SERVER['OPENROUTER_MODEL'] ?? $_ENV['OPENROUTER_MODEL'] ?? 'mistralai/mistral-7b-instruct:free'));

        $name = trim($this->toPromptString($universe->getName()));
        $shortDescription = trim($this->toPromptString($universe->getShortDescription() ?? ''));
        $storyContext = trim($this->toPromptString($universe->getStoryContext() ?? ''));
        $themes = trim($this->toPromptString($universe->getThemes() ?? ''));
        $characterName = trim($this->toPromptString($characterContext['name'] ?? 'The hero'));
        $classRole = trim($this->toPromptString($characterContext['classRole'] ?? 'Adventurer'));
        $previousExcerpt = $this->safeSlice($previousText, max(0, $this->safeLength($previousText) - 900), 900);

        if ($mode === 'ending') {
            $systemPrompt = 'You are an award-winning game narrative writer. Write a dramatic bad-ending narration that feels cinematic, tragic, and emotionally resonant. Keep it PG-13 and immersive. Length: 90 to 170 words, 1-2 short paragraphs. Mention the hero name and class role naturally. End with a haunting final line that implies the world changed for the worse. Return plain text only.';
            $userPrompt = sprintf(
                "Hero Name: %s\nHero Class: %s\nUniverse Name: %s\nShort Description: %s\nStory Context: %s\nThemes: %s\nSeed: %d\nPrevious bad-ending text excerpt: %s\nRequirement: If previous text exists, continue it with NEW details and avoid repeating lines.",
                $characterName,
                $classRole,
                $name,
                $shortDescription,
                $storyContext,
                $themes,
                $seed,
                $previousExcerpt !== '' ? $previousExcerpt : '(none)'
            );
        } elseif ($mode === 'victory') {
            $systemPrompt = 'You are an uplifting game narrative writer. Write a heartfelt congratulation message for a player who completed the adventure. Keep it positive, specific, and sincere. Mention the hero name and class role naturally. Do not write tragedy, do not add random plot twists, and do not use placeholders. IMPORTANT: Do not mention scores, stats, numbers, bars, percentages, ranks, or performance metrics. This message must be pure congratulations only. Length: 60 to 120 words, 1 short paragraph. Return plain text only.';
            $userPrompt = sprintf(
                "Hero Name: %s\nHero Class: %s\nUniverse Name: %s\nUniverse Tone: %s\nStory Context: %s\nAccomplishment: %s\nRequirement: Congratulate the player for finishing in a clean ending message without any score/stat mention.",
                $characterName,
                $classRole,
                $name,
                $shortDescription,
                $storyContext,
                $previousExcerpt !== '' ? $previousExcerpt : '(no summary provided)'
            );
        } elseif ($previousExcerpt !== '') {
            $systemPrompt = 'You are an award-winning fantasy and sci-fi game narrative writer. Continue an opening cinematic story with deep lore and vivid imagery. Keep it PG-13, serious, and immersive. Length: 90 to 170 words in 1-2 short paragraphs. Do not restart the intro and do not repeat previous lines. Return plain text only.';
            $userPrompt = sprintf(
                "Hero Name: %s\nHero Class: %s\nUniverse Name: %s\nShort Description: %s\nStory Context: %s\nThemes: %s\nSeed: %d\nPrevious opening text excerpt: %s\nRequirement: Continue naturally with new events and escalation.",
                $characterName,
                $classRole,
                $name,
                $shortDescription,
                $storyContext,
                $themes,
                $seed,
                $previousExcerpt
            );
        } else {
            $systemPrompt = 'You are an award-winning fantasy and sci-fi game narrative writer. Write a rich opening cinematic narration with deep lore, emotional stakes, and vivid imagery. Keep it PG-13, serious, and immersive. Length: 260 to 420 words, in 3-5 short paragraphs. Mention the hero name and class role naturally. End with a strong quest hook and immediate first objective. Avoid goofy tone and modern slang. Return plain text only.';
            $userPrompt = sprintf(
                "Hero Name: %s\nHero Class: %s\nUniverse Name: %s\nShort Description: %s\nStory Context: %s\nThemes: %s\nSeed: %d\nRequirement: Make a different opening story each launch while preserving universe tone.",
                $characterName,
                $classRole,
                $name,
                $shortDescription,
                $storyContext,
                $themes,
                $seed
            );
        }

        $temperature = $mode === 'victory' ? 0.35 : 1.0;

        $payload = [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => 900,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $jsonBody = json_encode($payload);
        if ($jsonBody === false) {
            return null;
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: http://localhost',
            'X-Title: Midgar Quiz',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $jsonBody,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents('https://openrouter.ai/api/v1/chat/completions', false, $context);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        return $this->normalizeStory($content);
    }

    private function generateFallbackStory(
        Universe $universe,
        int $seed,
        array $characterContext = [],
        string $mode = 'intro',
        string $previousText = ''
    ): string
    {
        $name = trim($this->toPromptString($universe->getName()));
        $shortDescription = trim($this->toPromptString($universe->getShortDescription() ?? 'A mysterious world.'));
        $themes = trim($this->toPromptString($universe->getThemes() ?? 'adventure, danger, discovery'));
        $characterName = trim($this->toPromptString($characterContext['name'] ?? 'The hero'));
        $classRole = trim($this->toPromptString($characterContext['classRole'] ?? 'Adventurer'));
        $mode = $this->normalizeMode($mode);
        $previousText = trim($previousText);

        $openings = [
            'At dawn, the sky cracked with distant echoes.',
            'When the lanterns dimmed, old roads woke up again.',
            'A cold wind crossed the valley as forgotten bells rang once.',
            'Before sunrise, the horizon burned with impossible colors.',
        ];

        $hooks = [
            'Rumors speak of a sealed gate that opens only for the brave.',
            'Every village whispers about a relic hidden beneath the ruins.',
            'A shadowed force gathers beyond the last safe outpost.',
            'An omen marks this day as the beginning of a dangerous cycle.',
        ];

        $goals = [
            'Your first step may decide who survives the coming storm.',
            'Each choice now shapes alliances, enemies, and destiny.',
            'The path forward demands both strength and clever judgment.',
            'Only those who adapt quickly can rewrite what fate expects.',
        ];

        $pick = function (array $items, int $offset) use ($seed): string {
            $index = abs(crc32($seed . ':' . $offset)) % count($items);
            return $items[$index];
        };

        if ($mode === 'ending') {
            $endings = [
                'The last beacon failed, and the skies closed over the realm.',
                'Cities fell silent as fear replaced memory and hope.',
                'No anthem remained, only the echo of what should have been.',
                'With the hero broken, darkness rewrote every border and oath.',
            ];

            $story = sprintf(
                "%s In %s, %s the %s is remembered not for triumph, but for the day fate was abandoned. %s\n\nThe final consequence lingers: the world survives, but only as a warning.",
                $pick($endings, 11),
                $name !== '' ? $name : 'this universe',
                $characterName,
                $classRole,
                $pick($hooks, 12)
            );

            if ($previousText !== '') {
                $story = sprintf(
                    "%s Another chapter of ruin unfolds: alliances shatter, guardians vanish, and the people of %s speak your name in whispers of regret.",
                    $pick($endings, 13),
                    $name !== '' ? $name : 'this world'
                );
            }

            return $this->normalizeStory($story);
        }

        if ($mode === 'victory') {
            $summary = $previousText !== '' ? $previousText : 'you overcame every final challenge';
            $story = sprintf(
                "Congratulations, %s the %s. You completed your journey in %s with courage, focus, and consistency. %s. Today, your name stands as a true champion of this world. Well played, and well earned.",
                $characterName,
                $classRole,
                $name !== '' ? $name : 'this universe',
                $summary
            );

            return $this->normalizeStory($story);
        }

        if ($previousText !== '') {
            $continuations = [
                'As the journey deepens, hidden factions emerge and test your resolve at every crossing.',
                'Ancient wards begin to fail, forcing you toward a choice no hero can postpone for long.',
                'What first felt like survival now reveals a larger war, and your next step may decide the realm.',
                'The deeper truth rises: every enemy you face points toward a single forgotten origin.',
            ];

            $story = sprintf(
                "%s %s The road ahead in %s narrows into danger, where courage must move faster than fear.",
                $pick($continuations, 21),
                $pick($goals, 22),
                $name !== '' ? $name : 'this universe'
            );

            return $this->normalizeStory($story);
        }

        $story = sprintf(
            "%s\n\nIn %s, %s the %s steps into a land shaped by %s. The old powers have shifted, borders have thinned, and every promise of safety now carries a hidden cost. Themes of %s run through every settlement and ruin, where memories are carved into stone and fear travels faster than light.\n\n%s The roads ahead are filled with mercenaries, broken oaths, and creatures drawn to unrest. Every battle is more than survival: it is proof that your name belongs in this world’s history. %s\n\nYour first quest begins now: track the nearest hostile force, survive the encounter, and recover one clue from the battlefield before nightfall.",
            $pick($openings, 1),
            $name !== '' ? $name : 'this universe',
            $characterName,
            $classRole,
            $this->safeLower($shortDescription),
            $themes,
            $pick($hooks, 2),
            $pick($goals, 3)
        );

        return $this->normalizeStory($story);
    }

    private function normalizeMode(string $mode): string
    {
        $normalized = strtolower(trim($mode));
        if (in_array($normalized, ['ending', 'victory'], true)) {
            return $normalized;
        }

        return 'intro';
    }

    private function normalizeStory(string $story): string
    {
        $story = str_replace(["\r\n", "\r"], "\n", trim($story));
        $lines = array_map(function (string $line): string {
            return trim((string) preg_replace('/[ \t]+/', ' ', $line));
        }, explode("\n", $story));

        $story = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? $story;

        if ($this->safeLength($story) > 4200) {
            $story = $this->safeSlice($story, 0, 4197) . '...';
        }
        return $story;
    }

    private function safeLower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return (string) mb_strtolower($value);
        }
        return strtolower($value);
    }

    private function safeLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value);
        }
        return strlen($value);
    }

    private function safeSlice(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, $start, $length);
        }
        return substr($value, $start, $length);
    }

    private function toPromptString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                if ($item === null) {
                    continue;
                }
                if (is_scalar($item)) {
                    $parts[] = (string) $item;
                    continue;
                }
                $parts[] = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            }
            return implode(', ', array_filter($parts, static fn(string $p): bool => $p !== ''));
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
