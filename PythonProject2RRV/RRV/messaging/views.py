from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib.auth import get_user_model
from .models import Conversation, Message
from notifications.models import Notification  # Importation de ton app notifications

# Récupération du modèle User personnalisé
User = get_user_model()


@login_required
def chat_list(request):
    conversations = Conversation.objects.filter(
        participants=request.user
    ).order_by('-updated_at')
    return render(request, 'messaging/chat_list.html', {'conversations': conversations})


@login_required
def chat_detail(request, conversation_id):
    conversation = get_object_or_404(
        Conversation,
        id=conversation_id,
        participants=request.user
    )

    messages_list = conversation.messages.all().order_by('timestamp')
    # Identification de l'interlocuteur (celui qui recevra la notification)
    other_participant = conversation.participants.exclude(id=request.user.id).first()

    if request.method == 'POST':
        content = request.POST.get('content')
        if content:
            # 1. Création du message
            Message.objects.create(
                conversation=conversation,
                sender=request.user,
                content=content
            )

            # 2. Création de la notification pour l'interlocuteur
            if other_participant:
                Notification.objects.create(
                    recipient=other_participant,
                    notification_type='message',
                    text=f"Nouveau message de {request.user.username}",
                    link_id=conversation.id  # On stocke l'ID de la conversation pour le lien
                )

            # 3. Mise à jour de la conversation
            conversation.save()
            return redirect('messaging:chat_detail', conversation_id=conversation.id)

    return render(request, 'messaging/chat_detail.html', {
        'conversation': conversation,
        'messages_list': messages_list,
        'other_participant': other_participant
    })


@login_required
def start_conversation(request, user_id):
    other_user = get_object_or_404(User, id=user_id)
    if other_user == request.user:
        return redirect('marketplace:home')

    conversation = Conversation.objects.filter(
        participants=request.user
    ).filter(
        participants=other_user
    ).first()

    if not conversation:
        conversation = Conversation.objects.create()
        conversation.participants.add(request.user, other_user)

    return redirect('messaging:chat_detail', conversation_id=conversation.id)


@login_required
def delete_message(request, message_id):
    message = get_object_or_404(Message, id=message_id, sender=request.user)
    conversation_id = message.conversation.id
    message.delete()
    return redirect('messaging:chat_detail', conversation_id=conversation_id)