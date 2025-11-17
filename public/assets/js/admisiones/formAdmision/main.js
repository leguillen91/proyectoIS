// admission-form/main.js
// Importa y registra los componentes de NavBar y Footer
import "../../components/NavBar.js";
import "../../components/Footer.js";
import { FormManager } from './managerForm.js';

document.addEventListener('DOMContentLoaded', () => {
    const formManager = new FormManager();
    formManager.init();
});