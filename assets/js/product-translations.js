let typingInterval;
let waitInterval;

function getActiveTab() {
    const activeTab = document.querySelector('.tab-content.active');
    if (!activeTab) return null;
    const lang = activeTab.dataset.lang || activeTab.id?.split('-')[1];
    return { tab: activeTab, lang };
}

function setMessage(id, text, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
    el.className = `form-message body-small-regular ${type}`;
}

function typeMessage(id, text, loop = false, type = '') {
    const el = document.getElementById(id);
    if (!el) return;

    clearInterval(typingInterval);
    clearInterval(waitInterval);

    let index = 0;

    function startTyping() {
        el.textContent = '';
        el.className = `form-message body-small-regular ${type}`;
        index = 0;

        typingInterval = setInterval(() => {
            el.textContent += text[index];
            index++;

            if (index >= text.length) {
                clearInterval(typingInterval);
                if (loop) {
                    let dots = 0;
                    waitInterval = setInterval(() => {
                        el.textContent = text + '.'.repeat(dots % 4);
                        dots++;
                        if (dots > 10) {
                            clearInterval(waitInterval);
                            startTyping();
                        }
                    }, 300);
                }
            }
        }, 50);
    }

    startTyping();
}

function stopTyping(id, finalText, type = '') {
    clearInterval(typingInterval);
    clearInterval(waitInterval);
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = finalText;
    el.className = `form-message body-small-regular ${type}`;
}

function closeDropdown() {
    const dropdown = document.querySelector('.translation-dropdown');
    if (dropdown) dropdown.classList.remove('show');
}

async function generateTranslations() {
    closeDropdown();

    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const inputName = lang === 'ru' ? 'product_title' : 'title_' + lang;
    const textareaName = lang === 'ru' ? 'product_content' : 'description_' + lang;

    const title = tab.querySelector(`input[name="${inputName}"]`)?.value.trim() || '';
    const desc  = tab.querySelector(`textarea[name="${textareaName}"]`)?.value.trim() || '';

    if (!title && !desc) return setMessage('action-message', 'Пустой текст для перевода', 'error');

    typeMessage('action-message', 'Генерация переводов', true, 'info');

    try {
        const resp = await fetch(translationVars.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'generate_translations',
                _ajax_nonce: translationVars.nonce,
                title,
                desc,
                lang
            })
        });

        const data = await resp.json();
        stopTyping('action-message', '', '');

        if (!data.success || !data.data) return setMessage('action-message', data.error || 'Ошибка при генерации', 'error');

        const translations = data.data;

        ['ru','en','ro'].forEach(l => {
            if (l === lang || !translations[l]) return;

            const tabTarget = document.querySelector(`.tab-content[data-lang="${l}"]`);
            if (!tabTarget) return;

            const inputNameTarget = l === 'ru' ? 'product_title' : 'title_' + l;
            const textareaNameTarget = l === 'ru' ? 'product_content' : 'description_' + l;

            const input = tabTarget.querySelector(`input[name="${inputNameTarget}"]`);
            const textarea = tabTarget.querySelector(`textarea[name="${textareaNameTarget}"]`);

            if (input && translations[l].title) input.value = translations[l].title.replace(/\\n/g, '\n');
            if (textarea && translations[l].desc) textarea.value = translations[l].desc.replace(/\\n/g, '\n');
        });

        setMessage('action-message', 'Переводы готовы', 'success');
    } catch (e) {
        console.error(e);
        stopTyping('action-message', 'Ошибка при генерации', 'error');
    }
}

async function improveText() {
    closeDropdown();

    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const inputName = lang === 'ru' ? 'product_title' : 'title_' + lang;
    const textareaName = lang === 'ru' ? 'product_content' : 'description_' + lang;

    const titleEl = tab.querySelector(`input[name="${inputName}"]`);
    const descEl  = tab.querySelector(`textarea[name="${textareaName}"]`);

    if (!titleEl?.value.trim() && !descEl?.value.trim()) return setMessage('action-message', 'Пустой текст для улучшения', 'error');

    typeMessage('action-message', 'Улучшаем текст', true, 'info');

    try {
        const resp = await fetch(translationVars.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'improve_text',
                _ajax_nonce: translationVars.nonce,
                title: titleEl.value,
                desc: descEl.value,
                lang
            })
        });

        const data = await resp.json();
        stopTyping('action-message', '', '');

        if (data.error) {
            console.error('Improve text error:', data.raw || data.error);
            return setMessage('action-message', data.error || 'Ошибка при улучшении текста', 'error');
        }

        if (data.title) titleEl.value = data.title.replace(/\\n/g, '\n');
        if (data.desc) descEl.value = data.desc.replace(/\\n/g, '\n');

        setMessage('action-message', 'Текст улучшен', 'success');
    } catch (err) {
        console.error(err);
        stopTyping('action-message', 'Ошибка при улучшении текста', 'error');
    }
}

async function generateSEOText() {
    closeDropdown();

    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const textareaName = lang === 'ru' ? 'product_content' : 'description_' + lang;
    const descEl = tab.querySelector(`textarea[name="${textareaName}"]`);

    if (!descEl?.value.trim()) return setMessage('action-message', 'Пустой текст для SEO', 'error');

    typeMessage('action-message', 'Генерируем SEO текст', true, 'info');

    try {
        const resp = await fetch(translationVars.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'generate_seo_text',
                _ajax_nonce: translationVars.nonce,
                desc: descEl.value,
                lang
            })
        });

        const data = await resp.json();
        stopTyping('action-message', '', '');

        if (data.error) return setMessage('action-message', data.error, 'error');

        if (data.seo_text) descEl.value = data.seo_text.replace(/\\n/g, '\n');

        setMessage('action-message', 'SEO текст готов', 'success');
    } catch (err) {
        console.error(err);
        stopTyping('action-message', 'Ошибка при генерации SEO текста', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.querySelector('.translation-dropdown');
    const actionBtn = document.getElementById('translation-action-button');

    if (actionBtn && dropdown) {
        actionBtn.addEventListener('click', () => dropdown.classList.toggle('show'));
    }

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});
