<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Form\ParticipationEditType;
use App\Form\ParticipationType;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participations', name: 'participation_')]
class ParticipationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ParticipationRepository $participationRepository): Response
    {
        $participations = $participationRepository->findAll();

        return $this->render('participations/index.html.twig', [
            'participations' => $participations,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participation = new Participation();
        $form = $this->createParticipationForm($participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Participation créée avec succès');
            return $this->redirectToRoute('participation_show', ['id' => $participation->getId()]);
        }

        return $this->render('participations/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Participation $participation): Response
    {
        return $this->render('participations/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationEditType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Participation modifiée avec succès');
            return $this->redirectToRoute('participation_index');
        }

        return $this->render('participations/edit.html.twig', [
            'form' => $form->createView(),
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Participation supprimée avec succès');
        }

        return $this->redirectToRoute('participation_index');
    }

    private function createParticipationForm(Participation $participation)
    {
        return $this->createForm(ParticipationType::class, $participation);
    }
}
