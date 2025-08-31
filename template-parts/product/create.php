<?php
$current_user_id = $args['current_user_id'] ?? get_current_user_id();
?>

<div class="product__wrapper create content-main">
    <div class="container-medium">
        <section class="product-create">
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php?action=create_product'); ?>">
                <h1 class="product-create__title display-small"><?php echo t('Создать объявление', 'Create Listing', 'Creează Anunț'); ?></h1>
                <?php wp_nonce_field('create_product_form', 'product_form_nonce'); ?>

                <!-- Категории -->
                <fieldset class="form-group">
                    <div class="category-selectors" id="category-selectors" data-restored="1">
                        <?php
                        $selected_categories = isset($selected_categories) ? $selected_categories : [];
                        $sorted_term_ids = sort_categories_by_hierarchy($selected_categories);
                        ?>
                        <div id="preselected-categories" data-terms="<?php echo esc_attr(json_encode($sorted_term_ids)); ?>"></div>
                        <input type="hidden" name="selected_categories" id="selected_categories_input" value="">
                        <script>
                            const translations = {
                                selectCategory: <?php echo json_encode(t('Выберите категорию', 'Select category', 'Selectați categoria')); ?>,
                                labelLevel0: <?php echo json_encode(t('Категория', 'Category', 'Categorie')); ?>,
                                labelLevel1: <?php echo json_encode(t('Подкатегория', 'Subcategory', 'Subcategorie')); ?>,
                                labelLevel2: <?php echo json_encode(t('Под-подкатегория', 'Sub-subcategory', 'Sub-subcategorie')); ?>,
                            };
                        </script>
                    </div>
                </fieldset>

                <!-- Табы с языками -->
                <section class="form-group tabs">
                    <?php $language = $GLOBALS['language']; ?>
                    <ul class="tab-buttons">
                        <li class="tab-btn body-small-semibold <?php if ($language === 'ru') echo 'active'; ?>" data-tab="tab-ru">RU</li>
                        <li class="tab-btn body-small-semibold <?php if ($language === 'en') echo 'active'; ?>" data-tab="tab-en">EN</li>
                        <li class="tab-btn body-small-semibold <?php if ($language === 'ro') echo 'active'; ?>" data-tab="tab-ro">RO</li>
                    </ul>

                    <!-- RU -->
                    <div class="tab-content <?php if ($language === 'ru') echo 'active'; ?>" id="tab-ru">
                        <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                        <input type="text" class="form-input input-secondary body-medium-regular" name="product_title"
                               placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>">
                        <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                        <textarea name="product_content" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                  placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"></textarea>
                    </div>

                    <!-- EN -->
                    <div class="tab-content <?php if ($language === 'en') echo 'active'; ?>" id="tab-en">
                        <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                        <input type="text" class="form-input input-secondary body-medium-regular" name="title_en"
                               placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>">
                        <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                        <textarea name="description_en" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                  placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"></textarea>
                    </div>

                    <!-- RO -->
                    <div class="tab-content <?php if ($language === 'ro') echo 'active'; ?>" id="tab-ro">
                        <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                        <input type="text" class="form-input input-secondary body-medium-regular" name="title_ro"
                               placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>">
                        <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                        <textarea name="description_ro" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                  placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"></textarea>
                        <small class="body-small-regular">0 / 300</small>
                    </div>

                    <div class="translation-button">
                        <div id="translation-message" class="form-message body-medium-regular"></div>
                        <button type="button" class="button secondary-button-small generate-translation" onclick="generateTranslations()">
                            <?php echo t('Сгенерировать переводы', 'Generate Translations', 'Generează traduceri'); ?>
                        </button>
                    </div>
                </section>

                <!-- Галерея -->
                <div class="form-group">
                    <label class="form-label label-large">
                        <?php echo t('Изображения (до 6 шт., первое — миниатюра)', 'Images (up to 6, first is thumbnail)', 'Imagini (până la 6, prima este miniatura)'); ?>
                    </label>
                    <input type="file" name="product_gallery[]" accept="image/*" multiple class="form-file body-medium-regular" id="product_gallery_input" onchange="checkGalleryLimit(this)">
                    <input type="hidden" name="gallery_order" id="gallery_order_input" value="">
                    <input type="hidden" name="remove_gallery_ids[]" id="remove_gallery_ids_input" value="">
                    <input type="hidden" name="main_thumbnail_id" id="main_thumbnail_id" value="">
                    <div id="gallery_preview" class="gallery-preview"></div>
                </div>

                <!-- Цена -->
                <section class="form-group">
                    <label class="form-label label-large"><?php echo t('Цена (леи)', 'Price (lei)', 'Preț (lei)'); ?></label>
                    <input type="number" step="0.01" name="product_price" class="form-input input-secondary body-medium-regular" required>
                </section>

                <!-- Статус -->
                <div class="form-group">
                    <label class="form-label"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                    <select name="product_status" class="form-select">
                        <option value="draft"><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                        <option value="publish"><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <input type="submit" name="submit_product" value="<?php echo t('Создать', 'Create', 'Creează'); ?>" class="form-submit primary-button-large button-large">
                </div>
            </form>
        </section>
    </div>
</div>

<script>
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
</script>
