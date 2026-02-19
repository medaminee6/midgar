<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Enum\CommandeEtat;
use App\Repository\CommandeRepository;
use App\Repository\ShopAutomationEventRepository;
use App\Repository\StockPredictionRepository;
use App\Service\ShopAnalyticsService;
use App\Service\StockPredictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminShopController extends AbstractController
{
    #[Route('/commandes', name: 'admin_commandes', methods: ['GET'])]
    public function list(CommandeRepository $commandeRepository): Response
    {
        return $this->render('admin/commandes.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }

    #[Route('/commandes/{id}', name: 'admin_commande_detail', methods: ['GET'])]
    public function detail(Commande $commande): Response
    {
        return $this->render('admin/commande_detail.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commandes/{id}/edit', name: 'admin_commande_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commande $commande,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('POST')) {
            $commande->setAcheteur((string) $request->request->get('acheteur', ''));
            $etatValue = (string) $request->request->get('etat', '');
            if ($etatValue !== '') {
                $commande->setEtat(CommandeEtat::from($etatValue));
            }

            $errors = $validator->validate($commande);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }

                return $this->render('admin/commande_edit.html.twig', [
                    'commande' => $commande,
                    'etats' => CommandeEtat::cases(),
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Commande modifiee avec succes.');

            return $this->redirectToRoute('admin_commandes');
        }

        return $this->render('admin/commande_edit.html.twig', [
            'commande' => $commande,
            'etats' => CommandeEtat::cases(),
        ]);
    }

    #[Route('/commandes/{id}/delete', name: 'admin_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_commande_' . $commande->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_commandes');
        }

        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande supprimee avec succes.');

        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/analytics', name: 'admin_shop_analytics', methods: ['GET'])]
    public function analytics(
        ShopAnalyticsService $analyticsService,
        ShopAutomationEventRepository $eventRepository
    ): Response
    {
        $kpis = $analyticsService->getKPIs();
        $segments = $analyticsService->getCustomerSegments();
        $dailySales = $analyticsService->getDailySalesLast7Days();
        $topProducts = $analyticsService->getTopProducts();
        $performance = $analyticsService->getProductPerformance();
        $automationEvents = $eventRepository->findBy([], ['created_at' => 'DESC'], 10);
        
        return $this->render('admin/shop_analytics.html.twig', [
            'kpis' => $kpis,
            'segments' => $segments,
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'product_performance' => $performance,
            'automation_events' => $automationEvents,
        ]);
    }

    #[Route('/automation-history', name: 'admin_shop_automation_history', methods: ['GET'])]
    public function automationHistory(ShopAutomationEventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy([], ['created_at' => 'DESC'], 100);

        return $this->render('admin/automation_history.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/stock-predictions', name: 'admin_stock_predictions', methods: ['GET', 'POST'])]
    public function stockPredictions(
        StockPredictionRepository $predictionRepository,
        StockPredictionService $predictionService,
        Request $request
    ): Response
    {
        if ($request->isMethod('POST')) {
            $predictionService->predictAllProducts();
            $this->addFlash('success', 'Stock predictions updated successfully');
            return $this->redirectToRoute('admin_stock_predictions');
        }

        $predictions = $predictionRepository->findLatestPredictions(100);
        $critical = $predictionRepository->findCriticalProducts(30);

        return $this->render('admin/stock_predictions.html.twig', [
            'predictions' => $predictions,
            'critical_predictions' => $critical,
        ]);
    }
}
