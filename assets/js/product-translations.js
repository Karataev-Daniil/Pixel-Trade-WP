function showMessage(message, type = 'info') {
    const messageBlock = document.getElementById('translation-message');
    messageBlock.textContent = message;
    messageBlock.className = 'body-small-semibold';
    messageBlock.classList.add(type);
}

document.getElementById('translation-action-button').addEventListener('click', () => {
    document.getElementById('translation-action-menu').parentElement.classList.toggle('show');
});

window.addEventListener('click', function(e) {
    if (!e.target.matches('#translation-action-button')) {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dd => dd.classList.remove('show'));
    }
});

function initCharCounters() {
    const textareas = document.querySelectorAll(
        'textarea[name="product_content"], textarea[name="description_en"], textarea[name="description_ro"]'
    );

    textareas.forEach(textarea => {
        const counter = textarea.parentElement.querySelector('small.form-hint');
        const maxLength = 2000;
        updateCounter(textarea, counter, maxLength);

        textarea.addEventListener('input', () => {
            updateCounter(textarea, counter, maxLength);
        });
    });
}

function updateCounter(textarea, counterElement, maxLength) {
    const currentLength = textarea.value.length;
    counterElement.textContent = `${currentLength} / ${maxLength}`;
    if (currentLength > maxLength) {
        counterElement.classList.add('error');
    } else {
        counterElement.classList.remove('error');
    }
}

document.addEventListener('DOMContentLoaded', initCharCounters);

function generateTranslations() {
    const titleRu = document.querySelector('input[name="product_title"]').value.trim();
    const descRu = document.querySelector('textarea[name="product_content"]').value.trim();
    const titleEn = document.querySelector('input[name="title_en"]').value.trim();
    const descEn = document.querySelector('textarea[name="description_en"]').value.trim();
    const titleRo = document.querySelector('input[name="title_ro"]').value.trim();
    const descRo = document.querySelector('textarea[name="description_ro"]').value.trim();

    if ((titleEn && descEn) && (titleRo && descRo) && (titleRu && descRu)) {
        showMessage('Все переводы уже заполнены.', 'warning');
        return;
    }

    let sourceLang = '';
    let title = '';
    let description = '';

    if (titleEn && descEn) { sourceLang = 'en'; title = titleEn; description = descEn; }
    else if (titleRo && descRo) { sourceLang = 'ro'; title = titleRo; description = descRo; }
    else if (titleRu && descRu) { sourceLang = 'ru'; title = titleRu; description = descRu; }
    else { showMessage('Заполните хотя бы одну версию заголовка и описания.', 'error'); return; }

    showMessage('Генерация переводов...', 'info');

    fetch(translationVars.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'generate_translations',
            title,
            description,
            source_lang: sourceLang,
            product_id: document.querySelector('input[name="product_id"]')?.value || 0,
            _ajax_nonce: translationVars.nonce
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.data.title_en) document.querySelector('input[name="title_en"]').value = data.data.title_en;
            if (data.data.title_ro) document.querySelector('input[name="title_ro"]').value = data.data.title_ro;
            if (data.data.title_ru) document.querySelector('input[name="product_title"]').value = data.data.title_ru;
            if (data.data.description_en) document.querySelector('textarea[name="description_en"]').value = data.data.description_en;
            if (data.data.description_ro) document.querySelector('textarea[name="description_ro"]').value = data.data.description_ro;
            if (data.data.description_ru) document.querySelector('textarea[name="product_content"]').value = data.data.description_ru;

            showMessage('Переводы успешно сгенерированы и заполнены.', 'success');

            initCharCounters();
        } else {
            showMessage('Ошибка перевода.', 'error');
        }
    })
    .catch(() => showMessage('Ошибка связи с сервером.', 'error'));
}

function showImproveOptions() {
    document.getElementById('improve-options').classList.toggle('hidden');
}

function improveText(style) {
    const textarea = document.querySelector('textarea[name="product_content"]');
    const text = textarea.value.trim();

    if (!text) { showMessage('Поле описания пустое.', 'error'); return; }

    showMessage('Улучшаем текст...', 'info');

    fetch(translationVars.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'ai_improve_text',
            text,
            style,
            lang: 'ru',
            _ajax_nonce: translationVars.nonce
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            textarea.value = data.data.improved_text;
            showMessage('Текст улучшен.', 'success');
            initCharCounters();
        } else {
            showMessage(data.data || 'Ошибка при улучшении текста.', 'error');
        }
    })
    .catch(() => showMessage('Ошибка связи с сервером.', 'error'));
}

