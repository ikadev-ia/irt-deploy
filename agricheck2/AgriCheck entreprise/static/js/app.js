(function () {
    "use strict";

    function initTooltips() {
        if (!window.bootstrap) {
            return;
        }
        document.querySelectorAll("[data-bs-toggle='tooltip']").forEach(function (node) {
            new bootstrap.Tooltip(node);
        });
    }

    function initWelcomeCarousels() {
        document.querySelectorAll(".agricheck-carousel").forEach(function (carousel) {
            var cards = Array.prototype.slice.call(carousel.querySelectorAll(".welcome-card"));
            var dots = document.querySelector("[data-carousel-dots='" + carousel.id + "']");
            if (!cards.length) {
                return;
            }

            function currentIndex() {
                var center = carousel.scrollLeft + carousel.clientWidth / 2;
                var bestIndex = 0;
                var bestDistance = Infinity;
                cards.forEach(function (card, index) {
                    var cardCenter = card.offsetLeft + card.offsetWidth / 2;
                    var distance = Math.abs(center - cardCenter);
                    if (distance < bestDistance) {
                        bestDistance = distance;
                        bestIndex = index;
                    }
                });
                return bestIndex;
            }

            function scrollToCard(index) {
                var card = cards[Math.max(0, Math.min(index, cards.length - 1))];
                carousel.scrollTo({ left: card.offsetLeft - 16, behavior: "smooth" });
            }

            function updateDots() {
                if (!dots) {
                    return;
                }
                var active = currentIndex();
                Array.prototype.slice.call(dots.children).forEach(function (dot, index) {
                    dot.classList.toggle("active", index === active);
                });
            }

            if (dots && !dots.children.length) {
                cards.forEach(function (_card, index) {
                    var dot = document.createElement("button");
                    dot.type = "button";
                    dot.setAttribute("aria-label", "Carte " + (index + 1));
                    dot.addEventListener("click", function () {
                        scrollToCard(index);
                    });
                    dots.appendChild(dot);
                });
            }

            document.querySelectorAll("[data-carousel-prev='" + carousel.id + "']").forEach(function (button) {
                button.addEventListener("click", function () {
                    scrollToCard(currentIndex() - 1);
                });
            });
            document.querySelectorAll("[data-carousel-next='" + carousel.id + "']").forEach(function (button) {
                button.addEventListener("click", function () {
                    scrollToCard(currentIndex() + 1);
                });
            });
            carousel.addEventListener("scroll", function () {
                window.requestAnimationFrame(updateDots);
            }, { passive: true });
            updateDots();
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initTooltips();
        initWelcomeCarousels();
    });
})();
