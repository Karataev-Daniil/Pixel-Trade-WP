function generateTranslations() {
    const titleRu = document.querySelector('input[name="product_title"]').value.trim();
    const descRu = document.querySelector('textarea[name="product_content"]').value.trim();
    const titleEn = document.querySelector('input[name="title_en"]').value.trim();
    const descEn = document.querySelector('textarea[name="description_en"]').value.trim();
    const titleRo = document.querySelector('input[name="title_ro"]').value.trim();
    const descRo = document.querySelector('textarea[name="description_ro"]').value.trim();
    const messageBlock = document.getElementById('translation-message');

    if ((titleEn && descEn) && (titleRo && descRo) && (titleRu && descRu)) {
        messageBlock.style.color = 'orange';
        messageBlock.textContent = 'Все переводы уже заполнены.';
        return;
    }

    let sourceLang = '';
    let title = '';
    let description = '';

    if (titleEn && descEn) {
        sourceLang = 'en';
        title = titleEn;
        description = descEn;
    } else if (titleRo && descRo) {
        sourceLang = 'ro';
        title = titleRo;
        description = descRo;
    } else if (titleRu && descRu) {
        sourceLang = 'ru';
        title = titleRu;
        description = descRu;
    } else {
        messageBlock.style.color = 'red';
        messageBlock.textContent = 'Заполните хотя бы одну версию заголовка и описания.';
        return;
    }

    messageBlock.style.color = 'black';
    messageBlock.textContent = 'Генерация переводов...';

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

            messageBlock.style.color = 'green';
            messageBlock.textContent = 'Переводы успешно сгенерированы и заполнены.';
        } else {
            messageBlock.style.color = 'red';
            messageBlock.textContent = 'Ошибка перевода.';
        }
    })
    .catch(() => {
        messageBlock.style.color = 'red';
        messageBlock.textContent = 'Ошибка связи с сервером.';
    });
}

function showImproveOptions() {
    document.getElementById('improve-options').classList.toggle('hidden');
}

function improveText(style) {
    const textarea = document.querySelector('textarea[name="product_content"]');
    const text = textarea.value.trim();
    const messageBlock = document.getElementById('translation-message');

    if (!text) {
        messageBlock.style.color = 'red';
        messageBlock.textContent = 'Поле описания пустое.';
        return;
    }

    messageBlock.style.color = 'black';
    messageBlock.textContent = 'Улучшаем текст...';

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
            messageBlock.style.color = 'green';
            messageBlock.textContent = 'Текст улучшен.';
        } else {
            messageBlock.style.color = 'red';
            messageBlock.textContent = data.data || 'Ошибка при улучшении текста.';
        }
    })
    .catch(() => {
        messageBlock.style.color = 'red';
        messageBlock.textContent = 'Ошибка связи с сервером.';
    });
}

function generateSEOText() {
    const textarea = document.querySelector('textarea[name="product_content"]');
    const title = document.querySelector('input[name="product_title"]').value.trim();
    const category = document.querySelector('select[name="product_category"]')?.value.trim() || '';
    const text = textarea.value.trim();
    const messageBlock = document.getElementById('translation-message');

    if (!title || !text || !category) {
        showPopup({
            title: 'Внимание!',
            message: '⚠️ Рекомендуется заполнить название, описание и категорию товара для лучшего SEO. Продолжить генерацию?',
            type: 'warning',
            buttons: [
                { 
                    text: 'Продолжить', 
                    className: 'primary', 
                    callback: () => proceedSEOGeneration() 
                },
                { 
                    text: 'Отмена', 
                    className: 'secondary', 
                    callback: () => { } 
                }
            ]
        });
        return;
    }

    proceedSEOGeneration();

    function proceedSEOGeneration() {
        messageBlock.style.color = 'black';
        messageBlock.textContent = 'Генерация SEO-текста...';

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
                messageBlock.style.color = 'green';
                messageBlock.textContent = 'SEO-текст сгенерирован.';
            } else {
                messageBlock.style.color = 'red';
                messageBlock.textContent = data.data || 'Ошибка при генерации SEO-текста.';
            }
        })
        .catch(() => {
            messageBlock.style.color = 'red';
            messageBlock.textContent = 'Ошибка связи с сервером.';
        });
    }
}
