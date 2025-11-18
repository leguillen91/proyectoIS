//import CONFIG from "../config.js";
export default class NavBar extends HTMLElement {
    constructor(){
        super()
    }

    connectedCallback(){
        this.render();
    }

    render(){
        const baseURL = "/";
        this.innerHTML = `
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm"
     style="background-color: #003e8a;">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-white" href="#">
        <img src="../../assets/Logo.png" alt="Logo UNAH" class="navbar-logo me-2" style="height:45px;">
        UNAH Systems
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContenido">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarContenido">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active fw-semibold" href="/index.php">Inicio</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold" href="../../../index.php" data-bs-toggle="dropdown">Estudiantes</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Pregrado</a></li>
              <li><a class="dropdown-item" href="#">Postgrado</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link active fw-semibold" href="../../../admission/index.php">Admisiones</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="../../../views/library/virtualLibrary.html">Biblioteca Virtual</a>
          </li>
          
          <li class="nav-item" id="linkSoftware">
            <a class="nav-link fw-semibold" href="../../../views/librarySoftware/librarySoftware.html">Módulo Software</a>
          </li>
          <li class="nav-item" id="navSessionArea">
          <a id="btnLogin" class="nav-link fw-semibold" href="../../../views/login.html">Acceder</a>
        </li>


        </ul>
      </div>
    </div>
  </nav>
        `;
    }

}

customElements.define('nav-bar',NavBar);




