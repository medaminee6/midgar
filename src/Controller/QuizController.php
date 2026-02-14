<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuizController extends AbstractController
{
    public function __construct(
        private QuestionRepository $questionRepository
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
}
