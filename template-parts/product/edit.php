<?php
$product_id = $args['product_id'] ?? get_the_ID();
if (!$product_id) return;

require_once get_theme_file_path('includes/product-helpers.php');

$title = esc_attr(get_the_title($product_id));
$content = esc_textarea(get_post_field('post_content', $product_id));
$status = get_post_status($product_id);
$thumbnail_id = get_post_thumbnail_id($product_id);
$gallery = get_field('product_gallery', $product_id);
$gallery_ids = [];

if (!empty($gallery)) {
    foreach ($gallery as $image) {
        if (is_array($image) && isset($image['ID'])) $gallery_ids[] = $image['ID'];
        elseif (is_numeric($image)) $gallery_ids[] = $image;
    }
}

if ($thumbnail_id && !in_array($thumbnail_id, $gallery_ids)) array_unshift($gallery_ids, $thumbnail_id);

$price = get_post_meta($product_id, 'product_price', true);
$selected_categories = wp_get_post_terms($product_id, 'product_cat');
$sorted_term_ids = function_exists('sort_categories_by_hierarchy') ? sort_categories_by_hierarchy($selected_categories) : [];
$language = $GLOBALS['language'] ?? 'ru';
?>

<div class="product__wrapper edit">
    <div class="container-medium">
        <section class="product-edit">
            <form method="post" enctype="multipart/form-data">
                <h1 class="product-edit__title display-small"><?php echo t('Редактировать объявление', 'Edit Listing', 'Editează Anunț'); ?></h1>
                <?php wp_nonce_field('save_product_form', 'product_form_nonce'); ?>

                <!-- Categories -->
                <fieldset class="form-group">
                    <div id="category-selectors" class="category-selectors" data-restored="1">
                        <div id="preselected-categories" data-terms="<?php echo esc_attr(json_encode($sorted_term_ids)); ?>"></div>
                        <script>
                            const translations = {
                                selectCategory: <?php echo json_encode(t('Выберите категорию', 'Select category', 'Selectați categoria')); ?>,
                                labelLevel0: <?php echo json_encode(t('Категория', 'Category', 'Categorie')); ?>,
                                labelLevel1: <?php echo json_encode(t('Подкатегория', 'Subcategory', 'Subcategorie')); ?>,
                                labelLevel2: <?php echo json_encode(t('Под-подкатегория', 'Sub-subcategory', 'Sub-subcategorie')); ?>
                            };
                        </script>
                    </div>
                </fieldset>

                <!-- Language tabs -->
                <section class="form-group tabs">
                    <ul class="tab-buttons" role="tablist">
                        <?php foreach (['ru','en','ro'] as $lang): ?>
                            <li class="tab-btn body-small-semibold <?php if ($language === $lang) echo 'active'; ?>" data-tab="tab-<?php echo $lang; ?>"><?php echo strtoupper($lang); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php foreach (['ru','en','ro'] as $lang): ?>
                        <?php
                        $title_key = $lang === 'ru' ? 'product_title' : "title_{$lang}";
                        $desc_key = $lang === 'ru' ? 'product_content' : "description_{$lang}";
                        $title_val = $lang === 'ru' ? $title : esc_attr(get_post_meta($product_id, "_title_{$lang}", true));
                        $desc_val = $lang === 'ru' ? $content : esc_textarea(get_post_meta($product_id, "_description_{$lang}", true));
                        ?>
                        <div class="tab-content <?php if ($language === $lang) echo 'active'; ?>" id="tab-<?php echo $lang; ?>">
                            <label class="label-large" for="title_<?php echo $lang; ?>"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                            <input type="text" id="title_<?php echo $lang; ?>" name="<?php echo $title_key; ?>" class="form-input input-secondary body-medium-regular" value="<?php echo $title_val; ?>">

                            <label class="label-large" for="desc_<?php echo $lang; ?>"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                            <textarea id="desc_<?php echo $lang; ?>" name="<?php echo $desc_key; ?>" rows="5" maxlength="300" oninput="updateCharCount(this)" class="form-textarea input-tertiary body-medium-regular"><?php echo $desc_val; ?></textarea>
                            <small class="body-small-regular">0 / 300</small>
                        </div>
                    <?php endforeach; ?>

                    <div class="translation-button">
                        <div id="translation-message" class="form-message body-medium-regular"></div>
                        <button type="button" class="button secondary-button-small generate-translation" onclick="generateTranslations()">
                            <?php echo t('Сгенерировать переводы', 'Generate Translations', 'Generează traduceri'); ?>
                        </button>
                    </div>
                </section>

                <!-- Status -->
                <fieldset class="form-group">
                    <label class="form-label label-large" for="product_status"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                    <select id="product_status" name="product_status" class="form-select body-medium-regular">
                        <option value="draft" <?php selected($status, 'draft'); ?>><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                        <option value="publish" <?php selected($status, 'publish'); ?>><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                    </select>
                </fieldset>
 
                <!-- Gallery -->
                <fieldset class="form-group">
                    <label class="form-label label-large" for="product_gallery_input"><?php echo t('Изображения (до 6 шт., первое — миниатюра)', 'Images (up to 6, first is thumbnail)', 'Imagini (până la 6, prima este miniatura)'); ?></label>
                    <input type="file" name="product_gallery_input[]" id="product_gallery_input" multiple accept="image/*" onchange="checkGalleryLimit(this)">
                    <input type="hidden" id="gallery_order_input" name="gallery_order_input" value="">
                    <input type="hidden" id="remove_gallery_ids_input" name="remove_gallery_ids_input" value="">

                    <div id="gallery_preview" class="gallery-preview">
                        <?php foreach ($gallery_ids as $index => $id): ?>
                            <div class="gallery-item<?php echo ($index === 0) ? ' thumbnail' : ''; ?>" data-id="<?php echo esc_attr($id); ?>">
                                <?php echo wp_get_attachment_image($id, 'full'); ?>
                                <input type="hidden" name="existing_gallery_ids[]" value="<?php echo esc_attr($id); ?>">
                                <button type="button" class="gallery-remove link-small-default" title="<?php echo t('Удалить', 'Remove', 'Șterge'); ?>">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- Price -->
                <div class="form-group">
                    <label class="form-label label-large"><?php echo t('Цена (леи)', 'Price (lei)', 'Preț (lei)'); ?></label>
                    <input type="number" step="0.01" name="product_price" value="<?php echo esc_attr($price); ?>" class="form-input body-medium-regular" required>
                </div>

                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                <div class="form-group">
                    <input type="submit" name="submit_product" value="<?php echo t('Обновить', 'Update', 'Actualizează'); ?>" class="form-submit primary-button-large button-large">
                </div>
            </form>

            <!-- Progress -->
            <nav class="form-progress" aria-label="Progress">
                <ol class="form-progress__steps" id="form-progress-bar">
                    <?php foreach (['category','title','description','image','price'] as $step): ?>
                        <li class="form-progress__step" data-step="<?php echo $step; ?>" <?php if ($step==='title') echo 'aria-current="step"'; ?>>
                            <span class="form-progress__circle"></span>
                            <span class="form-progress__label body-small-semibold"><?php echo t(ucfirst($step), ucfirst($step), ucfirst($step)); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </section>
    </div>
</div>
