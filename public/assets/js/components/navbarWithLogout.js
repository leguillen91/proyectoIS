//import CONFIG from "../config.js";
export default class NavBarWithLogout extends HTMLElement {
    constructor(){
        super()
    }

    connectedCallback(){
        this.render();
    }

    render(){
        const baseURL = "/";
        this.innerHTML = `
            <nav class="navbar navbar-expand-lg bg-body-white border-bottom border-dark-subtle shadow-sm">
                <div class="container-fluid">
                    <a class="navbar-brand fs-2" href="/">UNAH</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse ms-3" id="navbarSupportedContent">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link fs-5" aria-current="page" href="/">Inicio</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-5" href="${baseURL}matricula/log-in/login-matricula.php">Matrícula</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-5" href="${baseURL}admisiones/index.php">Admisiones</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-5" href="${baseURL}biblioteca/log-in/login-biblioteca.php">Biblioteca Virtual</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fs-5" href="${baseURL}musica/log-in/login-musica.php">Música</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle fs-5" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Pregrado
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="${baseURL}docente/log-in/login-docente.php">Docente</a></li>
                                    <li><a class="dropdown-item" href="${baseURL}estudiantes/log-in/login-estudiante.php">Estudiante</a></li>
                                    <li><a class="dropdown-item" href="${baseURL}coordinador/log-in/login-coordinador.php">Coordinador</a></li>
                                    <li><a class="dropdown-item" href="${baseURL}jefe/log-in/login-jefe.php">Jefe de departamento</a></li>
                                </ul>
                            </li>
                        </ul>

                        <form method="POST" class="text-end me-3">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Cerrar sesión
                            </button>
                        </form>

                    </div>
                </div>
            </nav>
        `;
    }

}

customElements.define('nav-bar-logout',NavBarWithLogout);