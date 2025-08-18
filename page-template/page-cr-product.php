<?php
/* Template Name: Создание товара */

if (!is_user_logged_in()) {
    wp_die(t('Только для зарегистрированных пользователей.', 'Only for registered users.', 'Doar pentru utilizatori înregistrați.'));
}

$current_user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product'])) {
    if (!isset($_POST['product_form_nonce']) || !wp_verify_nonce($_POST['product_form_nonce'], 'create_product_form')) {
        wp_die(t('Ошибка безопасности.', 'Security error.', 'Eroare de securitate.'));
    }

    $post_data = [
        'post_title'   => sanitize_text_field($_POST['product_title']),
        'post_content' => sanitize_textarea_field($_POST['product_content']),
        'post_status'  => sanitize_text_field($_POST['product_status']),
        'post_type'    => 'product',
        'post_author'  => $current_user_id,
    ];

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        update_post_meta($post_id, 'product_price', floatval($_POST['product_price']));
        update_post_meta($post_id, '_title_en', sanitize_text_field($_POST['title_en']));
        update_post_meta($post_id, '_description_en', sanitize_textarea_field($_POST['description_en']));
        update_post_meta($post_id, '_title_ro', sanitize_text_field($_POST['title_ro']));
        update_post_meta($post_id, '_description_ro', sanitize_textarea_field($_POST['description_ro']));

        if (!empty($_POST['selected_categories'])) {
            wp_set_post_terms($post_id, array_map('intval', $_POST['selected_categories']), 'product_cat');
        }

        if (!empty($_FILES['product_gallery']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attachment_ids = [];
            foreach ($_FILES['product_gallery']['name'] as $key => $value) {
                if ($_FILES['product_gallery']['name'][$key]) {
                    $file = [
                        'name'     => $_FILES['product_gallery']['name'][$key],
                        'type'     => $_FILES['product_gallery']['type'][$key],
                        'tmp_name' => $_FILES['product_gallery']['tmp_name'][$key],
                        'error'    => $_FILES['product_gallery']['error'][$key],
                        'size'     => $_FILES['product_gallery']['size'][$key],
                    ];
                    $_FILES['upload_attachment'] = $file;
                    $attachment_id = media_handle_upload('upload_attachment', $post_id);
                    if (!is_wp_error($attachment_id)) {
                        $attachment_ids[] = $attachment_id;
                    }
                }
            }
            if (!empty($attachment_ids)) {
                set_post_thumbnail($post_id, $attachment_ids[0]);
                update_post_meta($post_id, '_product_image_gallery', implode(',', $attachment_ids));
            }
        }

        wp_safe_redirect(get_permalink($post_id));
        exit;
    } else {
        wp_die(t('Ошибка создания.', 'Creation error.', 'Eroare la creare.'));
    }
}

get_header();
?>

