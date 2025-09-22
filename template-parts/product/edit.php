<?php
$product_id = $args['product_id'] ?? get_the_ID();
if (!$product_id) return;

require_once get_theme_file_path('includes/product-helpers.php');

$title       = esc_attr(get_the_title($product_id));
$content     = esc_textarea(get_post_field('post_content', $product_id));
$status      = get_post_status($product_id);
$thumbnail   = get_post_thumbnail_id($product_id);
$gallery     = get_post_meta($product_id, 'product_gallery', true);
$gallery     = is_array($gallery) ? $gallery : [];
$price       = get_post_meta($product_id, 'product_price', true);
$currency    = strtolower(get_post_meta($product_id, 'product_currency', true) ?: 'lei');
$type        = get_post_meta($product_id, 'product_type', true) ?: 'sell';
$selected_terms = wp_get_post_terms($product_id, 'product_cat');
$sorted_term_ids = function_exists('sort_categories_by_hierarchy') ? sort_categories_by_hierarchy($selected_terms) : [];
$language    = $GLOBALS['language'] ?? 'ru';
?>
<script>
window.existingDynamicFields = <?php
    $dynamic_features = get_post_meta($product_id, 'dynamic_features', true);
    if (!is_array($dynamic_features)) $dynamic_features = [];
    echo json_encode($dynamic_features, JSON_UNESCAPED_UNICODE);
?>;
</script>

