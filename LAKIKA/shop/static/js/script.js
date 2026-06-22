function ajouterPanier(nom, prix, quantite) {
    let panier = JSON.parse(localStorage.getItem("panier")) || [];

    let produitExiste = panier.find(item => item.nom === nom);

    if (produitExiste) {
        produitExiste.quantite += parseInt(quantite);
    } else {
        panier.push({
            nom: nom,
            prix: parseInt(prix),
            quantite: parseInt(quantite)
        });
    }

    localStorage.setItem("panier", JSON.stringify(panier));

afficherNotification("Produit ajouté au panier !");
    mettreAJourCompteur();
}

function afficherPanier() {
    let panier = JSON.parse(localStorage.getItem("panier")) || [];
    let panierContainer = document.getElementById("panier-items");
    let totalElement = document.getElementById("total");

    if (!panierContainer) return;

    panierContainer.innerHTML = "";
    let total = 0;

    if (panier.length === 0) {
        panierContainer.innerHTML = `
            <p style="margin-top:20px; color:gray;">
                Votre panier est vide
            </p>
        `;
    }

    panier.forEach((produit, index) => {
        total += produit.prix * produit.quantite;

        panierContainer.innerHTML += `
            <div class="panier-item">
                <h3>${produit.nom}</h3>
                <p>Prix : ${produit.prix} FCFA</p>
                <p>Quantité : ${produit.quantite}</p>

                <button onclick="supprimerProduit(${index})">
                    Supprimer
                </button>
            </div>
        `;
    });

    totalElement.innerText = total;
}

function supprimerProduit(index) {
    let panier = JSON.parse(localStorage.getItem("panier")) || [];

    panier.splice(index, 1);

    localStorage.setItem("panier", JSON.stringify(panier));

    afficherPanier();
}

function viderPanier() {
    localStorage.removeItem("panier");

    afficherPanier();

afficherNotification("Panier vidé !");
}

document.addEventListener("DOMContentLoaded", function () {
    afficherPanier();
});



function rechercherProduit() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let produits = document.querySelectorAll(".searchable-product");

    produits.forEach(function(produit) {
        let contenu = produit.innerText.toLowerCase();

        if (contenu.includes(input)) {
            produit.style.display = "block";
        } else {
            produit.style.display = "none";
        }
    });
}
function mettreAJourCompteur() {

    let panier = JSON.parse(localStorage.getItem("panier")) || [];

    let total = 0;

    panier.forEach(item => {
        total += item.quantite;
    });

    let compteur = document.getElementById("cart-count");

    if(compteur){
        compteur.innerText = total;
    }
}
document.addEventListener("DOMContentLoaded", function () {
    afficherPanier();
    mettreAJourCompteur();
});
function afficherNotification(message){

    let notification = document.getElementById("notification");

    notification.innerText = message;

    notification.classList.add("show");

    setTimeout(() => {
        notification.classList.remove("show");
    }, 3000);
}
window.addEventListener("load", function(){

    let loader = document.getElementById("loader");

    setTimeout(() => {

        loader.style.opacity = "0";

        loader.style.visibility = "hidden";

    }, 1500);

});
const elements = document.querySelectorAll(
    ".produit-card, .panier-item, .payment-card, .home-footer"
);

function afficherAuScroll(){

    elements.forEach(element => {

        let position = element.getBoundingClientRect().top;

        let hauteurEcran = window.innerHeight;

        if(position < hauteurEcran - 100){

            element.classList.add("show-scroll");

        }

    });

}

elements.forEach(element => {
    element.classList.add("hidden-scroll");
});

window.addEventListener("scroll", afficherAuScroll);

afficherAuScroll();
// afficher bouton scroll
window.addEventListener("scroll", function(){

    let btn = document.getElementById("topBtn");

    if(window.scrollY > 300){
        btn.style.display = "block";
    }else{
        btn.style.display = "none";
    }

});

// retour haut
function retourHaut(){

    window.scrollTo({
        top:0,
        behavior:"smooth"
    });

}
// compteur animé
let counters = document.querySelectorAll(".counter");

counters.forEach(counter => {

    let updateCounter = () => {

        let target = +counter.getAttribute("data-target");

        let count = +counter.innerText;

        let increment = target / 100;

        if(count < target){

            counter.innerText = Math.ceil(count + increment);

            setTimeout(updateCounter, 30);

        }else{

            counter.innerText = target;

        }

    };

    updateCounter();

});
// ====================
// PROFIL
// ====================

function saveProfile(){

    let nom = document.getElementById("nom").value;
    let email = document.getElementById("email").value;
    let telephone = document.getElementById("telephone").value;

    localStorage.setItem("nom", nom);
    localStorage.setItem("email", email);
    localStorage.setItem("telephone", telephone);

    alert("Profil enregistré avec succès");
}


// ====================
// MODE SOMBRE
// ====================

function toggleDarkMode(){

    document.body.classList.toggle("dark-mode");

    localStorage.setItem(
        "darkMode",
        document.body.classList.contains("dark-mode")
    );
}

window.addEventListener("load", function(){

    if(localStorage.getItem("darkMode") === "true"){
        document.body.classList.add("dark-mode");
    }

    if(localStorage.getItem("notifications") === "true"){
        document.getElementById("notifSwitch").checked = true;
    }

    document.getElementById("nom").value =
        localStorage.getItem("nom") || "";

    document.getElementById("email").value =
        localStorage.getItem("email") || "";

    document.getElementById("telephone").value =
        localStorage.getItem("telephone") || "";
});


// ====================
// NOTIFICATIONS
// ====================

function toggleNotif(){

    let active =
        document.getElementById("notifSwitch").checked;

    localStorage.setItem(
        "notifications",
        active
    );

    if(active){
        alert("Notifications activées");
    }else{
        alert("Notifications désactivées");
    }
}
function getLocation(){

    if(navigator.geolocation){

        navigator.geolocation.getCurrentPosition(function(position){

            document.getElementById("latitude").value =
                position.coords.latitude;

            document.getElementById("longitude").value =
                position.coords.longitude;

            alert("Votre position a été récupérée avec succès.");

        }, function(){

            alert("Localisation refusée ou impossible.");

        });

    } else {

        alert("La géolocalisation n'est pas supportée par votre navigateur.");

    }
}
function toggleMenu(){

    let menu = document.getElementById("menu");

    menu.classList.toggle("active");

    console.log(menu.className);
}
function changeQty(id, value){

    let input = document.getElementById(id);

    let current = parseInt(input.value);

    current += value;

    if(current < 1){
        current = 1;
    }

    input.value = current;
}
