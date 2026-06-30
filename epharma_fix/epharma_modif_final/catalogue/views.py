from django.shortcuts import render, get_object_or_404, redirect
from django.contrib import messages
from django.db.models import Q
from .models import Medicament, Categorie, Panier, Pharmacie

def accueil(request):
    categories = Categorie.objects.all()
    populaires = Medicament.objects.filter(est_populaire=True, est_disponible=True)[:6]
    recents = Medicament.objects.filter(est_disponible=True).order_by('-date_ajout')[:4]
    return render(request, 'catalogue/accueil.html', {
        'categories': categories,
        'populaires': populaires,
        'recents': recents,
    })

def liste_medicaments(request):
    medicaments = Medicament.objects.filter(est_disponible=True)
    categories = Categorie.objects.all()
    categorie_slug = request.GET.get('categorie')
    recherche = request.GET.get('q', '')
    categorie_active = None

    if categorie_slug:
        categorie_active = get_object_or_404(Categorie, slug=categorie_slug)
        medicaments = medicaments.filter(categorie=categorie_active)

    if recherche:
        medicaments = medicaments.filter(
            Q(nom__icontains=recherche) |
            Q(description__icontains=recherche) |
            Q(composition__icontains=recherche)
        )

    return render(request, 'catalogue/medicaments.html', {
        'medicaments': medicaments,
        'categories': categories,
        'categorie_active': categorie_active,
        'recherche': recherche,
    })

def detail_medicament(request, slug):
    medicament = get_object_or_404(Medicament, slug=slug, est_disponible=True)
    similaires = Medicament.objects.filter(
        categorie=medicament.categorie,
        est_disponible=True
    ).exclude(pk=medicament.pk)[:4]
    return render(request, 'catalogue/detail.html', {
        'medicament': medicament,
        'similaires': similaires,
    })

def ajouter_panier(request, medicament_id):
    if not request.session.session_key:
        request.session.create()
    medicament = get_object_or_404(Medicament, pk=medicament_id)
    item, created = Panier.objects.get_or_create(
        session_key=request.session.session_key,
        medicament=medicament,
        defaults={'quantite': 1}
    )
    if not created:
        item.quantite += 1
        item.save()
    messages.success(request, f'"{medicament.nom}" ajouté au panier !')
    return redirect(request.META.get('HTTP_REFERER', 'accueil'))

def voir_panier(request):
    if not request.session.session_key:
        request.session.create()
    items = Panier.objects.filter(session_key=request.session.session_key).select_related('medicament')
    total = sum(item.sous_total for item in items)
    return render(request, 'catalogue/panier.html', {'items': items, 'total': total})

def modifier_panier(request, item_id):
    item = get_object_or_404(Panier, pk=item_id, session_key=request.session.session_key)
    action = request.POST.get('action')
    if action == 'augmenter':
        item.quantite += 1
        item.save()
    elif action == 'diminuer':
        if item.quantite > 1:
            item.quantite -= 1
            item.save()
        else:
            item.delete()
    elif action == 'supprimer':
        item.delete()
        messages.info(request, 'Article retiré du panier.')
    return redirect('panier')

def liste_pharmacies(request):
    pharmacies = Pharmacie.objects.filter(est_active=True)
    return render(request, 'catalogue/pharmacies.html', {'pharmacies': pharmacies})


# ─── VUE AMO ───────────────────────────────────────────────
from django.contrib.auth.decorators import login_required
from .models import CarteAMO

@login_required
def amo_view(request):
    carte = CarteAMO.objects.filter(utilisateur=request.user).first()

    if request.method == 'POST':
        numero = request.POST.get('numero_carte', '').strip()
        nom    = request.POST.get('nom_assure', '').strip()
        expiry = request.POST.get('date_expiration', '').strip()

        if len(numero) < 4:
            messages.error(request, 'Numéro de carte invalide.')
        else:
            CarteAMO.objects.update_or_create(
                utilisateur=request.user,
                defaults={
                    'numero_carte': numero,
                    'nom_assure': nom,
                    'date_expiration': expiry,
                    'est_valide': True,
                }
            )
            messages.success(request, 'Votre carte AMO a été enregistrée avec succès !')
            return redirect('amo')

    return render(request, 'catalogue/amo.html', {'carte': carte})
