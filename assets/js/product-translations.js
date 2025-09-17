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

async function generateTranslations() {
    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const title = tab.querySelector('input')?.value.trim() || '';
    const desc = tab.querySelector('textarea')?.value.trim() || '';

    if (!title && !desc) {
        return setMessage('translation-message', 'Пустой текст для перевода', 'error');
    }

    typeMessage('translation-message', 'Генерация переводов', true, 'info');

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
        stopTyping('translation-message', '', '');
      
        if (!data.success || !data.data) {
            return setMessage('translation-message', data.error || 'Ошибка при генерации', 'error');
        }
      
        const translations = data.data;
      
        ['ru','en','ro'].forEach(l => {
            if (l === lang || !translations[l]) return;

            const tabTarget = document.querySelector(`.tab-content[data-lang="${l}"]`);
            if (!tabTarget) return;

            const inputName = l === 'ru' ? 'product_title' : 'title_' + l;
            const textareaName = l === 'ru' ? 'product_content' : 'description_' + l;

            const input = tabTarget.querySelector(`input[name="${inputName}"]`);
            const textarea = tabTarget.querySelector(`textarea[name="${textareaName}"]`);

            if (input && translations[l].title) input.value = translations[l].title.replace(/\\n/g, '\n');
            if (textarea && translations[l].desc) textarea.value = translations[l].desc.replace(/\\n/g, '\n');
        });
      
        setMessage('translation-message', 'Переводы готовы', 'success');
    } catch (e) {
        console.error(e);
        stopTyping('translation-message', 'Ошибка при генерации', 'error');
    }
}

async function showImproveOptions() {
    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const titleEl = tab.querySelector('input[name^="title"]');
    const descEl = tab.querySelector('textarea[name^="description"], textarea[name^="product_content"]');

    if (!titleEl.value.trim() && !descEl.value.trim()) {
        setMessage('translation-message', 'Пустой текст для улучшения', 'error');
        return;
    }

    typeMessage('translation-message', 'Улучшаем текст', true, 'info');

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
        stopTyping('translation-message', '', '');

        if (data.error) {
            console.error('Improve text error:', data.raw || data.error);
            setMessage('translation-message', data.error || 'Ошибка при улучшении текста', 'error');
            return;
        }

        if (data.title) titleEl.value = data.title.replace(/\\n/g, '\n');
        if (data.desc) descEl.value = data.desc.replace(/\\n/g, '\n');

        setMessage('translation-message', 'Текст улучшен', 'success');

    } catch (err) {
        console.error(err);
        stopTyping('translation-message', 'Ошибка при улучшении текста', 'error');
    }
}

async function generateSEOText() {
    const active = getActiveTab();
    if (!active) return;

    const { tab, lang } = active;
    const descEl = tab.querySelector('textarea[name^="description"], textarea[name^="product_content"]');

    typeMessage('translation-message', 'Генерируем SEO текст', true, 'info');

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
        stopTyping('translation-message', '', '');

        if (data.error) {
            setMessage('translation-message', data.error, 'error');
            return;
        }

        if (data.seo_text) descEl.value = data.seo_text.replace(/\\n/g, '\n');

        setMessage('translation-message', 'SEO текст готов', 'success');

    } catch (err) {
        console.error(err);
        stopTyping('translation-message', 'Ошибка при генерации SEO текста', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.querySelector('.dropdown');
    const actionBtn = document.getElementById('translation-action-button');
    if (actionBtn && dropdown) {
        actionBtn.addEventListener('click', () => dropdown.classList.toggle('open'));
    }

    if (translationVars.activeTab) {
        const activeLang = translationVars.activeTab;
        const activeTab = document.querySelector(`.tab-content[data-lang="${activeLang}"]`) || document.getElementById(`tab-${activeLang}`);
        if (activeTab) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            activeTab.classList.add('active');

            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            const btn = document.querySelector(`.tab-btn[data-tab="tab-${activeLang}"]`);
            if (btn) btn.classList.add('active');
        }
    }
});
