from django.shortcuts import render, redirect
from django.contrib.auth import login, logout, authenticate
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from .forms import InscriptionForm, ConnexionForm, ProfilForm
from commandes.models import Commande

def inscription(request):
    if request.user.is_authenticated:
        return redirect('accueil')
    if request.method == 'POST':
        form = InscriptionForm(request.POST)
        if form.is_valid():
            user = form.save()
            login(request, user)
            messages.success(request, f'Bienvenue {user.first_name} ! Votre compte a été créé.')
            return redirect('accueil')
    else:
        form = InscriptionForm()
    return render(request, 'accounts/inscription.html', {'form': form})

def connexion(request):
    if request.user.is_authenticated:
        return redirect('accueil')
    if request.method == 'POST':
        form = ConnexionForm(request, data=request.POST)
        if form.is_valid():
            user = form.get_user()
            login(request, user)
            messages.success(request, f'Bienvenue {user.first_name or user.username} !')
            return redirect(request.GET.get('next', 'accueil'))
    else:
        form = ConnexionForm()
    return render(request, 'accounts/connexion.html', {'form': form})

@login_required
def deconnexion(request):
    logout(request)
    messages.info(request, 'Vous avez été déconnecté.')
    return redirect('connexion')

@login_required
def profil(request):
    commandes = Commande.objects.filter(client=request.user).order_by('-date_creation')[:5]
    if request.method == 'POST':
        form = ProfilForm(request.POST, request.FILES, instance=request.user)
        if form.is_valid():
            form.save()
            messages.success(request, 'Profil mis à jour avec succès !')
            return redirect('profil')
    else:
        form = ProfilForm(instance=request.user)
    return render(request, 'accounts/profil.html', {'form': form, 'commandes': commandes})

@login_required
def mes_adresses(request):
    if request.method == 'POST':
        adresse = request.POST.get('adresse')
        if adresse:
            request.user.adresse = adresse
            request.user.save()
            messages.success(request, 'Adresse mise à jour !')
    return render(request, 'accounts/adresses.html')
