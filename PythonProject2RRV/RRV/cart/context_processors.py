from .models import Cart  # <--- AJOUTEZ CETTE LIGNE

def cart_count(request):
    if request.user.is_authenticated:
        # Maintenant, Cart est reconnu grâce à l'importation ci-dessus
        cart = Cart.objects.filter(user=request.user).first()
        count = cart.items.count() if cart else 0
        return {'cart_count': count}
    return {'cart_count': 0}