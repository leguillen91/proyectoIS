export default class Footer extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.render();
    }

    render() {
        this.innerHTML = `
        <footer class="bg-primary text-white text-center py-3 mt-3">
    <p class="mb-0 small">
      <strong>PROJECT UNAH</strong> © <?= date('Y') ?> — Derechos Reservados
    </p>
  </footer>
        `;
    }
}

customElements.define("custom-footer", Footer);