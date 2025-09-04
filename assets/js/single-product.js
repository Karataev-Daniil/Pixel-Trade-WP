const MAX_IMAGES = 10;
let newImageIndex = 0;

function setFieldMessage(id, message = '', type = '') {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = message;
    el.classList.remove('error', 'warning', 'success');
    if (type) el.classList.add(type);
}

function clearFieldMessage(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = '';
    el.classList.remove('error', 'warning', 'success');
}

function clearMessageForField(fieldIdOrName) {
    const msgEl = document.getElementById('message_' + fieldIdOrName) || 
                  document.getElementById(fieldIdOrName);
    if (msgEl) {
        msgEl.textContent = '';
        msgEl.classList.remove('error', 'warning', 'success');
    }

    if (fieldIdOrName === 'product_title' || fieldIdOrName === 'product_content') {
        const translationMessage = document.getElementById('translation-message');
        if (translationMessage) {
            translationMessage.textContent = '';
            translationMessage.classList.remove('error', 'warning', 'success');
        }
    }
}
document.querySelectorAll('input[name="product_title"], textarea[name="product_content"]').forEach(el => {
    const id = el.name;
    el.addEventListener('input', () => clearMessageForField(id));
    el.addEventListener('change', () => clearMessageForField(id));
});

function updateGalleryOrder() {
    const items = document.querySelectorAll('#gallery_preview .gallery-item');
    const order = Array.from(items).map(item => item.dataset.id);
    const input = document.getElementById('gallery_order_input');
    if (input) input.value = order.join(',');
}

function validateForm() {
    const activeTab = document.querySelector('.tab-content.active');
    if (!activeTab) return false;

    let firstInvalid = null;
    let hasErrors = false;
    const errors = [];

    const title = activeTab.querySelector('input[name="product_title"]');
    const content = activeTab.querySelector('textarea[name="product_content"]');

    ['translation-message', title?.dataset.msgId, content?.dataset.msgId, 'message_selected_categories', 'message_product_price', 'message_product_gallery'].forEach(clearFieldMessage);

    if (!title || !title.value.trim()) {
        if (title) setFieldMessage(title.dataset.msgId, title.placeholder || 'Введите название', 'warning');
        errors.push('Введите название');
        firstInvalid = firstInvalid || title;
        hasErrors = true;
    }

    if (!content || !content.value.trim()) {
        if (content) setFieldMessage(content.dataset.msgId, content.placeholder || 'Введите описание', 'warning');
        errors.push('Введите описание');
        firstInvalid = firstInvalid || content;
        hasErrors = true;
    }

    if (errors.length > 0) {
        const translationMessage = document.getElementById('translation-message');
        if (translationMessage) {
            translationMessage.textContent = errors.join(' и ');
            translationMessage.className = 'form-message body-small-regular warning';
        }
    }

    const selectedCategories = Array.from(document.querySelectorAll('#category-selectors .category-select'))
        .map(s => s.value)
        .filter(v => v);

    if (!selectedCategories.length) {
        setFieldMessage('message_selected_categories', translations.selectCategory, 'warning');
        firstInvalid = firstInvalid || document.getElementById('category-selectors');
        hasErrors = true;
    } else {
        const selectedInput = document.getElementById('selected_categories_input');
        if (selectedInput) selectedInput.value = selectedCategories.join(',');
    }

    const price = document.querySelector('input[name="product_price"]');
    const oldPrice = document.querySelector('input[name="product_old_price"]');
    const priceValue = parseFloat(price?.value || 0);
    const oldPriceValue = parseFloat(oldPrice?.value || 0);

    if ((isNaN(priceValue) || priceValue <= 0) && (isNaN(oldPriceValue) || oldPriceValue <= 0)) {
        setFieldMessage('message_product_price', price?.placeholder || 'Введите цену', 'warning');
        firstInvalid = firstInvalid || price || oldPrice;
        hasErrors = true;
    }

    const galleryItems = document.querySelectorAll('#gallery_preview .gallery-item');
    const galleryError = document.getElementById('message_product_gallery');
    if (galleryItems.length === 0) {
        setFieldMessage('message_product_gallery', translations.addImage || 'Добавьте хотя бы одно изображение', 'warning');
        firstInvalid = firstInvalid || document.getElementById('gallery_preview');
        hasErrors = true;
    } else if (galleryError) {
        galleryError.classList.remove('error', 'warning');
        galleryError.classList.add('success');
    }

    if (firstInvalid && firstInvalid.offsetParent !== null) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return !hasErrors;
}