<div class="product__wrapper edit content-main">
    <div class="container-medium">
        <main>
            <section class="product-create">
                <form id="edit-product-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                    <?php wp_nonce_field('save_product_form', 'product_form_nonce'); ?>

                    <h1 class="product-create__title display-large">
                        <?php echo t('Редактировать объявление', 'Edit Listing', 'Editează Anunț'); ?>
                    </h1>

                    <section class="form-group form-group--type">
                        <div class="input-block">
                            <label class="form-label label-large" for="product_type"><?php echo t('Тип объявления', 'Type', 'Tip'); ?></label>
                            <select name="product_type" id="product_type" class="form-select select--secondary body-medium-regular">
                                <option value="sell" <?php selected($type, 'sell'); ?>><?php echo t('Продам', 'Sell', 'Vând'); ?></option>
                                <option value="buy" <?php selected($type, 'buy'); ?>><?php echo t('Куплю', 'Buy', 'Cumpăr'); ?></option>
                            </select>
                        </div>
                    </section>

                    <fieldset class="form-group form-group--categories">
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
                        <input type="hidden" id="selected_categories_input" name="product_categories[]" value="">
                    </fieldset>

                    <section class="form-group form-group--dynamic-features" id="dynamic-features-container">
                        <label class="label-large">
                            <?php echo t('Дополнительные характеристики', 'Additional features', 'Caracteristici suplimentare'); ?>
                        </label>
                        <div class="dynamic-features-fields" id="dynamic-features-fields"></div>
                        <input type="hidden" id="dynamic_fields_input" name="dynamic_fields" value="">
                    </section>

                    <section class="form-group form-group--tabs tabs">
                        <ul class="tab-buttons" role="tablist">
                            <?php foreach (['ru','en','ro'] as $lang): ?>
                                <li class="tab-btn body-small-semibold <?php if ($language === $lang) echo 'active'; ?>" data-tab="tab-<?php echo $lang; ?>"><?php echo strtoupper($lang); ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <?php foreach (['ru','en','ro'] as $lang): ?>
                            <?php
                            $title_key = $lang === 'ru' ? 'product_title' : "title_{$lang}";
                            $desc_key  = $lang === 'ru' ? 'product_content' : "description_{$lang}";
                            $title_val = $lang === 'ru' ? $title : esc_attr(get_post_meta($product_id, "_title_{$lang}", true));
                            $desc_val  = $lang === 'ru' ? $content : esc_textarea(get_post_meta($product_id, "_description_{$lang}", true));
                            ?>
                            <div class="tab-content <?php if ($language === $lang) echo 'active'; ?>" data-lang="<?php echo $lang; ?>" id="tab-<?php echo $lang; ?>">
                                <div class="input-block">
                                    <label class="label-large" for="title_<?php echo $lang; ?>"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                                    <input type="text" id="title_<?php echo $lang; ?>" name="<?php echo $title_key; ?>" class="form-input input--secondary body-medium-regular" value="<?php echo $title_val; ?>">
                                </div>

                                <div class="input-block">
                                    <label class="label-large" for="desc_<?php echo $lang; ?>"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                                    <textarea id="desc_<?php echo $lang; ?>" name="<?php echo $desc_key; ?>" rows="12" maxlength="2000" oninput="updateCharCount(this)" class="form-textarea textarea--secondary body-medium-regular"><?php echo $desc_val; ?></textarea>
                                    <small class="form-hint body-small-regular">0 / 2000</small>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="content-setting">
                            <div id="translation-message" class="form-message body-small-regular"></div>
                            <div class="dropdown">
                                <button id="translation-action-button" class="secondary-button-small" type="button">
                                    <?php echo t('Действия', 'Actions', 'Acțiuni'); ?>
                                </button>
                                <div class="dropdown-content" id="translation-action-menu">
                                    <button class="link-button-gray" type="button" onclick="generateTranslations()">
                                        <?php echo t('Генерировать переводы', 'Generate translations', 'Generează traduceri'); ?>
                                    </button>
                                    <button class="link-button-gray" type="button" onclick="showImproveOptions()">
                                        <?php echo t('Улучшить текст', 'Improve text', 'Îmbunătățește textul'); ?>
                                    </button>
                                    <button class="link-button-gray" type="button" onclick="generateSEOText()">
                                        <?php echo t('Сгенерировать SEO-текст', 'Generate SEO text', 'Generează text SEO'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                        
                    <fieldset class="form-group form-group--gallery">
                        <label class="form-label label-large" for="product_gallery_input">
                            <?php echo t('Изображения (до 10 шт., первое — миниатюра)', 'Images (up to 10, first is thumbnail)', 'Imagini (până la 10, prima este miniatura)'); ?>
                        </label>

                        <input type="file" name="product_gallery_input[]" class="visually-hidden" id="product_gallery_input" multiple accept="image/*" onchange="checkGalleryLimit(this)">
                        <input type="hidden" id="gallery_order_input" name="gallery_order_input" value="<?php echo esc_attr(implode(',', $gallery)); ?>">
                        <input type="hidden" id="remove_gallery_ids_input" name="remove_gallery_ids_input" value="">

                        <div id="gallery_preview" class="gallery-preview">

                            <?php foreach ($gallery as $index => $id): ?>
                                <div class="gallery-item<?php echo ($index === 0) ? ' thumbnail' : ''; ?>" data-id="<?php echo esc_attr($id); ?>">
                                    <?php echo wp_get_attachment_image($id, 'medium'); ?>
                                    <input type="hidden" name="existing_gallery_ids[]" value="<?php echo esc_attr($id); ?>">
                                    <button type="button" class="gallery-remove" title="<?php echo t('Удалить', 'Remove', 'Șterge'); ?>">✕</button>
                                </div>
                            <?php endforeach; ?>
                            
                            <label class="btn-upload" for="product_gallery_input">
                                <div class="btn-upload__icon">
                                    <?php 
                                        $svg = file_get_contents(get_template_directory() . '/images/icon-camera.svg');
                                        $svg = str_replace('<svg', '<svg class="icon icon-camera"', $svg);
                                        echo $svg;
                                    ?>
                                </div>
                                <span class="btn-upload__text uppercase-small">
                                    <?php echo t('Добавить фото (до 10 шт.)', 'Add photo (up to 10)', 'Adaugă foto (până la 10)'); ?>
                                </span>
                            </label>
                        </div>
                            
                        <small class="form-hint body-small-regular">
                            <?php echo t('Первое изображение станет миниатюрой.', 'The first image will become the thumbnail.', 'Prima imagine va deveni miniatura.'); ?>
                        </small>
                        <div class="form-message body-small-regular" id="message_product_gallery"></div>
                    </fieldset>

                    <div class="form-group form-group--price">
                        <div class="form-group__left">
                            <label class="form-label label-large" for="product_price">
                                <?php echo t('Цена', 'Price', 'Preț'); ?>
                            </label>
                            <div class="price-input-wrapper">
                                <div class="input-block">
                                    <input type="number" step="0.01" name="product_price" id="product_price"
                                        value="<?php echo esc_attr($price); ?>"
                                        class="form-input input--secondary body-medium-regular"
                                        placeholder="<?php echo t('Укажите цену', 'Enter the price', 'Introduceți prețul'); ?>"
                                        min="0.01" max="1000000">
                                </div>

                                <div class="input-block">
                                    <select name="product_currency"
                                        class="form-select select--secondary body-medium-regular">
                                        <option value="lei" <?php selected($currency, 'lei'); ?>>lei</option>
                                        <option value="usd" <?php selected($currency, 'usd'); ?>>usd</option>
                                        <option value="eur" <?php selected($currency, 'eur'); ?>>eur</option>
                                    </select>
                                </div>                 
                            </div>
                        </div>
                        <div class="form-group__right">
                            <div class="input-block">
                                <label class="form-label label-large" for="product_status"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                                <select id="product_status" name="product_status" class="form-select select--secondary body-medium-regular">
                                    <option value="draft" <?php selected($status, 'draft'); ?>><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                                    <option value="publish" <?php selected($status, 'publish'); ?>><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-message body-small-regular" id="message_product_status"></div>
                    </div>

                    <div class="form-group">
                        <input type="submit" name="submit_product" value="<?php echo t('Обновить', 'Update', 'Actualizează'); ?>" class="form-submit primary-button-large button-large">
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
