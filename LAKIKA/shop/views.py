from pyexpat.errors import messages

from django.shortcuts import render
from .models import Produit
from django.contrib.auth.forms import UserCreationForm
from django.contrib.auth import authenticate, login, logout
from django.shortcuts import redirect
from .models import Commande
from django.contrib.auth.decorators import login_required

def index(request):
    return render(request, 'shop/index.html')

def apropos(request):
    return render(request, 'shop/apropos.html')

def contact(request):
    return render(request, 'shop/contact.html')

def aide(request):
    return render(request, 'shop/aide.html')

def confidentialite(request):
    return render(request, 'shop/confidentialite.html')

def cookies(request):
    return render(request, 'shop/cookies.html')

def parametres(request):
    return render(request, 'shop/parametres.html')
@login_required(login_url='/login/')
def paiement(request):

    if request.method == "POST":
        telephone = request.POST.get("telephone")
        methode = request.POST.get("methode")
        adresse = request.POST.get("adresse_livraison", "")  # vide si pas "livraison"

        Commande.objects.create(
            client=request.user,
            telephone=telephone,
            methode_paiement=methode,
            adresse_livraison=adresse,   # <-- nécessite ce champ dans models.py
        )

        return render(
            request,
            "shop/success.html",
            {
                "nom": request.user.username,
                "methode": methode,
            }
        )

    return render(request, "shop/paiement.html")



def inscription(request):
    form = UserCreationForm()

    if request.method == "POST":
        form = UserCreationForm(request.POST)

        if form.is_valid():
            form.save()
            return redirect("login")

    return render(request, "shop/inscription.html", {"form": form})

def connexion(request):
    if request.method == "POST":
        username = request.POST.get("username")
        password = request.POST.get("password")

        user = authenticate(request, username=username, password=password)

        if user:
            login(request, user)
            return redirect("index")
        else:
            messages.error(request, "Nom d'utilisateur ou mot de passe incorrect.")

    return render(request, "shop/login.html")
def deconnexion(request):
    logout(request)
    return redirect("index")









def index(request):
    produits = Produit.objects.all()
    return render(request, "shop/index.html", {"produits": produits})
def panier(request):
    return render(request,"shop/panier.html")