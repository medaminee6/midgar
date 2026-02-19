# 🤖 Guide Configuration - Prédictions de Stock (AI)

## Qu'est-ce qu'une "tâche chaque nuit"? (Cron)

**Cron** = Tâche programmée qui s'exécute automatiquement à des horaires réguliers sur le serveur.

Exemple:
- ✅ Chaque nuit à 2h du matin → calcule les prédictions
- ✅ Chaque heure → met à jour les statistiques
- ✅ Chaque semaine → génère un rapport

---

## 📋 Comment Configurer le Cron?

### Étape 1: SSH sur ton serveur

```bash
ssh user@tonserveur.com
```

### Étape 2: Ouvrir le crontab

```bash
crontab -e
```

### Étape 3: Ajouter la ligne (prédictions chaque nuit à 2h)

```cron
0 2 * * * cd /chemin/vers/midgar && php bin/console app:stock:predict >> /var/log/midgar-predict.log 2>&1
```

**Explication:**
- `0 2 * * *` = Tous les jours à 2:00 AM
- `cd /chemin/vers/midgar` = Aller dans le dossier du projet
- `php bin/console app:stock:predict` = Exécuter la commande
- `>> /var/log/midgar-predict.log 2>&1` = Sauvegarder les résultats dans un fichier log

### Étape 4: Sauvegarder (CTRL+X, puis Y, puis ENTER)

---

## 📅 Autres Exemples de Cron

| Fréquence | Syntaxe |
|-----------|---------|
| Chaque heure | `0 * * * * ...` |
| Chaque 6 heures | `0 */6 * * * ...` |
| Chaque jour à 3h | `0 3 * * * ...` |
| Chaque semaine (dimanche) | `0 0 * * 0 ...` |
| Chaque mois | `0 0 1 * * ...` |

---

## 🔍 Vérifier si ça marche

### Vérifier les logs

```bash
tail -f /var/log/midgar-predict.log
```

### Vérifier les tâches en cours

```bash
crontab -l
```

### Tester manuellement

```bash
php bin/console app:stock:predict
```

---

## 🌐 Hébergement Mutualisé (cPanel)?

Si tu utilises cPanel:
1. Aller dans **Cron Jobs**
2. Ajouter une tâche
3. Common Settings: **Daily (3:00 AM)**
4. Command: `/usr/bin/php /home/tonuser/public_html/midgar/bin/console app:stock:predict`

---

## 💾 Alternativement: Utiliser Symfony Scheduler

Pour les projets Symfony 6.4+, tu peux aussi utiliser le scheduler intégré au lieu de cron.

Modifie `config/services.yaml`:
```yaml
services:
    App\Command\StockPredictionCommand:
        tags:
            - kernel.event_subscriber
```

Et dans `config/packages/scheduler.yaml`:
```yaml
framework:
    scheduler:
        enabled: true
```

---

## 📊 Après: Vérifier les Résultats

URL: `http://localhost:8000/admin/stock-predictions`

Tu verras:
- ✅ Tous les produits avec prédictions
- 🚨 Les produits critiques (rupture < 30 jours)
- 📈 Graphiques de confiance
- 📅 Dates estimées de rupture

---

## Résumé

1. **Installer**: ✅ Fait (php-ml)
2. **Créer Command**: ✅ Fait (`app:stock:predict`)
3. **Configurer Cron**: À toi de faire!
4. **Vérifier**: Attendre le jour suivant ou tester manuellement

**Besoin d'aide pour le cron?** Dis-moi ton hébergeur (AWS, OVH, 1&1, cPanel, etc) et je ferai les ajustements.
