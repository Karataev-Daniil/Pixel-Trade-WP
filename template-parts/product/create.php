<?php
$current_user_id = $args['current_user_id'] ?? get_current_user_id();
$form_data = get_transient('product_form_errors_' . $current_user_id);
delete_transient('product_form_errors_' . $current_user_id);

$errors = $form_data['errors'] ?? [];
$old = $form_data['old'] ?? [];
?>

<script>
const existingDynamicFields = <?php
    echo json_encode($old['dynamic_fields'] ?? (isset($product_id) ? get_post_meta($product_id, 'dynamic_features', true) : []));
?> || {};
</script>

<div class="product__wrapper create content-main">
    <div class="container-medium">
        <main>
            <section class="product-create">
                <form id="create-product-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="create_product">
                    <?php wp_nonce_field('create_product_form', 'product_form_nonce'); ?>

                    <h1 class="product-create__title display-large">
                        <?php echo t('Создать объявление', 'Create Listing', 'Creează Anunț'); ?>
                    </h1>

                    <!-- Type -->
                    <section class="form-group form-group--type">
                        <div class="input-block">
                            <label class="form-label label-large" for="product_type">
                                <?php echo t('Тип объявления', 'Listing type', 'Tip anunț'); ?>
                            </label>
                            <select id="product_type" name="product_type" class="form-select select--secondary body-small-regular">
                                <option value="sell" <?php selected($old['product_type'] ?? '', 'sell'); ?>><?php echo t('Продам', 'Sell', 'Vând'); ?></option>
                                <option value="buy" <?php selected($old['product_type'] ?? '', 'buy'); ?>><?php echo t('Куплю', 'Buy', 'Cumpăr'); ?></option>
                            </select>
                            <small class="form-hint body-small-regular">
                                <?php echo t('Выберите, что хотите сделать: продать или купить', 'Select whether you want to sell or buy', 'Alegeți dacă doriți să vindeți sau să cumpărați'); ?>
                            </small>
                            <div class="form-message body-small-regular" id="message_product_type">
                                <?php if (!empty($errors['product_type'])): ?>
                                    <span class="error-text"><?php echo esc_html($errors['product_type']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Categories -->
                    <fieldset class="form-group form-group--categories">
                        <div class="category-selectors" id="category-selectors" data-restored="1">
                            <?php
                            $selected_categories = $old['selected_categories'] ?? [];
                            $sorted_term_ids = sort_categories_by_hierarchy($selected_categories);
                            ?>
                            <div id="preselected-categories" data-terms="<?php echo esc_attr(json_encode($sorted_term_ids)); ?>"></div>
                            <?php
                            $selected_categories = $selected_categories ?? [];
                            if (!is_array($selected_categories)) {
                                $selected_categories = array_filter(array_map('trim', explode(',', $selected_categories)));
                            }

                            $sorted_term_ids = sort_categories_by_hierarchy($selected_categories);
                            ?>
                            <input type="hidden" name="selected_categories" id="selected_categories_input" 
                                        value="<?php echo esc_attr(implode(',', $selected_categories)); ?>">


                            <script>
                                const translations = {
                                    selectCategory: <?php echo json_encode(t('Выберите категорию', 'Select category', 'Selectați categoria')); ?>,
                                    labelLevel0: <?php echo json_encode(t('Категория', 'Category', 'Categorie')); ?>,
                                    labelLevel1: <?php echo json_encode(t('Подкатегория', 'Subcategory', 'Subcategorie')); ?>,
                                    labelLevel2: <?php echo json_encode(t('Под-подкатегория', 'Sub-subcategory', 'Sub-subcategorie')); ?>,
                                };
                            </script>
                        </div>
                        <small class="form-hint body-small-regular">
                            <?php echo t('Выберите категорию товара. Можно выбрать несколько уровней', 'Select the product category. Multiple levels allowed', 'Selectați categoria produsului. Sunt permise mai multe niveluri'); ?>
                        </small>
                        <div class="form-message body-small-regular" id="message_selected_categories">
                            <?php if (!empty($errors['selected_categories'])): ?>
                                <span class="error-text"><?php echo esc_html($errors['selected_categories']); ?></span>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <!-- Dynamic Features -->
                    <section class="form-group form-group--dynamic-features" id="dynamic-features-container">
                        <label class="label-large">
                            <?php echo t('Дополнительные характеристики', 'Additional features', 'Caracteristici suplimentare'); ?>
                        </label>
                        <div class="dynamic-features-fields" id="dynamic-features-fields"></div>
                        <input type="hidden" name="dynamic_fields" id="dynamic_fields_input" value="<?php echo esc_attr(json_encode($old['dynamic_fields'] ?? [])); ?>">
                        <div class="form-message body-small-regular" id="message_dynamic_fields">
                            <?php if (!empty($errors['dynamic_fields'])): ?>
                                <span class="error-text"><?php echo esc_html($errors['dynamic_fields']); ?></span>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Tabs -->
                    <section class="form-group form-group--tabs tabs">
                        <?php $language = $GLOBALS['language']; ?>
                        <input type="hidden" name="product_lang" id="product_lang_input" value="<?php echo esc_attr($language); ?>">
                        <ul class="tab-buttons">
                            <li class="tab-btn body-small-semibold <?php if ($language === 'ru') echo 'active'; ?>" data-tab="tab-ru">RU</li>
                            <li class="tab-btn body-small-semibold <?php if ($language === 'en') echo 'active'; ?>" data-tab="tab-en">EN</li>
                            <li class="tab-btn body-small-semibold <?php if ($language === 'ro') echo 'active'; ?>" data-tab="tab-ro">RO</li>
                        </ul>
                        <?php
                        $tabs = [
                            'ru' => ['title' => 'product_title', 'content' => 'product_content'],
                            'en' => ['title' => 'title_en', 'content' => 'description_en'],
                            'ro' => ['title' => 'title_ro', 'content' => 'description_ro']
                        ];
                        foreach ($tabs as $lang => $fields):
                        ?>
                        <div class="tab-content <?php if ($language === $lang) echo 'active'; ?>" id="tab-<?php echo $lang; ?>" data-lang="<?php echo $lang; ?>">
                            <div class="input-block">
                                <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                                <input type="text" class="form-input input--secondary body-small-regular" 
                                       name="<?php echo $fields['title']; ?>"
                                       value="<?php echo esc_attr($old[$fields['title']] ?? ''); ?>"
                                       placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>"
                                       data-lang="<?php echo $lang; ?>">
                                <div class="form-message body-small-regular" id="message_<?php echo $fields['title']; ?>">
                                    <?php if (!empty($errors[$fields['title']])): ?>
                                        <span class="error-text"><?php echo esc_html($errors[$fields['title']]); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="input-block">
                                <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                                <textarea name="<?php echo $fields['content']; ?>" rows="12" class="form-textarea textarea--secondary body-small-regular"
                                          placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"
                                          data-lang="<?php echo $lang; ?>"><?php echo esc_textarea($old[$fields['content']] ?? ''); ?></textarea>
                                <small class="form-hint body-small-regular">0 / 2000</small>
                                <div class="form-message body-small-regular" id="message_<?php echo $fields['content']; ?>">
                                    <?php if (!empty($errors[$fields['content']])): ?>
                                        <span class="error-text"><?php echo esc_html($errors[$fields['content']]); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="translation-dropdown">
                            <div id="translation-action-button" class="button secondary-button-small">
                                <?php echo t('Действия с текстом', 'Text Actions', 'Acțiuni text'); ?>
                            </div>
                            <div class="dropdown-content">
                                <div class="dropdown-item tertiary-button-small button-small" onclick="generateTranslations()">
                                    <?php echo t('Сгенерировать переводы', 'Generate Translations', 'Generează traduceri'); ?>
                                </div>
                                <div class="dropdown-item tertiary-button-small button-small" onclick="improveText()">
                                    <?php echo t('Улучшить текст', 'Improve Text', 'Îmbunătățește textul'); ?>
                                </div>
                                <div class="dropdown-item tertiary-button-small button-small" onclick="generateSEOText()">
                                    <?php echo t('Сгенерировать SEO-текст', 'Generate SEO Text', 'Generează text SEO'); ?>
                                </div>
                            </div>
                        </div>
                        <div id="action-message" class="form-message body-medium-regular"></div>
                    </section>

                    <!-- Gallery -->
                    <section class="form-group form-group--gallery">
                        <label class="form-label label-large"><?php echo t('Изображения', 'Images', 'Imagini'); ?></label>
                        <div id="gallery_preview" class="gallery-preview">
                            <label class="btn-upload" for="product_gallery_input">
                                <div class="btn-upload__icon">
                                    <?php 
                                        $svg = file_get_contents(get_template_directory() . '/images/camera.svg');
                                        $svg = str_replace('<svg', '<svg class="icon icon-camera"', $svg);
                                        echo $svg;
                                    ?>
                                </div>
                                <span class="btn-upload__text uppercase-small"><?php echo t('Добавить фото (до 10 шт.)', 'Add photo (up to 10)', 'Adaugă foto (până la 10)'); ?></span>
                            </label>
                        </div>
                        <small class="form-hint body-small-regular"><?php echo t('Первое изображение станет миниатюрой.', 'The first image will become the thumbnail.', 'Prima imagine va deveni miniatura.'); ?></small>
                        <input type="file" name="product_gallery[]" accept="image/*" multiple class="form-file visually-hidden" 
                               id="product_gallery_input" onchange="checkGalleryLimit(this)">
                        <input type="hidden" name="gallery_order" id="gallery_order_input" value="<?php echo esc_attr($old['gallery_order'] ?? ''); ?>">
                        <input type="hidden" name="remove_gallery_ids[]" id="remove_gallery_ids_input" value="<?php echo esc_attr($old['remove_gallery_ids'] ?? ''); ?>">
                        <input type="hidden" name="main_thumbnail_id" id="main_thumbnail_id" value="<?php echo esc_attr($old['main_thumbnail_id'] ?? ''); ?>">
                        <div class="form-message body-small-regular" id="message_product_gallery">
                            <?php if (!empty($errors['product_gallery'])): ?>
                                <span class="error-text"><?php echo esc_html($errors['product_gallery']); ?></span>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Price -->
                    <section class="form-group form-group--price">
                        <div class="form-group__left">
                            <label class="form-label label-large"><?php echo t('Цена', 'Price', 'Preț'); ?></label>
                            <div class="price-input-wrapper">
                                <div class="input-block">
                                    <input type="number" step="0.01" name="product_price" class="form-input input--secondary body-small-regular"
                                           placeholder="<?php echo t('Укажите цену', 'Enter the price', 'Introduceți prețul'); ?>"
                                           min="0.01" max="1000000"
                                           value="<?php echo esc_attr($old['product_price'] ?? ''); ?>">
                                    <div class="form-message body-small-regular" id="message_product_price">
                                        <?php if (!empty($errors['product_price'])): ?>
                                            <span class="error-text"><?php echo esc_html($errors['product_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="input-block">
                                    <select name="product_currency" class="form-select select--secondary body-small-regular">
                                        <option value="lei" <?php selected($old['product_currency'] ?? '', 'lei'); ?>>lei</option>
                                        <option value="usd" <?php selected($old['product_currency'] ?? '', 'usd'); ?>>usd</option>
                                        <option value="eur" <?php selected($old['product_currency'] ?? '', 'eur'); ?>>eur</option>
                                    </select>
                                    <div class="form-message body-small-regular" id="message_product_currency">
                                        <?php if (!empty($errors['product_currency'])): ?>
                                            <span class="error-text"><?php echo esc_html($errors['product_currency']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group__right">
                            <div class="input-block">
                                <label class="form-label label-large"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                                <select name="product_status" class="form-select select--secondary body-medium-regular">
                                    <option value="publish" <?php selected($old['product_status'] ?? '', 'publish'); ?>><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                                    <option value="draft" <?php selected($old['product_status'] ?? '', 'draft'); ?>><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                                </select>
                                <div class="form-message body-small-regular" id="message_product_status">
                                    <?php if (!empty($errors['product_status'])): ?>
                                        <span class="error-text"><?php echo esc_html($errors['product_status']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Submit -->
                    <div class="form-group">
                        <button type="submit" name="submit_product" class="form-submit primary-button-large">
                            <?php echo t('Создать', 'Create', 'Creează'); ?>
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