window.checkGalleryLimit = function(input) {
    const files = Array.from(input.files || []);
    const preview = document.getElementById('gallery_preview');
    const errorField = document.getElementById('message_product_gallery');

    if (!preview || !errorField) return;

    errorField.textContent = '';
    errorField.classList.remove('error', 'warning', 'success');

    if (!files.length) return;

    const existingCount = preview.querySelectorAll('.gallery-item').length;
    const remaining = MAX_IMAGES - existingCount;
    if (remaining <= 0) {
        errorField.textContent = `Достигнут лимит ${MAX_IMAGES} изображений.`;
        errorField.classList.add('error');
        input.value = '';
        return;
    }

    const toAdd = files.slice(0, remaining);

    toAdd.forEach(file => {
        const currentIndex = newImageIndex++;
        const div = document.createElement('div');
        div.className = 'gallery-item';
        div.dataset.id = 'new-' + currentIndex;

        const loaderContainer = document.createElement('div');
        loaderContainer.className = 'gallery-loader-container';
        const loader = document.createElement('div');
        loader.className = 'gallery-loading';
        loaderContainer.appendChild(loader);
        div.appendChild(loaderContainer);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'gallery-remove link-small-default';
        removeBtn.title = 'Удалить';
        removeBtn.setAttribute('aria-label', 'Удалить фото');
        removeBtn.textContent = '✕';
        div.appendChild(removeBtn);

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'new_file_indexes[]';
        hidden.value = currentIndex;
        div.appendChild(hidden);

        const addButton = preview.querySelector('.btn-upload') || preview.querySelector('.btn-upload__icon')?.parentNode;
        preview.insertBefore(div, addButton || null);

        const reader = new FileReader();
        reader.onload = function(e) {
            loaderContainer.remove();
            const img = document.createElement('img');
            img.src = e.target.result;
            div.prepend(img);
            updateGalleryOrder();
        };
        reader.readAsDataURL(file);
    });

    input.value = '';
    updateGalleryOrder();
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input, textarea, select').forEach(el => {
        const id = el.id || el.name;
        el.addEventListener('input', () => clearMessageForField(id));
        el.addEventListener('change', () => clearMessageForField(id));
    });

    const form = document.getElementById('create-product-form');
    if (form) {
        form.addEventListener('submit', e => {
            e.preventDefault();
            if (validateForm()) form.submit();
        });
    }

    const gallery = document.getElementById('gallery_preview');
    if (gallery) {
        gallery.addEventListener('click', e => {
            if (!e.target.classList.contains('gallery-remove')) return;
            const item = e.target.closest('.gallery-item');
            if (!item) return;
            const removeInput = document.getElementById('remove_gallery_ids_input');

            if (item.dataset.id && !item.dataset.id.startsWith('new-')) {
                const currentValue = removeInput.value ? removeInput.value.split(',') : [];
                currentValue.push(item.dataset.id);
                removeInput.value = currentValue.join(',');
            }
            item.remove();
            updateGalleryOrder();

            const errorField = document.getElementById('message_product_gallery');
            if (errorField) {
                errorField.classList.remove('error', 'warning');
                errorField.classList.add('success');
            }
        });

        if (typeof Sortable !== 'undefined') {
            new Sortable(gallery, { animation: 150, ghostClass: 'sortable-ghost', onEnd: updateGalleryOrder });
        }
    }

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const tab = document.getElementById(btn.dataset.tab);
            if (tab) tab.classList.add('active');
        });
    });

    updateGalleryOrder();
});
