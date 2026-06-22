JavaScript
function scrollToSection() {
    document.getElementById("presentation").scrollIntoView({
        behavior: "smooth"
    });
}
// navbar dynamique au scroll
window.addEventListener("scroll", function () {
    let navbar = document.getElementById("navbar");

    if(navbar){
        navbar.classList.toggle("scrolled", window.scrollY > 50);
    }
});

// slider automatique
let slides = document.querySelectorAll(".slides");
let currentSlide = 0;

setInterval(() => {

    console.log("Slide actuelle :", currentSlide);

    slides[currentSlide].classList.remove("active");

    currentSlide++;

    if(currentSlide >= slides.length){
        currentSlide = 0;
    }

    slides[currentSlide].classList.add("active");

}, 3000);
function logout() {
    alert("Déconnexion réussie !");
    window.location.href = "index.html";
}
// MENU HAMBURGER

const menuToggle =
document.getElementById("menu-toggle");

const navMenu =
document.getElementById("nav-menu");

if(menuToggle){

    menuToggle.addEventListener("click", () => {

        navMenu.classList.toggle("active");

    });

}