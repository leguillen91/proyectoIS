import "../../components/navbar.js";
import "../../components/footer.js";
import { FormManager } from './managerForm.js';

document.addEventListener('DOMContentLoaded', () => {
    const formManager = new FormManager();
    formManager.init();
});