function generateSEOText() {
    const textarea = document.querySelector('textarea[name="product_content"]');
    const title = document.querySelector('input[name="product_title"]').value.trim();
    const category = document.querySelector('select[name="product_category"]')?.value.trim() || '';
    const text = textarea.value.trim();

    if (!title || !text || !category) {
        showPopup({
            title: 'Внимание!',
            message: 'Рекомендуется заполнить название, описание и категорию товара для лучшего SEO. Продолжить генерацию?',
            type: 'warning',
            buttons: [
                { text: 'Отмена', className: 'primary-button-small', callback: () => { } },
                { text: 'Продолжить', className: 'secondary-button-small', callback: () => proceedSEOGeneration() }
            ]
        });
        return;
    }

    proceedSEOGeneration();

    function proceedSEOGeneration() {
        showMessage('Генерация SEO-текста...', 'info');

        fetch(translationVars.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'ai_seo_text',
                text,
                title,
                category,
                lang: 'ru',
                _ajax_nonce: translationVars.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                textarea.value = data.data.seo_text;
                showMessage('SEO-текст сгенерирован.', 'success');
                initCharCounters();
            } else {
                showMessage(data.data || 'Ошибка при генерации SEO-текста.', 'error');
            }
        })
        .catch(() => showMessage('Ошибка связи с сервером.', 'error'));
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    function updateSelectedCategories(ids) {
        document.getElementById('selected_categories_input').value = ids.join(',');
    }
});
document.querySelectorAll('.language-button').forEach(btn => {
    btn.addEventListener('click', () => {
        // Сохраняем состояние формы перед переходом
        saveFormState(); // функция сохранения из localStorage (title, description, price, категории, галерея, ошибки)
    });
});

window.addEventListener('DOMContentLoaded', restoreFormState); // восстанавливаем
// Сохраняем данные и ошибки для каждого языка
function saveFormState() {
    const activeTab = document.querySelector('.tab-content.active');
    const currentLang = activeTab.dataset.lang;

    const formState = {
        title: activeTab.querySelector('input')?.value || '',
        description: activeTab.querySelector('textarea')?.value || '',
        price: document.querySelector('input[name="product_price"]')?.value || '',
        oldPrice: document.querySelector('input[name="product_old_price"]')?.value || '',
        categories: document.querySelector('#preselected-categories')?.dataset.terms || '[]',
        gallery: [...document.querySelectorAll('#gallery_preview .gallery-item')].map(item => item.dataset.id),
        errors: {
            title: document.getElementById('message_product_title')?.textContent || '',
            description: document.getElementById('message_product_content')?.textContent || '',
            price: document.getElementById('message_product_price')?.textContent || '',
            categories: document.getElementById('message_selected_categories')?.textContent || '',
            gallery: document.getElementById('message_product_gallery')?.textContent || '',
            translation: document.getElementById('translation-message')?.textContent || '',
        }
    };

    localStorage.setItem('productForm_' + currentLang, JSON.stringify(formState));
}

// Восстанавливаем данные и ошибки
function restoreFormState() {
    const activeTab = document.querySelector('.tab-content.active');
    const currentLang = activeTab.dataset.lang;

    const saved = localStorage.getItem('productForm_' + currentLang);
    if (!saved) return;

    const state = JSON.parse(saved);

    activeTab.querySelector('input') && (activeTab.querySelector('input').value = state.title);
    activeTab.querySelector('textarea') && (activeTab.querySelector('textarea').value = state.description);

    document.querySelector('input[name="product_price"]') && (document.querySelector('input[name="product_price"]').value = state.price);
    document.querySelector('input[name="product_old_price"]') && (document.querySelector('input[name="product_old_price"]').value = state.oldPrice);

    const categoriesEl = document.querySelector('#preselected-categories');
    if (categoriesEl) categoriesEl.dataset.terms = state.categories;

    const galleryPreview = document.getElementById('gallery_preview');
    if (galleryPreview) {
        galleryPreview.innerHTML = '';
        state.gallery.forEach(id => {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            div.dataset.id = id;
            galleryPreview.appendChild(div);
        });
    }

    // Ошибки
    for (const key in state.errors) {
        const el = document.getElementById(key === 'description' ? 'message_product_content' :
                                           key === 'translation' ? 'translation-message' :
                                           'message_product_' + key);
        if (el) el.textContent = state.errors[key];
    }
}
