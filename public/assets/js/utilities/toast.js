export default class Toast {
    constructor(toastElement){
        this.toastElement = toastElement;
        this.body = toastElement.querySelector('.toast-body');
        if (this.toastElement){
            this.instance = bootstrap.Toast.getOrCreateInstance(this.toastElement);
        }
    }

    toggleToast(ensureClosed = false) {
        if (ensureClosed){
            this.instance.hide();
            return;
        }

        if (this.toastElement.classList.contains('show')) {
            this.instance.hide();
        } else {
            this.instance.show();
        }
        return;
    }

    setBody(content){
        this.body.innerText = content;
    }

    setDanger(){
        this.toastElement.classList.toggle('bg-danger');
    }
    setSuccess(){
        this.toastElement.classList.toggle('bg-success');
    }
}