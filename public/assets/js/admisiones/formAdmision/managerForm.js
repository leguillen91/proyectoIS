import { FormValidator } from '../modules/FormValidator.js';
import Toast from './../../utilities/toast.js';

export class FormManager {
  constructor() {
    this.form = document.getElementById('admissionForm');
    this.submitButton = this.form?.querySelector('button[type="submit"]');
    this.validator = new FormValidator();
    this.carrerasDisponibles = [];
  }

  init() {
    if (!this.form) return;

    this.setupInitialState();
    this.setupEventListeners();
    this.loadCentrosRegionales();
  }

  setupInitialState() {
    if (!this.submitButton) return;
    this.submitButton.disabled = true;
    this.submitButton.title =
      'Complete todos los campos correctamente para habilitar el envío';
  }

  setupEventListeners() {
    if (!this.form) return;

    // Eventos generales de validación
    this.form.querySelectorAll('input, select').forEach((field) => {
      field.addEventListener('input', () => this.validator.validateForm());
      field.addEventListener('change', () => this.validator.validateForm());
    });

    // Eventos específicos
    document.getElementById('nombre_completo')?.addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(
        /[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g,
        ''
      );
      if (this.validator.validateNombreCompleto())
        this.validator.markValid(e.target);

      this.validator.validateForm();
    });

    document
      .getElementById('tipo_identificacion')
      ?.addEventListener('change', (e) => {
        this.handleTipoIdentificacionChange(e.target.value);
        this.validator.markValid(e.target);
        this.validator.validateForm();
      });

    document.getElementById('identidad')?.addEventListener('input', (e) => {
      if (this.validator.validateIdentificacion())
        this.validator.markValid(e.target);
      this.validator.validateForm();
    });

    document.getElementById('pasaporte')?.addEventListener('input', (e) => {
      if (this.validator.validateIdentificacion())
        this.validator.markValid(e.target);
      this.validator.validateForm();
    });

    document.getElementById('telefono')?.addEventListener('input', (e) => {
      if (this.validator.validateTelefono()) this.validator.markValid(e.target);
      this.validator.validateForm();
    });

    document.getElementById('correo')?.addEventListener('input', (e) => {
      if (this.validator.validateEmail()) this.validator.markValid(e.target);
      this.validator.validateForm();
    });

    document
      .getElementById('certificado')
      ?.addEventListener('input', (e) => {
        if (this.validator.validateCertificado())
          this.validator.markValid(e.target);
        this.validator.validateForm();
      });

    document
      .getElementById('centro_regional')
      ?.addEventListener('change', (e) => {
        const idCentro =
          e.target.options[e.target.selectedIndex].dataset.idCentro;
        if (idCentro) this.loadCarrerasPorCentro(idCentro);

        this.validator.markValid(e.target);
        this.validator.validateForm();
      });

    document
      .getElementById('carrera_principal')
      ?.addEventListener('change', (e) => {
        this.updateCarreraSecundaria();
        this.validator.markValid(e.target);
        this.validator.validateForm();
      });

    document
      .getElementById('carrera_secundaria')
      ?.addEventListener('change', (e) => {
        this.validator.markValid(e.target);
        this.validator.validateForm();
      });

