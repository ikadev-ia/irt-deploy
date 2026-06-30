from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from catalogue.models import Panier
from .models import Commande, LigneCommande

@login_required
def checkout(request):
    if not request.session.session_key:
        return redirect('panier')
    items = Panier.objects.filter(session_key=request.session.session_key).select_related('medicament')
    if not items.exists():
        messages.warning(request, 'Votre panier est vide.')
        return redirect('panier')
    total = sum(item.sous_total for item in items)

    if request.method == 'POST':
        adresse = request.POST.get('adresse_livraison', request.user.adresse)
        telephone = request.POST.get('telephone', request.user.telephone)
        mode_paiement = request.POST.get('mode_paiement', 'especes')
        notes = request.POST.get('notes', '')
        ordonnance = request.FILES.get('ordonnance')

        commande = Commande.objects.create(
            client=request.user,
            adresse_livraison=adresse,
            telephone=telephone,
            mode_paiement=mode_paiement,
            notes=notes,
            total=total,
            ordonnance=ordonnance,
        )
        for item in items:
            LigneCommande.objects.create(
                commande=commande,
                medicament=item.medicament,
                quantite=item.quantite,
                prix_unitaire=item.medicament.prix,
            )
            # Diminuer le stock
            med = item.medicament
            med.stock = max(0, med.stock - item.quantite)
            med.save()
        items.delete()

        # ── Assignation automatique du livreur ──────────────
        from livraison.models import Livraison
        from django.conf import settings
        from django.contrib.auth import get_user_model
        User = get_user_model()

        # Changer le statut de la commande en "en_route"
        commande.statut = 'en_route'
        commande.save()

        # Trouver un livreur disponible
        livreur = User.objects.filter(role='livreur').first()
        if livreur:
            Livraison.objects.get_or_create(
                commande=commande,
                defaults={
                    'livreur': livreur,
                    'statut': 'en_route',
                    'latitude': 12.6392,
                    'longitude': -8.0029,
                }
            )
        # ────────────────────────────────────────────────────

        messages.success(request, f'Commande #{commande.numero} passée avec succès ! 🎉')
        return redirect('detail_commande', pk=commande.pk)

    return render(request, 'commandes/checkout.html', {
        'items': items,
        'total': total,
        'user': request.user,
    })

@login_required
def mes_commandes(request):
    commandes = Commande.objects.filter(client=request.user).prefetch_related('lignes')
    return render(request, 'commandes/liste.html', {'commandes': commandes})

@login_required
def detail_commande(request, pk):
    commande = get_object_or_404(Commande, pk=pk, client=request.user)
    return render(request, 'commandes/detail.html', {'commande': commande})

@login_required
def annuler_commande(request, pk):
    commande = get_object_or_404(Commande, pk=pk, client=request.user)
    if commande.statut in ['en_attente', 'confirmee']:
        commande.statut = 'annulee'
        commande.save()
        messages.success(request, f'Commande #{commande.numero} annulée.')
    else:
        messages.error(request, 'Cette commande ne peut plus être annulée.')
    return redirect('mes_commandes')


# ─── REÇU WEB ──────────────────────────────────────────────
@login_required
def recu_web(request, pk):
    commande = get_object_or_404(Commande, pk=pk, client=request.user)
    from catalogue.models import CarteAMO
    carte = CarteAMO.objects.filter(utilisateur=request.user).first()
    reduction_amo = 0
    if carte:
        for ligne in commande.lignes.all():
            if ligne.medicament.couvert_amo and ligne.medicament.taux_amo > 0:
                reduction_amo += int(ligne.medicament.prix * ligne.medicament.taux_amo / 100) * ligne.quantite
    return render(request, 'commandes/recu.html', {
        'commande': commande,
        'carte': carte,
        'reduction_amo': reduction_amo,
        'total_final': int(commande.total) - reduction_amo + 500,
    })


