import CONFIG from "../../config.js";
export class ApiService {
    constructor() {
        if (CONFIG.ENV == 'PROD'){
            this.baseUrl = `${CONFIG.API_BASE_URL}Admisiones`;
        }else{
            this.baseUrl = 'http://localhost:3000/public/api/Admisiones';
        }
    }

    async fetchCentrosRegionales() {
        const response = await fetch(`${this.baseUrl}/get/centrosRegionales/index.php`);
        return await response.json();
    }

    async fetchCarrerasPorCentro(idCentro) {
        const response = await fetch(`${this.baseUrl}/get/carrerasPorCentro/${idCentro}`);
        return await response.json();
    }

    async submitForm(formData) {
        const response = await fetch(`${this.baseUrl}/post/solicitudAdmision/`, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    }

    async sendConfirmationEmail(email){
        const response = await fetch(`${this.baseUrl}/post/correoConfirmacion/index.php`,{
            method: 'POST',
            body: JSON.stringify({ email: email })
        });
        return await response.json();
    }
}