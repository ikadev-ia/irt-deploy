from django.shortcuts import render, get_object_or_404, redirect
from django.contrib.auth.decorators import login_required
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_POST
from django.utils import timezone
from django.contrib import messages
from commandes.models import Commande
from .models import Livraison
import json


# ─── VUE CLIENT : Suivi de sa livraison ───────────────────
@login_required
def suivi(request, pk):
    commande = get_object_or_404(Commande, pk=pk, client=request.user)
    livraison = getattr(commande, 'livraison', None)
    return render(request, 'livraison/suivi.html', {
        'commande': commande,
        'livraison': livraison,
    })


# ─── API : Position du livreur (appelée par JS toutes les 5s) ─
def api_position_livreur(request, pk):
    """Retourne la position GPS du livreur en JSON."""
    commande = get_object_or_404(Commande, pk=pk)
    livraison = getattr(commande, 'livraison', None)

    if not livraison:
        return JsonResponse({'statut': 'pas_de_livraison'})

    return JsonResponse({
        'statut': livraison.statut,
        'statut_display': livraison.get_statut_display(),
        'latitude': livraison.latitude,
        'longitude': livraison.longitude,
        'livreur_nom': livraison.livreur.get_full_name() if livraison.livreur else None,
        'livreur_tel': livraison.livreur.telephone if livraison.livreur else None,
        'heure_depart': livraison.heure_depart.strftime('%H:%M') if livraison.heure_depart else None,
        'derniere_maj': livraison.derniere_maj_position.strftime('%H:%M:%S') if livraison.derniere_maj_position else None,
        'commande_statut': commande.statut,
    })


# ─── API : Le livreur envoie sa position GPS ──────────────
@csrf_exempt
@require_POST
@login_required
def api_maj_position(request, pk):
    """Le livreur envoie sa position depuis son téléphone."""
    livraison = get_object_or_404(Livraison, pk=pk, livreur=request.user)
    data = json.loads(request.body)

    livraison.latitude = data.get('latitude')
    livraison.longitude = data.get('longitude')
    livraison.derniere_maj_position = timezone.now()
    livraison.save()

    return JsonResponse({'ok': True})


# ─── VUE LIVREUR : Tableau de bord du livreur ─────────────
@login_required
def tableau_livreur(request):
    if request.user.role != 'livreur':
        messages.error(request, "Accès réservé aux livreurs.")
        return redirect('accueil')

    livraisons = Livraison.objects.filter(
        livreur=request.user
    ).exclude(statut='livree').select_related('commande', 'commande__client')

    livraisons_terminees = Livraison.objects.filter(
        livreur=request.user, statut='livree'
    ).select_related('commande')[:10]

    return render(request, 'livraison/tableau_livreur.html', {
        'livraisons': livraisons,
        'livraisons_terminees': livraisons_terminees,
    })


# ─── VUE LIVREUR : Changer le statut manuellement ─────────
@login_required
@require_POST
def changer_statut(request, pk):
    livraison = get_object_or_404(Livraison, pk=pk, livreur=request.user)
    nouveau_statut = request.POST.get('statut')

    if nouveau_statut == 'en_route':
        livraison.mettre_en_route()
        livraison.commande.statut = 'en_route'
        livraison.commande.save()
        messages.success(request, "Statut mis à jour : En route !")

    elif nouveau_statut == 'livree':
        livraison.marquer_livree()
        messages.success(request, "Livraison marquée comme effectuée !")

    elif nouveau_statut == 'arrive':
        livraison.statut = 'arrive'
        livraison.save()
        messages.success(request, "Vous êtes arrivé à destination !")

    return redirect('tableau_livreur')
