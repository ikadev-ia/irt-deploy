from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from shop.models import ShopProduct
from marketplace.models import Product  # Import du modèle marketplace
from cart.models import Cart, CartItem


def checkout(request, item_id):
    """
    Affiche la page de paiement pour un article unique (Marketplace ou Shop).
    """
    item_type = request.GET.get('type')  # Récupère le type via l'URL (?type=marketplace)

    # Logique de sélection du modèle selon le type
    if item_type == 'marketplace':
        article = get_object_or_404(Product, id=item_id)
    else:
        article = get_object_or_404(ShopProduct, id=item_id)

    preset_amount = article.price

    context = {
        'items': [{'content_object': article, 'quantity': 1, 'total_item_price': article.price}],
        'montant_total': preset_amount,
        'item_id': item_id,
        'item_type': item_type,  # Transmis au template pour usage futur
        'payment_methods': [
            {'id': 'OM', 'name': 'Orange Money'},
            {'id': 'W', 'name': 'Wave'},
            {'id': 'MOOV', 'name': 'Moov Africa'},
            {'id': 'CARD', 'name': 'Carte Bancaire'}
        ]
    }
    return render(request, 'payments/checkout.html', context)


@login_required
def checkout_cart(request):
    """
    Affiche la page de paiement pour le panier complet ou une sélection.
    """
    selected_ids = request.GET.getlist('items')

    if selected_ids:
        items = CartItem.objects.filter(id__in=selected_ids, cart__user=request.user)
    else:
        cart = Cart.objects.filter(user=request.user).first()
        items = cart.items.all() if cart else []

    if not items:
        return redirect('cart:cart_detail')

    montant_total = sum(item.content_object.price * item.quantity for item in items)

    context = {
        'items': items,
        'montant_total': montant_total,
        'payment_methods': [
            {'id': 'OM', 'name': 'Orange Money'},
            {'id': 'W', 'name': 'Wave'},
            {'id': 'MOOV', 'name': 'Moov Africa'},
            {'id': 'CARD', 'name': 'Carte Bancaire'}
        ]
    }

    return render(request, 'payments/checkout.html', context)


def confirmer_paiement(request):
    """
    Traite la soumission du formulaire de paiement.
    """
    if request.method == 'POST':
        methode = request.POST.get('method')
        montant_saisi = request.POST.get('amount')

        # Logique de traitement avec le montant_saisi
        print(f"Paiement de {montant_saisi} via {methode}")

        return render(request, 'payments/success.html', {
            'methode': methode,
            'montant': montant_saisi
        })

    return redirect('marketplace:home')