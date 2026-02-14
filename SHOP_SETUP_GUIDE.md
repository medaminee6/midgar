# Fantasy Store - Guide de Configuration et Utilisation

## Vue d'ensemble

Vous avez maintenant un système complet de boutique en ligne (shop) intégré à votre plateforme Midgar avec :

- **2 Entités Doctrine** : Produit et Commande
- **CRUD complet** : Créer, Lire, Mettre à jour, Supprimer les produits et commandes
- **Panier de session** : Gestion du panier sans entité base de données
- **Système de commandes** : Créer et gérer les commandes
- **Interfaces Twig** : Templates modernes et intuitives

## 📋 Architecture

### Entités

#### Produit
```
- id (Integer, clé primaire)
- nom_produit (String)
- description (Text, optionnel)
- prix (Decimal)
- type_produit (String)
- quantite_disponible (Integer)
- date_ajout (DateTime)
- commandes (Relation OneToMany vers Commande)
```

#### Commande
```
- id (Integer, clé primaire)
- quantite (Integer)
- date_commande (DateTime)
- etat (String: en_attente, confirmée, expédiée, livrée, annulée)
- acheteur (String)
- prix_total (Decimal)
- reference_commande (String, unique)
- produit (Relation ManyToOne vers Produit)
```

### Services

**CartService** - Gestion du panier basée sur les sessions (sans base de données)
- `addItem(productId, quantity)` - Ajouter au panier
- `removeItem(productId)` - Retirer du panier
- `updateQuantity(productId, quantity)` - Mettre à jour la quantité
- `getCart()` - Obtenir tous les articles du panier
- `getCartCount()` - Obtenir le nombre total d'articles
- `clearCart()` - Vider le panier
- `isEmpty()` - Vérifier si le panier est vide

## 🔧 Configuration

### 1. Configurer la base de données MySQL

Modifiez le fichier `.env` avec vos identifiants MySQL :

```env
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/fantasy_store?serverVersion=8.0&charset=utf8mb4"
```

Remplacez :
- `utilisateur` : votre utilisateur MySQL
- `motdepasse` : votre mot de passe MySQL
- `127.0.0.1` : l'adresse de votre serveur MySQL
- `3306` : le port MySQL (3306 par défaut)

### 2. Créer la base de données

```bash
php bin/console doctrine:database:create --if-not-exists
```

### 3. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

Cela créera les tables `produit` et `commande` dans votre base de données.

## 🛍️ Routes disponibles

### Boutique (Shop)
- `GET /shop` - Liste des produits
- `GET /shop/produit/{id}` - Détails d'un produit
- `GET/POST /shop/produit/create` - Créer un produit
- `GET/POST /shop/produit/{id}/edit` - Modifier un produit
- `POST /shop/produit/{id}/delete` - Supprimer un produit

### Panier
- `GET /shop/cart` - Voir le panier
- `POST /shop/cart/add/{id}` - Ajouter au panier
- `POST /shop/cart/remove/{id}` - Retirer du panier
- `POST /shop/cart/update/{id}` - Mettre à jour la quantité

### Commandes
- `GET /shop/commandes` - Liste des commandes
- `GET /shop/commande/{id}` - Détails d'une commande
- `GET/POST /shop/commandes` - Passer une commande
- `GET/POST /shop/commande/{id}/edit` - Modifier une commande
- `POST /shop/commande/{id}/delete` - Supprimer une commande

## 📊 Flux d'utilisation

### Exemple de flux d'achat

1. **Consulter la boutique**
   ```
   GET /shop
   ```
   Affiche tous les produits disponibles

2. **Ajouter au panier**
   ```
   POST /shop/cart/add/3
   ```
   Ajoute le produit avec l'ID 3 au panier (basé sur les sessions)

3. **Consulter le panier**
   ```
   GET /shop/cart
   ```
   Affiche les articles du panier avec le total

4. **Passer la commande**
   ```
   GET /shop/checkout
   POST /shop/checkout
   ```
   Affiche un formulaire et crée une commande dans la base de données

5. **Consulter les commandes**
   ```
   GET /shop/commandes
   ```
   Affiche toutes les commandes créées

## 💾 Panier sans entité

Le panier est stocké **uniquement en session** (pas de table base de données). Cela signifie :

- ✅ Les articles restent dans le panier pendant la session utilisateur
- ✅ Le panier se vide à la fermeture du navigateur ou après l'expiration de session
- ✅ Pas de besoin d'authentification pour le panier
- ✅ Solutions simple et efficace

Les **commandes** elles-mêmes sont persistées en base de données dans la table `commande`.

## 📁 Structure des fichiers créés