    this.form.addEventListener('submit', (e) => this.handleSubmit(e));
  }

  // ================================
  //  SIN API datos de prueba (mock)
  // ================================

  async loadCentrosRegionales() {
    try {
      const data = {
        data: [
          {
            codigo_centro: 'CU',
            id_centro: 1,
            nombre_centro: 'Ciudad Universitaria - Tegucigalpa',
          },
          {
            codigo_centro: 'UNAHVS',
            id_centro: 2,
            nombre_centro: 'UNAH-VS - San Pedro Sula',
          },
        ],
      };

      this.populateCentrosRegionales(data);
    } catch (error) {
      console.error('Error cargando centros regionales:', error);
      alert('Error al cargar centros regionales');
    }
  }

  populateCentrosRegionales(data) {
    const select = document.getElementById('centro_regional');
    if (!select) return;

    select.innerHTML =
      '<option value="" selected disabled>Seleccione un centro regional</option>';

    data.data.forEach((centro) => {
      select.innerHTML += `<option value="${centro.codigo_centro}" data-id-centro="${centro.id_centro}">${centro.nombre_centro}</option>`;
    });

    select.disabled = false;
  }

  async loadCarrerasPorCentro(idCentro) {
    if (!idCentro) return;

    try {
      const carrerasPorCentro = {
        1: [
          {
            codigo_carrera: 'IS',
            nombre_carrera: 'Ingeniería en Sistemas',
          },
          {
            codigo_carrera: 'PSI',
            nombre_carrera: 'Psicología',
          },
        ],
        2: [
          {
            codigo_carrera: 'ADM',
            nombre_carrera: 'Administración de Empresas',
          },
          {
            codigo_carrera: 'DER',
            nombre_carrera: 'Derecho',
          },
        ],
      };

      this.carrerasDisponibles = carrerasPorCentro[idCentro] || [];
      this.populateCarreraPrincipal();
      this.updateCarreraSecundaria();
    } catch (error) {
      console.error('Error cargando carreras:', error);
      const newToast = new Toast(document.getElementById('toast'));
      newToast.setBody('Error al cargar carreras');
      newToast.setDanger();
      newToast.toggleToast();
    }
  }

  populateCarreraPrincipal() {
    const select = document.getElementById('carrera_principal');
    if (!select) return;

    select.innerHTML =
      '<option value="" selected disabled>Seleccione una carrera</option>';

    this.carrerasDisponibles.forEach((carrera) => {
      select.innerHTML += `<option value="${carrera.codigo_carrera}">${carrera.nombre_carrera}</option>`;
    });

    select.disabled = false;
  }

  updateCarreraSecundaria() {
    const carreraPrincipal = document.getElementById('carrera_principal');
    const carreraSecundaria = document.getElementById('carrera_secundaria');
    if (!carreraPrincipal || !carreraSecundaria) return;

    const carreraPrincipalId = carreraPrincipal.value;

    carreraSecundaria.innerHTML =
      '<option value="">Seleccione una carrera</option>';

    this.carrerasDisponibles.forEach((carrera) => {
      if (carrera.codigo_carrera !== carreraPrincipalId) {
        carreraSecundaria.innerHTML += `<option value="${carrera.codigo_carrera}">${carrera.nombre_carrera}</option>`;
      }
    });

    carreraSecundaria.disabled = !carreraPrincipalId;
  }


  handleTipoIdentificacionChange(tipo) {
    const grupoIdentidad = document.getElementById('grupo_identidad');
    const grupoPasaporte = document.getElementById('grupo_pasaporte');
    const campoIdentidad = document.getElementById('identidad');
    const campoPasaporte = document.getElementById('pasaporte');

    if (!grupoIdentidad || !grupoPasaporte) return;

    if (tipo === 'Hondureño(a)') {
      grupoIdentidad.style.display = 'block';
      if (campoIdentidad) {
        campoIdentidad.disabled = false;
        campoIdentidad.required = true;
      }

      grupoPasaporte.style.display = 'none';
      if (campoPasaporte) {
        campoPasaporte.disabled = true;
        campoPasaporte.required = false;
        campoPasaporte.value = '';
      }
    } else if (tipo === 'Extranjero(a)') {
      grupoPasaporte.style.display = 'block';
      if (campoPasaporte) {
        campoPasaporte.disabled = false;
        campoPasaporte.required = true;
      }

      grupoIdentidad.style.display = 'none';
      if (campoIdentidad) {
        campoIdentidad.disabled = true;
        campoIdentidad.required = false;
        campoIdentidad.value = '';
      }
    }
  }

  async handleSubmit(e) {
    e.preventDefault();

    if (!this.validator.validateForm()) {
      alert('Por favor complete todos los campos correctamente');
      return;
    }

    if (!this.submitButton) return;

    this.submitButton.disabled = true;
    this.submitButton.textContent = 'Enviando...';

    try {
      // Por ahora solo simulacion con éxito mientras no hay API:
      const formData = new FormData(this.form);
      console.log('Datos enviados:', Object.fromEntries(formData));

      const result = { status: 200 };
      this.submitButton.type = '';
      this.submitButton.classList.remove('btn-primary');
      this.submitButton.classList.add('btn-outline-secondary');

      this.handleSubmissionResult(result, formData);
    } catch (error) {
      console.error('Error:', error);
      alert('Error de conexión: ' + error.message);
    } finally {
      this.submitButton.disabled = false;
      this.submitButton.textContent = 'Enviar Solicitud';
    }
  }

  handleSubmissionResult(result, formData) {
    if (result.status === 200) {
      const newToast = new Toast(document.getElementById('toast'));
      newToast.setBody('Tu solicitud fue enviada (demo, sin guardar en BD)');
      newToast.setSuccess();
      newToast.toggleToast();

      // Redirigir luego de 10s
      setTimeout(() => (window.location.href = '/admisiones/index.php'), 10000);
    } else {
      this.validator.displayErrors(result.errors || {});
      alert(result.message || 'Error al procesar el formulario');
    }
  }
}
