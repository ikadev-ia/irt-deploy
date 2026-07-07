"""
Script pour peupler la base de données avec des données de démonstration.
Exécuter avec : python populate_db.py
"""
import os
import django
import shutil

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'epharma.settings')
django.setup()

from django.conf import settings
from accounts.models import Utilisateur
from catalogue.models import Categorie, Medicament, Pharmacie

# ── Nettoyage des doublons avant insertion ──
print("🧹 Nettoyage des doublons existants...")
noms_vus = {}
for med in Medicament.objects.all().order_by('nom', '-image', 'id'):
    nom_key = med.nom.strip().lower()
    if nom_key not in noms_vus:
        noms_vus[nom_key] = med.id
    else:
        med.delete()

# Supprimer les fantômes sans aucune image
Medicament.objects.filter(image='', image_url__isnull=True).delete()
Medicament.objects.filter(image='', image_url='').delete()
print(f"  ✓ {Medicament.objects.count()} médicament(s) propre(s) en base\n")

# Copier les images locales dans le dossier media
print("🖼️  Copie des images médicaments...")
src_dir = os.path.join(settings.BASE_DIR, 'media', 'medicaments')
os.makedirs(src_dir, exist_ok=True)

print("\n🌱 Création des catégories...")
categories_data = [
    {'nom': 'Médicaments', 'icone': 'fa-pills', 'description': 'Médicaments généraux et spécialités'},
    {'nom': 'Produits santé', 'icone': 'fa-heart-pulse', 'description': 'Produits pour la santé au quotidien'},
    {'nom': 'Bien-être', 'icone': 'fa-spa', 'description': 'Vitamines, compléments et bien-être'},
    {'nom': 'Ordonnances', 'icone': 'fa-file-medical', 'description': 'Médicaments sur ordonnance uniquement'},
]

categories = {}
for data in categories_data:
    cat, _ = Categorie.objects.get_or_create(nom=data['nom'], defaults=data)
    categories[data['nom']] = cat
    print(f"  ✓ {cat.nom}")

