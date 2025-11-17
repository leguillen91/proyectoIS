// Importa y registra los componentes de NavBar y Footer
import "../../components/NavBar.js";
import "../../components/Footer.js";

function navigateTo(page) {
    document.body.style.opacity = '0.8';
    
    setTimeout(() => {
        window.location.href = page;
    }, 200);
}

document.querySelectorAll('.nav-button').forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
    
    button.addEventListener('mousedown', function() {
        this.style.transform = 'translateY(-2px) scale(0.98)';
    });
    
    button.addEventListener('mouseup', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
});

window.navigateTo = navigateTo;