<?php

namespace App\Controller;

use App\Entity\Favoris;
use App\Entity\Oeuvre;
use App\Entity\Artefact;
use App\Entity\User;
use App\Repository\FavorisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/favoris')]
class FavorisController extends AbstractController
{

    #[Route('/oeuvre/{id}/toggle', name: 'favoris_oeuvre_toggle', methods: ['POST'])]
    public function toggleOeuvreFavori(Oeuvre $oeuvre, EntityManagerInterface $em, FavorisRepository $favorisRepo): JsonResponse
    {
        // Check if user is logged in
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        
        // Check if already favorited
        $existing = $favorisRepo->findByUserAndOeuvre($userId, $oeuvre->getId());
        
        if ($existing) {
            // Remove from favorites
            $em->remove($existing);
            $em->flush();
            
            $count = $favorisRepo->countOeuvreLikes($oeuvre->getId());
            
            return new JsonResponse([
                'success' => true,
                'favorited' => false,
                'count' => $count
            ]);
        } else {
            // Add to favorites
            $favori = new Favoris();
            $favori->setUserId($userId);
            $favori->setOeuvreId($oeuvre->getId());
            $em->persist($favori);
            $em->flush();
            
            $count = $favorisRepo->countOeuvreLikes($oeuvre->getId());
            
            return new JsonResponse([
                'success' => true,
                'favorited' => true,
                'count' => $count
            ]);
        }
    }

    #[Route('/artefact/{id}/toggle', name: 'favoris_artefact_toggle', methods: ['POST'])]
    public function toggleArtefactFavori(Artefact $artefact, EntityManagerInterface $em, FavorisRepository $favorisRepo): JsonResponse
    {
        // Check if user is logged in
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        
        // Check if already favorited
        $existing = $favorisRepo->findByUserAndArtefact($userId, $artefact->getId());
        
        if ($existing) {
            // Remove from favorites
            $em->remove($existing);
            $em->flush();
            
            $count = $favorisRepo->countArtefactLikes($artefact->getId());
            
            return new JsonResponse([
                'success' => true,
                'favorited' => false,
                'count' => $count
            ]);
        } else {
            // Add to favorites
            $favori = new Favoris();
            $favori->setUserId($userId);
            $favori->setArtefactId($artefact->getId());
            $em->persist($favori);
            $em->flush();
            
            $count = $favorisRepo->countArtefactLikes($artefact->getId());
            
            return new JsonResponse([
                'success' => true,
                'favorited' => true,
                'count' => $count
            ]);
        }
    }

    #[Route('/check/oeuvre/{id}', name: 'favoris_check_oeuvre', methods: ['GET'])]
    public function checkOeuvreFavori(Oeuvre $oeuvre, FavorisRepository $favorisRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'favorited' => false,
                'count' => $favorisRepo->countOeuvreLikes($oeuvre->getId())
            ]);
        }

        $existing = $favorisRepo->findByUserAndOeuvre($user->getId(), $oeuvre->getId());
        $count = $favorisRepo->countOeuvreLikes($oeuvre->getId());
        
        return new JsonResponse([
            'favorited' => $existing !== null,
            'count' => $count
        ]);
    }

    #[Route('/check/artefact/{id}', name: 'favoris_check_artefact', methods: ['GET'])]
    public function checkArtefactFavori(Artefact $artefact, FavorisRepository $favorisRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'favorited' => false,
                'count' => $favorisRepo->countArtefactLikes($artefact->getId())
            ]);
        }

        $existing = $favorisRepo->findByUserAndArtefact($user->getId(), $artefact->getId());
        $count = $favorisRepo->countArtefactLikes($artefact->getId());
        
        return new JsonResponse([
            'favorited' => $existing !== null,
            'count' => $count
        ]);
    }

    #[Route('/check/oeuvres', name: 'favoris_check_all_oeuvres', methods: ['GET'])]
    public function checkAllOeuvreFavoris(FavorisRepository $favorisRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['favoritedIds' => []]);
        }

        $favoris = $favorisRepo->findFavoriOeuvresByUser($user->getId());
        $ids = array_map(function($f) { return $f->getOeuvreId(); }, $favoris);
        
        return new JsonResponse(['favoritedIds' => $ids]);
    }

    #[Route('/check/artefacts', name: 'favoris_check_all_artefacts', methods: ['GET'])]
    public function checkAllArtefactFavoris(FavorisRepository $favorisRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['favoritedIds' => []]);
        }

        $favoris = $favorisRepo->findFavoriArtefactsByUser($user->getId());
        $ids = array_map(function($f) { return $f->getArtefactId(); }, $favoris);
        
        return new JsonResponse(['favoritedIds' => $ids]);
    }
}
