<?php
$current_user_id = $args['current_user_id'] ?? get_current_user_id();
?>
<script>
const existingDynamicFields = <?php
    echo json_encode(
        isset($product_id) ? get_post_meta($product_id, 'dynamic_features', true) : []
    );
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

                    <section class="form-group form-group--type">
                        <label class="form-label label-large" for="product_type">
                            <?php echo t('Тип объявления', 'Listing type', 'Tip anunț'); ?>
                        </label>
                        <select id="product_type" name="product_type" class="form-select select-tertiary body-medium-regular">
                            <option value="sell"><?php echo t('Продам', 'Sell', 'Vând'); ?></option>
                            <option value="buy"><?php echo t('Куплю', 'Buy', 'Cumpăr'); ?></option>
                        </select>
                        <small class="form-hint body-small-regular">
                            <?php echo t('Выберите, что хотите сделать: продать или купить', 'Select whether you want to sell or buy', 'Alegeți dacă doriți să vindeți sau să cumpărați'); ?>
                        </small>
                        <div class="form-message body-small-regular" id="message_product_type"></div>
                    </section>

                    <fieldset class="form-group form-group--categories">
                        <div class="category-selectors" id="category-selectors" data-restored="1">
                            <?php
                            $selected_categories = $selected_categories ?? [];
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
                        <small class="form-hint body-small-regular">
                            <?php echo t('Выберите категорию товара. Можно выбрать несколько уровней', 'Select the product category. Multiple levels allowed', 'Selectați categoria produsului. Sunt permise mai multe niveluri'); ?>
                        </small>
                        <div class="form-message body-small-regular" id="message_selected_categories"></div>
                    </fieldset>

                    <section class="form-group form-group--dynamic-features" id="dynamic-features-container">
                        <h2><?php echo t('Дополнительные характеристики', 'Additional features', 'Caracteristici suplimentare'); ?></h2>
                        <div class="dynamic-features-fields" id="dynamic-features-fields"></div>
                        <input type="hidden" name="dynamic_fields" id="dynamic_fields_input">
                    </section>

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
                            <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                            <input type="text" class="form-input input-secondary body-medium-regular" 
                                   name="<?php echo $fields['title']; ?>"
                                   placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>"
                                   data-lang="<?php echo $lang; ?>">
                        
                            <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                            <textarea name="<?php echo $fields['content']; ?>" rows="12" class="form-textarea input-tertiary body-medium-regular"
                                      placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>"
                                      data-lang="<?php echo $lang; ?>"></textarea>
                            <small class="form-hint body-small-regular">0 / 2000</small>
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

                    <section class="form-group form-group--gallery">
                        <label class="form-label label-large"><?php echo t('Изображения', 'Images', 'Imagini'); ?></label>
                        <div id="gallery_preview" class="gallery-preview">
                            <label class="btn-upload" for="product_gallery_input">
                                <div class="btn-upload__icon">
                                    <?php 
                                        $svg = file_get_contents(get_template_directory() . '/images/icon-camera.svg');
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
                        <input type="hidden" name="gallery_order" id="gallery_order_input" value="">
                        <input type="hidden" name="remove_gallery_ids[]" id="remove_gallery_ids_input" value="">
                        <input type="hidden" name="main_thumbnail_id" id="main_thumbnail_id" value="">
                        <div class="form-message body-small-regular" id="message_product_gallery"></div>
                    </section>

                    <section class="form-group form-group--price">
                        <div class="form-group__left">
                            <label class="form-label label-large"><?php echo t('Цена', 'Price', 'Preț'); ?></label>
                            <div class="price-input-wrapper">
                                <input type="number" step="0.01" name="product_price" class="form-input input-secondary body-medium-regular"
                                       placeholder="<?php echo t('Укажите цену', 'Enter the price', 'Introduceți prețul'); ?>"
                                       min="0.01" max="1000000">
                                <select name="product_currency" class="form-select select-tertiary body-medium-regular">
                                    <option value="lei">lei</option>
                                    <option value="usd">usd</option>
                                    <option value="eur">eur</option>
                                </select>
                            </div>
                            <small class="form-hint body-small-regular">
                                <?php echo t('Введите цену без пробелов и символов, только цифры', 'Enter the price as digits only', 'Introduceți prețul doar cu cifre'); ?>
                            </small>
                            <div class="form-message body-small-regular" id="message_product_price"></div>
                        </div>
                        <div class="form-group__right">
                            <label class="form-label label-large"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                            <select name="product_status" class="form-select select-tertiary body-medium-regular">
                                <option value="publish"><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                                <option value="draft"><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                            </select>
                            <div class="form-message body-small-regular" id="message_product_status"></div>
                        </div>
                    </section>

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
