<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Repository\UserPreferenceRepository;
use App\Entity\UserPreference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuizController extends AbstractController
{
    public function __construct(
        private QuestionRepository $questionRepository,
        private ReponseRepository $reponseRepository,
        private UserPreferenceRepository $userPreferenceRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('/quiz', name: 'quiz_default', methods: ['GET'])]
    public function start(): Response
    {
        return $this->redirectToRoute('quiz', ['step' => 1]);
    }

    #[Route('/quiz/{step}', name: 'quiz', methods: ['GET', 'POST'], requirements: ['step' => '\d+'])]
    public function index(int $step = 1, Request $request): Response
    {
        // Fetch all questions ordered by creation
        $allQuestions = $this->questionRepository->findBy([], ['id' => 'ASC']);
        $total = count($allQuestions);

        // If this is a POST request, save the selected response to session
        if ($request->isMethod('POST')) {
            $reponseId = $request->request->get('reponse');
            if ($reponseId) {
                $session = $request->getSession();
                $responses = $session->get('quiz_responses', []);
                $responses[$step - 1] = $reponseId;
                $session->set('quiz_responses', $responses);
            }
        }

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

    #[Route('/quiz/complete', name: 'quiz_complete', methods: ['POST'])]
    public function completeQuiz(Request $request): Response
    {
        // Get user
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get session responses
        $session = $request->getSession();
        $responses = $session->get('quiz_responses', []);

        if (empty($responses)) {
            $this->addFlash('error', 'Aucune réponse de quiz trouvée.');
            return $this->redirectToRoute('quiz', ['step' => 1]);
        }

        // Collect tags from selected responses
        $allTags = [];
        foreach ($responses as $reponseId) {
            $reponse = $this->reponseRepository->find($reponseId);
            if ($reponse && $reponse->getTag()) {
                $allTags[] = $reponse->getTag();
            }
        }

        // Merge tags into a single string, separated by commas
        $tagsString = implode(',', $allTags);

        // Save or update user preferences
        $userPreference = $this->userPreferenceRepository->findByUserId($user->getId());
        if (!$userPreference) {
            $userPreference = new UserPreference();
            $userPreference->setUser($user);
        }
        $userPreference->setTags($tagsString);
        $this->em->persist($userPreference);
        $this->em->flush();

        // Clear session
        $session->remove('quiz_responses');

        // Flash message and redirect
        $this->addFlash('success', 'Quiz complété ! Tes préférences ont été mises à jour.');
        return $this->redirectToRoute('discover');
    }
}
