## 📋 Checklist - Partie Shop

### 1. BOUTIQUE (Shop)

#### ✅ Catalogue Produits
- URL: `http://localhost:8000/shop`
- Vérifications:
  - [ ] Liste de produits avec:
    - Image/icône
    - Nom du produit
    - Prix en EUR
    - Description courte
    - Bouton "Ajouter au panier"
  - [ ] Indicateur du panier dans l'en-tête (nombre d'articles)
  - [ ] Chaque produit cliquable pour détails

#### ✅ Fiche Produit
- URL: `http://localhost:8000/shop/produit/{id}`
- Vérifications:
  - [ ] Détails du produit affichés
  - [ ] Prix et description visibles
  - [ ] Bouton "Ajouter au panier"

### 2. PANIER

#### ✅ Panier (Cart)
- URL: `http://localhost:8000/shop/cart`
- Vérifications:
  - [ ] Afficher les articles ajoutés
  - [ ] Quantité modifiable par article
  - [ ] Bouton supprimer par article
  - [ ] Sous-total, TVA, total affichés
  - [ ] Bouton "Procéder au paiement"
  - [ ] Le panier persiste en session

### 3. COMMANDES

#### ✅ Créer une Commande
- URL: `http://localhost:8000/shop/commandes` (après ajout au panier)
- Vérifications:
  - [ ] Formulaire de commande:
    - Nom acheteur
    - Email
    - Adresse (si formulaire complet)
  - [ ] Calcul automatique du total
  - [ ] Référence de commande générée (unique)
  - [ ] Statut initial: "en_attente"
  - [ ] Sauvegarde en base de données

#### ✅ Consulter les Commandes
- URL: `http://localhost:8000/shop/commandes`
- Vérifications:
  - [ ] Liste de toutes les commandes
  - [ ] Colonnes: ID, Référence, Acheteur, Produit, Quantité, Total, Statut, Date
  - [ ] Chaque commande cliquable pour voir détails
  - [ ] Boutons d'édition/suppression

### 4. ADMIN - BOUTIQUE

#### ✅ Gestion des Produits
- URL: `http://localhost:8000/admin/produits`
- Vérifications:
  - [ ] Liste de tous les produits
  - [ ] Éditer: nom, prix, stock, description
  - [ ] Supprimer un produit
  - [ ] Créer un nouveau produit
  - [ ] Champs: nom_produit, prix, quantite_disponible, type_produit

#### ✅ Gestion des Commandes
- URL: `http://localhost:8000/admin/commandes`
- Vérifications:
  - [ ] Tableau de toutes les commandes
  - [ ] Voir les détails: produit, quantité, acheteur, prix, date
  - [ ] Modifier le statut: en_attente → confirmée → expédiée → livrée
  - [ ] Supprimer une commande
  - [ ] Filtrer par statut

#### ✅ Analytique Boutique
- URL: `http://localhost:8000/admin/analytics`
- Vérifications:
  - [ ] Cartes KPI (ventes 30j, commandes 30j, croissance, panier moyen)
  - [ ] Graphique ventes 7 jours avec prévision
  - [ ] Graphique top produits par quantités vendues
  - [ ] Tableau performance produits (demande, marge, risque)
  - [ ] Segments clients (VIP, réguliers, occasionnels)

### 5. AUTOMATISATION (UI SEULEMENT)

#### ⚠️ Workflows (UI présente, backend non implémenté)
- URL: `http://localhost:8000/admin/analytics`
- Vérifications:
  - [ ] Cartes: Auto-Approvisionnement, Email Clients, Alertes Stock, Publication Produits
  - [ ] Affichage des cartes OK (pas d'actions automatiques)

**Mise à jour**: 19 Février 2026
MAILER_DSN=smtp://salahchebil123@gmail.com:snss pzuu wofm lacwMAILER_DSN=smtp://salahchebil123@gmail.com:APP_PASSWORD@smtp.gmail.com:587MAILER_DSN=smtp://