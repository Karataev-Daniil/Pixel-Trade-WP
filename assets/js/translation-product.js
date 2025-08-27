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
