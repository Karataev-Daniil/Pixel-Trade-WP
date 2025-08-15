let newImageIndex = 0;

window.checkGalleryLimit = function (input) {
    const files = input.files;
    const existingCount = document.querySelectorAll('#gallery_preview .gallery-item').length;

    if (existingCount + files.length > 6) {
        alert('Можно загрузить не более 6 изображений.');
        input.value = '';
        return;
    }

    const preview = document.getElementById('gallery_preview');

    Array.from(files).forEach((file) => {
        const reader = new FileReader();
        const currentIndex = newImageIndex++;

        reader.onload = function (e) {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            div.dataset.id = 'new-' + currentIndex;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'new_file_indexes[]';
            hidden.value = currentIndex;

            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="gallery-remove link-small-default" title="Удалить">✕</button>
            `;

            div.appendChild(hidden);
            preview.appendChild(div);

            updateGalleryOrder();
        };

        reader.readAsDataURL(file);
    });

    input.value = '';
};

function updateGalleryOrder() {
    const items = document.querySelectorAll('#gallery_preview .gallery-item');
    const order = Array.from(items).map(item => item.dataset.id);
    document.getElementById('gallery_order_input').value = order.join(',');
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('gallery_preview').addEventListener('click', function (e) {
        if (e.target.classList.contains('gallery-remove')) {
            const item = e.target.closest('.gallery-item');
            const removeInput = document.getElementById('remove_gallery_ids_input');

            if (item.dataset.id && !item.dataset.id.startsWith('new-')) {
                const currentValue = removeInput.value ? removeInput.value.split(',') : [];
                currentValue.push(item.dataset.id);
                removeInput.value = currentValue.join(',');
            }

            item.remove();
            updateGalleryOrder();
        }
    });

    // Инициализация порядка при загрузке страницы
    updateGalleryOrder();
});
