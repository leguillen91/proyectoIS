document.addEventListener("DOMContentLoaded", () => {

    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "../../index.php";
        return;
    }

    // ELEMENTOS
    const searchInput = document.getElementById("searchInput");
    const searchResults = document.getElementById("searchResults");
    const receivedRequests = document.getElementById("receivedRequests");
    const sentRequests = document.getElementById("sentRequests");
    const contactList = document.getElementById("contactList");

    // ============================================================
    //  UTILITARIOS
    // ============================================================

    function cardLoading(text = "Cargando...") {
        return `<p class="text-muted text-center my-2">${text}</p>`;
    }

    function emptyMessage(text) {
        return `<p class="text-muted">${text}</p>`;
    }

    function avatar() {
        return `<img src="../../assets/img/avatar.png" class="avatar-sm me-3">`;
    }

    // ============================================================
    // 1. BUSCAR ESTUDIANTES
    // ============================================================

    document.getElementById("btnSearch").addEventListener("click", searchStudents);

    async function searchStudents() {
        const term = searchInput.value.trim();

        if (!term) {
            searchResults.innerHTML = "";
            return;
        }

        searchResults.innerHTML = cardLoading("Buscando...");

        try {
            const res = await fetch(`/api/students/searchStudent.php?term=${encodeURIComponent(term)}`, {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (!data.ok || data.results.length === 0) {
                searchResults.innerHTML = emptyMessage("No se encontraron resultados.");
                return;
            }

            let html = "";

            data.results.forEach(s => {
                html += `
                    <div class="card p-3 mb-2 contact-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                ${avatar()}
                                <div>
                                    <strong>${s.fullName}</strong><br>
                                    <small>Cuenta: ${s.enrollmentCode}</small>
                                </div>
                            </div>

                            <button class="btn btn-success btn-sm" onclick="sendRequest(${s.id})">
                                <i class="bi bi-person-plus-fill"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            searchResults.innerHTML = html;

        } catch (e) {
            console.error(e);
            searchResults.innerHTML = emptyMessage("Error al buscar.");
        }
    }

    // ============================================================
    // 2. ENVIAR SOLICITUD DE CONTACTO
    // ============================================================

    window.sendRequest = async (receiverId) => {
        try {
            const res = await fetch("/api/students/sendContactRequest.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ receiverId })
            });

            const data = await res.json();

            if (!data.ok) {
                alert(data.error || "No se pudo enviar la solicitud.");
                return;
            }

            searchInput.value = "";
            searchResults.innerHTML = "";

            loadSentRequests();
            alert("Solicitud enviada.");

        } catch (e) {
            console.error(e);
            alert("Error en el servidor.");
        }
    };

    // ============================================================
    // 3. SOLICITUDES RECIBIDAS
    // ============================================================

    async function loadReceivedRequests() {
        receivedRequests.innerHTML = cardLoading();

        try {
            const res = await fetch("/api/students/getContactRequests.php", {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (!data.ok || data.requests.length === 0) {
                receivedRequests.innerHTML = emptyMessage("No tienes solicitudes pendientes.");
                return;
            }

            let html = "";

            data.requests.forEach(r => {
                html += `
                    <div class="card p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            
                            <div class="d-flex align-items-center">
                                ${avatar()}
                                <div>
                                    <strong>${r.fullName}</strong><br>
                                    <small>Cuenta: ${r.enrollmentCode}</small>
                                </div>
                            </div>

                            <div>
                                <button class="btn btn-success btn-sm me-2"
                                        onclick="respondRequest(${r.requestId}, 'accepted')">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-danger btn-sm"
                                        onclick="respondRequest(${r.requestId}, 'rejected')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            receivedRequests.innerHTML = html;

        } catch (e) {
            console.error(e);
            receivedRequests.innerHTML = emptyMessage("Error al cargar solicitudes.");
        }
    }

    // ============================================================
    // 4. RESPONDER SOLICITUD (ACEPTAR / RECHAZAR)
    // ============================================================

    window.respondRequest = async (requestId, action) => {

        try {
            const res = await fetch("/api/students/respondContactRequest.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ requestId, action })
            });

            const data = await res.json();

            if (data.ok) {
                loadReceivedRequests();
                loadContacts();
            } else {
                alert("Error: " + (data.error || "No se pudo procesar."));
            }

        } catch (e) {
            console.error(e);
            alert("Error del servidor.");
        }
    };

    // ============================================================
    // 5. SOLICITUDES ENVIADAS
    // ============================================================

    async function loadSentRequests() {
        sentRequests.innerHTML = cardLoading();

        try {
            const res = await fetch("/api/students/listSentRequests.php", {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (!data.ok || data.requests.length === 0) {
                sentRequests.innerHTML = emptyMessage("No has enviado solicitudes.");
                return;
            }

            let html = "";

            data.requests.forEach(r => {
                html += `
                    <div class="card p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center">
                                ${avatar()}
                                <div>
                                    <strong>${r.fullName}</strong><br>
                                    <small>Cuenta: ${r.enrollmentCode}</small>
                                </div>
                            </div>

                            <div>
                                <span class="badge bg-warning text-dark badge-status">${r.status}</span>

                                ${r.status === 'pending' ? `
                                    <button class="btn btn-outline-danger btn-sm ms-2"
                                            onclick="cancelRequest(${r.requestId})">
                                        Cancelar
                                    </button>` : ``}
                            </div>
                        </div>
                    </div>
                `;
            });

            sentRequests.innerHTML = html;

        } catch (e) {
            console.error(e);
            sentRequests.innerHTML = emptyMessage("Error al cargar solicitudes enviadas.");
        }
    }

    // ============================================================
    // 6. CANCELAR SOLICITUD
    // ============================================================

    window.cancelRequest = async (requestId) => {
        try {
            const res = await fetch("/api/students/cancelContactRequest.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ requestId })
            });

            const data = await res.json();

            if (data.ok) {
                loadSentRequests();
            } else {
                alert(data.error || "Error al cancelar solicitud");
            }

        } catch (e) {
            console.error(e);
            alert("Error del servidor.");
        }
    };

    // ============================================================
    // 7. CONTACTOS ACEPTADOS
    // ============================================================

    async function loadContacts() {
        contactList.innerHTML = cardLoading();

        try {
            const res = await fetch("/api/students/getContacts.php", {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (!data.ok || data.contacts.length === 0) {
                contactList.innerHTML = emptyMessage("Aún no tienes contactos.");
                return;
            }

            let html = "";

            data.contacts.forEach(c => {
                html += `
                    <div class="card p-3 mb-2 contact-card"
                         onclick="goToChat(${c.studentId})">
                        <div class="d-flex align-items-center justify-content-between">

                            <div class="d-flex align-items-center">
                                ${avatar()}
                                <div>
                                    <strong>${c.fullName}</strong><br>
                                    <small>Cuenta: ${c.enrollmentCode}</small>
                                </div>
                            </div>

                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="event.stopPropagation(); deleteContact(${c.studentId});">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            contactList.innerHTML = html;

        } catch (e) {
            console.error(e);
            contactList.innerHTML = emptyMessage("Error al cargar contactos.");
        }
    }

    // redirección al chat
    window.goToChat = (id) => {
        window.location.href = `chat.html?with=${id}`;
    };

    // ============================================================
    // 8. ELIMINAR CONTACTO
    // ============================================================

    window.deleteContact = async (contactId) => {

        if (!confirm("¿Eliminar este contacto?")) return;

        try {
            const res = await fetch("/api/students/deleteContact.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ contactId })
            });

            const data = await res.json();

            if (data.ok) loadContacts();
            else alert(data.error || "No se pudo eliminar");

        } catch (e) {
            console.error(e);
            alert("Error del servidor.");
        }
    };

    // ============================================================
    // 9. CARGAR TODO AL INICIO
    // ============================================================

    loadContacts();
    loadSentRequests();
    loadReceivedRequests();

    // LOGOUT
    document.getElementById("btnLogout").addEventListener("click", () => {
        localStorage.removeItem("accessToken");
        window.location.href = "../../index.php";
    });
});
