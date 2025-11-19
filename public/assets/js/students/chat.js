let lastMessageId = 0;


document.addEventListener("DOMContentLoaded", () => {

    // =============================
    //  TOKEN Y PROTECCIÓN DE ACCESO
    // =============================
    const token = localStorage.getItem("accessToken");
    if (!token) {
        window.location.href = "../../index.php";
        return;
    }

    // =============================
    //  ELEMENTOS DEL DOM
    // =============================
    const contactsPanel = document.getElementById("contactsPanel");
    const messagesArea = document.getElementById("messagesArea");
    const chatName = document.getElementById("chatName");
    const chatStatus = document.getElementById("chatStatus");
    const messageInput = document.getElementById("messageInput");
    const btnSend = document.getElementById("btnSend");

    let activeContact = null;
    let myId = null;

    // =============================
    // 0. OBTENER INFORMACIÓN DEL USUARIO
    // =============================
    async function loadContext() {
        try {
            const res = await fetch("/api/auth/me.php", {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();
            if (data.ok && data.user.studentId) {
                myId = data.user.studentId;
            }
        } catch (e) {
            console.error("Error cargando contexto:", e);
        }
    }

    // =============================
    // 1. CARGAR CONTACTOS (CON AVATAR)
    // =============================
    async function loadContacts() {
        contactsPanel.innerHTML = `<p class="text-muted text-center mt-3">Cargando...</p>`;

        try {
            const res = await fetch("/api/students/getContacts.php", {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (!data.ok) {
                contactsPanel.innerHTML = `<p class="text-danger text-center mt-3">Error al cargar contactos</p>`;
                return;
            }

            const contacts = data.contacts;

            if (!contacts.length) {
                contactsPanel.innerHTML = `<p class="text-muted text-center mt-3">No tienes contactos</p>`;
                return;
            }

            contactsPanel.innerHTML = "";

            contacts.forEach(c => {
                const item = document.createElement("div");
                item.classList.add("list-group-item", "contact-item");
                 item.dataset.id = c.studentId;
                item.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="../../assets/img/avatar.png" class="avatar-sm me-2">
                        <div>
                            <strong>${c.fullName}</strong><br>
                            <span class="text-muted">Cuenta: ${c.enrollmentCode}</span>
                        </div>
                    </div>
                `;

                item.addEventListener("click", () => {
                    selectContact(c.studentId, c.fullName);
                });

                contactsPanel.appendChild(item);
            });

        } catch (e) {
            console.error(e);
            contactsPanel.innerHTML = `<p class="text-danger text-center mt-3">Error</p>`;
        }
    }

    // =============================
    // 2. SELECCIONAR CONTACTO
    // =============================
    window.selectContact = async (contactId, name) => {

        activeContact = contactId;

        chatName.textContent = name;
        chatStatus.textContent = "Cargando...";
        messageInput.disabled = false;
        btnSend.disabled = false;

        showChatMobile(); // ← N U E V O

        loadMessages();
        updateContactStatus();
    };
 
    // =============================
    // MODO RESPONSIVE (MOSTRAR CHAT)
    // =============================
    function showChatMobile() {
        if (window.innerWidth <= 768) {
            document.querySelector(".chat-wrapper").classList.add("chat-mobile-active");
        }
    }

    // =============================
    // MODO RESPONSIVE (VOLVER A CONTACTOS)
    // =============================
    document.getElementById("btnBackContacts").addEventListener("click", () => {
        if (window.innerWidth <= 768) {
            document.querySelector(".chat-wrapper").classList.remove("chat-mobile-active");
        }
    });


    // =============================
    // 3. CARGAR MENSAJES (AVATAR + ANIMACIÓN + SEPARADORES)
    // =============================
    async function loadMessages() {
    if (!activeContact) return;

    try {
        const res = await fetch(`/api/students/getMessages.php?contactId=${activeContact}`, {
            headers: { "Authorization": "Bearer " + token }
        });

        const data = await res.json();

        if (!data.ok) {
            messagesArea.innerHTML = `<p class="text-danger text-center">Error al cargar mensajes</p>`;
            return;
        }

        const msgs = data.messages;

        if (!msgs.length) {
            messagesArea.innerHTML = `<p class="text-muted text-center mt-4">No hay mensajes aún</p>`;
            return;
        }

        let html = "";
        let lastDate = null;

        msgs.forEach(m => {
            const mine = (m.senderId == myId); // 🔥 CORRECTO

            const msgDate = m.sentAt.split(" ")[0];

            if (msgDate !== lastDate) {
                html += `
                    <div class="date-separator">
                        <span>${getDateLabel(msgDate)}</span>
                    </div>
                `;
                lastDate = msgDate;
            }

            html += `
                <div class="d-flex ${mine ? 'justify-content-end' : 'justify-content-start'} message-wrapper">

                    ${mine ? '' : `<img src="../../assets/img/avatar.png" class="avatar-sm me-2">`}

                    <div class="message ${mine ? 'mine' : 'theirs'}">
                        ${m.message}
                        <div class="msg-time">${m.sentAt}</div>
                    </div>

                    ${mine ? `<img src="../../assets/img/avatar.png" class="avatar-sm ms-2">` : ''}
                </div>
            `;
        });

        msgs.forEach(m => {

                // Saltar mensajes ya renderizados
                if (m.messageId <= lastMessageId) return;

                const mine = (m.senderId == myId);
                const msgDate = m.sentAt.split(" ")[0];

                // Separador si cambia el día
                if (msgDate !== lastDate) {
                    const sep = document.createElement("div");
                    sep.className = "date-separator";
                    sep.innerHTML = `<span>${getDateLabel(msgDate)}</span>`;
                    messagesArea.appendChild(sep);
                    lastDate = msgDate;
                }

                // Crear wrapper
                const wrapper = document.createElement("div");
                wrapper.className = `d-flex ${mine ? 'justify-content-end' : 'justify-content-start'} message-wrapper`;

                // Avatar izquierdo
                if (!mine) {
                    wrapper.innerHTML += `<img src="../../assets/img/avatar.png" class="avatar-sm me-2">`;
                }

                // Mensaje
                const msg = document.createElement("div");
                msg.className = `message ${mine ? 'mine' : 'theirs'}`;
                msg.innerHTML = `
                    ${m.message}
                    <div class="msg-time">${m.sentAt}</div>
                `;
                wrapper.appendChild(msg);

                // Avatar derecho
                if (mine) {
                    wrapper.innerHTML += `<img src="../../assets/img/avatar.png" class="avatar-sm ms-2">`;
                }

                messagesArea.appendChild(wrapper);

                lastMessageId = m.messageId; // marcar último renderizado
            });

            // scroll abajo SOLO si hay mensajes nuevos
            messagesArea.scrollTop = messagesArea.scrollHeight;


        } catch (e) {
            console.error(e);
            messagesArea.innerHTML = `<p class="text-danger text-center">Error</p>`;
        }
    }


    // =============================
    // FUNCIÓN PARA SEPARADORES
    // =============================
    function getDateLabel(dateStr) {
        const today = new Date();
        const msgDate = new Date(dateStr);

        const diff = Math.floor((today - msgDate) / (1000 * 60 * 60 * 24));

        if (diff === 0) return "Hoy";
        if (diff === 1) return "Ayer";
        if (diff === 2) return "Hace 2 días";

        return dateStr;
    }

    // =============================
    // 4. ENVIAR MENSAJE
    // =============================
    btnSend.addEventListener("click", sendMessage);

    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    async function sendMessage() {
        if (!activeContact) return;

        const text = messageInput.value.trim();
        if (!text) return;

        messageInput.value = "";

        try {
            const res = await fetch("/api/students/sendMessage.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    receiverId: activeContact,
                    message: text
                })
            });

            const data = await res.json();

            if (data.ok) {
                loadMessages();
            }

        } catch (e) {
            console.error(e);
        }
    }

    // =============================
    // 5. ESTADO ONLINE / ÚLTIMA CONEXIÓN
    // =============================
    async function updateContactStatus() {
        if (!activeContact) return;

        try {
            const res = await fetch(`/api/students/getOnlineStatus.php?id=${activeContact}`, {
                headers: { "Authorization": "Bearer " + token }
            });

            const data = await res.json();

            if (data.ok) {
                if (data.isOnline == 1) {
                    chatStatus.textContent = "En línea";
                    chatStatus.style.color = "green";
                } else {
                    chatStatus.textContent = "Últ. vez: " + data.lastSeen;
                    chatStatus.style.color = "gray";
                }
            }

        } catch (e) {
            console.error(e);
        }
    }

    // =============================
    // 6. REFRESCO AUTOMÁTICO
    // =============================
    setInterval(() => {
        if (activeContact) {
            loadMessages();
        }
    }, 3000);

    // =============================
    // 7. REGISTRAR ONLINE STATUS
    // =============================
    async function setOnline() {
        await fetch("/api/students/setOnlineStatus.php", {
            method: "POST",
            headers: {
                "Authorization": "Bearer " + token,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ isOnline: 1 })
        });
    }

    // ===============================================
    // LEER PARAMETROS DE LA URL
    // ===============================================
    function getParam(name) {
        const url = new URL(window.location.href);
        return url.searchParams.get(name);
    }

    // ===============================================
    // AUTOSELECCIONAR CONTACTO DESDE URL
    // ===============================================
    async function autoSelectContact() {
        const contactId = getParam("with");
        if (!contactId) return;

        // Esperar a que los contactos carguen
        const interval = setInterval(() => {
            const contactItems = document.querySelectorAll(".contact-item");
            if (contactItems.length > 0) {
                clearInterval(interval);

                const contactsPanel = document.getElementById("contactsPanel");
                const target = Array.from(contactsPanel.children)
                    .find(item => item.dataset.id == contactId);

                if (target) {
                    target.click(); // 🔥 Simulamos el clic real
                }
            }
        }, 200);
    }


    // =============================
    // 8. INICIO
    // =============================
    loadContext();
    setOnline();
    loadContacts();
    autoSelectContact();
});
