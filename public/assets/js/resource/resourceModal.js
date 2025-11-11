const moduleParam = new URLSearchParams(window.location.search).get("module") || "Software";
let CURRENT_MODULE = moduleParam;

async function initModal() {
  try {
    const res = await fetch(`/api/resource/metadata.php?module=${CURRENT_MODULE}`);
    const data = await res.json();

    if (!data.ok) {
      console.error("Error metadata:", data.error || "Respuesta no válida");
      alert("Error cargando metadatos del módulo: " + (data.error || "Sin detalles"));
      return;
    }

    // ============= Licencias =============
    const licenseSelect = document.querySelector('[name="licenseId"]');
    licenseSelect.innerHTML = `<option value="">Sin licencia</option>` +
      (data.data.licenses || []).map(l => `<option value="${l.idLicense}">${l.name}</option>`).join("");

    // ============= Autores / Tags =============
    const authorsInput = document.querySelector('[name="authors"]');
    const tagsInput = document.querySelector('[name="tags"]');
    if (authorsInput && data.data.authors)
      setupAutocomplete(authorsInput, data.data.authors);
    if (tagsInput && data.data.tags)
      setupAutocomplete(tagsInput, data.data.tags);

  } catch (err) {
    console.error("Error cargando metadatos del módulo:", err);
    alert("Error cargando metadatos del módulo. Revisa la consola para más detalles.");
  }
}


function setupAutocomplete(input, list) {
  let suggestionsDiv;

  input.addEventListener("input", () => {
    const parts = input.value.split(",");
    const last = parts[parts.length - 1].trim().toLowerCase();

    if (last.length < 2) {
      if (suggestionsDiv) suggestionsDiv.remove();
      return;
    }

    const matches = list.filter(item => item.toLowerCase().includes(last)).slice(0, 5);
    if (!matches.length) {
      if (suggestionsDiv) suggestionsDiv.remove();
      return;
    }

    if (!suggestionsDiv) {
      suggestionsDiv = document.createElement("div");
      suggestionsDiv.className = "autocomplete-suggestions border rounded bg-white position-absolute shadow-sm";
      input.parentNode.appendChild(suggestionsDiv);
    }

    suggestionsDiv.innerHTML = matches
      .map(m => `<div class="p-1 px-2 suggestion-item">${m}</div>`)
      .join("");

    suggestionsDiv.querySelectorAll(".suggestion-item").forEach(el => {
      el.onclick = () => {
        parts[parts.length - 1] = el.textContent.trim();
        input.value = parts.map(p => p.trim()).filter(Boolean).join(", ") + ", ";
        suggestionsDiv.remove();
        input.focus();
      };
    });
  });

  document.addEventListener("click", e => {
    if (suggestionsDiv && !input.contains(e.target)) suggestionsDiv.remove();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const modalCreate = document.getElementById("modalCreate");
  if (!modalCreate) return;
  const observer = new MutationObserver(() => {
    const isVisible = modalCreate.classList.contains("show");
    if (isVisible) initModal();
  });
  observer.observe(modalCreate, { attributes: true, attributeFilter: ["class"] });
});
