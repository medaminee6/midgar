<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReponseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReponseRepository $reponseRepository,
        private QuestionRepository $questionRepository
    ) {}

    #[Route('/admin/quiz-reponses/create', name: 'admin_quiz_reponses_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $option = $request->request->get('option');
        $tag = $request->request->get('tag');
        $questionId = $request->request->get('question_id');
        $errors = [];

        // VALIDATION START

        // Validate question exists
        $question = $this->questionRepository->find($questionId);
        if (!$question) {
            $errors[] = 'Question not found';
        }

        // 1. option: NOT empty
        if (empty($option)) {
            $errors[] = 'L\'option ne peut pas être vide.';
        }

        // 2. tag: NOT empty
        if (empty($tag)) {
            $errors[] = 'Le tag ne peut pas être vide.';
        }
        // 3. tag: Must start with "#"
        elseif (!str_starts_with(trim($tag), '#')) {
            $errors[] = 'Le tag doit commencer par un #.';
        }

        // VALIDATION END

        if (empty($errors)) {
            $reponse = new Reponse();
            $reponse->setOption($option);
            $reponse->setTag($tag);
            $reponse->setQuestion($question);
            $this->entityManager->persist($reponse);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin_quiz_questions', $errors ? ['errors' => $errors] : []);
    }

    #[Route('/admin/quiz-reponses/edit/{id}', name: 'admin_quiz_reponses_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $reponse = $this->reponseRepository->find($id);

        if (!$reponse) {
            throw $this->createNotFoundException('Reponse not found');
        }

        return $this->render('admin/quiz-questions.html.twig', [
            'editReponse' => $reponse,
            'questions' => $this->questionRepository->findAll(),
            'reponses' => $this->reponseRepository->findAll(),
            'search' => null,
            'sort' => 'createdAt_desc',
        ]);
    }

    #[Route('/admin/quiz-reponses/update/{id}', name: 'admin_quiz_reponses_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $reponse = $this->reponseRepository->find($id);

        if (!$reponse) {
            throw $this->createNotFoundException('Reponse not found');
        }

        $option = $request->request->get('option');
        $tag = $request->request->get('tag');
        $questionId = $request->request->get('question_id');
        $errors = [];

        // VALIDATION START

        // Validate question exists
        $question = $this->questionRepository->find($questionId);
        if (!$question) {
            $errors[] = 'Question not found';
        }

        // 1. option: NOT empty
        if (empty($option)) {
            $errors[] = 'L\'option ne peut pas être vide.';
        }

        // 2. tag: NOT empty
        if (empty($tag)) {
            $errors[] = 'Le tag ne peut pas être vide.';
        }
        // 3. tag: Must start with "#"
        elseif (!str_starts_with(trim($tag), '#')) {
            $errors[] = 'Le tag doit commencer par un #.';
        }

        // VALIDATION END

        if (empty($errors)) {
            $reponse->setOption($option);
            $reponse->setTag($tag);
            $reponse->setQuestion($question);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('admin_quiz_questions', $errors ? ['errors' => $errors] : []);
    }

    #[Route('/admin/quiz-reponses/delete/{id}', name: 'admin_quiz_reponses_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $reponse = $this->reponseRepository->find($id);
        $errors = [];

        if (!$reponse) {
            throw $this->createNotFoundException('Reponse not found');
        }

        // CSRF token validation
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_reponse_' . $id, $token)) {
            $errors[] = 'Token de sécurité invalide. Veuillez réessayer.';
        }

        if (empty($errors)) {
            $this->entityManager->remove($reponse);
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
