const MAX_IMAGES = 10;
let newImageIndex = 0;
let translationChecked = false;

function updateCounter(el) {
    const counter = el.closest('.tab-content').querySelector('.form-hint');
    if (!counter) return;
    counter.textContent = `${el.value.length} / 2000`; 
}

function attachLengthValidation() {
    document.querySelectorAll('textarea[name="product_content"], textarea[name="description_en"], textarea[name="description_ro"]').forEach(el => {
        el.addEventListener('input', () => {
            if (el.value.length > 2000) el.value = el.value.slice(0, 2000);
            updateCounter(el);

            const activeTab = el.closest('.tab-content');
            const title = activeTab.querySelector('input');
            const content = activeTab.querySelector('textarea');
            const errorField = document.getElementById('translation-message');

            if (!errorField) return;

            let errors = [];
            const titleEmpty = !title.value.trim();
            const contentEmpty = !content.value.trim();
            const titleShort = !titleEmpty && title.value.length < 3;
            const contentShort = !contentEmpty && content.value.length < 5;

            if (titleEmpty && contentEmpty) errors.push('Введите название и описание');
            else if (titleEmpty) errors.push('Введите название');
            else if (contentEmpty) errors.push('Введите описание');

            if (titleShort && contentShort) errors.push('Название и описание слишком короткие');
            else if (titleShort) errors.push('Название слишком короткое (мин. 3 символа)');
            else if (contentShort) errors.push('Описание слишком короткое (мин. 5 символов)');

            if (errors.length) {
                errorField.textContent = errors.join('; ');
                errorField.classList.add('warning');
            } else {
                errorField.textContent = '';
                errorField.classList.remove('warning', 'error', 'success');
            }
        });
    });
}

function updateGalleryOrder() {
    const items = document.querySelectorAll('#gallery_preview .gallery-item');
    const order = Array.from(items).map(item => item.dataset.id);
    const input = document.getElementById('gallery_order_input');
    if (input) input.value = order.join(',');
}

window.checkGalleryLimit = function(input) {
    const files = Array.from(input.files || []);
    if (!files.length) return;

    const preview = document.getElementById('gallery_preview');
    const existingCount = preview.querySelectorAll('.gallery-item').length;
    const remaining = MAX_IMAGES - existingCount;
    if (remaining <= 0) {
        setFieldMessage('message_product_gallery', `Достигнут лимит ${MAX_IMAGES} изображений.`, 'error');
        return;
    }

    const toAdd = files.slice(0, remaining);

    toAdd.forEach(file => {
        const currentIndex = newImageIndex++;
        const div = document.createElement('div');
        div.className = 'gallery-item';
        div.dataset.id = 'new-' + currentIndex;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            div.prepend(img);
            setFieldMessage('message_product_gallery', 'Фото успешно добавлено', 'success');
        };
        reader.readAsDataURL(file);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'gallery-remove';
        removeBtn.textContent = '✕';
        div.appendChild(removeBtn);

        preview.insertBefore(div, preview.querySelector('.btn-upload') || null);
    });

    const dt = new DataTransfer();
    toAdd.forEach(f => dt.items.add(f));
    input.files = dt.files;

    updateGalleryOrder();
}

function checkTranslationsBeforeSubmit() {
    const tabs = document.querySelectorAll('.tab-content');
    const translationMessage = document.getElementById('translation-message');
    if (!translationMessage) return true;

    let missingTranslations = [];

    tabs.forEach(tab => {
        const lang = tab.id.split('-')[1];
        const title = tab.querySelector('input');
        const content = tab.querySelector('textarea');

        if (title && content && !title.value.trim() && !content.value.trim()) {
            missingTranslations.push(lang);
        }
    });

    if (missingTranslations.length && !translationChecked) {
        translationMessage.textContent = `У вас нет переводов для языков: ${missingTranslations.join(', ')}. Сгенерируйте их — это бесплатно!`;
        translationMessage.classList.remove('error', 'warning', 'success');
        translationMessage.classList.add('info');
        translationChecked = true;
        return false;
    }

    return true;
}

function clearMessageForField(fieldIdOrName) {
    const msgEl = document.getElementById('message_' + fieldIdOrName) || 
                  document.getElementById(fieldIdOrName);
    if (msgEl) {
        msgEl.textContent = '';
        msgEl.classList.remove('error', 'warning', 'success');
    }

    if (fieldIdOrName.startsWith('product_title') || fieldIdOrName.startsWith('product_content')) {
        const translationMessage = document.getElementById('translation-message');
        if (translationMessage) {
            translationMessage.textContent = '';
            translationMessage.classList.remove('error', 'warning', 'success', 'info');
        }
    }
}

