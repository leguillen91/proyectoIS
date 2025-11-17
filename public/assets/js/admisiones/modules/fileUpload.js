export const setupFileUploads = () => {
    setupFileUpload('csv', 20);
};

const setupFileUpload = (type, maxSizeMB) => {
    const area = document.getElementById(`${type}UploadArea`);
    const input = document.getElementById(`${type}Input`);
    const info = document.getElementById(`${type}Info`);
    const progress = document.getElementById(`${type}Progress`);
    
    if (!area || !input) return;

    // Click en el área
    area.addEventListener('click', () => input.click());
    
    // Cambio en el input de archivo
    input.addEventListener('change', () => handleFileSelect(input, area, info, progress, maxSizeMB));
    
    // Configurar drag and drop
    setupDragAndDrop(area, input, info, progress, maxSizeMB);
};

const setupDragAndDrop = (area, input, info, progress, maxSizeMB) => {
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        area.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        area.addEventListener(eventName, () => area.classList.add('active'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        area.addEventListener(eventName, () => area.classList.remove('active'), false);
    });

    area.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        input.files = files;
        handleFileSelect(input, area, info, progress, maxSizeMB);
    });
};

const handleFileSelect = (input, area, info, progress, maxSizeMB) => {
    const file = input.files[0];
    if (!file) return;
    
    const maxSize = maxSizeMB * 1024 * 1024;
    
    if (file.size > maxSize) {
        area.classList.add('error');
        input.value = '';
        return;
    }
    
    area.classList.remove('error');
    area.classList.add('active');
    
    let fileSize = (file.size / (1024 * 1024)).toFixed(2);
    let extension = 'MB';
    if (fileSize < 1){
        fileSize = (file.size * Math.pow(10, -3)).toFixed(2) ;
        extension = 'KB'
    }
    info.innerHTML = `
        <span class="file-name">${file.name}</span>
        <span class="file-size">(${fileSize} ${extension})</span>
    `;
    
    simulateUploadProgress(progress);
};

const simulateUploadProgress = (progress) => {
    progress.classList.remove('d-none');
    const progressBar = progress.querySelector('.progress-bar');
    progressBar.style.width = '0%';
    
    let width = 0;
    const interval = setInterval(() => {
        if (width >= 100) {
            clearInterval(interval);
        } else {
            width += 10;
            progressBar.style.width = `${width}%`;
        }
    }, 50);
};