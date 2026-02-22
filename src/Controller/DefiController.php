<?php

namespace App\Controller;

use App\Entity\Defi;
use App\Entity\Participation;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\StatutParticipation;
use App\Form\DefiType;
use App\Form\ParticiperType;
use App\Form\ParticipationStatutType;
use App\Repository\DefiRepository;
use App\Repository\ParticipationRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class DefiController extends AbstractController
{
    
    // AI IMAGE GENERATION FOR PARTICIPATION
    #[Route('/ai/generate-image', name: 'ai_generate_image', methods: ['POST'])]
    public function generateAiImage(Request $request): JsonResponse
    {
        $prompt = $request->request->get('prompt', '');
        $defiId = $request->request->get('defiId', 0);
        
        if (empty($prompt)) {
            return new JsonResponse(['error' => 'Le prompt est requis'], 400);
        }
        
        // Try Ollama first for image generation (if available with vision model)
        $ch = @curl_init('http://localhost:11434/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $ollamaCheck = @curl_exec($ch);
        $ollamaHttpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Since Ollama doesn't have native image generation, we'll generate a placeholder
        // In production, you'd integrate with DALL-E, Midjourney API, or Stable Diffusion
        if ($ollamaHttpCode === 200) {
            // Generate a descriptive text that could be used with an image generation API
            // For now, return a placeholder image based on the description
            $imageUrl = $this->generatePlaceholderImage($prompt);
            return new JsonResponse(['imageUrl' => $imageUrl]);
        }
        
        // Fallback: generate a placeholder image
        $imageUrl = $this->generatePlaceholderImage($prompt);
        return new JsonResponse(['imageUrl' => $imageUrl]);
    }
    
    private function generatePlaceholderImage(string $prompt): string
    {
        // First, try to use Stable Diffusion local API
        $stableDiffusionUrl = 'http://127.0.0.1:7860/sdapi/v1/txt2img';
        
        $ch = curl_init($stableDiffusionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $sdCheck = @curl_exec($ch);
        $sdHttpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $sdError = @curl_error($ch);
        curl_close($ch);
        
        // If Stable Diffusion is available, use it
        if ($sdHttpCode === 200 || $sdHttpCode === 400) {
            try {
                $postData = json_encode([
                    'prompt' => $prompt . ', high quality, detailed, digital art, fantasy style',
                    'negative_prompt' => 'low quality, blurry, distorted',
                    'steps' => 20,
                    'width' => 512,
                    'height' => 512,
                    'cfg_scale' => 7
                ]);
                
                $ch = curl_init($stableDiffusionUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response, true);
                    if (isset($data['images'][0])) {
                        // Save the base64 image
                        $filename = 'ai_' . uniqid() . '.png';
                        $projectDir = $this->getParameter('kernel.project_dir');
                        $uploadsDir = $projectDir . '/public/uploads/participations';
                        
                        if (!is_dir($uploadsDir)) {
                            @mkdir($uploadsDir, 0777, true);
                        }
                        
                        $imageData = base64_decode($data['images'][0]);
                        $filepath = $uploadsDir . '/' . $filename;
                        file_put_contents($filepath, $imageData);
                        
                        return '/uploads/participations/' . $filename;
                    }
                }
            } catch (\Exception $e) {
                // Fall back to SVG placeholder
            }
        }
        
        // Fallback: Generate SVG placeholder
        $filename = 'ai_' . uniqid() . '.svg';
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads/participations';
        
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0777, true);
        }
        
        // Generate colors based on prompt hash
        $hash = md5($prompt);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        
        // Create an SVG placeholder with visual elements based on keywords
        $svgContent = $this->generateSvgFromPrompt($prompt, $r, $g, $b);
        
        // Save SVG file
        $filepath = $uploadsDir . '/' . $filename;
        file_put_contents($filepath, $svgContent);
        
        return '/uploads/participations/' . $filename;
    }
    
    private function generateSvgFromPrompt(string $prompt, int $r, int $g, int $b): string
    {
        $promptLower = strtolower($prompt);
        
        // Determine visual elements based on keywords
        $elements = [];
        if (strpos($promptLower, 'dragon') !== false) $elements[] = '<circle cx="200" cy="300" r="80" fill="#8B4513" opacity="0.6"/><path d="M150,250 Q200,200 250,250" stroke="#FFD700" stroke-width="3" fill="none"/><text x="200" y="420" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🐉 Dragon</text>';
        if (strpos($promptLower, 'ocean') !== false || strpos($promptLower, 'mer') !== false || strpos($promptLower, 'water') !== false) $elements[] = '<path d="M0,400 Q200,300 400,400 T800,400 L800,600 L0,600 Z" fill="#006994" opacity="0.7"/><circle cx="700" cy="100" r="40" fill="#FFD700"/><text x="400" y="500" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🌊 Océan</text>';
        if (strpos($promptLower, 'forest') !== false || strpos($promptLower, 'forêt') !== false || strpos($promptLower, 'tree') !== false) $elements[] = '<rect x="100" y="200" width="60" height="300" fill="#8B4513"/><circle cx="130" cy="180" r="60" fill="#228B22"/><circle cx="180" cy="220" r="50" fill="#006400"/><text x="400" y="550" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🌲 Forêt</text>';
        if (strpos($promptLower, 'fire') !== false || strpos($promptLower, 'feu') !== false) $elements[] = '<path d="M200,500 Q250,400 300,500 Q350,350 400,500 Q450,300 500,500" fill="#FF4500" opacity="0.8"/><text x="350" y="550" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🔥 Feu</text>';
        if (strpos($promptLower, 'star') !== false || strpos($promptLower, 'étoile') !== false || strpos($promptLower, 'space') !== false || strpos($promptLower, 'espace') !== false) { $elements[] = '<circle cx="100" cy="100" r="2" fill="white"/><circle cx="300" cy="50" r="3" fill="white"/><circle cx="500" cy="150" r="2" fill="white"/><circle cx="700" cy="80" r="2" fill="white"/><circle cx="200" cy="300" r="80" fill="#FFD700" opacity="0.3"/><text x="400" y="550" font-family="Arial" font-size="18" fill="white" text-anchor="middle">⭐ Espace</text>'; }
        if (strpos($promptLower, 'castle') !== false || strpos($promptLower, 'château') !== false) $elements[] = '<rect x="150" y="300" width="100" height="150" fill="#808080"/><rect x="180" y="250" width="40" height="50" fill="#808080"/><polygon points="150,300 200,250 250,300" fill="#A52A2A"/><text x="200" y="500" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🏰 Château</text>';
        if (strpos($promptLower, 'flower') !== false || strpos($promptLower, 'fleur') !== false) $elements[] = '<circle cx="200" cy="300" r="40" fill="#FF69B4"/><circle cx="240" cy="280" r="30" fill="#FF1493"/><circle cx="180" cy="280" r="30" fill="#FF69B4"/><circle cx="200" cy="340" r="40" fill="#32CD32"/><text x="200" y="420" font-family="Arial" font-size="18" fill="white" text-anchor="middle">🌸 Fleur</text>';
        if (strpos($promptLower, 'mountain') !== false || strpos($promptLower, 'montagne') !== false) $elements[] = '<polygon points="100,500 250,200 400,500" fill="#708090"/><polygon points="300,500 450,150 600,500" fill="#A9A9A9"/><polygon points="350,300 450,150 550,300" fill="white" opacity="0.8"/><text x="350" y="550" font-family="Arial" font-size="18" fill="white" text-anchor="middle">⛰️ Montagne</text>';
        
        $elementsStr = implode('', $elements);
        if (empty($elementsStr)) {
            $elementsStr = '<text x="400" y="300" font-family="Arial" font-size="20" fill="white" text-anchor="middle">' . htmlspecialchars(substr($prompt, 0, 40)) . '</text>';
        }
        
        return '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600">
            <defs>
                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:rgb(' . $r . ',' . $g . ',' . $b . ');stop-opacity:1" />
                    <stop offset="100%" style="stop-color:rgb(' . (255-$r) . ',' . (255-$g) . ',' . (255-$b) . ');stop-opacity:1" />
                </linearGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#grad)"/>
            ' . $elementsStr . '
            <text x="50%" y="580" font-family="Arial" font-size="12" fill="white" text-anchor="middle" opacity="0.7">Inspiré de: ' . htmlspecialchars(substr($prompt, 0, 30)) . '</text>
        </svg>';
    }
    
    // PUBLIC ROUTES
    #[Route('/challenges', name: 'challenges_index', methods: ['GET'])]
    public function challenges(Request $request, DefiRepository $defiRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'recent');
        $statut = $request->query->get('statut');
        $defis = $defiRepository->searchDefis($search, $sortBy, $statut);

        return $this->render('challenges.html.twig', [
            'defis' => $defis,
            'search' => $search,
            'sortBy' => $sortBy,
            'statut' => $statut,
        ]);
    }
    #[Route('/challenges/search', name: 'challenges_search', methods: ['GET'])]
    public function challengesSearch(Request $request, DefiRepository $defiRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'recent');
        $statut = $request->query->get('statut');
        $defis = $defiRepository->searchDefis($search, $sortBy, $statut);

        // Group by status
        $activeDefis = [];
        $closedDefis = [];
        
        foreach ($defis as $defi) {
            if ($defi->getStatut()->value === 'OUVERT') {
                $activeDefis[] = $defi;
            } else {
                $closedDefis[] = $defi;
            }
        }

        return $this->json([
            'active' => array_map(fn($d) => [
                'id' => $d->getId(),
                'titre' => $d->getTitre(),
                'description' => $d->getDescription(),
                'theme' => $d->getTheme(),
                'statut' => $d->getStatut()->value,
                'dateDebut' => $d->getDateDebut()->format('d/m/Y'),
                'dateFin' => $d->getDateFin()->format('d/m/Y'),
                'imageCover' => $d->getImageCover(),
            ], $activeDefis),
            'closed' => array_map(fn($d) => [
                'id' => $d->getId(),
                'titre' => $d->getTitre(),
                'description' => $d->getDescription(),
                'theme' => $d->getTheme(),
                'statut' => $d->getStatut()->value,
                'dateDebut' => $d->getDateDebut()->format('d/m/Y'),
                'dateFin' => $d->getDateFin()->format('d/m/Y'),
                'imageCover' => $d->getImageCover(),
            ], $closedDefis),
            'count' => count($defis),
        ]);
    }

    #[Route('/challenges/{id}/participer', name: 'challenges_participate', methods: ['GET', 'POST'])]
    public function participer(Request $request, Defi $defi, EntityManagerInterface $entityManager, OeuvreRepository $oeuvreRepository): Response
    {
        if ($defi->getStatut()->value !== 'OUVERT') {
            $this->addFlash('error', 'Ce défi n\'est plus ouvert aux participations.');
            return $this->redirectToRoute('challenges_index');
        }

        $form = $this->createForm(ParticiperType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Get the description value safely
            $description = $form->get('description')->getData();
            $artworkId = $form->get('artworkId')->getData();
            
            // Custom validation: check if at least artworkId or painted image exists
            $paintedImageData = $request->request->get('paintedImageData');
            $hasArtwork = !empty($artworkId) && $artworkId > 0;
            $hasPaintedImage = !empty($paintedImageData);
            
            // Validate description - only add custom validation errors if form is not already invalid
            if (!$form->get('description')->getErrors(true)->count()) {
                if (empty(trim($description ?? ''))) {
                    $form->get('description')->addError(new \Symfony\Component\Form\FormError('La description est requise.'));
                } elseif (strlen($description ?? '') < 5) {
                    $form->get('description')->addError(new \Symfony\Component\Form\FormError('La description doit contenir au moins 5 caractères.'));
                } elseif (strlen($description ?? '') > 5000) {
                    $form->get('description')->addError(new \Symfony\Component\Form\FormError('La description ne peut pas dépasser 5000 caractères.'));
                }
            }
            
            // Validate artwork ID if provided
            if (!empty($artworkId)) {
                if (!is_numeric($artworkId) || (int)$artworkId <= 0) {
                    $form->get('artworkId')->addError(new \Symfony\Component\Form\FormError('L\'ID de l\'œuvre doit être un nombre positif.'));
                } else {
                    // Check if artwork exists
                    $oeuvre = $oeuvreRepository->find((int)$artworkId);
                    if (!$oeuvre) {
                        $form->get('artworkId')->addError(new \Symfony\Component\Form\FormError('L\'œuvre avec l\'ID ' . (int)$artworkId . ' n\'existe pas.'));
                    }
                }
            }
            
            // Check if at least one of artworkId or painted image is provided
            if (!$hasArtwork && !$hasPaintedImage) {
                $form->get('artworkId')->addError(new \Symfony\Component\Form\FormError('Veuillez indiquer l\'ID d\'une œuvre existante ou peindre une nouvelle œuvre.'));
            }
            
            // Check if form is valid after all validations
            if ($form->isValid()) {
                $data = $form->getData();
                $artworkIdValue = isset($data['artworkId']) && $data['artworkId'] > 0 ? (int) $data['artworkId'] : null;

                $participation = new Participation();
                $participation->setDefi($defi);
                $participation->setDescription($data['description']);
                $participation->setArtworkId($artworkIdValue);
                $participation->setDateSoumission(new \DateTime());
                $participation->setStatut(StatutParticipation::EN_ATTENTE);
                $participation->setUserId(1); // TODO: remplacer par l'utilisateur connecté

                $entityManager->persist($participation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre participation a bien été enregistrée. Merci !');
                return $this->redirectToRoute('challenges_index');
            }
        }

        return $this->render('challenges/participer.html.twig', [
            'defi' => $defi,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/challenges/{id}/peindre', name: 'challenges_peindre', methods: ['GET'])]
    public function peindre(Defi $defi): Response
    {
        return $this->render('challenges/peindre.html.twig', [
        
            'defi' => $defi,
        ]);
    }


    #[Route('/challenges/{id}/peindre-save', name: 'challenges_peindre_save', methods: ['POST'])]
    public function peindreSave(Request $request, Defi $defi, EntityManagerInterface $entityManager): Response
    {
        $imageData = $request->request->get('imageData');
        $description = $request->request->get('description', 'Peinture digitale');

        if (!$imageData) {
            $this->addFlash('error', 'Aucune image reçue depuis l\'éditeur.');
            return $this->redirectToRoute('challenges_participate', ['id' => $defi->getId()]);
        }

        // data URL -> decode
        if (preg_match('/^data:(image\/png|image\/jpeg|image\/webp);base64,(.*)$/', $imageData, $matches)) {
            $mime = $matches[1];
            $base64 = $matches[2];
        } else {
            // try generic split
            $parts = explode(',', $imageData, 2);
            $base64 = $parts[1] ?? null;
            $mime = 'image/png';
        }

        if (!$base64) {
            $this->addFlash('error', 'Format d\'image invalide.');
            return $this->redirectToRoute('challenges_participate', ['id' => $defi->getId()]);
        }

        $data = base64_decode($base64);
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads/participations';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0777, true);
        }

        $ext = 'png';
        if (strpos($mime, 'jpeg') !== false) $ext = 'jpg';
        if (strpos($mime, 'webp') !== false) $ext = 'webp';

        $filename = uniqid('paint_') . '.' . $ext;
        $target = $uploadsDir . '/' . $filename;
        file_put_contents($target, $data);

        $participation = new Participation();
        $participation->setDefi($defi);
        $participation->setDescription($description);
        $participation->setArtworkId(null);
        $participation->setImageFileName($filename);
        $participation->setDateSoumission(new \DateTime());
        $participation->setStatut(StatutParticipation::EN_ATTENTE);
        $participation->setUserId(1); // TODO: remplacer par l'utilisateur connecté

        $entityManager->persist($participation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre peinture a été enregistrée et envoyée. Merci !'); 
        return $this->redirectToRoute('challenges_index');
    }

#[Route('/admin/challenges', name: 'admin_challenges', methods: ['GET', 'POST'])]
public function adminChallenges(Request $request, DefiRepository $defiRepository, EntityManagerInterface $entityManager): Response
{
    $defi = new Defi();
    $form = $this->createForm(DefiType::class, $defi);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Get unmapped field values
            $dateDebut = $form->get('dateDebut')->getData();
            $dateFin = $form->get('dateFin')->getData();
            
            // Validate dates are not empty - add form error if empty
            if (empty($dateDebut)) {
                $form->get('dateDebut')->addError(new \Symfony\Component\Form\FormError('La date de début est obligatoire'));
            }
            if (empty($dateFin)) {
                $form->get('dateFin')->addError(new \Symfony\Component\Form\FormError('La date de fin est obligatoire'));
            }
            
            // Check if form is still valid after date validation
            if (!$form->isValid()) {
                $defis = $defiRepository->findAll();
                $stats = [
                    'defisOuverts' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'OUVERT')),
                    'defisFermes' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'FERME' || $d->getStatut()->value === 'FERMEE')),
                    'totalParticipations' => array_reduce($defis, fn($total, $d) => $total + $d->getParticipations()->count(), 0),
                ];
                $monthlyStats = ['labels' => [], 'data' => []];
                return $this->render('admin/challenges.html.twig', [
                    'defis' => $defis,
                    'form' => $form->createView(),
                    'defi' => null,
                    'stats' => $stats,
                    'monthlyStats' => $monthlyStats,
                ]);
            }
            
            // Set dates on entity
            $defi->setDateDebut($dateDebut);
            $defi->setDateFin($dateFin);
            
            $defi->setCreateurId(1);
            $entityManager->persist($defi);
            $entityManager->flush();
            $this->addFlash('success', 'Défi créé avec succès');
            return $this->redirectToRoute('admin_challenges');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            $defis = $defiRepository->findAll();
            $stats = [
                'defisOuverts' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'OUVERT')),
                'defisFermes' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'FERME' || $d->getStatut()->value === 'FERMEE')),
                'totalParticipations' => array_reduce($defis, fn($total, $d) => $total + $d->getParticipations()->count(), 0),
            ];
            $monthlyStats = ['labels' => [], 'data' => []];
            return $this->render('admin/challenges.html.twig', [
                'defis' => $defis,
                'form' => $form->createView(),
                'defi' => null,
                'stats' => $stats,
                'monthlyStats' => $monthlyStats,
            ]);
        }
    }

    $defis = $defiRepository->findAll();
    
    // Calcul des statistiques dynamiques
    $stats = [
        'defisOuverts' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'OUVERT')),
        'defisFermes' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'FERME' || $d->getStatut()->value === 'FERMEE')),
        'totalParticipations' => array_reduce($defis, fn($total, $d) => $total + $d->getParticipations()->count(), 0),
    ];
    
    // Statistiques mensuelles des participations (6 derniers mois)
    $monthlyStats = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = new \DateTime();
        $date->modify("-{$i} months");
        $month = $date->format('Y-m');
        $monthLabel = $date->format('M');
        
        $participationsThisMonth = 0;
        foreach ($defis as $defi) {
            foreach ($defi->getParticipations() as $participation) {
                if ($participation->getDateSoumission() && $participation->getDateSoumission()->format('Y-m') === $month) {
                    $participationsThisMonth++;
                }
            }
        }
        
        $monthlyStats['labels'][] = $monthLabel;
        $monthlyStats['data'][] = $participationsThisMonth;
    }

    return $this->render('admin/challenges.html.twig', [
        'defis' => $defis,
        'form' => $form->createView(),
        'defi' => null,
        'stats' => $stats,
        'monthlyStats' => $monthlyStats,
        'prefill' => [
            'titre' => $request->query->get('titre', ''),
            'theme' => $request->query->get('theme', ''),
            'description' => $request->query->get('description', ''),
        ],
    ]);
}

    // DEFI OPERATIONS
    #[Route('/defis/{id}/edit', name: 'defi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Defi $defi, DefiRepository $defiRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createDefiForm($defi);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation des champs non mappés (dates)
            $dateDebut = $form->get('dateDebut')->getData();
            $dateFin = $form->get('dateFin')->getData();
            
            // Vérifier si les dates sont vides (null ou chaîne vide)
            if (empty($dateDebut)) {
                $form->get('dateDebut')->addError(new \Symfony\Component\Form\FormError('La date de début est obligatoire'));
            }
            if (empty($dateFin)) {
                $form->get('dateFin')->addError(new \Symfony\Component\Form\FormError('La date de fin est obligatoire'));
            }
            
            if ($form->isValid()) {
                // Set dates manually for unmapped fields
                $defi->setDateDebut($dateDebut);
                $defi->setDateFin($dateFin);
                
                $entityManager->flush();

                $this->addFlash('success', 'Défi modifié avec succès');
                return $this->redirectToRoute('admin_challenges');
            }
        }

        $defis = $defiRepository->findAll();

        // Calcul des statistiques dynamiques
        $stats = [
            'defisOuverts' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'OUVERT')),
            'defisFermes' => count(array_filter($defis, fn($d) => $d->getStatut()->value === 'FERME' || $d->getStatut()->value === 'FERMEE')),
            'totalParticipations' => array_reduce($defis, fn($total, $d) => $total + $d->getParticipations()->count(), 0),
        ];

        // Statistiques mensuelles des participations (6 derniers mois)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $month = $date->format('Y-m');
            $monthLabel = $date->format('M');

            $participationsThisMonth = 0;
            foreach ($defis as $defi) {
                foreach ($defi->getParticipations() as $participation) {
                    if ($participation->getDateSoumission() && $participation->getDateSoumission()->format('Y-m') === $month) {
                        $participationsThisMonth++;
                    }
                }
            }

            $monthlyStats['labels'][] = $monthLabel;
            $monthlyStats['data'][] = $participationsThisMonth;
        }

        return $this->render('admin/challenges.html.twig', [
            'form' => $form->createView(),
            'defi' => $defi,
            'defis' => $defis,
            'stats' => $stats,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    #[Route('/defis/{id}', name: 'defi_delete', methods: ['POST'])]
    public function delete(Request $request, Defi $defi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $defi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($defi);
            $entityManager->flush();

            $this->addFlash('success', 'Défi supprimé avec succès');
        }

        return $this->redirectToRoute('admin_challenges');
    }

    // AI GENERATE DIFFICULTY
    #[Route('/admin/defis/{id}/generate-difficulte', name: 'defi_generate_difficulte', methods: ['POST'])]
    public function generateDifficulte(Request $request, Defi $defi, EntityManagerInterface $entityManager): Response
    {
        // Analyze the challenge description to determine difficulty
        $titre = $defi->getTitre() ?? '';
        $description = $defi->getDescription() ?? '';
        $theme = $defi->getTheme() ?? '';
        
        $analysisText = $titre . ' ' . $description . ' ' . $theme;
        
        // Try Ollama first
        $ch = @curl_init('http://localhost:11434/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $ollamaCheck = @curl_exec($ch);
        $ollamaHttpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $difficulte = null;
        
        if ($ollamaHttpCode === 200 && !empty($ollamaCheck)) {
            try {
                $prompt = "Analyse ce défi et détermine sa difficulté. 

Défi: $analysisText

Réponds UNIQUEMENT avec un mot: facile, moyen, difficile ou expert";
                
                $ch = curl_init('http://localhost:11434/api/generate');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => 'llama3.2',
                    'prompt' => $prompt,
                    'stream' => false
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response, true);
                    if (isset($data['response'])) {
                        $result = strtolower(trim($data['response']));
                        if (in_array($result, ['facile', 'moyen', 'difficile', 'expert'])) {
                            $difficulte = $result;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fall through to local generator
            }
        }
        
        // If Ollama didn't work, use local AI
        if (!$difficulte) {
            $difficulte = $this->generateLocalDifficulty($analysisText);
        }
        
        // Set the difficulty on the challenge
        if ($difficulte) {
            $defi->setDifficulte(\App\Enum\DifficulteEnum::from($difficulte));
            $entityManager->flush();
            $this->addFlash('success', 'Difficulté générée: ' . ucfirst($difficulte));
        } else {
            $this->addFlash('error', 'Impossible de générer la difficulté');
        }
        
        return $this->redirectToRoute('admin_challenges');
    }
    
    private function generateLocalDifficulty(string $text): string
    {
        $text = strtolower($text);
        
        // Expert keywords
        $expertKeywords = ['master', 'expert', 'avancé', 'complexe', 'professionnel', 'perfectionnement', 'maîtrise', 'création originale', 'innovation', 'breakthrough', 'pionnier'];
        // Difficult keywords
        $difficultKeywords = ['difficile', 'intermédiaire', 'créatif', 'design', 'composition', 'technique', 'avancé', 'artistique', 'élaboré', 'détaillé'];
        // Easy keywords  
        $easyKeywords = ['facile', 'débutant', 'simple', 'base', 'initiation', 'introduction', 'découverte', 'apprentissage', 'premier', 'basic'];
        
        $expertScore = 0;
        $difficultScore = 0;
        $easyScore = 0;
        
        foreach ($expertKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) $expertScore += 2;
        }
        foreach ($difficultKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) $difficultScore += 1;
        }
        foreach ($easyKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) $easyScore += 1;
        }
        
        // Check text length as a factor
        $length = strlen($text);
        if ($length > 500) $difficultScore += 1;
        if ($length > 1000) $expertScore += 1;
        if ($length < 100) $easyScore += 1;
        
        if ($expertScore > $difficultScore && $expertScore > $easyScore) {
            return 'expert';
        } elseif ($difficultScore > $easyScore) {
            return 'difficile';
        } else {
            return 'facile';
        }
    }

    // ADMIN PARTICIPATIONS - LIST BY DEFI
    #[Route('/admin/challenges/{id}/participations', name: 'admin_defi_participations', methods: ['GET'])]
    public function adminDefiParticipations(Defi $defi, ParticipationRepository $participationRepository): Response
    {
        $participations = $participationRepository->findBy(['defi' => $defi]);

        return $this->render('admin/participations.html.twig', [
            'defi' => $defi,
            'participations' => $participations,
        ]);
    }

    // AI GENERATE CHALLENGE - PAGE
    #[Route('/admin/challenges/ai-create', name: 'admin_challenges_ai_create', methods: ['GET', 'POST'])]
    public function aiCreateChallenge(Request $request): Response
    {
        $result = null;
        $prompt = '';
        
        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt', '');
        } elseif ($request->isMethod('GET')) {
            $prompt = $request->query->get('prompt', '');
        }
        
        if (!empty($prompt)) {
            // Try Ollama (local free AI) first
            $ollamaAvailable = false;
            
            // Check if Ollama is running locally
            $ch = @curl_init('http://localhost:11434/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            $ollamaCheck = @curl_exec($ch);
            $ollamaHttpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($ollamaHttpCode === 200 && !empty($ollamaCheck)) {
                $ollamaAvailable = true;
            }
            
            if ($ollamaAvailable) {
                // Use Ollama (free local AI)
                try {
                    $ollamaPrompt = "Crée un défi créatif pour une plateforme. 
                    
Sujet: $prompt

Génère un titre accrocheur (max 50 caractères), un thème (un seul mot en français), et une description détaillée (2-3 phrases en français).

Réponds UNIQUEMENT en JSON avec ce format:
{
  \"titre\": \"...\",
  \"theme\": \"...\",
  \"description\": \"...\"
}";
                    
                    $ch = curl_init('http://localhost:11434/api/generate');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'model' => 'llama3.2',
                        'prompt' => $ollamaPrompt,
                        'stream' => false,
                        'format' => 'json'
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $data = json_decode($response, true);
                        if (isset($data['response'])) {
                            $result = json_decode($data['response'], true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $result = [
                                    'titre' => 'Défi ' . ucfirst($prompt),
                                    'theme' => 'Créatif',
                                    'description' => 'Participez à ce défi créatif sur le thème de ' . $prompt . '!'
                                ];
                            }
                        } else {
                            throw new \Exception('Invalid Ollama response');
                        }
                    } else {
                        throw new \Exception('Ollama HTTP ' . $httpCode);
                    }
                } catch (\Exception $e) {
                    // Fall through to xAI or random
                }
            }
            
            // If Ollama didn't work, try xAI API
            if (!isset($result) || !isset($result['titre'])) {
                // Get API key from environment variable
                $apiKey = $_ENV['XAI_API_KEY'] ?? getenv('XAI_API_KEY');
                
                if (empty($apiKey)) {
                    // Advanced local AI generator (completely free, no API needed)
                    $result = $this->generateAdvancedLocalChallenge($prompt);
                } else {
                $fullPrompt = "Crée un défi créatif pour une plateforme. 
                
Sujet: $prompt

Génère un titre accrocheur (max 50 caractères), un thème (un seul mot), et une description détaillée (2-3 phrases).

Réponds UNIQUEMENT en JSON avec ce format:
{
  \"titre\": \"...\",
  \"theme\": \"...\",
  \"description\": \"...\"
}";

                try {
                    $ch = curl_init('https://api.x.ai/v1/chat/completions');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'model' => 'grok-2-vision',
                        'messages' => [
                            ['role' => 'system', 'content' => 'Tu es un assistant créatif qui génère des défis pour une plateforme. Réponds toujours en JSON valide.'],
                            ['role' => 'user', 'content' => $fullPrompt]
                        ],
                        'temperature' => 0.7
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $apiKey
                    ]);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        $result = ['error' => 'cURL error: ' . $curlError];
                    } elseif ($httpCode !== 200) {
                        // Use advanced local generator when API fails (no credits, etc.)
                        $result = $this->generateAdvancedLocalChallenge($prompt);
                    } else {
                        $data = json_decode($response, true);
                        
                        if (isset($data['choices'][0]['message']['content'])) {
                            $content = $data['choices'][0]['message']['content'];
                            $content = trim($content);
                            
                            // Try to parse JSON from the response
                            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                                $content = $matches[1];
                            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
                                $content = $matches[1];
                            }
                            
                            $result = json_decode($content, true);
                            
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $result = [
                                    'titre' => 'Défi ' . ucfirst($prompt),
                                    'theme' => 'Creatif',
                                    'description' => 'Participez à ce défi créatif sur le thème de ' . $prompt . '!'
                                ];
                            }
                        } else {
                            $result = ['error' => 'Invalid API response format'];
                        }
                    }
                } catch (\Exception $e) {
                    $result = ['error' => $e->getMessage()];
                }
                } // End xAI else
            } // End if (!isset($result) || !isset($result['titre']))
        } // End if (!empty($prompt))

        return $this->render('admin/ai_create_challenge.html.twig', [
            'result' => $result,
            'prompt' => $prompt,
        ]);
    }

    // ADMIN PARTICIPATIONS - ALL (no defi filter)
    #[Route('/admin/challenges/participations', name: 'admin_participations', methods: ['GET'])]
    public function adminParticipations(Request $request, ParticipationRepository $participationRepository): Response
    {
        $userId = $request->query->get('userId');
        $participations = $participationRepository->searchParticipations($userId ? (int) $userId : null);

        return $this->render('admin/participations.html.twig', [
            'participations' => $participations,
            'filteredUserId' => $userId,
            'defi' => null,
        ]);
    }

    #[Route('/admin/participations/{id}/edit-statut', name: 'admin_participation_edit_statut', methods: ['GET', 'POST'])]
    public function adminParticipationEditStatut(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationStatutType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Statut de la participation mis à jour.');
            return $this->redirectToRoute('admin_participations');
        }

        // Récupérer l'utilisateur
        $user = $entityManager->getRepository(User::class)->find($participation->getUserId());

        return $this->render('admin/participation_edit_statut.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/participations/{id}/delete', name: 'admin_participation_delete', methods: ['POST'])]
    public function adminParticipationDelete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('admin_delete_participation' . $participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
            $this->addFlash('success', 'Participation supprimée.');
        }

        return $this->redirectToRoute('admin_participations');
    }

    private function createDefiForm(Defi $defi)
    {
        return $this->createForm(DefiType::class, $defi);
    }

    private function generateAdvancedLocalChallenge(string $userPrompt): array
    {
        $themes = [
            'Sport' => ['sport', 'football', 'tennis', 'basket', 'volley', 'rugby', 'natation', 'athletisme', 'cyclisme', 'marathon', 'compétition', 'équipe', 'match', 'joueur', 'ballon', 'terrain', 'score', 'victoire', 'entraînement', 'performance', 'endurance', 'gol', 'but', 'tir', 'course', 'podium'],
            'Écologie' => ['écologie', 'vert', 'nature', 'durable', 'protection', 'biodiversité', 'environnement', 'climat', 'réchauffement', 'recyclage', 'energie', 'ocean', 'marin', 'forêt', 'animal', 'pollution', 'développement', 'vert'],
            'Art' => ['art', 'créatif', 'design', 'expression', 'culture', 'muséal', 'peinture', 'dessin', 'sculpture', 'photo', 'musée', 'exposition', 'artiste', 'galerie', 'création', 'oeuvre', ' masterpiece'],
            'Technologie' => ['technologie', 'digital', 'innovation', 'smart', 'futur', 'numérique', 'robot', 'ia', 'intelligence', 'artificielle', 'code', 'programmation', 'app', 'application', 'startup', 'tech'],
            'Santé' => ['santé', 'bien-être', 'vitalité', 'alimentation', 'mental', 'fitness', 'gym', 'yoga', 'méditation', 'relaxation', 'forme', 'bienêtre', 'santé', 'bien'],
            'Musique' => ['musique', 'rythmique', 'mélodie', 'concert', 'instrument', 'chant', 'chanson', 'dj', 'beat', 'son', 'audio', 'studio', 'mix'],
            'Science' => ['science', 'découverte', 'recherche', 'laboratoire', 'espace', 'physique', 'chimie', 'biologie', 'astronomie', 'espace', 'expériences', 'invention'],
            'Nature' => ['nature', 'extérieur', 'randonnée', 'paysage', 'flore', 'faune', 'montagne', 'forêt', 'plage', 'désert', 'voyage', 'exploration'],
            'Créativité' => ['créativité', 'imagination', 'originalité', 'inspiration', 'artistique', 'vision', 'création', 'concept', 'design', 'art'],
            'Innovation' => ['innovation', 'nouveau', 'rupture', 'transformation', 'pionnier', 'aventure', 'disruptif', 'unique', 'futur', 'modern'],
            'Éducation' => ['éducation', 'apprentissage', 'formation', 'connaissance', 'pédagogie', 'éveil', 'études', 'université', 'école', 'cours'],
            'Social' => ['social', 'communauté', 'solidarité', 'partage', 'ensemble', 'humanitaire', 'bénévolat', 'aide', 'collectif']
        ];

        $promptLower = mb_strtolower($userPrompt, 'UTF-8');
        $selectedTheme = 'Sport';
        $themeKeywords = [];

        foreach ($themes as $themeName => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($promptLower, $keyword) !== false) {
                    $selectedTheme = $themeName;
                    $themeKeywords = $keywords;
                    break 2;
                }
            }
        }

        if (empty($themeKeywords)) {
            $themeKeys = array_keys($themes);
            $selectedTheme = $themeKeys[array_rand($themeKeys)];
            $themeKeywords = $themes[$selectedTheme];
        }

        // Clean and extract prompt words
        $promptWords = array_filter(explode(' ', preg_replace('/[^a-zA-Zàâäéèêëïîôùûüç]+/i', ' ', $promptLower)));
        $promptContext = !empty($promptWords) ? implode(' ', array_slice($promptWords, 0, 3)) : $selectedTheme;

        // Professional titles
        $titles = [
            "Défi $selectedTheme: $promptContext - Edition Spéciale",
            "Challenge $promptContext - Triomphez!, ",
            "$selectedTheme Challenge: Votre Moment de Gloire",
            "Concours $promptContext - Montrez Votre Talent",
            "Le Grand Défi $promptContext - À Vous de Jouer!",
            "$promptContext: Relevez le Défi Ultime!",
            "Événement $selectedTheme: Défi$promptContext",
            "$promptContext - Le Défi qui Change Tout"
        ];

        // Detailed professional descriptions
        $descriptions = [
            "Bienvenue dans ce défi exceptionnel! Nous cherchons les talents les plus créatifs pour participer au challenge '$promptContext'. 
            
            Votre mission: créez quelque chose d'unique et d'innovant autour de ce thème passionnant. Laissez parler votre créativité et montrez-nous ce dont vous êtes capable!
            
            Les meilleures réalisations seront mises en avant et récompensées. N'attendez plus, montrez votre talent au monde entier!",
            
            "Participez au défi '$promptContext' et prouvé que vous avez ce qu'il faut pour gagner!
            
            Ce challenge est ouvert à tous ceux qui souhaitent démontrer leur créativité et leur expertise. Que vous soyez débutant ou professionnel, ce défi est fait pour vous!
            
            Créez, innovez, et impressionnez notre communauté. Les opportunités sont infinies!",
            
            "Le défi '$promptContext' est officiellement lancé! Nous cherchons des créateurs ambitieux prêts à relever ce challenge exclusif.
            
            Votre objectif: réaliser une œuvre originale qui captures l'essence de '$promptContext'. Que ce soit une photo, une vidéo, un dessin, ou toute autre forme d'art, nous voulons voir votre vision!
            
            Rejoignez-nous et faites partie de cette aventure créative exceptionnelle!",
            
            "Vous êtes prêt pour le défi '$promptContext'? Nous mettons au défi tous les créatifs de se dépasser!
            
            Ce challenge représente une opportunité unique de démontrer vos compétences et votre imagination. Chaque participation compte et peut faire la différence.
            
            Montrez-nous ce que vous avez changé: les meilleures créations seront exposées et célébrées!",
            
            "Le moment est venu de briller avec le défi '$promptContext'! 
            
            Nous lançons ce challenge pour rassembler la communauté autour d'un thème captivant. Votre tâche est de créer quelque chose d'extraordinaire qui inspirera les autres.
            
            Utilisez toutes vos compétences et laissez libre cours à votre imagination. Le succès vous attend!"
        ];

        return [
            'titre' => $titles[array_rand($titles)],
            'theme' => $selectedTheme,
            'description' => $descriptions[array_rand($descriptions)]
        ];
    }
}
