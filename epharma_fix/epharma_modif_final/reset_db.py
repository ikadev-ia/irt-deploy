"""
Script RESET complet — supprime la base et repart de zéro.
Exécuter avec : python reset_db.py
"""
import os, django, shutil

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'epharma.settings')
django.setup()

from catalogue.models import Categorie, Medicament, Pharmacie
from accounts.models import Utilisateur
from django.conf import settings

print("🗑️  Suppression de toutes les données...")
Medicament.objects.all().delete()
Pharmacie.objects.all().delete()
Categorie.objects.all().delete()
Utilisateur.objects.filter(is_superuser=False).delete()
print("  ✓ Tables vidées\n")

# ── CATÉGORIES ──
print("🌱 Catégories...")
cats = {}
for nom, icone, desc in [
    ('Médicaments',   'fa-pills',        'Médicaments généraux'),
    ('Produits santé','fa-heart-pulse',   'Produits santé quotidien'),
    ('Bien-être',     'fa-spa',           'Vitamines et compléments'),
    ('Ordonnances',   'fa-file-medical',  'Sur ordonnance uniquement'),
]:
    c = Categorie.objects.create(nom=nom, icone=icone, description=desc)
    cats[nom] = c
    print(f"  ✓ {nom}")

# ── MÉDICAMENTS ──
print("\n💊 Médicaments...")
meds = [
    # nom, catégorie, description, composition, posologie, prix, stock, ordonnance, populaire, image
    ('Doliprane 1000mg',         'Médicaments',    'Paracétamol antalgique et antipyrétique. Soulage douleur et fièvre.',                     'Paracétamol 1000mg',                               '1 comprimé toutes les 6h. Max 4g/jour.',        1500,  100, False, True,  'doliprane.jpg'),
    ('Ibuprofène 400mg',         'Médicaments',    'Anti-inflammatoire non stéroïdien (AINS). Douleurs, fièvre, inflammations.',               'Ibuprofène 400mg — 10x10 comprimés',               '1 comprimé 3x/jour au cours des repas.',        2000,   80, False, True,  'ibuprofene.jpg'),
    ('Vitamine C 500',           'Bien-être',      'Complément Juvamine effervescent. Renforce l\'immunité. Sans sucres, arôme orange.',       'Acide ascorbique 500mg — 30 comprimés',            '1 comprimé effervescent/jour dans un verre d\'eau.',2000, 80, False, True,  'vitamine_c.jpg'),
    ('Biseptine Spray',          'Produits santé', 'Antiseptique spray pour plaies superficielles. Flacon 50ml.',                             'Chlorhexidine, Chlorure de benzalkonium',          'Appliquer sur plaie propre. Usage externe.',    1200,   60, False, True,  'biseptine.jpg'),
    ('Amoxicilline 1g',          'Ordonnances',    'Antibiotique pénicilline Viatris. 6 comprimés dispersibles. Sur ordonnance.',              'Amoxicilline 1g',                                  'Selon prescription médicale.',                 3500,   40, True,  True,  'amoxicilline.jpg'),
    ('Oméprazole 20mg',          'Ordonnances',    'Inhibiteur pompe à protons. Reflux gastrique et ulcères. 14 comprimés.',                   'Oméprazole 20mg',                                  '1 comprimé/jour avant le repas du matin.',     4500,   35, True,  False, 'omeprazole.jpg'),
    ('Zinc Magnésium',           'Bien-être',      'Complément Optimum Nutrition — Zinc, Magnésium et Vitamine B6 pour la vitalité. 90 caps.', 'Zinc, Magnésium, Vitamine B6',                     '1 capsule/jour au moment des repas.',          5000,   50, False, True,  'zinc_magnesium.jpg'),
    ('Sérum physiologique',      'Produits santé', 'Physiodose NaCl 0.9% — Unidoses stériles 50ml. Nettoyage nasal et oculaire.',             'Chlorure de sodium 0.9%',                          '1-2 doses par narine selon besoin.',            800,  200, False, False, 'physiodose.jpg'),
    ('Metformine 500mg',         'Ordonnances',    'Antidiabétique oral Mylan Pharma. Diabète type 2. 30 comprimés. Sur ordonnance.',          'Metformine chlorhydrate 500mg',                    'Selon prescription. À prendre pendant les repas.',2500, 30, True,  False, 'metformine.jpg'),
    ('Thermomètre digital',      'Produits santé', 'Thermomètre médical à lecture rapide 60s. Affichage LCD. Mémoire dernière mesure.',        'Dispositif médical',                               'Usage axillaire, buccal ou rectal.',           8000,   25, False, False, 'thermometre.jpg'),
    ('Triofan Sirop toux',       'Médicaments',    'Sirop contre la toux aux extraits de plantes Verfora 175ml. Adultes et enfants +3ans.',    'Plantago, Althaea, huile de thym, miel',           '10ml 3x/jour adulte. 5ml enfant.',             2200,   45, False, True,  'triofan_sirop.jpg'),
    ('Mustela Eau Micellaire',   'Produits santé', 'Eau micellaire visage & corps 400ml. Sans rinçage, sans parfum. Aloe vera bio.',           'Aloe vera bio, Huile d\'olive',                    'Appliquer sur coton. Pas de rinçage.',         6500,   30, False, False, 'mustela_micellar.jpg'),
]

