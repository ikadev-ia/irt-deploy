"""
Script de configuration automatique du système de livraison E-Pharma.
Lance avec : python setup_livraison.py
"""
import os
import django

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'epharma.settings')
django.setup()

from django.contrib.auth import get_user_model
from commandes.models import Commande
from livraison.models import Livraison

User = get_user_model()

print("\n" + "="*55)
print("   CONFIGURATION E-PHARMA — SYSTÈME DE LIVRAISON")
print("="*55)

# ── 1. Créer le compte admin ──────────────────────────────
print("\n[1/4] Création du compte administrateur...")
if not User.objects.filter(username='admin').exists():
    admin = User.objects.create_superuser(
        username='admin',
        email='admin@epharma.com',
        password='Admin1234!',
        first_name='Admin',
        last_name='E-Pharma',
    )
    admin.role = 'admin'
    admin.save()
    print("    Compte admin créé  — admin / Admin1234!")
else:
    admin = User.objects.get(username='admin')
    admin.is_staff = True
    admin.is_superuser = True
    admin.save()
    print("    Compte admin existe déjà — admin / Admin1234!")

# ── 2. Créer le compte livreur ────────────────────────────
print("\n[2/4] Création du compte livreur...")
if not User.objects.filter(username='livreur1').exists():
    livreur = User.objects.create_user(
        username='livreur1',
        email='livreur1@epharma.com',
        password='Livreur1234!',
        first_name='Moussa',
        last_name='Coulibaly',
    )
    livreur.role = 'livreur'
    livreur.telephone = '+223 76 00 11 22'
    livreur.save()
    print("    Compte livreur créé  — livreur1 / Livreur1234!")
else:
    livreur = User.objects.get(username='livreur1')
    livreur.role = 'livreur'
    livreur.save()
    print("    Compte livreur existe déjà — livreur1 / Livreur1234!")

# ── 3. Assigner les commandes en attente au livreur ───────
print("\n[3/4] Assignation des commandes au livreur...")
commandes = Commande.objects.exclude(statut='annulee').exclude(statut='livree')

if not commandes.exists():
    print("    Aucune commande trouvée — passe d'abord une commande sur l'appli !")
else:
    for commande in commandes:
        # Créer ou mettre à jour la livraison
        livraison, created = Livraison.objects.get_or_create(
            commande=commande,
            defaults={'livreur': livreur, 'statut': 'en_route'}
        )
        if not created:
            livraison.livreur = livreur
            livraison.statut = 'en_route'
            livraison.save()

        # Mettre la commande en route
        commande.statut = 'en_route'
        commande.save()

        print(f"    Commande #{commande.numero} → assignée à {livreur.get_full_name()} (En route)")

# ── 4. Ajouter position GPS initiale (centre Bamako) ──────
print("\n[4/4] Position GPS initiale du livreur...")
for livraison in Livraison.objects.filter(livreur=livreur):
    if not livraison.latitude:
        livraison.latitude = 12.6392
        livraison.longitude = -8.0029
        livraison.save()
        print(f"    Position initiale : Bamako centre → Commande #{livraison.commande.numero}")

print("\n" + "="*55)
print("   CONFIGURATION TERMINÉE !")
print("="*55)
print()
print("  Comptes créés :")
print("  - Admin    : admin / Admin1234!")
print("  - Livreur  : livreur1 / Livreur1234!")
print()
print("  Pour tester le suivi en direct :")
print("  1. Ouvre l'appli et va sur ta commande")
print("  2. Clique 'Suivre ma livraison en direct'")
print("  3. Sur un autre onglet, connecte-toi en tant que livreur1")
print("  4. Va sur /livraison/livreur/ et clique 'Démarrer'")
print("  5. Ta position GPS va s'afficher sur la carte du client !")
print("="*55 + "\n")