function validateForm() {
    const activeTab = document.querySelector('.tab-content.active');
    if (!activeTab) return false;

    let firstInvalid = null;
    let hasErrors = false;
    const errorField = document.getElementById('translation-message');
    if (!errorField) return false;
    errorField.textContent = '';
    errorField.classList.remove('error', 'warning', 'success', 'info');

    const title = activeTab.querySelector('input');
    const content = activeTab.querySelector('textarea');

    const titleEmpty = !title.value.trim();
    const contentEmpty = !content.value.trim();
    const titleShort = !titleEmpty && title.value.length < 3;
    const contentShort = !contentEmpty && content.value.length < 5;

    let errors = [];

    if (titleEmpty && contentEmpty) errors.push('Введите название и описание');
    else if (titleEmpty) errors.push('Введите название');
    else if (contentEmpty) errors.push('Введите описание');

    if (titleShort && contentShort) errors.push('Название и описание слишком короткие');
    else if (titleShort) errors.push('Название слишком короткое (мин. 3 символа)');
    else if (contentShort) errors.push('Описание слишком короткое (мин. 5 символов)');

    if (errors.length) {
        errorField.textContent = errors.join('; ');
        errorField.classList.add('warning');
        firstInvalid = firstInvalid || title;
        hasErrors = true;
    }

    const selectedCategories = Array.from(document.querySelectorAll('#category-selectors .category-select')).map(s => s.value).filter(v => v);
    if (!selectedCategories.length) {
        setFieldMessage('message_selected_categories', translations.selectCategory, 'warning');
        firstInvalid = firstInvalid || document.getElementById('category-selectors');
        hasErrors = true;
    } else {
        document.getElementById('selected_categories_input').value = selectedCategories.join(',');
    }

    const price = document.querySelector('input[name="product_price"]');
    const priceValue = parseFloat(price?.value || 0);
    if (isNaN(priceValue) || priceValue <= 0) {
        setFieldMessage('message_product_price', 'Введите корректную цену', 'warning');
        price?.classList.add('input-error');
        firstInvalid = firstInvalid || price;
        hasErrors = true;
    }

    const galleryItems = document.querySelectorAll('#gallery_preview .gallery-item');
    if (galleryItems.length === 0) {
        setFieldMessage('message_product_gallery', 'Добавьте хотя бы одно изображение', 'warning');
        firstInvalid = firstInvalid || document.getElementById('gallery_preview');
        hasErrors = true;
    } else if (galleryItems.length > MAX_IMAGES) {
        setFieldMessage('message_product_gallery', `Не более ${MAX_IMAGES} изображений`, 'warning');
        hasErrors = true;
    }

    if (firstInvalid && firstInvalid.offsetParent !== null) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (firstInvalid.focus) setTimeout(() => firstInvalid.focus(), 300);
    }

    const missingTranslations = checkTranslations();

    if (missingTranslations.length && !translationChecked) {
        translationChecked = true;
        return false;
    }

    return !hasErrors;
}

document.addEventListener('DOMContentLoaded', () => {
    attachLengthValidation();

    document.querySelectorAll('input, textarea, select').forEach(el => {
        const id = el.id || el.name;
        el.addEventListener('input', () => {
            clearMessageForField(id);
            translationChecked = false;
        });
        el.addEventListener('change', () => clearMessageForField(id));
    });

    const galleryPreview = document.getElementById('gallery_preview');
    if (galleryPreview && typeof existingGallery !== 'undefined' && Array.isArray(existingGallery)) {
        existingGallery.forEach((imgId) => {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            div.dataset.id = imgId;

            const img = document.createElement('img');
            img.src = getImageUrl(imgId);
            div.prepend(img);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'gallery-remove';
            removeBtn.textContent = '✕';
            div.appendChild(removeBtn);

            galleryPreview.insertBefore(div, galleryPreview.querySelector('.btn-upload') || null);
        });
        updateGalleryOrder();
    }

    if (galleryPreview) {
        galleryPreview.addEventListener('click', e => {
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
            new Sortable(galleryPreview, { animation: 150, ghostClass: 'sortable-ghost', onEnd: updateGalleryOrder });
        }
    }

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const tab = document.getElementById(btn.dataset.tab);
            if (tab) tab.classList.add('active');
            const lang = btn.dataset.tab.split('-')[1];
            const langInput = document.getElementById('product_lang_input');
            if (langInput) langInput.value = lang;
            checkTranslations();
        });
    });

    const form = document.getElementById('create-product-form') || document.getElementById('edit-product-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!checkTranslationsBeforeSubmit()) {
                e.preventDefault(); // блокируем отправку первый раз
            }
        });
    }

    checkTranslations();
});

function getImageUrl(imgId) {
    if (typeof galleryPaths !== 'undefined' && galleryPaths[imgId]) return galleryPaths[imgId];
    return '';
}
