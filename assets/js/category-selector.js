const ajaxUrl = categorySelectorVars.ajaxUrl;

document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('category-selectors');
    if (!wrapper) return;

    const preselectedContainer = wrapper.querySelector('#preselected-categories');
    const preselectedTerms = preselectedContainer?.dataset?.terms ? JSON.parse(preselectedContainer.dataset.terms) : [];

    function createSelect(level, options, selectedId = null) {
        const container = document.createElement('div');
        container.classList.add('category-select-wrapper');
        container.dataset.level = level;

        const label = document.createElement('label');
        label.classList.add(level === 0 ? 'label-large' : 'label-medium');
        label.textContent = translations['labelLevel' + (level || 0)] || ``;

        const select = document.createElement('select');
        select.name = 'product_categories[]';
        select.classList.add('category-select', 'select-tertiary', 'body-small-regular');
        select.dataset.level = level;

        const defaultOption = document.createElement('option');
        defaultOption.textContent = translations.selectCategory;
        defaultOption.value = '';
        select.appendChild(defaultOption);

        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.term_id;
            option.textContent = opt.name[categorySelectorVars.language] || opt.name['ru'];
            if (selectedId && parseInt(selectedId) === parseInt(opt.term_id)) option.selected = true;
            select.appendChild(option);
        });

        container.appendChild(label);
        container.appendChild(select);
        return container;
    }

    function removeLowerLevels(startLevel) {
        wrapper.querySelectorAll('.category-select-wrapper').forEach(wrap => {
            if (parseInt(wrap.dataset.level) > startLevel) wrap.remove();
        });
    }

    function handleSelectChange(select) {
        const level = parseInt(select.dataset.level);
        removeLowerLevels(level);

        if (select.value) loadSubcategories(select.value, level + 1);

        const selectedCategories = Array.from(wrapper.querySelectorAll('.category-select'))
            .map(s => s.value)
            .filter(v => v);

        const selectedInput = document.getElementById('selected_categories_input');
        if (selectedInput) selectedInput.value = selectedCategories.join(',');

        const msgEl = document.getElementById('message_selected_categories');
        if (msgEl && selectedCategories.length) {
            msgEl.textContent = '';
            msgEl.classList.remove('warning', 'error', 'success');
        }
    }

    function loadSubcategories(parentId = 0, level = 0, selectedId = null) {
        fetch(`${ajaxUrl}?action=get_subcategories&parent=${parentId}`)
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || !data.length) return;
                const selectWrapper = createSelect(level, data, selectedId);
                const select = selectWrapper.querySelector('select');
                wrapper.appendChild(selectWrapper);
                select.addEventListener('change', () => handleSelectChange(select));
            })
            .catch(err => console.error(err));
    }

    async function restoreChain() {
        let currentParent = 0;
        let level = 0;

        for (let selectedId of preselectedTerms) {
            const data = await fetch(`${ajaxUrl}?action=get_subcategories&parent=${currentParent}`)
                .then(res => res.json())
                .catch(() => []);

            if (!data.some(cat => parseInt(cat.term_id) === parseInt(selectedId))) break;

            const selectWrapper = createSelect(level, data, selectedId);
            const select = selectWrapper.querySelector('select');
            wrapper.appendChild(selectWrapper);
            select.addEventListener('change', () => handleSelectChange(select));

            currentParent = selectedId;
            level++;
        }

        const last = await fetch(`${ajaxUrl}?action=get_subcategories&parent=${currentParent}`)
            .then(res => res.json())
            .catch(() => []);

        if (last.length > 0) {
            const selectWrapper = createSelect(level, last);
            const select = selectWrapper.querySelector('select');
            wrapper.appendChild(selectWrapper);
            select.addEventListener('change', () => handleSelectChange(select));
        }
    }

    if (preselectedTerms.length) restoreChain();
    else loadSubcategories(0, 0);
});
