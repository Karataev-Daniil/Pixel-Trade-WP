const ajaxUrl = categorySelectorVars.ajaxUrl;
const lang = categorySelectorVars.language;
let categoryFeaturesCache = {};

// Load dynamic category features
function loadCategoryFeatures(categoryId) {
    if (categoryFeaturesCache[categoryId]) {
        return Promise.resolve(categoryFeaturesCache[categoryId]);
    }
    return fetch(`${ajaxUrl}?action=get_category_features&category_id=${categoryId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                categoryFeaturesCache[categoryId] = res.data;
                return res.data;
            } else {
                console.error('Ошибка при загрузке характеристик', res);
                return {};
            }
        })
        .catch(err => {
            console.error('Ошибка AJAX get_category_features', err);
            return {};
        });
}

function initCategorySelectors() {
    const wrapper = document.getElementById('category-selectors');
    if (!wrapper) return;

    const dynamicFieldsContainer = document.getElementById('dynamic-features-fields');
    const selectedInput = document.getElementById('selected_categories_input');
    const existingDynamicFields = window.existingDynamicFields || {};
    const preselectedContainer = wrapper.querySelector('#preselected-categories');
    const preselectedTerms = preselectedContainer?.dataset?.terms
        ? JSON.parse(preselectedContainer.dataset.terms)
        : [];

    function generateFieldName(label) {
        return '__' + label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9а-яё_-]/gi, '');
    }

    function createSelect(level, options, selectedId = null) {
        const container = document.createElement('div');
        container.classList.add('category-select-wrapper');
        container.dataset.level = level;

        const label = document.createElement('label');
        label.classList.add(level === 0 ? 'label-large' : 'label-medium');
        label.textContent = translations['labelLevel' + (level || 0)] || 'Категория';
        container.appendChild(label);

        const select = document.createElement('select');
        select.name = 'product_categories[]';
        select.dataset.level = level;
        select.classList.add('category-select', 'select--secondary', 'body-small-regular');

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = translations.selectCategory || '--- Выберите ---';
        select.appendChild(defaultOption);

        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.term_id;
            option.textContent = opt.name[lang] || opt.name['en'] || opt.name['ru'] || Object.values(opt.name)[0];
            if (selectedId && parseInt(selectedId) === parseInt(opt.term_id)) option.selected = true;
            select.appendChild(option);
        });

        container.appendChild(select);
        return container;
    }

    function removeLowerLevels(level) {
        wrapper.querySelectorAll('.category-select-wrapper').forEach(wrap => {
            if (parseInt(wrap.dataset.level) > level) wrap.remove();
        });
    }

    function updateSelectedInput() {
        const selectedCategories = Array.from(wrapper.querySelectorAll('select'))
            .map(s => s.value)
            .filter(v => v);
        selectedInput.value = selectedCategories.join(',');
    }

    function createDynamicField(fieldKey, fieldData) {
        const fieldLabelText = fieldData.label?.[lang] || fieldData.label?.en || fieldData.label?.ru || fieldKey;
        const fieldName = generateFieldName(fieldKey);

        const wrapperDiv = document.createElement('div');
        wrapperDiv.classList.add('input-block');

        const label = document.createElement('label');
        label.classList.add('label-medium');
        label.textContent = fieldLabelText + ': ';
        wrapperDiv.appendChild(label);

        let input;
        if (fieldData.options && fieldData.options.length) {
            input = document.createElement('select');
            input.name = fieldName;
            input.classList.add('category-select', 'select--secondary', 'body-small-regular');

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '---';
            input.appendChild(defaultOption);

            fieldData.options.forEach(opt => {
                const val = typeof opt === 'object'
                    ? opt[lang] || opt['en'] || opt['ru'] || Object.values(opt)[0]
                    : opt;

                const option = document.createElement('option');
                option.value = val;
                option.textContent = val;
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.name = fieldName;
            input.classList.add('category-select', 'input--secondary', 'body-small-regular');
        }

        if (existingDynamicFields[fieldName] !== undefined) {
            input.value = existingDynamicFields[fieldName];
        }

        input.style.width = '100%';
        wrapperDiv.appendChild(input);
        return wrapperDiv;
    }

    async function renderDynamicFields() {
        dynamicFieldsContainer.innerHTML = '';
        const selectedCategoryIds = selectedInput.value
            ? selectedInput.value.split(',').map(v => parseInt(v)).filter(v => !isNaN(v))
            : [];

        let hasFields = false;
        for (let categoryId of selectedCategoryIds) {
            const categoryFields = await loadCategoryFeatures(categoryId);
            if (Object.keys(categoryFields).length > 0) {
                hasFields = true;
                Object.entries(categoryFields).forEach(([fieldKey, fieldData]) => {
                    const field = createDynamicField(fieldKey, fieldData);
                    dynamicFieldsContainer.appendChild(field);
                });
            }
        }

        dynamicFieldsContainer.parentElement.classList.toggle('show', hasFields);
    }

    function handleSelectChange(select) {
        const level = parseInt(select.dataset.level);
        removeLowerLevels(level);

        const selectedCats = Array.from(wrapper.querySelectorAll('select'))
            .map(s => s.value)
            .filter(v => v);
        if (selectedCats.length > 0) clearMessageForField('selected_categories');

        if (select.value) loadSubcategories(select.value, level + 1);

        updateSelectedInput();
        renderDynamicFields();
    }

    async function loadSubcategories(parentId = 0, level = 0, selectedId = null) {
        try {
            const res = await fetch(`${ajaxUrl}?action=get_subcategories&parent=${parentId}`);
            const data = await res.json();
            if (!Array.isArray(data) || !data.length) return;
            const selectWrapper = createSelect(level, data, selectedId);
            const select = selectWrapper.querySelector('select');
            wrapper.appendChild(selectWrapper);
            select.addEventListener('change', () => handleSelectChange(select));
        } catch (e) {
            console.error('Ошибка загрузки подкатегорий', e);
        }
    }

    async function restoreChain() {
        let currentParent = 0;
        let level = 0;
        for (let selectedId of preselectedTerms) {
            try {
                const res = await fetch(`${ajaxUrl}?action=get_subcategories&parent=${currentParent}`);
                const data = await res.json();
                if (!data.some(cat => parseInt(cat.term_id) === parseInt(selectedId))) break;

                const selectWrapper = createSelect(level, data, selectedId);
                const select = selectWrapper.querySelector('select');
                wrapper.appendChild(selectWrapper);
                select.addEventListener('change', () => handleSelectChange(select));

                currentParent = selectedId;
                level++;
            } catch (e) {
                console.error('Ошибка восстановления цепочки категорий', e);
            }
        }
        await loadSubcategories(currentParent, level);
        updateSelectedInput();
        renderDynamicFields();
    }

    if (preselectedTerms.length) restoreChain();
    else loadSubcategories(0, 0);
}

// helper to collect dynamic fields on submit
function collectDynamicFields() {
    const dynamicFieldsContainer = document.getElementById('dynamic-features-fields');
    const hiddenDynamicFields = document.getElementById('dynamic_fields_input');
    if (!dynamicFieldsContainer || !hiddenDynamicFields) return;

    const allDynamicData = {};
    dynamicFieldsContainer.querySelectorAll('input, select').forEach(input => {
        if (input && input.name) {
            const value = input.value.trim();
            if (value !== '') allDynamicData[input.name] = value;
        }
    });
    hiddenDynamicFields.value = JSON.stringify(allDynamicData);
}

document.addEventListener('DOMContentLoaded', () => {
    initCategorySelectors();
});
