<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Commande;
use App\Enum\CommandeEtat;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/shop')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'shop_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepo): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $sortBy = $request->query->get('sort', 'date');

        // Get filtered products based on search/type/sort
        $produits = $produitRepo->searchProduits(
            $search ?: null,
            $type ?: null,
            $sortBy
        );

        // Get all product types for the filter dropdown
        $types = $produitRepo->getProductTypes();
        $typeList = array_map(fn($row) => $row['type_produit'], $types);

        return $this->render('shop/index.html.twig', [
            'produits' => $produits,
            'types' => $typeList,
            'search' => $search,
            'selectedType' => $type,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/produit/create', name: 'shop_produit_create', methods: ['GET', 'POST'])]
    public function createProduit(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $produit = new Produit();
            $produit->setNomProduit((string)$request->request->get('nom_produit', ''));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((string)$request->request->get('prix', ''));
            $produit->setTypeProduit((string)$request->request->get('type_produit', ''));
            $produit->setQuantiteDisponible((int)$request->request->get('quantite_disponible', 0));

            $errors = $validator->validate($produit);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('shop/produit/create.html.twig', [
                    'produit' => $produit,
                ]);
            }

            $em->persist($produit);
            $em->flush();

            return $this->redirectToRoute('shop_index');
        }

        return $this->render('shop/produit/create.html.twig');
    }

    #[Route('/produit/{id}/edit', name: 'shop_produit_edit', methods: ['GET', 'POST'])]
    public function editProduit(Produit $produit, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $produit->setNomProduit((string)$request->request->get('nom_produit', ''));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((string)$request->request->get('prix', ''));
            $produit->setTypeProduit((string)$request->request->get('type_produit', ''));
            $produit->setQuantiteDisponible((int)$request->request->get('quantite_disponible', 0));

            $errors = $validator->validate($produit);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('shop/produit/edit.html.twig', [
                    'produit' => $produit,
                ]);
            }

            $em->flush();

            return $this->redirectToRoute('shop_index');
        }

        return $this->render('shop/produit/edit.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/produit/{id}/delete', name: 'shop_produit_delete', methods: ['POST'])]
    public function deleteProduit(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();

        return $this->redirectToRoute('shop_index');
    }

    #[Route('/produit/{id}', name: 'shop_detail', methods: ['GET'])]
    public function detail(Produit $produit): Response
    {
        return $this->render('shop/detail.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/cart', name: 'shop_cart', methods: ['GET'])]
    public function cart(CartService $cartService, ProduitRepository $produitRepo): Response
    {
        $cartItems = $cartService->getCart();
        $productIds = $cartService->getProductIds();
        $produits = [];

        if (!empty($productIds)) {
            $produits = $produitRepo->findBy(['id' => $productIds]);
        }

        // Build cart data with product info
        $cartData = [];
        foreach ($produits as $produit) {
            if (isset($cartItems[$produit->getId()])) {
                $cartData[] = [
                    'produit' => $produit,
                    'quantity' => $cartItems[$produit->getId()]['quantity'],
                    'subtotal' => bcmul($produit->getPrix(), $cartItems[$produit->getId()]['quantity'], 2),
                ];
            }
        }

        // Calculate total
        $total = 0;
        foreach ($cartData as $item) {
            $total += $item['subtotal'];
        }

        return $this->render('shop/cart.html.twig', [
            'cartData' => $cartData,
            'total' => $total,
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'shop_cart_add', methods: ['POST'])]
    public function addToCart(Produit $produit, Request $request, CartService $cartService): Response
    {
        $quantity = (int)$request->request->get('quantity', 1);

        if ($quantity > 0 && $quantity <= $produit->getQuantiteDisponible()) {
            $cartService->addItem($produit->getId(), $quantity);
        }

        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/cart/remove/{id}', name: 'shop_cart_remove', methods: ['POST'])]
    public function removeFromCart(Produit $produit, CartService $cartService): Response
    {
        $cartService->removeItem($produit->getId());

        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/cart/update/{id}', name: 'shop_cart_update', methods: ['POST'])]
    public function updateCart(Produit $produit, Request $request, CartService $cartService): Response
    {
        $quantity = (int)$request->request->get('quantity', 0);
        $cartService->updateQuantity($produit->getId(), $quantity);

        return $this->redirectToRoute('shop_cart');
    }

    #[Route('/commandes', name: 'shop_commandes', methods: ['GET'])]
    public function listCommandes(CommandeRepository $commandeRepo): Response
    {
        $commandes = $commandeRepo->findAll();

        return $this->render('shop/commandes/list.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/admin/produits/create', name: 'admin_produits_create', methods: ['POST'])]
    public function adminCreateProduit(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $produit = new Produit();
        $produit->setNomProduit((string)$request->request->get('nom_produit', ''));
        $produit->setDescription($request->request->get('description'));
        $produit->setPrix((string)$request->request->get('prix', ''));
        $produit->setTypeProduit((string)$request->request->get('type_produit', ''));
        $produit->setQuantiteDisponible((int)$request->request->get('quantite_disponible', 0));

        $errors = $validator->validate($produit);
        if ($errors->count() > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        } else {
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit créé avec succès');
        }

        return $this->redirectToRoute('admin_produits');
    }

    #[Route('/admin/produits/{id}/edit', name: 'admin_produits_edit', methods: ['GET', 'POST'])]
    public function adminEditProduit(Produit $produit, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $produit->setNomProduit((string)$request->request->get('nom_produit', ''));
            $produit->setDescription($request->request->get('description'));
            $produit->setPrix((string)$request->request->get('prix', ''));
            $produit->setTypeProduit((string)$request->request->get('type_produit', ''));
            $produit->setQuantiteDisponible((int)$request->request->get('quantite_disponible', 0));

            $errors = $validator->validate($produit);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('admin/produits-edit.html.twig', [
                    'produit' => $produit,
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès');
            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/produits-edit.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/admin/produits/{id}/delete', name: 'admin_produits_delete', methods: ['POST'])]
    public function adminDeleteProduit(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé avec succès');

        return $this->redirectToRoute('admin_produits');
    }

    #[Route('/checkout', name: 'shop_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, CartService $cartService, ProduitRepository $produitRepo, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($cartService->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('shop_cart');
        }

        if ($request->isMethod('POST')) {
            $acheteur = trim((string)$request->request->get('acheteur', ''));
            $cartItems = $cartService->getCart();
            $productIds = $cartService->getProductIds();
            $produits = $produitRepo->findBy(['id' => $productIds]);

            $totalPrice = 0;
            $commandes = [];
            $hasValidationError = false;

            foreach ($produits as $produit) {
                if (isset($cartItems[$produit->getId()])) {
                    $quantity = $cartItems[$produit->getId()]['quantity'];
                    $subtotal = bcmul($produit->getPrix(), (string)$quantity, 2);
                    $totalPrice += (float)$subtotal;

                    $commande = new Commande();
                    $commande->setAcheteur($acheteur);
                    $commande->setQuantite($quantity);
                    $commande->setPrixTotal($subtotal);
                    $commande->setProduit($produit);
                    $commande->setEtat(CommandeEtat::EN_ATTENTE);

                    $errors = $validator->validate($commande);
                    if ($errors->count() > 0) {
                        foreach ($errors as $error) {
                            $this->addFlash('error', $error->getMessage());
                        }
                        $hasValidationError = true;
                        break;
                    }

                    $em->persist($commande);
                    $commandes[] = $commande;
                }
            }

            if (!$hasValidationError) {
                $em->flush();
                $cartService->clearCart();
                $this->addFlash('success', 'Commande créée avec succès! Références: ' . implode(', ', array_map(fn($c) => $c->getReferenceCommande(), $commandes)));
                return $this->redirectToRoute('shop_commandes');
            }
        }

        // Get cart data for display
        $cartItems = $cartService->getCart();
        $productIds = $cartService->getProductIds();
        $produits = [];

        if (!empty($productIds)) {
            $produits = $produitRepo->findBy(['id' => $productIds]);
        }

        $cartData = [];
        $total = 0;
        foreach ($produits as $produit) {
            if (isset($cartItems[$produit->getId()])) {
                $quantity = $cartItems[$produit->getId()]['quantity'];
                $subtotal = bcmul($produit->getPrix(), $quantity, 2);
                $cartData[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }
        }

        return $this->render('shop/checkout.html.twig', [
            'cartData' => $cartData,
            'total' => $total,
        ]);
    }

    #[Route('/commande/{id}/edit', name: 'shop_commande_edit', methods: ['GET', 'POST'])]
    public function editCommande(Commande $commande, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $etatValue = $request->request->get('etat');
            $commande->setAcheteur((string)$request->request->get('acheteur', ''));
            if ($etatValue !== null && $etatValue !== '') {
                $etat = CommandeEtat::from($etatValue);
                $commande->setEtat($etat);
            }

            $errors = $validator->validate($commande);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('shop/commandes/edit.html.twig', [
                    'commande' => $commande,
                    'etats' => CommandeEtat::cases(),
                ]);
            }

            $em->flush();

            return $this->redirectToRoute('shop_commandes');
        }

        return $this->render('shop/commandes/edit.html.twig', [
            'commande' => $commande,
            'etats' => CommandeEtat::cases(),
        ]);
    }

    #[Route('/commande/{id}/delete', name: 'shop_commande_delete', methods: ['POST'])]
    public function deleteCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $em->remove($commande);
        $em->flush();

        return $this->redirectToRoute('shop_commandes');
    }

    #[Route('/commande/{id}', name: 'shop_commande_detail', methods: ['GET'])]
    public function detailCommande(Commande $commande): Response
    {
        return $this->render('shop/commandes/detail.html.twig', [
            'commande' => $commande,
        ]);
    }
}
