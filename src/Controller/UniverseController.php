<?php
namespace App\Controller;

use App\Entity\Universe;
use App\Form\UniverseType;
use App\Repository\UniverseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/universes')]
class UniverseController extends AbstractController
{
    #[Route('', name: 'universe_index', methods: ['GET'])]
    public function index(Request $request, UniverseRepository $repo): Response
    {
        $criteria = [
            'genre' => $request->query->get('genre'),
            'search' => $request->query->get('search'),
            'sort' => $request->query->get('sort'),
        ];

        $universes = $repo->findFiltered($criteria);

        return $this->render('universes.html.twig', ['universes' => $universes]);
    }

    #[Route('/create-universe', name: 'universe_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $universe = new Universe();
        $form = $this->createForm(UniverseType::class, $universe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // handle themes string -> array
            $themesRaw = $form->get('themes')->getData();
            $themes = [];
            if ($themesRaw) {
                $themes = array_map('trim', explode(',', $themesRaw));
            }
            $universe->setThemes($themes);

            $file = $form->get('bannerFile')->getData();
            if ($file) {
                $data = file_get_contents($file->getPathname());
                $universe->setBannerImage($data);
            }

            $em->persist($universe);
            $em->flush();

            $this->addFlash('success', 'Univers créé.');

            return $this->redirectToRoute('universe_index');
        }

        return $this->render('create-universe.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'universe_edit', methods: ['GET','POST'])]
    public function edit(Universe $universe, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UniverseType::class, $universe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $themesRaw = $form->get('themes')->getData();
            $themes = [];
            if ($themesRaw) $themes = array_map('trim', explode(',', $themesRaw));
            $universe->setThemes($themes);

            $file = $form->get('bannerFile')->getData();
            if ($file) {
                $data = file_get_contents($file->getPathname());
                $universe->setBannerImage($data);
            }

            $em->flush();
            $this->addFlash('success', 'Univers mis à jour.');
            return $this->redirectToRoute('universe_index');
        }

        return $this->render('create-universe.html.twig', ['form' => $form->createView(), 'universe' => $universe]);
    }

    #[Route('/{id}', name: 'universe_show', methods: ['GET'])]
    public function show(Universe $universe): Response
    {
        return $this->render('universe_detail.html.twig', ['universe' => $universe]);
    }

    #[Route('/{id}/delete', name: 'universe_delete', methods: ['POST'])]
    public function delete(Universe $universe, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_universe_'.$universe->getId(), $request->request->get('_token'))) {
            $em->remove($universe);
            $em->flush();
            $this->addFlash('success', 'Univers supprimé.');
        }

        return $this->redirectToRoute('universe_index');
    }
}
