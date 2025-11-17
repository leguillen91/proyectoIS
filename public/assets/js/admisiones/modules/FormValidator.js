export class FormValidator {
    constructor() {
        this.form = document.getElementById('admissionForm');
        this.submitButton = document.querySelector('#admissionForm button[type="submit"]');
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;

        inputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                this.markInvalid(input, 'Este campo es requerido');
                isValid = false;
            }
        });

        isValid = this.validateNombreCompleto() && isValid;
        isValid = this.validateIdentificacion() && isValid;
        isValid = this.validateTelefono() && isValid;
        isValid = this.validateEmail() && isValid;
        isValid = this.validateCertificado() && isValid;
        isValid = this.validateCarreras() && isValid;

        this.submitButton.disabled = !isValid;
        return isValid;
    }

    validateNombreCompleto() {
        const input = document.getElementById('nombre_completo');
        const value = input.value.trim();
        const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]{2,50}$/;
        
        if (!regex.test(value)) {
            this.markInvalid(input, 'Solo letras y espacios (2-50 caracteres)');
            return false;
        }
        
        this.markValid(input);
        return true;
    }

    validateIdentificacion() {
        const tipo = document.getElementById('tipo_identificacion').value;
        
        if (tipo === 'Hondureño(a)') {
            return this.validateIdentidadHonduras();
        } else if (tipo === 'Extranjero(a)') {
            return this.validatePasaporte();
        }
        
        return true;
    }

    validateIdentidadHonduras() {
        const input = document.getElementById('identidad');
        const value = input.value.trim();
        
        if (!/^\d{4}-?\d{4}-?\d{5}$/.test(value)) {
            this.markInvalid(input, 'Formato inválido. Use: 0000-0000-00000');
            return false;
        }

        const digits = value.replace(/-/g, '');
        const codigoMunicipio = parseInt(digits.substr(0, 4));
        const anio = parseInt(digits.substr(4, 4));
        
        if (!this.validarCodigoMunicipio(codigoMunicipio)) {
            this.markInvalid(input, 'Código de municipio inválido');
            return false;
        }

        if (anio <= 1950) {
            this.markInvalid(input, 'Año debe ser mayor a 1950');
            return false;
        }

        this.markValid(input);
        return true;
    }

    validarCodigoMunicipio(codigo) {
        const rangosValidos = [
            [101, 108], [201, 210], [301, 321], [401, 423],
            [501, 512], [601, 616], [701, 719], [801, 828],
            [901, 906], [1001, 1017], [1101, 1104], [1201, 1219],
            [1301, 1328], [1401, 1416], [1501, 1523], [1601, 1628],
            [1701, 1709], [1801, 1811]
        ];
        
        return rangosValidos.some(([min, max]) => codigo >= min && codigo <= max);
    }

    validatePasaporte() {
        const input = document.getElementById('pasaporte');
        const value = input.value.trim();
        const regex = /^[A-PR-WY][1-9]\d{5,8}$/;
        
        if (!regex.test(value)) {
            this.markInvalid(input, 'Formato de pasaporte inválido');
            return false;
        }
        
        this.markValid(input);
        return true;
    }

    validateTelefono() {
        const input = document.getElementById('telefono');
        const value = input.value.trim();
        const regex = /^(\+?(504)\s?)?[0-9]{3,4}[- ]?[0-9]{4}$/;
        
        if (!regex.test(value)) {
            this.markInvalid(input, 'Número inválido para Honduras');
            return false;
        }
        
        this.markValid(input);
        return true;
    }

    validateEmail() {
        const input = document.getElementById('correo');
        const value = input.value.trim();
        const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        
        if (!regex.test(value)) {
            this.markInvalid(input, 'Correo electrónico inválido');
            return false;
        }
        
        this.markValid(input);
        return true;
    }

    validateCertificado() {
        const input = document.getElementById('certificado');
        const file = input.files[0];
        
        if (!file) {
            this.markInvalid(input, 'Debe subir un archivo');
            return false;
        }

        const validTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/jpg', 'image/webp'];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!validTypes.includes(file.type)) {
            this.markInvalid(input, 'Solo se aceptan JPG, PNG, WEBP o PDF');
            return false;
        }

        if (file.size > maxSize) {
            this.markInvalid(input, 'El archivo es demasiado grande (máx. 2MB)');
            return false;
        }

        this.markValid(input);
        return true;
    }

    validateCarreras() {
        const carreraPrincipal = document.getElementById('carrera_principal');
        const carreraSecundaria = document.getElementById('carrera_secundaria');
        
        if (!carreraPrincipal.value) {
            this.markInvalid(carreraPrincipal, 'Seleccione una carrera principal');
            return false;
        }

        if (carreraSecundaria.value && carreraPrincipal.value === carreraSecundaria.value) {
            this.markInvalid(carreraSecundaria, 'La carrera secundaria no puede ser igual a la principal');
            return false;
        }

        this.markValid(carreraPrincipal);
        this.markValid(carreraSecundaria);
        return true;
    }

    markInvalid(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
        }
    }

    markValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }

    displayErrors(errors) {
        Object.entries(errors).forEach(([fieldName, message]) => {
            const input = this.form.querySelector(`[name="${fieldName}"]`);
            if (input) this.markInvalid(input, message);
        });
    }
}