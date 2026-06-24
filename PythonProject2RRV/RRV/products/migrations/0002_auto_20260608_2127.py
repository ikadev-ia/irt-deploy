from django.db import migrations

def transferer_produits(apps, schema_editor):
    # Charger les anciens modèles via apps.get_model
    ShopProduct = apps.get_model('shop', 'ShopProduct')
    MarketplaceProduct = apps.get_model('marketplace', 'Product')
    # Charger le nouveau modèle centralisé
    Product = apps.get_model('products', 'Product')

    # 1. Transfert des produits de la Boutique
    # On suppose ici que ShopProduct utilise 'name' comme champ de titre
    for old_item in ShopProduct.objects.all():
        Product.objects.create(
            name=old_item.name,
            description=old_item.description,
            price=old_item.price,
            image=old_item.image,
            product_type='BOUTIQUE'
        )

    # 2. Transfert des produits de la Marketplace
    # Ici on utilise 'title' au lieu de 'name' et 'seller' au lieu de 'owner'
    for old_item in MarketplaceProduct.objects.all():
        Product.objects.create(
            name=old_item.title,
            description=old_item.description,
            price=old_item.price,
            image=old_item.image,
            product_type='MARKETPLACE',
            owner=old_item.seller
        )

class Migration(migrations.Migration):

    dependencies = [
        ('products', '0001_initial'),
        ('shop', '0001_initial'),        # Ajouté
        ('marketplace', '0001_initial'), # Ajouté
    ]

    operations = [
        migrations.RunPython(transferer_produits),
    ]