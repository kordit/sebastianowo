document.addEventListener("DOMContentLoaded", function () {
    let tooltip = document.createElement("div");
    tooltip.classList.add("svg-tooltip");
    document.body.appendChild(tooltip);

    document.querySelectorAll("svg path").forEach(path => {
        let link = path.getAttribute("data-link");
        let color = path.getAttribute("data-color");
        let title = path.getAttribute("data-title");
        let owner = path.getAttribute("data-availability");
        let ownerColor = path.getAttribute("data-availability_village_id") ? path.getAttribute("data-color") : null;

        // Jeśli kolor jest "none", ustaw czarny (#000) z przezroczystością 0.3
        if (!color || color.toLowerCase() === "none") {
            color = "#000";
        }
        if (!owner || owner.toLowerCase() === "none") {
            owner = "Wolne";
        }

        path.style.fill = color;
        path.style.opacity = "0.6"; // Domyślna przezroczystość
        path.style.transition = "opacity 0.2s ease-in-out"; // Płynna animacja

        // Obsługa hovera
        path.addEventListener("mouseenter", function () {
            path.style.opacity = "0.1"; // Zwiększenie przezroczystości po najechaniu
            tooltip.style.display = "block";
            tooltip.innerHTML = title;

            if (owner) {
                let ownerSpan = document.createElement("span");
                ownerSpan.textContent = ` (${owner})`;
                if (ownerColor) {
                    ownerSpan.style.color = ownerColor;
                }
                tooltip.appendChild(ownerSpan);
            }
        });

        path.addEventListener("mousemove", function (e) {
            tooltip.style.left = `${e.pageX + 10}px`;
            tooltip.style.top = `${e.pageY + 10}px`;
        });

        path.addEventListener("mouseleave", function () {
            path.style.opacity = "0.6"; // Powrót do domyślnej przezroczystości
            tooltip.style.display = "none";
        });

        // Kliknięcie przenosi do linku
        path.addEventListener("click", function () {
            if (link) {
                window.location.href = link;
            }
        });
    });
});

