<?php

namespace App\Controller;

use App\Entity\Defi;
use App\Entity\Participation;
use App\Enum\StatutParticipation;
use App\Form\DefiType;
use App\Form\ParticiperType;
use App\Form\ParticipationStatutType;
use App\Repository\DefiRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefiController extends AbstractController
{
    // PUBLIC ROUTES
    #[Route('/challenges', name: 'challenges_index', methods: ['GET'])]
    public function challenges(Request $request, DefiRepository $defiRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'recent');
        $defis = $defiRepository->searchDefis($search, $sortBy);

        return $this->render('challenges.html.twig', [
            'defis' => $defis,
            'search' => $search,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/challenges/{id}/participer', name: 'challenges_participate', methods: ['GET', 'POST'])]
    public function participer(Request $request, Defi $defi, EntityManagerInterface $entityManager): Response
    {
        if ($defi->getStatut()->value !== 'OUVERT') {
            $this->addFlash('error', 'Ce défi n\'est plus ouvert aux participations.');
            return $this->redirectToRoute('challenges_index');
        }

        $form = $this->createForm(ParticiperType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $artworkId = isset($data['artworkId']) && $data['artworkId'] > 0 ? (int) $data['artworkId'] : null;

            if (!$artworkId) {
                $this->addFlash('error', 'Veuillez indiquer l\'ID d\'une œuvre existante ou utilisez l\'éditeur de peinture.');
                return $this->render('challenges/participer.html.twig', [
                    'defi' => $defi,
                    'form' => $form->createView(),
                ]);
            }

            $participation = new Participation();
            $participation->setDefi($defi);
            $participation->setDescription($data['description']);
            $participation->setArtworkId($artworkId);
            $participation->setDateSoumission(new \DateTime());
            $participation->setStatut(StatutParticipation::EN_ATTENTE);
            $participation->setUserId(1); // TODO: remplacer par l'utilisateur connecté

            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre participation a bien été enregistrée. Merci !');
            return $this->redirectToRoute('challenges_index');
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
        $uploadsDir = $projectDir . '/public/uploads/defis';
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

    // ADMIN ROUTES
    #[Route('/admin/challenges', name: 'admin_challenges', methods: ['GET', 'POST'])]
    public function adminChallenges(Request $request, DefiRepository $defiRepository, EntityManagerInterface $entityManager): Response
    {
        $defi = new Defi();
        $form = $this->createDefiForm($defi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $defi->setCreateurId(1);
                $entityManager->persist($defi);
                $entityManager->flush();
                $this->addFlash('success', 'Défi créé avec succès');
                return $this->redirectToRoute('admin_challenges');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        $defis = $defiRepository->findAll();

        return $this->render('admin/challenges.html.twig', [
            'defis' => $defis,
            'form' => $form->createView(),
            'defi' => null,
        ]);
    }

    // DEFI OPERATIONS
    #[Route('/defis/{id}/edit', name: 'defi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Defi $defi, DefiRepository $defiRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createDefiForm($defi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Défi modifié avec succès');
            return $this->redirectToRoute('admin_challenges');
        }

        $defis = $defiRepository->findAll();

        return $this->render('admin/challenges.html.twig', [
            'form' => $form->createView(),
            'defi' => $defi,
            'defis' => $defis,
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

        return $this->render('admin/participation_edit_statut.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
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
}
