// Image Cropper with Cropper.js (loaded via CDN in layout.blade.php)
// No need to import - Cropper is available globally via CDN

window.ImageCropper = class {
    constructor() {
        this.cropper = null;
        this.currentImage = null;
        this.currentIndex = null;
        this.onSaveCallback = null;
    }

    open(imageUrl, imageIndex, onSave) {
        this.currentImage = imageUrl;
        this.currentIndex = imageIndex;
        this.onSaveCallback = onSave;

        // Mostrar modal
        const modal = document.getElementById('cropperModal');
        if (!modal) {
            console.error('Cropper modal not found');
            return;
        }

        modal.classList.remove('hidden');

        // Cargar imagen en el cropper
        const imageElement = document.getElementById('cropperImage');
        imageElement.src = imageUrl;

        // Destruir cropper anterior si existe
        if (this.cropper) {
            this.cropper.destroy();
        }

        // Inicializar Cropper.js después de que la imagen se cargue
        imageElement.onload = () => {
            this.cropper = new Cropper(imageElement, {
                aspectRatio: NaN, // Libre (sin restricción de aspecto)
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                responsive: true,
                background: true,
                modal: true,
                zoomable: true,
                zoomOnWheel: true,
                wheelZoomRatio: 0.1,
            });
        };
    }

    close() {
        const modal = document.getElementById('cropperModal');
        if (modal) {
            modal.classList.add('hidden');
        }

        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        this.currentImage = null;
        this.currentIndex = null;
        this.onSaveCallback = null;
    }

    save() {
        if (!this.cropper) {
            console.error('No cropper instance available');
            return;
        }

        // Obtener la imagen recortada como blob
        this.cropper.getCroppedCanvas({
            maxWidth: 1920,
            maxHeight: 1920,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        }).toBlob((blob) => {
            if (this.onSaveCallback) {
                // Convertir blob a base64 para enviar a Livewire
                const reader = new FileReader();
                reader.onloadend = () => {
                    const base64data = reader.result;
                    this.onSaveCallback(this.currentIndex, base64data);
                    this.close();
                };
                reader.readAsDataURL(blob);
            }
        }, 'image/jpeg', 0.9);
    }

    // Métodos de control
    rotate(degrees) {
        if (this.cropper) {
            this.cropper.rotate(degrees);
        }
    }

    flip(direction) {
        if (this.cropper) {
            if (direction === 'horizontal') {
                this.cropper.scaleX(-this.cropper.getData().scaleX || -1);
            } else {
                this.cropper.scaleY(-this.cropper.getData().scaleY || -1);
            }
        }
    }

    zoom(ratio) {
        if (this.cropper) {
            this.cropper.zoom(ratio);
        }
    }

    reset() {
        if (this.cropper) {
            this.cropper.reset();
        }
    }

    setAspectRatio(ratio) {
        if (this.cropper) {
            this.cropper.setAspectRatio(ratio);
        }
    }
};

// Crear instancia global
window.imageCropper = new window.ImageCropper();
