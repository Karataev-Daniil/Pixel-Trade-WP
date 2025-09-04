<?php
$current_user_id = $args['current_user_id'] ?? get_current_user_id();
?>

<div class="product__wrapper create content-main">
    <div class="container-medium">
        <main>
            <section class="product-create">
                <form id="create-product-form" method="post" enctype="multipart/form-data">
                    <h1 class="product-create__title display-large">
                        <?php echo t('Создать объявление', 'Create Listing', 'Creează Anunț'); ?>
                    </h1>
                    <?php wp_nonce_field('create_product_form', 'product_form_nonce'); ?>

                    <!-- Тип объявления -->
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

                    <!-- Categories -->
                    <fieldset class="form-group form-group--categories">
                        <legend class="label-large"><?php echo t('Категории', 'Categories', 'Categorii'); ?></legend>
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
                                    labelLevel0: <?php echo json_encode(t('', '', '')); ?>,
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

                     <!-- Language tabs -->
                    <section class="form-group form-group--tabs tabs">
                        <?php $language = $GLOBALS['language']; ?>
                        <ul class="tab-buttons">
                            <li class="tab-btn body-small-semibold <?php if ($language === 'ru') echo 'active'; ?>" data-tab="tab-ru">RU</li>
                            <li class="tab-btn body-small-semibold <?php if ($language === 'en') echo 'active'; ?>" data-tab="tab-en">EN</li>
                            <li class="tab-btn body-small-semibold <?php if ($language === 'ro') echo 'active'; ?>" data-tab="tab-ro">RO</li>
                        </ul>

                        <!-- RU tab -->
                        <div class="tab-content <?php if ($language === 'ru') echo 'active'; ?>" id="tab-ru" data-lang="ru">
                            <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                            <input type="text" class="form-input input-secondary body-medium-regular" name="product_title"
                                   placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>" data-lang="ru">

                            <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                            <textarea name="product_content" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                      placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>" data-lang="ru"></textarea>
                            <small class="form-hint body-small-regular">0 / 2000</small>
                        </div>

                        <!-- EN tab -->
                        <div class="tab-content <?php if ($language === 'en') echo 'active'; ?>" id="tab-en" data-lang="en">
                            <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                            <input type="text" class="form-input input-secondary body-medium-regular" name="title_en"
                                   placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>" data-lang="en">

                            <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                            <textarea name="description_en" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                      placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>" data-lang="en"></textarea>
                            <small class="form-hint body-small-regular">0 / 2000</small>
                        </div>

                        <!-- RO tab -->
                        <div class="tab-content <?php if ($language === 'ro') echo 'active'; ?>" id="tab-ro" data-lang="ro">
                            <label class="label-large"><?php echo t('Название', 'Title', 'Titlu'); ?></label>
                            <input type="text" class="form-input input-secondary body-medium-regular" name="title_ro"
                                   placeholder="<?php echo t('Введите название', 'Enter title', 'Introduceți titlul'); ?>" data-lang="ro">

                            <label class="label-large"><?php echo t('Описание', 'Description', 'Descriere'); ?></label>
                            <textarea name="description_ro" rows="5" class="form-textarea input-tertiary body-medium-regular"
                                      placeholder="<?php echo t('Введите описание', 'Enter description', 'Introduceți descrierea'); ?>" data-lang="ro"></textarea>
                            <small class="form-hint body-small-regular">0 / 2000</small>
                        </div>

                        <!-- Translation / Actions block -->
                        <div class="content-setting">
                            <div class="dropdown">
                                <button id="translation-action-button" class="secondary-button-small">
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
                            <div id="translation-message" class="form-message body-small-regular"></div>
                        </div>
                    </section>

                    <!-- Gallery -->
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
                        <input type="file" name="product_gallery[]" accept="image/*" multiple class="form-file visually-hidden" id="product_gallery_input" onchange="checkGalleryLimit(this)">
                        <input type="hidden" name="gallery_order" id="gallery_order_input" value="">
                        <input type="hidden" name="remove_gallery_ids[]" id="remove_gallery_ids_input" value="">
                        <input type="hidden" name="main_thumbnail_id" id="main_thumbnail_id" value="">
                        <div class="form-message body-small-regular" id="message_product_gallery"></div>
                    </section>

                    <!-- Price -->
                    <section class="form-group form-group--price">
                        <div class="form-group__left">
                            <label class="form-label label-large"><?php echo t('Цена', 'Price', 'Preț'); ?></label>
                            <div class="price-input-wrapper">
                                <input type="number" step="0.01" name="product_price" class="form-input input-secondary body-medium-regular"
                                       placeholder="<?php echo t('Укажите цену', 'Enter the price', 'Introduceți prețul'); ?>">
                                <select name="product_currency" class="form-select select-tertiary body-medium-regular">
                                    <option value="lei">лей</option>
                                    <option value="usd">$</option>
                                    <option value="eur">€</option>
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