print("\n💊 Création des médicaments...")
medicaments_data = [
    {
        'nom': 'Doliprane 1000mg', 'categorie': 'Médicaments',
        'description': 'Paracétamol 1000mg - Antalgique et antipyrétique. Soulage la douleur et la fièvre.',
        'composition': 'Paracétamol 1000mg',
        'posologie': '1 comprimé toutes les 6h max. Ne pas dépasser 4g/jour.',
        'prix': 1500, 'stock': 100, 'est_populaire': True,
        'image_file': 'doliprane.jpg',
    },
    {
        'nom': 'Vitamine C 500', 'categorie': 'Bien-être',
        'description': 'Complément alimentaire Juvamine - Vitamine C 500mg effervescent pour renforcer l\'immunité. Sans sucres, arôme orange naturel.',
        'composition': 'Acide ascorbique 500mg',
        'posologie': '1 comprimé effervescent par jour dans un verre d\'eau.',
        'prix': 2000, 'stock': 80, 'est_populaire': True,
        'image_file': 'vitamine_c.jpg',
    },
    {
        'nom': 'Biseptine Spray', 'categorie': 'Produits santé',
        'description': 'Solution antiseptique en spray pour nettoyer et désinfecter les plaies superficielles. Flacon 50ml.',
        'composition': 'Chlorhexidine, Chlorure de benzalkonium, Alcool benzylique',
        'posologie': 'Appliquer sur la plaie propre. Usage externe uniquement.',
        'prix': 1200, 'stock': 60, 'est_populaire': True,
        'image_file': 'biseptine.jpg',
    },
    {
        'nom': 'Amoxicilline 1g', 'categorie': 'Ordonnances',
        'description': 'Antibiotique de la famille des pénicillines (Viatris). Sur ordonnance médicale uniquement.',
        'composition': 'Amoxicilline 1g - 6 comprimés dispersibles',
        'posologie': 'Selon prescription médicale. Ne pas arrêter le traitement sans avis médical.',
        'prix': 3500, 'stock': 40, 'sur_ordonnance': True, 'est_populaire': True,
        'image_file': 'amoxicilline.jpg',
    },
    {
        'nom': 'Oméprazole 20mg', 'categorie': 'Médicaments',
        'description': 'Inhibiteur de la pompe à protons. Traitement des ulcères et du reflux gastrique. 14 comprimés.',
        'composition': 'Oméprazole 20mg',
        'posologie': '1 comprimé par jour avant le repas du matin.',
        'prix': 4500, 'stock': 35, 'sur_ordonnance': True,
        'image_file': 'omeprazole.jpg',
    },
    {
        'nom': 'Zinc Magnésium Aspartate', 'categorie': 'Bien-être',
        'description': 'Complément alimentaire Optimum Nutrition - Association zinc, magnésium et vitamine B6 pour la vitalité et l\'immunité. 90 capsules.',
        'composition': 'Zinc, Magnésium, Vitamine B6',
        'posologie': '1 capsule par jour au moment des repas.',
        'prix': 5000, 'stock': 50, 'est_populaire': True,
        'image_file': 'zinc_magnesium.jpg',
    },
    {
        'nom': 'Sérum physiologique Physiodose', 'categorie': 'Produits santé',
        'description': 'Solution isotonique stérile NaCl 0.9% - Unidoses de 50ml pour nettoyage nasal, oculaire et lavage de plaies.',
        'composition': 'Chlorure de sodium 0.9%',
        'posologie': 'Usage externe. 1-2 doses par narine selon besoin.',
        'prix': 800, 'stock': 200,
        'image_file': 'physiodose.jpg',
    },
    {
        'nom': 'Metformine 500mg', 'categorie': 'Ordonnances',
        'description': 'Antidiabétique oral Mylan Pharma - Traitement du diabète de type 2. 30 comprimés pelliculés. Sur ordonnance uniquement.',
        'composition': 'Metformine chlorhydrate 500mg',
        'posologie': 'Selon prescription. À prendre pendant les repas.',
        'prix': 2500, 'stock': 30, 'sur_ordonnance': True,
        'image_file': 'metformine.jpg',
    },
    {
        'nom': 'Thermomètre digital', 'categorie': 'Produits santé',
        'description': 'Thermomètre médical digital à lecture rapide en 60 secondes. Affichage LCD. Mémoire dernière mesure.',
        'composition': 'Dispositif médical',
        'posologie': 'Usage axillaire, buccal ou rectal selon modèle.',
        'prix': 8000, 'stock': 25,
        'image_file': 'thermometre.jpg',
    },
    {
        'nom': 'Triofan Sirop contre la toux', 'categorie': 'Médicaments',
        'description': 'Sirop complet contre la toux Verfora - Aux extraits de plantes (plantain, guimauve, thym). 175ml. Adultes et enfants dès 3 ans.',
        'composition': 'Plantago, Althaea, huile essentielle de thym, miel',
        'posologie': '10ml 3 fois par jour pour adulte. 5ml pour enfant.',
        'prix': 2200, 'stock': 45, 'est_populaire': True,
        'image_file': 'triofan_sirop.jpg',
    },
    {
        'nom': 'Mustela Eau Micellaire', 'categorie': 'Produits santé',
        'description': 'Eau micellaire visage & corps Mustela Family 400ml - Sans rinçage, sans parfum. Formule vegan avec aloe vera bio et huile d\'olive.',
        'composition': 'Aloe vera bio, Huile d\'olive',
        'posologie': 'Appliquer sur coton et nettoyer doucement. Pas de rinçage.',
        'prix': 6500, 'stock': 30,
        'image_file': 'mustela_micellar.jpg',
    },
]

for data in medicaments_data:
    cat_nom = data.pop('categorie')
    image_file = data.pop('image_file', None)
    cat = categories.get(cat_nom)

    med, created = Medicament.objects.get_or_create(
        nom=data['nom'],
        defaults={**data, 'categorie': cat}
    )

    # Associer l'image locale
    if image_file:
        img_path = f"medicaments/{image_file}"
        src = os.path.join(settings.BASE_DIR, 'media', img_path)
        if os.path.exists(src):
            med.image = img_path
            med.save()

    if created:
        print(f"  ✓ {med.nom} — {med.prix} FCFA")
    else:
        med.categorie = cat
        if image_file:
            img_path = f"medicaments/{image_file}"
            src = os.path.join(settings.BASE_DIR, 'media', img_path)
            if os.path.exists(src):
                med.image = img_path
        med.save()
        print(f"  ↻ {med.nom} (mis à jour)")

