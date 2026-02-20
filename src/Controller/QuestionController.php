<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuestionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestionRepository $questionRepository,
        private ReponseRepository $reponseRepository
    ) {}

    #[Route('/admin/quiz-questions', name: 'admin_quiz_questions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'createdAt_desc');
        
        // Parse sort parameter (format: field_direction)
        $sortParts = explode('_', $sort);
        $sortField = $sortParts[0] ?? 'createdAt';
        $dir = $sortParts[1] ?? 'desc';

        $questions = $this->questionRepository->searchAndSort($search, $sortField, $dir);
        $reponses = $search ? $this->reponseRepository->searchAndSort($search) : $this->reponseRepository->findAll();

        return $this->render('admin/quiz-questions.html.twig', [
            'questions' => $questions,
            'reponses' => $reponses,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/admin/quiz-questions/create', name: 'admin_quiz_questions_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $questionText = $request->request->get('question');
        $errors = [];

        // VALIDATION START

        // 1. question: NOT empty
        if (empty($questionText)) {
            $errors[] = 'La question ne peut pas être vide.';
        }
        // 2. question: Minimum 10 characters
        elseif (strlen($questionText) < 10) {
            $errors[] = 'La question doit contenir au minimum 10 caractères.';
        }
        // 3. question: Must end with "?"
        elseif (!str_ends_with(trim($questionText), '?')) {
            $errors[] = 'La question doit se terminer par un point d\'interrogation.';
        }

        // VALIDATION END

        if (empty($errors)) {
            $question = new Question();
            $question->setQuestion($questionText);
            $this->entityManager->persist($question);
            $this->entityManager->flush();
            $this->addFlash('success', 'Question créée avec succès.');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_quiz_questions');
    }

    #[Route('/admin/quiz-questions/edit/{id}', name: 'admin_quiz_questions_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $question = $this->questionRepository->find($id);

        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        return $this->render('admin/quiz-questions.html.twig', [
            'editQuestion' => $question,
            'questions' => $this->questionRepository->findAll(),
            'reponses' => $this->reponseRepository->findAll(),
            'search' => null,
            'sort' => 'createdAt_desc',
        ]);
    }

    #[Route('/admin/quiz-questions/update/{id}', name: 'admin_quiz_questions_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $question = $this->questionRepository->find($id);

        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        $questionText = $request->request->get('question');
        $errors = [];

        // VALIDATION START

        // 1. question: NOT empty
        if (empty($questionText)) {
            $errors[] = 'La question ne peut pas être vide.';
        }
        // 2. question: Minimum 10 characters
        elseif (strlen($questionText) < 10) {
            $errors[] = 'La question doit contenir au minimum 10 caractères.';
        }
        // 3. question: Must end with "?"
        elseif (!str_ends_with(trim($questionText), '?')) {
            $errors[] = 'La question doit se terminer par un point d\'interrogation.';
        }

        // VALIDATION END

        if (empty($errors)) {
            $question->setQuestion($questionText);
            $this->entityManager->flush();
            $this->addFlash('success', 'Question mise à jour avec succès.');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_quiz_questions');
    }

    #[Route('/admin/quiz-questions/delete/{id}', name: 'admin_quiz_questions_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $question = $this->questionRepository->find($id);
        $errors = [];

        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        // CSRF token validation
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_question_' . $id, $token)) {
            $errors[] = 'Token de sécurité invalide. Veuillez réessayer.';
        }

        if (empty($errors)) {
            $this->entityManager->remove($question);
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_quiz_questions');
        }

        // Re-render with errors
        return $this->render('admin/quiz-questions.html.twig', [
            'questions' => $this->questionRepository->findAll(),
            'reponses' => $this->reponseRepository->findAll(),
            'search' => null,
            'sort' => 'createdAt_desc',
            'errors' => $errors,
        ]);
    }
}
