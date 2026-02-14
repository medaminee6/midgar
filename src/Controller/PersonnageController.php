<?php
namespace App\Controller;

use App\Entity\Personnage;
use App\Form\PersonnageType;
use App\Repository\PersonnageRepository;
use App\Repository\UniverseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/personnages')]
class PersonnageController extends AbstractController
{

    #[Route('', name: 'personnage_index', methods: ['GET'])]
    public function index(Request $request, PersonnageRepository $repo, UniverseRepository $universeRepo): Response
    {
        // Get all universes for the filter dropdown
        $universes = $universeRepo->findAll();
        
        // Handle array parameters - convert to proper format
        $classRole = $request->query->all('classRole');
        $universe = $request->query->all('universe');
        
        // Get single value for sort and search
        $sort = $request->query->get('sort');
        $search = $request->query->get('search');
        
        $criteria = [
            'universe' => !empty($universe) ? $universe : null,
            'classRole' => !empty($classRole) ? $classRole : null,
            'search' => $search,
            'sort' => $sort,
        ];

        $personnages = $repo->findFiltered($criteria);

        return $this->render('personnages.html.twig', [
            'personnages' => $personnages,
            'universes' => $universes,
            'selectedClassRoles' => $classRole,
            'selectedUniverses' => $universe,
        ]);
    }

    #[Route('/create-personnage', name: 'personnage_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $personnage = new Personnage();
        $form = $this->createForm(PersonnageType::class, $personnage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('portraitFile')->getData();
            if ($file) {
                $data = file_get_contents($file->getPathname());
                $personnage->setPortraitImage($data);
            }

            $em->persist($personnage);
            $em->flush();

            $this->addFlash('success', 'Personnage créé.');
            return $this->redirectToRoute('personnage_index');
        }

        return $this->render('create-personnage.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'personnage_edit', methods: ['GET','POST'])]
    public function edit(Personnage $personnage, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PersonnageType::class, $personnage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('portraitFile')->getData();
            if ($file) {
                $data = file_get_contents($file->getPathname());
                $personnage->setPortraitImage($data);
            }

            $em->flush();
            $this->addFlash('success', 'Personnage mis à jour.');
            return $this->redirectToRoute('personnage_index');
        }

        return $this->render('create-personnage.html.twig', ['form' => $form->createView(), 'personnage' => $personnage]);
    }

    #[Route('/{id}', name: 'personnage_show', methods: ['GET'])]
    public function show(Personnage $personnage): Response
    {
        return $this->render('personnage_detail.html.twig', ['personnage' => $personnage]);
    }

    #[Route('/{id}/delete', name: 'personnage_delete', methods: ['POST'])]
    public function delete(Personnage $personnage, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_personnage_'.$personnage->getId(), $request->request->get('_token'))) {
            $em->remove($personnage);
            $em->flush();
            $this->addFlash('success', 'Personnage supprimé.');
        }

        return $this->redirectToRoute('personnage_index');
    }
}
