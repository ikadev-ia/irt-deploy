from django.urls import path
from . import views

app_name = 'messaging'

urlpatterns = [
    # On utilise 'inbox' comme nom principal pour la liste des messages
    path('', views.chat_list, name='inbox'),

    # On garde chat_list en alias au cas où il est utilisé ailleurs dans ton code
    path('list/', views.chat_list, name='chat_list'),

    path('chat/<int:conversation_id>/', views.chat_detail, name='chat_detail'),
    path('start/<int:user_id>/', views.start_conversation, name='start_conversation'),
    path('delete/<int:message_id>/', views.delete_message, name='delete_message'),
]