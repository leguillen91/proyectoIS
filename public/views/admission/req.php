<main class="rs-shell">
        <!-- Panel izquierdo: info -->
        <section class="rs-panel-info">
            <div class="rs-info-content">
                <div class="rs-badge">
                    <span class="rs-badge-icon">
                        <i class="bi bi-envelope-check"></i>
                    </span>
                    Seguimiento de solicitud
                </div>

                <h2 class="rs-info-title">
                    Consulta el estado de tu proceso de admisión
                </h2>

                <p class="rs-info-text">
                    Ingresa el correo electrónico que utilizaste al momento de inscribirte.
                    Te enviaremos un mensaje con el estado actual de tu solicitud
                    y los siguientes pasos a seguir.
                </p>

                <p class="rs-info-hint">
                    <i class="bi bi-info-circle"></i>
                    Asegúrate de revisar también tu bandeja de correo no deseado.
                </p>
            </div>
        </section>

        <!-- Panel derecho: formulario -->
        <section class="rs-panel-form">
            <h1 class="rs-form-title">Revisar estado de solicitud</h1>
            <p class="rs-form-subtitle">
                Te enviaremos un correo con el estado actual de tu solicitud.
            </p>

            <form id="statusForm">
                <div class="rs-input-wrapper">
                    <label for="email" class="rs-label">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        class="rs-input"
                        placeholder="tucorreo@ejemplo.com"
                        required
                    />
                </div>

                <div class="rs-button-row">
                    <button type="button" class="rs-btn rs-btn--cancel" id="cancelBtn">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancelar</span>
                    </button>

                    <button type="submit" class="rs-btn rs-btn--send" id="submitBtn">
                        <span id="sendText">
                            <i class="bi bi-send"></i>
                            <span>Enviar estado</span>
                        </span>
                        <span id="loadingText" style="display: none;">
                            <span class="rs-spinner"></span>
                            <span>Enviando...</span>
                        </span>
                    </button>
                </div>
            </form>
        </section>
    </main>

    <section id="response" class="rs-response"></section>