print("\n🏥 Création des pharmacies...")
pharmacies_data = [
    {
        'nom': 'Pharmacie Bougiba',
        'adresse': 'ACI 2000, Avenue de l\'OUA, Bamako',
        'quartier': 'ACI 2000',
        'telephone': '+223 20 29 35 00',
        'horaires': 'Lun-Sam : 8h-21h | Dim : 9h-18h',
    },
    {
        'nom': 'Pharmacie Mpewo',
        'adresse': 'Lafiabougou, Rue 230, Bamako',
        'quartier': 'Lafiabougou',
        'telephone': '+223 20 28 17 50',
        'horaires': 'Lun-Sam : 8h-21h | Dim : 9h-18h',
    },
    {
        'nom': 'Pharmacie Officine ALY ADAMA',
        'adresse': 'Avenue de la Nation, Baco-Djicoroni, Bamako',
        'quartier': 'Baco-Djicoroni',
        'telephone': '+223 20 22 93 73',
        'horaires': 'Lun-Sam : 8h-20h | Dim : 9h-17h',
    },
    {
        'nom': 'Pharmacie Mamadou Yattassaye',
        'adresse': 'Centre commercial, Commune III, Centre-ville, Bamako',
        'quartier': 'Commune III - Centre-ville',
        'telephone': '+223 20 22 33 98',
        'horaires': 'Lun-Sam : 8h-21h | Dim : 9h-18h',
    },
    {
        'nom': 'Pharmacie La Croix Verte',
        'adresse': 'ACI 2000, Bamako, Mali',
        'quartier': 'ACI 2000',
        'telephone': '+223 20 29 04 65',
        'horaires': 'Lun-Dim : 7h-23h (garde de nuit)',
    },
]

for data in pharmacies_data:
    ph, created = Pharmacie.objects.get_or_create(nom=data['nom'], defaults=data)
    if not created:
        for k, v in data.items():
            setattr(ph, k, v)
        ph.save()
    status = "✓" if created else "↻"
    print(f"  {status} {ph.nom} — {ph.quartier}")

print("\n👤 Création des utilisateurs de test...")
if not Utilisateur.objects.filter(username='admin').exists():
    admin = Utilisateur.objects.create_superuser('admin', 'admin@epharma.ml', 'admin123')
    admin.first_name = 'Admin'
    admin.last_name = 'E-Pharma'
    admin.role = 'admin'
    admin.save()
    print("  ✓ Superuser : admin / admin123")

if not Utilisateur.objects.filter(username='client_test').exists():
    client = Utilisateur.objects.create_user('client_test', 'client@test.ml', 'test1234')
    client.first_name = 'Mamadou'
    client.last_name = 'Diallo'
    client.telephone = '+223 70 00 00 01'
    client.adresse = 'Bamako, ACI 2000, Rue 234, Porte 15'
    client.role = 'client'
    client.save()
    print("  ✓ Client test : client_test / test1234")

if not Utilisateur.objects.filter(username='livreur_test').exists():
    livreur = Utilisateur.objects.create_user('livreur_test', 'livreur@epharma.ml', 'test1234')
    livreur.first_name = 'Aliou'
    livreur.last_name = 'Diop'
    livreur.telephone = '+223 76 00 00 02'
    livreur.role = 'livreur'
    livreur.save()
    print("  ✓ Livreur test : livreur_test / test1234")

print("\n✅ Base de données peuplée avec succès !")
print("\n📋 Récapitulatif :")
print(f"   • {Categorie.objects.count()} catégories")
print(f"   • {Medicament.objects.count()} médicaments")
print(f"   • {Pharmacie.objects.count()} pharmacies")
print(f"   • {Utilisateur.objects.count()} utilisateurs")
print("\n🚀 Lancez le serveur avec : python manage.py runserver")
print("🔑 Admin : http://127.0.0.1:8000/admin/ → admin / admin123")
# Ce bout de code sera ajouté au début du script
