<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Service\AnalyticsService;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\BarChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
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
        private ReponseRepository $reponseRepository,
        private AnalyticsService $analyticsService
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

        // Pie chart: distribution of recommended content tags.
        $piechart = new PieChart();
        $piechart->getData()->setArrayToDataTable($this->analyticsService->getRecommendedTagDistribution());
        $piechart->getOptions()->setTitle('Répartition des tags recommandés');
        $piechart->getOptions()->setHeight(320);
        $piechart->getOptions()->setBackgroundColor('transparent');
        $piechart->getOptions()->setColors(['#1aff9c', '#13c891', '#7cfad0', '#2dd4bf', '#34d399']);
        $piechart->getOptions()->setFontName('Inter, Arial, sans-serif');
        $piechart->getOptions()->getTitleTextStyle()->setColor('#ffffff');
        $piechart->getOptions()->getLegend()->getTextStyle()->setColor('#ffffff');
        $piechart->getOptions()->getChartArea()->setLeft(60);
        $piechart->getOptions()->getChartArea()->setTop(40);
        $piechart->getOptions()->getChartArea()->setWidth('80%');
        $piechart->getOptions()->getChartArea()->setHeight('70%');
        $piechart->getOptions()->getChartArea()->setBackgroundColor('transparent');

        // Bar chart: engagement score per tag (likes + 2x comments).
        $barchart = new BarChart();
        $barchart->getData()->setArrayToDataTable($this->analyticsService->getEngagementPerTag());
        $barchart->getOptions()->setTitle('Engagement par tag');
        $barchart->getOptions()->setHeight(320);
        $barchart->getOptions()->setBackgroundColor('transparent');
        $barchart->getOptions()->setColors(['#1aff9c']);
        $barchart->getOptions()->setFontName('Inter, Arial, sans-serif');
        $barchart->getOptions()->getTitleTextStyle()->setColor('#ffffff');
        $barchart->getOptions()->getLegend()->getTextStyle()->setColor('#ffffff');
        $barchart->getOptions()->getHAxis()->getTextStyle()->setColor('#ffffff');
        $barchart->getOptions()->getHAxis()->getGridlines()->setColor('#1f2937');
        $barchart->getOptions()->getVAxis()->getTextStyle()->setColor('#ffffff');
        $barchart->getOptions()->getVAxis()->getGridlines()->setColor('#1f2937');
        $barchart->getOptions()->getChartArea()->setLeft(60);
        $barchart->getOptions()->getChartArea()->setTop(40);
        $barchart->getOptions()->getChartArea()->setWidth('80%');
        $barchart->getOptions()->getChartArea()->setHeight('70%');
        $barchart->getOptions()->getChartArea()->setBackgroundColor('transparent');
        $barchart->getOptions()->getAnimation()->setStartup(true);
        $barchart->getOptions()->getAnimation()->setDuration(800);
        $barchart->getOptions()->getAnimation()->setEasing('out');

        // Line chart: daily interaction trend over the last 7 days.
        $linechart = new LineChart();
        $linechart->getData()->setArrayToDataTable($this->analyticsService->getUserActivityTrend());
        $linechart->getOptions()->setTitle('Tendance d\'activité (7 derniers jours)');
        $linechart->getOptions()->setHeight(320);
        $linechart->getOptions()->setBackgroundColor('transparent');
        $linechart->getOptions()->setColors(['#1aff9c']);
        $linechart->getOptions()->setFontName('Inter, Arial, sans-serif');
        $linechart->getOptions()->getTitleTextStyle()->setColor('#ffffff');
        $linechart->getOptions()->getLegend()->getTextStyle()->setColor('#ffffff');
        $linechart->getOptions()->getHAxis()->getTextStyle()->setColor('#ffffff');
        $linechart->getOptions()->getHAxis()->getGridlines()->setColor('#1f2937');
        $linechart->getOptions()->getVAxis()->getTextStyle()->setColor('#ffffff');
        $linechart->getOptions()->getVAxis()->getGridlines()->setColor('#1f2937');
        $linechart->getOptions()->getChartArea()->setLeft(60);
        $linechart->getOptions()->getChartArea()->setTop(40);
        $linechart->getOptions()->getChartArea()->setWidth('80%');
        $linechart->getOptions()->getChartArea()->setHeight('70%');
        $linechart->getOptions()->getChartArea()->setBackgroundColor('transparent');
        $linechart->getOptions()->getAnimation()->setStartup(true);
        $linechart->getOptions()->getAnimation()->setDuration(800);
        $linechart->getOptions()->getAnimation()->setEasing('out');

        return $this->render('admin/quiz-questions.html.twig', [
            'questions' => $questions,
            'reponses' => $reponses,
            'search' => $search,
            'sort' => $sort,
            'piechart' => $piechart,
            'barchart' => $barchart,
            'linechart' => $linechart,
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
        }

        return $this->redirectToRoute('admin_quiz_questions', $errors ? ['errors' => $errors] : []);
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
        }

        return $this->redirectToRoute('admin_quiz_questions', $errors ? ['errors' => $errors] : []);
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