# ─── REÇU PDF ──────────────────────────────────────────────
@login_required
def recu_pdf(request, pk):
    from django.http import HttpResponse
    from catalogue.models import CarteAMO
    import io
    from reportlab.lib.pagesizes import A4
    from reportlab.lib import colors
    from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
    from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
    from reportlab.lib.units import cm
    from reportlab.lib.enums import TA_CENTER, TA_RIGHT, TA_LEFT

    commande = get_object_or_404(Commande, pk=pk, client=request.user)
    carte = CarteAMO.objects.filter(utilisateur=request.user).first()

    VERT = colors.HexColor('#1aab5f')
    BLEU = colors.HexColor('#1565c0')
    DARK = colors.HexColor('#1a1a2e')
    GRAY = colors.HexColor('#6c757d')
    LGRAY = colors.HexColor('#f5f7fa')

    buffer = io.BytesIO()
    doc = SimpleDocTemplate(
        buffer, pagesize=A4,
        rightMargin=2*cm, leftMargin=2*cm,
        topMargin=2*cm, bottomMargin=2*cm
    )
    elements = []

    # Styles
    s_title = ParagraphStyle('t', fontSize=24, textColor=VERT, fontName='Helvetica-Bold', alignment=TA_CENTER)
    s_sub   = ParagraphStyle('s', fontSize=11, textColor=GRAY, alignment=TA_CENTER)
    s_h2    = ParagraphStyle('h2', fontSize=15, fontName='Helvetica-Bold', textColor=DARK, alignment=TA_CENTER)
    s_body  = ParagraphStyle('b', fontSize=11, textColor=DARK)
    s_foot  = ParagraphStyle('f', fontSize=10, textColor=GRAY, alignment=TA_CENTER)

    # En-tête
    elements.append(Paragraph("E-PHARMA MALI", s_title))
    elements.append(Spacer(1, 4))
    elements.append(Paragraph("Votre pharmacie en un clic — Bamako, Mali", s_sub))
    elements.append(Paragraph("epharma473@gmail.com  |  +223 82 53 41 83", s_sub))
    elements.append(Spacer(1, 14))
    elements.append(HRFlowable(width="100%", thickness=2, color=VERT))
    elements.append(Spacer(1, 14))
    elements.append(Paragraph(f"REÇU DE PAIEMENT  N° {commande.numero}", s_h2))
    elements.append(Spacer(1, 18))

    # Infos commande
    border = colors.HexColor('#eeeeee')
    info_rows = [
        ['Date :', commande.date_creation.strftime('%d/%m/%Y à %H:%M')],
        ['Client :', request.user.get_full_name() or request.user.username],
        ['Statut :', commande.get_statut_display()],
        ['Paiement :', commande.get_mode_paiement_display()],
        ['Adresse :', commande.adresse_livraison],
    ]
    if carte:
        info_rows.append(['Carte AMO :', carte.numero_carte])

    t_info = Table(info_rows, colWidths=[5*cm, 12*cm])
    t_info.setStyle(TableStyle([
        ('FONTNAME', (0,0), (0,-1), 'Helvetica-Bold'),
        ('FONTSIZE', (0,0), (-1,-1), 10.5),
        ('TEXTCOLOR', (0,0), (0,-1), GRAY),
        ('TEXTCOLOR', (1,0), (1,-1), DARK),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('TOPPADDING', (0,0), (-1,-1), 4),
    ]))
    elements.append(t_info)
    elements.append(Spacer(1, 18))
    elements.append(HRFlowable(width="100%", thickness=0.5, color=border))
    elements.append(Spacer(1, 14))

    # Titre articles
    elements.append(Paragraph("Détail de la commande", ParagraphStyle('sec', fontSize=13, fontName='Helvetica-Bold', textColor=DARK)))
    elements.append(Spacer(1, 8))

    # Table articles
    rows = [['Médicament', 'Qté', 'Prix unitaire', 'Sous-total']]
    subtotal = 0
    for ligne in commande.lignes.all():
        st = int(ligne.prix_unitaire) * ligne.quantite
        subtotal += st
        rows.append([
            ligne.medicament.nom,
            str(ligne.quantite),
            f"{int(ligne.prix_unitaire):,} FCFA",
            f"{st:,} FCFA",
        ])

    livraison = 500
    reduction_amo = 0
    if carte:
        for ligne in commande.lignes.all():
            if ligne.medicament.couvert_amo and ligne.medicament.taux_amo > 0:
                reduction_amo += int(ligne.medicament.prix * ligne.medicament.taux_amo / 100) * ligne.quantite

    rows.append(['', '', 'Livraison :', f"{livraison:,} FCFA"])
    if reduction_amo > 0:
        rows.append(['', '', 'Prise en charge AMO :', f"- {reduction_amo:,} FCFA"])
    total_final = subtotal + livraison - reduction_amo
    rows.append(['', '', 'TOTAL PAYÉ :', f"{total_final:,} FCFA"])

    t_arts = Table(rows, colWidths=[9*cm, 2*cm, 4*cm, 3*cm])
    t_arts.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), VERT),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE', (0,0), (-1,-1), 10.5),
        ('ROWBACKGROUNDS', (0,1), (-1,-4), [colors.white, LGRAY]),
        ('FONTNAME', (0,-1), (-1,-1), 'Helvetica-Bold'),
        ('TEXTCOLOR', (2,-1), (-1,-1), VERT),
        ('LINEABOVE', (0,-1), (-1,-1), 1.5, VERT),
        ('ALIGN', (1,0), (-1,-1), 'RIGHT'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 8),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
        ('ROUNDEDCORNERS', [6]),
    ]))
    elements.append(t_arts)
    elements.append(Spacer(1, 30))

    # Pied de page
    elements.append(HRFlowable(width="100%", thickness=0.5, color=border))
    elements.append(Spacer(1, 10))
    elements.append(Paragraph("Merci pour votre confiance — E-Pharma Mali", s_foot))
    elements.append(Paragraph("ISC Business School · Bamako, Mali · 2025-2026", s_foot))

    doc.build(elements)
    buffer.seek(0)
    response = HttpResponse(buffer, content_type='application/pdf')
    response['Content-Disposition'] = f'attachment; filename="recu_epharma_{commande.numero}.pdf"'
    return response
