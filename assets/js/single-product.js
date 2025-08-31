// глобальная переменная из wp_localize_script
const translations = singleProductData.translations;
const language = singleProductData.language;

function updateCharCount(textarea) {
    const count = textarea.value.length;
    const counter = textarea.nextElementSibling;
    if (counter) {
        counter.textContent = `${count} / 300`;
    }
    updateProgress();
}

function updateProgress() {
    const steps = {
        title: document.querySelector('input[name="product_title"]')?.value.trim().length > 0,
        description: document.querySelector('textarea[name="product_content"]')?.value.trim().length > 0,
        category: document.querySelector('#preselected-categories')?.dataset.terms !== '[]',
        price: (
            document.querySelector('input[name="product_price"]')?.value.trim().length > 0 ||
            document.querySelector('input[name="product_old_price"]')?.value.trim().length > 0
        ),
        image: document.querySelectorAll('#gallery_preview .gallery-item').length > 0,
    };

    document.querySelectorAll('.form-progress__step').forEach(step => {
        const key = step.dataset.step;
        step.classList.toggle('filled', steps[key]);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateProgress();

    document.querySelectorAll('input, textarea, select').forEach(el => {
        el.addEventListener('input', updateProgress);
        el.addEventListener('change', updateProgress);
    });

    document.querySelectorAll('textarea[maxlength]').forEach(updateCharCount);

    // табы
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

    // сортировка галереи
    const gallery = document.getElementById('gallery_preview');
    if (gallery) {
        new Sortable(gallery, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                updateGalleryOrder();
            }
        });
    }
});
