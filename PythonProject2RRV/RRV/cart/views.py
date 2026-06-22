from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.urls import reverse
from urllib.parse import urlencode
from django.contrib.contenttypes.models import ContentType
from django.http import JsonResponse

# Importation des modèles
from marketplace.models import Product as MarketplaceProduct
from shop.models import ShopProduct
from .models import Cart, CartItem

@login_required
def add_to_cart(request, product_id, product_type):
    """
    Ajoute un produit au panier via une requête AJAX.
    """
    if product_type == 'marketplace':
        product = get_object_or_404(MarketplaceProduct, id=product_id)
    elif product_type == 'shop':
        product = get_object_or_404(ShopProduct, id=product_id)
    else:
        return JsonResponse({'status': 'error', 'message': 'Type de produit non reconnu.'}, status=400)

    content_type = ContentType.objects.get_for_model(product)
    cart, _ = Cart.objects.get_or_create(user=request.user)

    cart_item, created = CartItem.objects.get_or_create(
        cart=cart,
        content_type=content_type,
        object_id=product.id,
        defaults={'quantity': 1}
    )

    if not created:
        cart_item.quantity += 1
        cart_item.save()

    return JsonResponse({'status': 'success', 'message': 'Article ajouté au panier avec succès !'})

@login_required
def cart_detail(request):
    """Affiche le détail du panier."""
    cart, _ = Cart.objects.get_or_create(user=request.user)
    return render(request, 'cart/cart_detail.html', {'cart': cart})

@login_required
def remove_from_cart(request, item_id):
    """Supprime un item du panier."""
    item = get_object_or_404(CartItem, id=item_id, cart__user=request.user)
    item.delete()
    return redirect('cart:cart_detail')

@login_required
def checkout_selected(request):
    """Redirige vers la page de paiement."""
    if request.method == 'POST':
        selected_ids = request.POST.getlist('selected_items')
        if not selected_ids:
            return redirect('cart:cart_detail')

        base_url = reverse('payments:checkout_cart')
        query_string = urlencode({'items': selected_ids}, doseq=True)
        return redirect(f"{base_url}?{query_string}")

    return redirect('cart:cart_detail')