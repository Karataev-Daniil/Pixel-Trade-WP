const MAX_IMAGES = 10;
let newImageIndex = 0;
let translationChecked = false;

// Counter
function updateCounter(el) {
    const counter = el.closest('.tab-content')?.querySelector('.form-hint');
    if (!counter) return;
    counter.textContent = `${el.value.length} / 2000`;
}

// Gallery order
function updateGalleryOrder() {
    const items = document.querySelectorAll('#gallery_preview .gallery-item');
    const order = Array.from(items).map(item => item.dataset.id);
    const input = document.getElementById('gallery_order_input');
    if (input) input.value = order.join(',');
}

// Clear message for field
function clearMessageForField(fieldIdOrName) {
    const msgEl = document.getElementById('message_' + fieldIdOrName) || document.getElementById(fieldIdOrName);
    if (msgEl) {
        msgEl.textContent = '';
        msgEl.classList.remove('error', 'warning', 'success', 'info');
    }
    if (fieldIdOrName.startsWith('product_title') || fieldIdOrName.startsWith('product_content')) {
        const translationMessage = document.getElementById('translation-message');
        if (translationMessage) {
            translationMessage.textContent = '';
            translationMessage.classList.remove('error', 'warning', 'success', 'info');
        }
    }
}

// Gallery limit check and preview
window.checkGalleryLimit = function(input) {
    const files = Array.from(input.files || []);
    if (!files.length) return;

    const preview = document.getElementById('gallery_preview');
    if (!preview) return;

    const existingCount = preview.querySelectorAll('.gallery-item').length;
    const remaining = MAX_IMAGES - existingCount;
    if (remaining <= 0) {
        alert(`Достигнут лимит ${MAX_IMAGES} изображений.`);
        input.value = '';
        return;
    }

    const toAdd = files.slice(0, remaining);
    const dt = new DataTransfer();

    toAdd.forEach(file => {
        const div = document.createElement('div');
        div.className = 'gallery-item';
        div.dataset.id = 'new-' + newImageIndex++;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'gallery-remove';
        removeBtn.textContent = '✕';
        div.appendChild(removeBtn);

        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            div.prepend(img);

            const uploadBtn = preview.querySelector('.btn-upload');
            preview.insertBefore(div, uploadBtn || null);

            updateGalleryOrder();
            setFieldMessage('product_gallery', 'Фото успешно добавлено', 'success');
        };
        reader.readAsDataURL(file);

        dt.items.add(file);
    });

    input.files = dt.files;
};

// Get image url
function getImageUrl(imgId) {
    return typeof galleryPaths !== 'undefined' && galleryPaths[imgId] ? galleryPaths[imgId] : '';
}

// Translation check before submit
function checkTranslationsBeforeSubmit() {
    const tabs = document.querySelectorAll('.tab-content');
    const translationMessage = document.getElementById('translation-message');
    if (!translationMessage) return true;

    let filledCount = 0;
    let missingLangs = [];

    tabs.forEach(tab => {
        const lang = tab.id.split('-')[1];
        const title = tab.querySelector('input');
        const content = tab.querySelector('textarea');

        if ((title && title.value.trim()) || (content && content.value.trim())) {
            filledCount++;
        } else {
            missingLangs.push(lang);
        }
    });

    // Если только один перевод
    if (filledCount === 1 && missingLangs.length > 0) {
        if (!translationChecked) {
            translationMessage.textContent = `Вы не заполнили переводы для языков: ${missingLangs.join(', ')}. 
            Сгенерируйте их — это бесплатно!`;
            translationMessage.classList.remove('error', 'warning', 'success');
            translationMessage.classList.add('info');
            translationChecked = true;
            return false; // блокируем первый сабмит
        }
        return true; // второй клик — отправляем
    }

    // Если вообще нет переводов
    if (filledCount === 0) {
        if (!translationChecked) {
            translationMessage.textContent = `У вас нет переводов. Заполните хотя бы один язык.`;
            translationMessage.classList.remove('success', 'warning', 'info');
            translationMessage.classList.add('error');
            translationChecked = true;
            return false;
        }
        return true;
    }

    return true; // всё ок — отправляем
}


