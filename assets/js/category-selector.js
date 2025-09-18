const ajaxUrl = categorySelectorVars.ajaxUrl;

document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('category-selectors');
    if (!wrapper) return;

    const dynamicFieldsContainer = document.getElementById('dynamic-features-fields');
    const selectedInput = document.getElementById('selected_categories_input');
    const hiddenDynamicFields = document.getElementById('dynamic_fields_input');

    window.existingDynamicFields = window.existingDynamicFields || {};
    const existingDynamicFields = window.existingDynamicFields;

    const preselectedContainer = wrapper.querySelector('#preselected-categories');
    const preselectedTerms = preselectedContainer?.dataset?.terms
        ? JSON.parse(preselectedContainer.dataset.terms)
        : [];

    function generateFieldName(label) {
        return '_' + label.toLowerCase()
                          .replace(/\s+/g, '-')
                          .replace(/[^a-z0-9а-яё_-]/gi, '');
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
        select.classList.add('category-select', 'select-tertiary', 'body-small-regular');

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = translations.selectCategory;
        select.appendChild(defaultOption);

        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.term_id;
            option.textContent = opt.name[categorySelectorVars.language] || opt.name['ru'];
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
        const fieldLabelText = fieldData.label?.[categorySelectorVars.language] || fieldData.label?.ru || fieldKey;
        const fieldName = generateFieldName(fieldKey);

        const wrapperDiv = document.createElement('div');
        wrapperDiv.classList.add('dynamic-field-wrapper');

        const label = document.createElement('label');
        label.classList.add('label-medium');
        label.textContent = fieldLabelText + ': ';
        wrapperDiv.appendChild(label);

        let input;
        if (fieldData.options && fieldData.options.length) {
            input = document.createElement('select');
            input.name = fieldName;
            input.classList.add('category-select', 'select-tertiary', 'body-small-regular');

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '---';
            input.appendChild(defaultOption);

            fieldData.options.forEach(opt => {
                const val = opt[categorySelectorVars.language] || opt.ru;
                const option = document.createElement('option');
                option.value = val;
                option.textContent = val;
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.name = fieldName;
            input.classList.add('category-select', 'input-secondary', 'body-small-regular');
        }

        if (existingDynamicFields[fieldName] !== undefined) {
            input.value = existingDynamicFields[fieldName];
        }

        input.style.width = '100%';
        wrapperDiv.appendChild(input);
        return wrapperDiv;
    }

    function renderDynamicFields() {
        dynamicFieldsContainer.innerHTML = '';

        const selectedCategoryIds = selectedInput.value
            ? selectedInput.value.split(',').map(v => parseInt(v)).filter(v => !isNaN(v))
            : [];

        selectedCategoryIds.forEach(categoryId => {
            const categoryFields = categorySelectorVars.categoryFeatures[categoryId];
            if (!categoryFields) return;

            Object.entries(categoryFields).forEach(([fieldKey, fieldData]) => {
                const field = createDynamicField(fieldKey, fieldData);
                dynamicFieldsContainer.appendChild(field);
            });
        });
    }

    function handleSelectChange(select) {
        const level = parseInt(select.dataset.level);
        removeLowerLevels(level);
        if (select.value) loadSubcategories(select.value, level + 1);

        updateSelectedInput();
        renderDynamicFields();
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
            });
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

        updateSelectedInput();
        renderDynamicFields();
    }

    if (preselectedTerms.length) restoreChain();
    else loadSubcategories(0, 0);
    ['create-product-form', 'edit-product-form'].forEach(formId => {
        const form = document.getElementById(formId);
        if (!form) return;

form.addEventListener('submit', function (e) {
    const allDynamicData = {};
    dynamicFieldsContainer.querySelectorAll('input, select').forEach(input => {
        if (input && input.name) {
            const value = input.value.trim();
            if (value !== '') {
                allDynamicData[input.name] = value;
            }
        }
    });
    hiddenDynamicFields.value = JSON.stringify(allDynamicData);
});

    });
});
