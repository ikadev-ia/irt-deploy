from django.shortcuts import render, redirect, get_object_or_404
from django.contrib import messages
from django.contrib.auth import login
from django.contrib.auth.decorators import login_required
from .forms import RegisterForm, ProfileUpdateForm

# On importe Product au lieu de Article
from marketplace.models import Product

def register_view(request):
    """Gère l'inscription des utilisateurs."""
    if request.method == 'POST':
        form = RegisterForm(request.POST, request.FILES)
        if form.is_valid():
            user = form.save()
            username = form.cleaned_data.get('username')
            messages.success(request, f"Bienvenue chez Revina, {username} !")
            login(request, user)
            return redirect('marketplace:home')
    else:
        form = RegisterForm()
    return render(request, 'accounts/register.html', {'form': form})

@login_required
def profile_view(request):
    """Affiche le profil de l'utilisateur."""
    return render(request, 'accounts/profile.html')

@login_required
def edit_profile(request):
    """Modification des informations personnelles."""
    if request.method == 'POST':
        form = ProfileUpdateForm(request.POST, request.FILES, instance=request.user)
        if form.is_valid():
            form.save()
            messages.success(request, "Votre profil a été mis à jour !")
            return redirect('profile')
    else:
        form = ProfileUpdateForm(instance=request.user)
    return render(request, 'accounts/edit_profile.html', {'form': form})

@login_required
def dashboard(request):
    """
    Tableau de bord affichant les produits mis en vente par Malick SY.
    """
    # Utilisation de 'seller' car c'est le nom du champ dans ton modèle Product
    articles = Product.objects.filter(seller=request.user)
    return render(request, 'accounts/dashboard.html', {'articles': articles})