for nom, cat_nom, desc, compo, poso, prix, stock, ordo, pop, img_file in meds:
    med = Medicament.objects.create(
        nom=nom, categorie=cats[cat_nom],
        description=desc, composition=compo, posologie=poso,
        prix=prix, stock=stock,
        sur_ordonnance=ordo, est_populaire=pop,
        image=f'medicaments/{img_file}' if img_file else ''
    )
    print(f"  ✓ {nom} — {prix} FCFA")

# ── PHARMACIES ──
print("\n🏥 Pharmacies...")
pharmacies = [
    ('Pharmacie Bougiba',              'ACI 2000, Avenue de l\'OUA, Bamako',                    'ACI 2000',                  '+223 20 29 35 00', 'Lun-Sam : 8h-21h | Dim : 9h-18h'),
    ('Pharmacie Mpewo',                'Lafiabougou, Rue 230, Bamako',                          'Lafiabougou',               '+223 20 28 17 50', 'Lun-Sam : 8h-21h | Dim : 9h-18h'),
    ('Pharmacie Officine ALY ADAMA',   'Avenue de la Nation, Baco-Djicoroni, Bamako',           'Baco-Djicoroni',            '+223 20 00 00 00', 'Lun-Sam : 8h-20h | Dim : 9h-17h'),
    ('Pharmacie Mamadou Yattassaye',   'Centre commercial, Commune III, Bamako',                'Commune III — Centre-ville','+223 20 00 00 01', 'Lun-Sam : 8h-21h | Dim : 9h-18h'),
    ('Pharmacie La Croix Verte',       'ACI 2000, Commune IV, Bamako',                          'ACI 2000 — Commune IV',     '+223 20 00 00 02', 'Lun-Dim : 7h-23h (garde de nuit)'),
]

for nom, adresse, quartier, tel, horaires in pharmacies:
    Pharmacie.objects.create(nom=nom, adresse=adresse, quartier=quartier, telephone=tel, horaires=horaires)
    print(f"  ✓ {nom}")

# ── UTILISATEURS ──
print("\n👤 Utilisateurs...")
if not Utilisateur.objects.filter(username='admin').exists():
    u = Utilisateur.objects.create_superuser('admin', 'admin@epharma.ml', 'admin123')
    u.first_name='Admin'; u.last_name='E-Pharma'; u.role='admin'; u.save()
print("  ✓ admin / admin123")

if not Utilisateur.objects.filter(username='client_test').exists():
    u = Utilisateur.objects.create_user('client_test','client@test.ml','test1234')
    u.first_name='Mamadou'; u.last_name='Diallo'; u.telephone='+223 70 00 00 01'
    u.adresse='Bamako, ACI 2000, Rue 234, Porte 15'; u.role='client'; u.save()
print("  ✓ client_test / test1234")

if not Utilisateur.objects.filter(username='livreur_test').exists():
    u = Utilisateur.objects.create_user('livreur_test','livreur@epharma.ml','test1234')
    u.first_name='Aliou'; u.last_name='Diop'; u.telephone='+223 76 00 00 02'; u.role='livreur'; u.save()
print("  ✓ livreur_test / test1234")

print(f"""
✅ Base de données recréée proprement !
   • {Categorie.objects.count()} catégories
   • {Medicament.objects.count()} médicaments (zéro doublon)
   • {Pharmacie.objects.count()} pharmacies
   • {Utilisateur.objects.count()} utilisateurs

🚀 Lance : python manage.py runserver
""")