<div class="product__wrapper create">
    <div class="container-medium">
        <section class="product-create">
            <form method="post" enctype="multipart/form-data">
                <h1 class="product-create__title display-small"><?php echo t('Создать объявление', 'Create Listing', 'Creează Anunț'); ?></h1>
                <?php wp_nonce_field('create_product_form', 'product_form_nonce'); ?>

                <fieldset class="form-group">
                    <div class="category-selectors" id="category-selectors" data-restored="1">
                        <?php
                        $selected_categories = isset($selected_categories) ? $selected_categories : [];
                        $sorted_term_ids = sort_categories_by_hierarchy($selected_categories);
                        ?>
                        <div id="preselected-categories" data-terms="<?php echo esc_attr(json_encode($sorted_term_ids)); ?>"></div>
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

                <section class="form-group tabs">
                    <?php $language = $GLOBALS['language']; ?>
                    <ul class="tab-buttons">
                        <li class="tab-btn body-small-semibold <?php if ($language === 'ru') echo 'active'; ?>" data-tab="tab-ru">RU</li>
                        <li class="tab-btn body-small-semibold <?php if ($language === 'en') echo 'active'; ?>" data-tab="tab-en">EN</li>
                        <li class="tab-btn body-small-semibold <?php if ($language === 'ro') echo 'active'; ?>" data-tab="tab-ro">RO</li>
                    </ul>

                    <div class="tab-content <?php if ($language === 'ru') echo 'active'; ?>" id="tab-ru">
                        <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                        <input type="text" class="form-input input-secondary body-medium-regular" name="product_title"
                               placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>">
                        <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                        <textarea name="product_content" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                  placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"></textarea>
                    </div>

                    <div class="tab-content <?php if ($language === 'en') echo 'active'; ?>" id="tab-en">
                        <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                        <input type="text" class="form-input input-secondary body-medium-regular" name="title_en"
                               placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>">
                        <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                        <textarea name="description_en" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                  placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"></textarea>
                    </div>

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

                <div class="form-group">
                    <label class="form-label label-large">
                        <?php echo t('Изображения (до 6 шт., первое — миниатюра)', 'Images (up to 6, first is thumbnail)', 'Imagini (până la 6, prima este miniatura)'); ?>
                    </label>

                    <input type="file" name="product_gallery[]" accept="image/*" multiple class="form-file body-medium-regular" id="product_gallery_input" onchange="checkGalleryLimit(this)">

                    <input type="hidden" name="gallery_order" id="gallery_order_input" value="">
                    <input type="hidden" name="remove_gallery_ids[]" id="remove_gallery_ids_input" value="">
                    <input type="hidden" name="main_thumbnail_id" id="main_thumbnail_id" value="">

                    <div id="gallery_preview" class="gallery-preview">
                        <?php if (!empty($gallery_ids)): ?>
                            <?php foreach (array_filter($gallery_ids) as $index => $id): ?>
                                <div class="gallery-item<?php echo ($index === 0) ? ' thumbnail' : ''; ?>" data-id="<?php echo esc_attr($id); ?>">
                                    <?php echo wp_get_attachment_image($id, 'full'); ?>
                                    <input type="hidden" name="existing_gallery_ids[]" value="<?php echo esc_attr($id); ?>">
                                    <span class="gallery-remove link-small-default" title="<?php echo t('Удалить', 'Remove', 'Șterge'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                    <button type="button" class="set-thumbnail-btn" title="<?php echo t('Сделать миниатюрой', 'Set as thumbnail', 'Setează ca miniatură'); ?>">★</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <section class="form-group">
                    <label class="form-label label-large"><?php echo t('Цена (леи)', 'Price (lei)', 'Preț (lei)'); ?></label>
                    <input type="number" step="0.01" name="product_price" class="form-input input-secondary body-medium-regular" required>
                </section>

                <div class="form-group">
                    <label class="form-label"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                    <select name="product_status" class="form-select">
                        <option value="draft"><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                        <option value="publish"><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                    </select>
                </div>

                <!-- Кнопка -->
                <div class="form-group">
                    <input type="submit" name="submit_product" value="<?php echo t('Создать', 'Create', 'Creează'); ?>" class="form-submit primary-button-large button-large">
                </div>
            </form>

            <nav class="form-progress" aria-label="Progress">
                <ol class="form-progress__steps" id="form-progress-bar">
                    <li class="form-progress__step" data-step="category">
                        <span class="form-progress__circle"></span>
                        <span class="form-progress__label body-small-semibold"><?php echo t('Категория', 'Category', 'Categorie'); ?></span>
                    </li>
                    <li class="form-progress__step" data-step="title" aria-current="step">
                        <span class="form-progress__circle"></span>
                        <span class="form-progress__label body-small-semibold"><?php echo t('Название', 'Title', 'Titlu'); ?></span>
                    </li>
                    <li class="form-progress__step" data-step="description">
                        <span class="form-progress__circle"></span>
                        <span class="form-progress__label body-small-semibold"><?php echo t('Описание', 'Description', 'Descriere'); ?></span>
                    </li>
                    <li class="form-progress__step" data-step="image">
                        <span class="form-progress__circle"></span>
                        <span class="form-progress__label body-small-semibold"><?php echo t('Изображения', 'Images', 'Imagini'); ?></span>
                    </li>
                    <li class="form-progress__step" data-step="price">
                        <span class="form-progress__circle"></span>
                        <span class="form-progress__label body-small-semibold"><?php echo t('Цена', 'Price', 'Preț'); ?></span>
                    </li>
                </ol>
            </nav>
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
});
</script>

<?php get_footer(); ?>
