document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form");
    const emailInput = document.querySelector("input[name='email']");
    const passwordInput = document.querySelector("input[name='password']");
    const errorMessage = document.createElement("div");
    errorMessage.classList.add("error-message");
    form.appendChild(errorMessage);

    form.addEventListener("submit", function(event) {
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        let valid = true;

        // Reset error message
        errorMessage.style.display = "none";
        errorMessage.textContent = "";

        // Validasi email
        if (!validateEmail(email)) {
            errorMessage.textContent = "Email tidak valid!";
            errorMessage.style.display = "block";
            valid = false;
        }

        // Validasi password
        if (password.length < 6) {
            errorMessage.textContent = "Password minimal harus 6 karakter!";
            errorMessage.style.display = "block";
            valid = false;
        }

        if (!valid) {
            event.preventDefault(); // Mencegah submit jika tidak valid
        }
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
});
// JavaScript for fade-in effect
document.addEventListener("DOMContentLoaded", function () {
    const welcomeSection = document.querySelector(".welcome");
    
    welcomeSection.style.opacity = 0;
    welcomeSection.style.transition = "opacity 2s ease-in-out";

    setTimeout(function () {
        welcomeSection.style.opacity = 1;
    }, 200);
});

// You can also add a console log to test if JS is working
console.log("Page loaded successfully!");
// Menu Toggle for Mobile View
const menuToggle = document.querySelector('.menu-toggle');
const navMenu = document.querySelector('nav ul');

menuToggle.addEventListener('click', () => {
    navMenu.classList.toggle('show');
});
