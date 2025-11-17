// Importa y registra los componentes de NavBar y Footer
import "../../components/NavBar.js";
import "../../components/Footer.js";

// Referencias a elementos del DOM
const form = document.getElementById("statusForm");
const submitBtn = document.getElementById("submitBtn");
const cancelBtn = document.getElementById("cancelBtn");
const responseDiv = document.getElementById("response");
const emailInput = document.getElementById("email");
const sendText = document.getElementById("sendText");
const loadingText = document.getElementById("loadingText");

const fakeDelay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));


if (submitBtn) {
  submitBtn.style.opacity = "0.9";
}

if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailInput?.value.trim();

    if (!email) {
      if (responseDiv) {
        responseDiv.innerHTML = `
          <div class="alert alert-danger">
            Por favor ingrese un correo electrónico.
          </div>`;
      }
      return;
    }

    if (submitBtn && sendText && loadingText) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = "0.7";
      sendText.style.display = "none";
      loadingText.style.display = "inline-flex";
    }

    if (responseDiv) {
      responseDiv.innerHTML = `
        <div class="alert alert-info">
          Enviando el estado al correo <strong>${email}</strong>...
        </div>`;
    }

    try {
        //Simular delay
      await fakeDelay(1200);

      if (responseDiv) {
        responseDiv.innerHTML = `
          <div class="alert alert-success">
            Se ha enviado la solicitud al correo:
            <strong>${email}</strong>.<br>
          </div>`;
      }
    } catch (error) {
      if (responseDiv) {
        responseDiv.innerHTML = `
          <div class="alert alert-danger">
            Ocurrió un error inesperado en el cliente: ${error.message}
          </div>`;
      }
    } finally {
      if (submitBtn && sendText && loadingText) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = "1";
        sendText.style.display = "inline-flex";
        loadingText.style.display = "none";
      }
    }
  });
}

if (cancelBtn) {
  cancelBtn.addEventListener("click", () => {
    window.history.back();
  });
}
