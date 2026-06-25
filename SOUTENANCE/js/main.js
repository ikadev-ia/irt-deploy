// Fonction pour le chatbot
let chatHistory = [];

function toggleChat() {
    const chatbotBody = document.getElementById('chatbotBody');
    chatbotBody.classList.toggle('collapsed');
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Ajouter le message de l'utilisateur
    addMessage(message, 'user');
    input.value = '';
    
    // Envoyer au backend
    fetch('chatbot_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        addMessage(data.response, 'bot');
    });
}

function addMessage(text, sender) {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}`;
    
    const icon = document.createElement('i');
    icon.className = sender === 'user' ? 'fas fa-user' : 'fas fa-robot';
    
    const content = document.createElement('div');
    content.className = 'message-content';
    content.textContent = text;
    
    messageDiv.appendChild(icon);
    messageDiv.appendChild(content);
    messagesDiv.appendChild(messageDiv);
    
    // Scroll vers le bas
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Fonction pour les notifications
function showNotification(message, type) {
    const notificationList = document.getElementById('notificationList');
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerHTML = `
        <i class="fas fa-${type === 'critical' ? 'exclamation-triangle' : (type === 'warning' ? 'warning' : 'info')}"></i>
        <span>${message}</span>
    `;
    
    notificationList.insertBefore(notif, notificationList.firstChild);
    
    // Supprimer après 5 secondes
    setTimeout(() => {
        notif.remove();
    }, 5000);
}

// Fonction pour vérifier les alertes en temps réel
function checkRealTimeAlerts() {
    setInterval(() => {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(notif => {
                    if (!notif.seen) {
                        showNotification(notif.message, notif.severity);
                    }
                });
            });
    }, 10000);
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Gestion des entrées clavier dans le chatbot
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    // Démarrer la vérification des alertes
    checkRealTimeAlerts();
    
    // Animation des cartes
    const cards = document.querySelectorAll('.stat-card, .card');
    cards.forEach((card, index) => {
        card.style.animation = `slideUp 0.5s ease-out ${index * 0.1}s both`;
    });
});

// Fonction pour charger les données des lots
function loadBatchData(batchId) {
    fetch(`get_batch_data.php?id=${batchId}`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour l'affichage
            updateBatchDisplay(data);
        });
}

// Fonction pour mettre à jour l'affichage des lots
function updateBatchDisplay(data) {
    const feedingInfo = document.getElementById('feedingInfo');
    const vaccineCalendar = document.getElementById('vaccineCalendar');
    
    if (feedingInfo) {
        feedingInfo.innerHTML = `
            <div class="feeding-info">
                <p><strong>Aliment actuel :</strong> ${data.feed_type === 'starter' ? 'Démarrage' : 'Concentré'}</p>
                <p><strong>Consommation journalière :</strong> ${data.daily_feed || 0} kg</p>
                <p><strong>Consommation totale :</strong> ${data.total_feed || 0} kg</p>
                ${data.feed_change_alert ? '<div class="alert warning">⚠️ Changement d\'aliment recommandé !</div>' : ''}
            </div>
        `;
    }
    
    if (vaccineCalendar) {
        let vaccineHtml = '<div class="vaccine-list">';
        data.vaccines.forEach(vaccine => {
            vaccineHtml += `
                <div class="vaccine-item ${vaccine.is_done ? 'done' : 'pending'}">
                    <div class="vaccine-info">
                        <strong>${vaccine.name}</strong>
                        <small>Jour ${vaccine.recommended_day}</small>
                    </div>
                    <div class="vaccine-status">
                        ${vaccine.is_done ? '<i class="fas fa-check-circle"></i> Fait' : '<i class="fas fa-clock"></i> À faire'}
                    </div>
                </div>
            `;
        });
        vaccineHtml += '</div>';
        vaccineCalendar.innerHTML = vaccineHtml;
    }
    
}