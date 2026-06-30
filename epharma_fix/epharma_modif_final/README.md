# 💊 E-Pharma — Pharmacie en ligne Django

Application web complète de livraison de médicaments à domicile, inspirée du design E-Pharma.

---

## 🚀 Installation rapide

### 1. Prérequis
- Python 3.10+
- pip

### 2. Installer les dépendances

```bash
pip install -r requirements.txt
```

### 3. Créer la base de données

```bash
python manage.py makemigrations accounts
python manage.py makemigrations catalogue
python manage.py makemigrations commandes
python manage.py makemigrations livraison
python manage.py migrate
```

### 4. Peupler avec des données de démo

```bash
python populate_db.py
```

### 5. Lancer le serveur

```bash
python manage.py runserver
```

### 6. Ouvrir dans le navigateur

- **App** : http://127.0.0.1:8000/
- **Admin** : http://127.0.0.1:8000/admin/

---

## 🔑 Comptes de test

| Rôle | Identifiant | Mot de passe |
|------|-------------|--------------|
| Administrateur | `admin` | `admin123` |
| Client | `client_test` | `test1234` |
| Livreur | `livreur_test` | `test1234` |

---

## 📱 Fonctionnalités

### Client
- ✅ Inscription / Connexion
- ✅ Catalogue de médicaments avec recherche
- ✅ Filtrage par catégorie
- ✅ Détail médicament (composition, posologie)
- ✅ Panier (ajout, modification, suppression)
- ✅ Commande avec adresse + mode de paiement
- ✅ Suivi des commandes (En attente → Confirmée → Préparée → En route → Livrée)
- ✅ Upload d'ordonnance
- ✅ Historique des commandes
- ✅ Profil utilisateur modifiable

### Administrateur (via /admin/)
- ✅ Gestion des médicaments (CRUD complet)
- ✅ Gestion des catégories
- ✅ Suivi et mise à jour des commandes
- ✅ Gestion des utilisateurs et livreurs
- ✅ Assignation des livraisons

---

## 🗂️ Structure du projet

```
epharma/
├── epharma/          # Configuration Django
│   ├── settings.py
│   └── urls.py
├── accounts/         # Gestion utilisateurs
│   ├── models.py     # Modèle Utilisateur personnalisé
│   ├── views.py      # Inscription, connexion, profil
│   └── templates/
├── catalogue/        # Médicaments et panier
│   ├── models.py     # Categorie, Medicament, Panier
│   ├── views.py      # Accueil, liste, détail, panier
│   └── templates/
├── commandes/        # Gestion des commandes
│   ├── models.py     # Commande, LigneCommande
│   ├── views.py      # Checkout, suivi
│   └── templates/
├── livraison/        # Suivi livraison
│   └── models.py     # Livraison avec livreur assigné
├── templates/        # Templates globaux
│   └── base.html
├── populate_db.py    # Script de données démo
└── requirements.txt
```

---

## 🎨 Design

- Couleurs : Vert E-Pharma `#1aab5f` + Bleu `#1565c0`
- Fonts : Nunito (principal) + Poppins (titres)
- Bootstrap 5 + FontAwesome 6
- Design responsive mobile-first
- Inspiré fidèlement du design original E-Pharma

---

## 📦 Modes de paiement supportés

- 🟠 Orange Money
- 🔵 Wave
- 💳 Carte bancaire
- 💵 Espèces à la livraison

---

## 🔧 Personnalisation

Pour passer en production :
1. Changer `SECRET_KEY` dans `settings.py`
2. Mettre `DEBUG = False`
3. Configurer `ALLOWED_HOSTS`
4. Utiliser PostgreSQL (remplacer la config `DATABASES`)
5. Configurer un serveur SMTP pour les emails