```
src/
├── Entity/
│   ├── Produit.php
│   └── Commande.php
├── Repository/
│   ├── ProduitRepository.php
│   └── CommandeRepository.php
├── Service/
│   └── CartService.php
└── Controller/
    └── ShopController.php

templates/
├── base.html.twig
└── shop/
    ├── index.html.twig
    ├── detail.html.twig
    ├── cart.html.twig
    ├── checkout.html.twig
    ├── produit/
    │   ├── create.html.twig
    │   └── edit.html.twig
    └── commandes/
        ├── list.html.twig
        ├── detail.html.twig
        └── edit.html.twig

migrations/
└── Version20260207142039.php
```

## 🔍 Exemples de code

### Utiliser le CartService dans un contrôleur

```php
use App\Service\CartService;

class MyController extends AbstractController
{
    public function myMethod(CartService $cartService)
    {
        // Ajouter un article
        $cartService->addItem(1, 2); // Produit ID 1, quantité 2
        
        // Obtenir le contenu du panier
        $cart = $cartService->getCart();
        
        // Obtenir le nombre d'articles
        $count = $cartService->getCartCount();
        
        // Supprimer un article
        $cartService->removeItem(1);
        
        // Vider le panier
        $cartService->clearCart();
    }
}
```

### Utiliser les repositories

```php
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;

class MyController extends AbstractController
{
    public function myMethod(ProduitRepository $produitRepo, CommandeRepository $commandeRepo)
    {
        // Obtenir tous les produits
        $produits = $produitRepo->findAll();
        
        // Obtenir les produits d'un certain type
        $livres = $produitRepo->findByType('Livre');
        
        // Obtenir toutes les commandes
        $commandes = $commandeRepo->findAll();
        
        // Obtenir les commandes d'un acheteur
        $myOrders = $commandeRepo->findByAcheteur('John Doe');
        
        // Obtenir les commandes par état
        $pending = $commandeRepo->findByEtat('en_attente');
    }
}
```

## 🎯 Cas d'usage

### Ajouter des produits manuellement

1. Allez sur `/shop/produit/create`
2. Remplissez le formulaire :
   - Nom du produit
   - Description
   - Prix
   - Type de produit
   - Quantité disponible
3. Cliquez sur "Créer le produit"

### Ajouter des produits par base de données

```sql
INSERT INTO produit (nom_produit, description, prix, type_produit, quantite_disponible, date_ajout)
VALUES 
('Épée Légendaire', 'Une magnifique épée forgée par les anciens', 49.99, 'Artefact', 5, NOW()),
('Carte du Monde Perdu', 'Une carte mystérieuse', 19.99, 'Oeuvre', 10, NOW()),
('Collection Fantasy Vol.1', 'Premier tome de la collection', 39.99, 'Collection', 15, NOW());
```

### Gérer les états de commande

Les états disponibles sont :
- `en_attente` - Commande reçue, en attente de traitement
- `confirmée` - Commande confirmée
- `expédiée` - Commande expédiée
- `livrée` - Commande livrée
- `annulée` - Commande annulée

Vous pouvez modifier l'état via `/shop/commande/{id}/edit`

## ⚠️ Notes importantes

1. **Sécurité** : Le système actuel n'a pas d'authentification. Pour la production, ajoutez une authentification utilisateur.

2. **Validation** : Ajoutez une validation plus robuste des formulaires pour la production.

3. **Paiement** : Intégrez un système de paiement (Stripe, PayPal, etc.) pour le checkout.

4. **Stock** : Le stock n'est pas automatiquement déduit. À implémenter selon vos besoins.

5. **Notifications** : Vous pouvez ajouter des emails de confirmation de commande.

## 📝 Prochaines étapes recommandées

1. Tester les différentes routes
2. Ajouter quelques produits de test
3. Passer une commande test
4. Vérifier les données en base de données
5. Personnaliser les templates selon votre design
6. Ajouter l'authentification utilisateur
7. Intégrer un système de paiement

## 🆘 Dépannage

### Erreur de connexion à la base de données

Vérifiez que :
- MySQL est lancé
- Les identifiants dans `.env` sont corrects
- La base de données existe (ou utilisez `doctrine:database:create`)

### Migration échouée

Réexécutez :
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Panier vide après refresh

C'est normal ! Les sessions PHP expirent. C'est le comportement voulu pour un panier sans persistance.

## 📞 Support

Pour toute question ou problème, consultez :
- Documentation Symfony : https://symfony.com/doc
- Documentation Doctrine : https://www.doctrine-project.org
- Code source du projet : Voir `/src/Entity`, `/src/Service`, `/src/Controller`
