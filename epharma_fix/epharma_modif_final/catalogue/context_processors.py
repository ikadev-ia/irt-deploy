from .models import Panier

def panier_count(request):
    if not request.session.session_key:
        return {'panier_count': 0}
    count = Panier.objects.filter(session_key=request.session.session_key).count()
    return {'panier_count': count}
