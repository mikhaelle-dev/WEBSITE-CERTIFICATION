document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profilePhotoForm');
    const input = document.getElementById('foto_profil');
    const editButton = document.getElementById('editProfilePhoto');
    const modalElement = document.getElementById('profilePhotoCropModal');
    const cropImage = document.getElementById('profilePhotoCropImage');
    const cropButton = document.getElementById('applyProfilePhotoCrop');
    const errorMessage = document.getElementById('profilePhotoCropError');

    if (!form || !input || !editButton || !modalElement || !cropImage || !cropButton || !errorMessage) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    let cropper = null;
    let sourceUrl = null;
    let imageReady = false;
    let modalReady = false;

    const showError = (message) => {
        errorMessage.textContent = message;
        errorMessage.classList.remove('d-none');
    };

    const clearCropper = () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (sourceUrl) {
            URL.revokeObjectURL(sourceUrl);
            sourceUrl = null;
        }
    };

    const initializeCropper = () => {
        if (!imageReady || !modalReady || cropper) {
            return;
        }

        if (typeof Cropper === 'undefined') {
            showError('Fitur potong foto gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.');
            return;
        }

        try {
            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false
            });
        } catch (error) {
            console.error('Profile photo cropper initialization failed:', error);
            showError('Foto gagal disiapkan untuk dipotong. Silakan pilih gambar lain.');
        }
    };

    editButton.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }

        errorMessage.classList.add('d-none');
        clearCropper();
        imageReady = false;
        modalReady = false;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            input.value = '';
            modal.show();
            showError('Pilih gambar berformat JPEG, PNG, atau WebP.');
            return;
        }

        sourceUrl = URL.createObjectURL(file);
        cropImage.onload = () => {
            imageReady = true;
            initializeCropper();
        };
        cropImage.onerror = () => {
            showError('Gambar tidak dapat dibuka. Pilih file gambar lain.');
        };
        modalElement.addEventListener('shown.bs.modal', () => {
            modalReady = true;
            initializeCropper();
        }, { once: true });
        modal.show();
        cropImage.src = sourceUrl;
    });

    cropButton.addEventListener('click', () => {
        if (!cropper) {
            showError('Pilih gambar yang valid dan tunggu sampai siap dipotong.');
            return;
        }

        cropButton.disabled = true;
        cropButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Menyimpan...';

        const canvas = cropper.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingQuality: 'high'
        });
        if (!canvas) {
            cropButton.disabled = false;
            cropButton.innerHTML = '<i class="bi bi-check2"></i> Gunakan foto';
            showError('Foto gagal diproses. Silakan coba gambar lain.');
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                cropButton.disabled = false;
                cropButton.innerHTML = '<i class="bi bi-check2"></i> Gunakan foto';
                showError('Foto gagal diproses. Silakan coba gambar lain.');
                return;
            }

            const croppedFile = new File([blob], 'foto-profil.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            input.files = transfer.files;
            form.submit();
        }, 'image/jpeg', 0.9);
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        clearCropper();
        imageReady = false;
        modalReady = false;
        cropImage.onload = null;
        cropImage.onerror = null;
        cropImage.removeAttribute('src');
        if (!cropButton.disabled) {
            input.value = '';
            errorMessage.classList.add('d-none');
        }
    });
});