// Set field message
function setFieldMessage(id, message = '', type = '') {
    const el = document.getElementById('message_' + id);
    if (!el) return;
    el.textContent = message;
    el.classList.remove('error', 'warning', 'success', 'info');
    if (type) el.classList.add(type);
}

function validateForm(form) {
    let valid = true;
    const errors = {}; // собираем ошибки для консоли

    // Categories
    const selectedCategories = form.querySelector('#selected_categories_input');
    if (!selectedCategories.value) {
        setFieldMessage('selected_categories', 'Выберите хотя бы одну категорию', 'error');
        errors['selected_categories'] = 'Выберите хотя бы одну категорию';
        valid = false;
    }

    // Titles + Descriptions (хотя бы один язык должен быть заполнен)
    const titleIds = ['product_title', 'title_en', 'title_ro'];
    const contentIds = ['product_content', 'description_en', 'description_ro'];

    const hasTitle = titleIds.some(id => {
        const input = form.querySelector(`[name="${id}"]`);
        return input && input.value.trim();
    });

    const hasContent = contentIds.some(id => {
        const textarea = form.querySelector(`[name="${id}"]`);
        return textarea && textarea.value.trim();
    });

    if (!hasTitle) {
        titleIds.forEach(id => setFieldMessage(id, 'Введите название хотя бы на одном языке', 'error'));
        errors['title'] = 'Введите название хотя бы на одном языке';
        valid = false;
    }

    if (!hasContent) {
        contentIds.forEach(id => setFieldMessage(id, 'Введите описание хотя бы на одном языке', 'error'));
        errors['content'] = 'Введите описание хотя бы на одном языке';
        valid = false;
    }

    // Price
    const priceInput = form.querySelector('[name="product_price"]');
    if (!priceInput.value || parseFloat(priceInput.value) <= 0) {
        setFieldMessage('product_price', 'Введите корректную цену', 'error');
        errors['product_price'] = 'Введите корректную цену';
        valid = false;
    }

    // Gallery
    const galleryItems = document.querySelectorAll('#gallery_preview .gallery-item');
    if (!galleryItems || galleryItems.length === 0) {
        setFieldMessage('product_gallery', 'Добавьте хотя бы одно фото', 'error');
        errors['product_gallery'] = 'Добавьте хотя бы одно фото';
        valid = false;
    }

    return valid;
}


// DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    // Input events
    document.querySelectorAll('input, textarea, select').forEach(el => {
        const id = el.id || el.name;
        el.addEventListener('input', () => {
            clearMessageForField(id);
            translationChecked = false;
            updateCounter(el);
        });
        el.addEventListener('change', () => clearMessageForField(id));
    });

    // Gallery preview setup
    const galleryPreview = document.getElementById('gallery_preview');
    const existingGalleryArray = typeof existingGallery !== 'undefined' ? existingGallery : [];

    if (galleryPreview && Array.isArray(existingGalleryArray)) {
        existingGalleryArray.forEach(imgId => {
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

    // Tab switch
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

            checkTranslationsBeforeSubmit();
        });
    });

    // Form submit
    const form = document.getElementById('create-product-form') || document.getElementById('edit-product-form');
    if (form) {
        form.addEventListener('submit', e => {
            if (typeof collectDynamicFields === 'function') collectDynamicFields();
            const isValid = validateForm(form);
            const translationsOk = checkTranslationsBeforeSubmit();
            if (!isValid || !translationsOk) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            // Всё ок, форма отправится
        });
    }

    // Slick slider
    jQuery(document).ready($ => {
        const $slider = $('.main-slider');
        if ($slider.length) {
            $slider.slick({
                infinite: false,
                swipe: false,
                draggable: true,
                slidesToScroll: 1,
                slidesToShow: 1,
                variableWidth: true,
                swipeToSlide: false,
                speed: 400,
                prevArrow: '<button class="slick-prev" aria-label="Назад"></button>',
                nextArrow: '<button class="slick-next" aria-label="Вперёд"></button>',
            });
        }
    });
});
