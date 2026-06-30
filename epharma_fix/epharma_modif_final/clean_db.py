"""
Script pour nettoyer les doublons dans la base de données.
Exécuter avec : python clean_db.py
"""
import os
import django

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'epharma.settings')
django.setup()

from catalogue.models import Medicament

print("🧹 Nettoyage des doublons de médicaments...\n")

# Récupérer tous les noms de médicaments
noms = Medicament.objects.values_list('nom', flat=True)
noms_vus = {}
supprimes = 0

for med in Medicament.objects.all().order_by('nom', '-image', 'id'):
    nom = med.nom.strip().lower()
    
    if nom not in noms_vus:
        # Premier qu'on voit — on garde celui qui a une image si possible
        noms_vus[nom] = med.id
    else:
        # Doublon — on supprime
        print(f"  🗑️  Suppression doublon : {med.nom} (id={med.id}, image={'Oui' if med.image else 'Non'})")
        med.delete()
        supprimes += 1

# Aussi supprimer les médicaments sans image ET sans image_url (fantômes)
fantomes = Medicament.objects.filter(image='', image_url__isnull=True)
nb_fantomes = fantomes.count()
if nb_fantomes > 0:
    print(f"\n  🗑️  Suppression de {nb_fantomes} médicament(s) sans photo...")
    for f in fantomes:
        print(f"      → {f.nom}")
    fantomes.delete()

print(f"\n✅ Nettoyage terminé !")
print(f"   • {supprimes} doublon(s) supprimé(s)")
print(f"   • {nb_fantomes} médicament(s) sans photo supprimé(s)")
print(f"   • {Medicament.objects.count()} médicament(s) restant(s)")
print("\n🔄 Relancez maintenant : python populate_db.py")
