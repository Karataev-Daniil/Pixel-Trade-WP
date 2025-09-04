function setFieldMessage(id, message = '', type = '') {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = message;
    el.className = `form-error ${type} body-small-regular`;
}

function validateForm() {
    const activeTab = document.querySelector('.tab-content.active');
    const currentLang = activeTab.dataset.lang;

    let firstInvalid = null;
    let hasErrors = false;

    const title = activeTab.querySelector('input');
    const content = activeTab.querySelector('textarea');

    const translationMessage = document.getElementById('translation-message');
    translationMessage.textContent = '';
    translationMessage.className = 'form-error body-small-regular';

    const errors = [];

    if (!title.value.trim()) {
        setFieldMessage('message_product_title', title.placeholder, 'warning');
        errors.push('Введите название');
        firstInvalid = firstInvalid || title;
        hasErrors = true;
    } else {
        setFieldMessage('message_product_title', 'Заполнено', 'success');
    }

    if (!content.value.trim()) {
        setFieldMessage('message_product_content', content.placeholder, 'warning');
        errors.push('Введите описание');
        firstInvalid = firstInvalid || content;
        hasErrors = true;
    } else {
        setFieldMessage('message_product_content', 'Заполнено', 'success');
    }

    if (errors.length > 0) {
        translationMessage.textContent = errors.join(' и ');
        translationMessage.className = 'form-error warning body-small-regular';
    }

    const categories = document.querySelector('#preselected-categories');
    if (!categories || categories.dataset.terms === '[]') {
        setFieldMessage('message_selected_categories', translations.selectCategory, 'warning');
        firstInvalid = firstInvalid || categories;
        hasErrors = true;
    } else {
        setFieldMessage('message_selected_categories', 'Выбрано', 'success');
    }

    const price = document.querySelector('input[name="product_price"]');
    const oldPrice = document.querySelector('input[name="product_old_price"]');
    if ((!price || !price.value.trim()) && (!oldPrice || !oldPrice.value.trim())) {
        setFieldMessage('message_product_price', price?.placeholder || 'Введите цену', 'warning');
        firstInvalid = firstInvalid || price || oldPrice;
        hasErrors = true;
    } else {
        setFieldMessage('message_product_price', 'Указана', 'success');
    }

    const gallery = document.querySelectorAll('#gallery_preview .gallery-item');
    if (gallery.length === 0) {
        setFieldMessage('message_product_gallery', translations.addImage || 'Добавьте хотя бы одно изображение', 'warning');
        firstInvalid = firstInvalid || document.getElementById('gallery_preview');
        hasErrors = true;
    } else {
        setFieldMessage('message_product_gallery', 'Добавлено', 'success');
    }

    if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });

    return !hasErrors;
}

function updateProgress() {
    const activeTab = document.querySelector('.tab-content.active');

    const steps = {
        category: document.querySelector('#preselected-categories')?.dataset.terms !== '[]',
        title: activeTab.querySelector('input')?.value.trim(),
        description: activeTab.querySelector('textarea')?.value.trim(),
        price: document.querySelector('input[name="product_price"]')?.value.trim() || document.querySelector('input[name="product_old_price"]')?.value.trim(),
        image: document.querySelectorAll('#gallery_preview .gallery-item').length > 0
    };

    document.querySelectorAll('.form-progress__step').forEach(step => {
        const key = step.dataset.step;
        step.classList.remove('filled', 'warning');
        step.classList.add(steps[key] ? 'filled' : 'warning');
    });
}

function updateGalleryOrder(evt) {
    const gallery = document.getElementById('gallery_order_input');
    if (!gallery) return;
    const ids = [...document.querySelectorAll('#gallery_preview .gallery-item')].map(item => item.dataset.id);
    gallery.value = ids.join(',');
}

document.addEventListener('DOMContentLoaded', () => {
    updateProgress();

    document.querySelectorAll('input, textarea, select').forEach(el => {
        el.addEventListener('input', updateProgress);
        el.addEventListener('change', updateProgress);
    });

    const form = document.getElementById('create-product-form');
    form.addEventListener('submit', e => {
        e.preventDefault();
        if (validateForm()) form.submit();
        else updateProgress();
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
            updateProgress();
        });
    });

    const gallery = document.getElementById('gallery_preview');
    if (gallery) {
        new Sortable(gallery, { animation: 150, ghostClass: 'sortable-ghost', onEnd: updateGalleryOrder });
    }
});
let newImageIndex = 0;
const MAX_IMAGES = 10;

window.checkGalleryLimit = function (input) {
    const files = Array.from(input.files || []);
    const preview = document.getElementById('gallery_preview');
    const errorField = document.getElementById('message_product_gallery');

    errorField.textContent = '';
    errorField.className = 'form-message body-small-regular';

    if (!files.length) {
        errorField.textContent = 'Файлы не выбраны.';
        errorField.classList.add('warning');
        return;
    }   

    const existingCount = preview.querySelectorAll('.gallery-item').length;
    const remaining = MAX_IMAGES - existingCount;

    if (remaining <= 0) {
        errorField.textContent = `Достигнут лимит ${MAX_IMAGES} изображений.`;
        errorField.classList.add('error');
        input.value = '';
        return;
    }

    const toAdd = files.slice(0, remaining);
    const skipped = files.length - toAdd.length;

    toAdd.forEach((file) => {
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

        const addButton = preview.querySelector('.btn-upload') || preview.querySelector('.btn-upload__icon').parentNode;
        preview.insertBefore(div, addButton);

        const reader = new FileReader();
        reader.onload = function (e) {
            loaderContainer.remove();

            const img = document.createElement('img');
            img.src = e.target.result;
            div.prepend(img);

            updateGalleryOrder();
        };
        reader.readAsDataURL(file);
    });

    if (skipped > 0) {
        errorField.textContent = `Добавлено ${toAdd.length} из ${files.length}. Достигнут лимит ${MAX_IMAGES}.`;
        errorField.classList.add('warning');
    } else {
        errorField.textContent = 'Файлы успешно добавлены.';
        errorField.classList.add('success');
    }

    input.value = '';
};

function updateGalleryOrder() {
    const items = document.querySelectorAll('#gallery_preview .gallery-item');
    const order = Array.from(items).map(item => item.dataset.id);
    document.getElementById('gallery_order_input').value = order.join(',');
}

document.addEventListener('DOMContentLoaded', () => {
    const preview = document.getElementById('gallery_preview');

    preview.addEventListener('click', function (e) {
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

            const errorField = document.getElementById('message_product_gallery');
            errorField.textContent = 'Фото удалено.';
            errorField.className = 'form-message body-small-regular success';
        }
    });
    updateGalleryOrder();
});
