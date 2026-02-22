<?php

namespace App\Controller;

use App\Entity\UserPreference;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Repository\UserPreferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class QuizController extends AbstractController
{
    public function __construct(
        private QuestionRepository $questionRepository,
        private ReponseRepository $reponseRepository,
        private UserPreferenceRepository $userPreferenceRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/quiz/{step}', name: 'quiz', methods: ['GET'], requirements: ['step' => '\d+'])]
    public function index(int $step = 1): Response
    {
        // Fetch all questions ordered by creation
        $allQuestions = $this->questionRepository->findBy([], ['id' => 'ASC']);
        $total = count($allQuestions);

        // Validate step
        if ($step < 1) {
            $step = 1;
        }
        if ($step > $total) {
            $step = $total;
        }

        // Get current question (step is 1-indexed, array is 0-indexed)
        $currentQuestion = $allQuestions[$step - 1] ?? null;

        if (!$currentQuestion) {
            throw $this->createNotFoundException('Question not found');
        }

        // Get reponses for this question
        $reponses = $currentQuestion->getReponses();

        return $this->render('quiz.html.twig', [
            'question' => $currentQuestion,
            'reponses' => $reponses,
            'step' => $step,
            'total' => $total,
        ]);
    }

    #[Route('/quiz/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        // Check if user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        // Get selected reponse IDs from POST
        $selectedReponses = $request->request->all('reponse') ?? [];
        
        if (empty($selectedReponses)) {
            return $this->redirectToRoute('quiz', ['step' => 1]);
        }

        // Collect all tags from selected reponses
        $allTags = [];
        foreach ($selectedReponses as $reponseId) {
            $reponse = $this->reponseRepository->find($reponseId);
            if ($reponse && $reponse->getTag()) {
                // Split tags if comma-separated and add unique tags
                $tags = array_map('trim', explode(',', $reponse->getTag()));
                $allTags = array_merge($allTags, $tags);
            }
        }

        // Remove duplicates and create comma-separated string
        $uniqueTags = array_unique($allTags);
        $tagsString = implode(',', $uniqueTags);

        // Create or update user preferences
        $userPreference = $this->userPreferenceRepository->findOneBy(['user' => $user]);
        
        if (!$userPreference) {
            $userPreference = new UserPreference();
            $userPreference->setUser($user);
        }

        $userPreference->setTags($tagsString);
        $userPreference->setUpdatedAt(new \DateTime());

        $this->userPreferenceRepository->save($userPreference, true);

        // Redirect to discover page
        return $this->redirectToRoute('discover');
    }
}
