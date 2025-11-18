<div class="container adm-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="nav-container">
                    <h1 class="main-title">Panel de Admisiones</h1>
                    <p class="subtitle">Elige el servicio al que deseas acceder</p>
                    
                    <div class="row g-4 mt-3">
                        <div class="col-lg-4 col-md-6">
                            <button
                                class="btn nav-button btn-home w-100 d-flex align-items-center"
                                onclick="navigateTo('/admission/registerForm/formAdmission.php')"
                            >
                                <div class="nav-icon-wrapper">
                                    <i class="bi bi-pencil"></i>
                                </div>
                                <div class="nav-text-wrapper">
                                    <span class="nav-title">Inscribirme</span>
                                    <span class="nav-desc">
                                        Completar el formulario para el examen de admisión.
                                    </span>
                                </div>
                            </button>
                        </div>
                        
                        <div class="col-lg-4 col-md-6">
                            <button
                                class="btn nav-button btn-about w-100 d-flex align-items-center"
                                onclick="navigateTo('/admission/req/status.php')"
                            >
                                <div class="nav-icon-wrapper">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                                <div class="nav-text-wrapper">
                                    <span class="nav-title">Estado de mi solicitud</span>
                                    <span class="nav-desc">
                                        Consultar si mi solicitud fue recibida y su estado actual.
                                    </span>
                                </div>
                            </button>
                        </div>
                        
                        <div class="col-lg-4 col-md-6">
                            <button
                                class="btn nav-button btn-contact w-100 d-flex align-items-center"
                                onclick="navigateTo('#')"
                            >
                                <div class="nav-icon-wrapper">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </div>
                                <div class="nav-text-wrapper">
                                    <span class="nav-title">Ingreso de revisor</span>
                                    <span class="nav-desc">
                                        Acceso exclusivo para revisores de solicitudes.
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>