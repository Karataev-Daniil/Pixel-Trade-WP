<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $current_user_id = get_current_user_id();
        $post_author_id  = get_the_author_meta('ID');
        $is_editing = isset($_GET['edit']) && $_GET['edit'] == 1 && $current_user_id === $post_author_id;
        $product_id = get_the_ID();

        if ($is_editing && $product_id && get_post_type($product_id) === 'product') :

                $title = esc_attr(get_the_title($product_id));
                $content = esc_textarea(get_post_field('post_content', $product_id));
                $status = get_post_status($product_id);
                $thumbnail_id = get_post_thumbnail_id($product_id);
                $gallery = get_field('product_gallery', $product_id);
                $gallery_ids = [];

                if (!empty($gallery)) {
                    foreach ($gallery as $image) {
                        if (isset($image['ID'])) {
                            $gallery_ids[] = $image['ID'];
                        }
                    }
                }

                $price = get_post_meta($product_id, 'product_price', true);

                $all_categories = get_terms([
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                ]);

                $selected_categories = wp_get_post_terms($product_id, 'product_cat');

                $deepest_term = null;
                $max_depth = 0;

                foreach ($selected_categories as $term) {
                    $depth = 0;
                    $parent = $term;
                    while ($parent->parent != 0) {
                        $parent = get_term($parent->parent, 'product_cat');
                        $depth++;
                    }
                    if ($depth > $max_depth) {
                        $max_depth = $depth;
                        $deepest_term = $term;
                    }
                }
            ?>     
            <div class="product__wrapper edit">
                <div class="container-medium">
                    <section class="product-edit">
                        <form method="post" enctype="multipart/form-data">
                            <h1 class="product-edit__title display-small">
                                <?php echo t('Редактировать объявление', 'Edit Listing', 'Editează Anunț'); ?>
                            </h1>
                            <?php wp_nonce_field('save_product_form', 'product_form_nonce'); ?>

                            <!-- Категории -->
                            <fieldset class="form-group">
                                <div id="category-selectors" class="category-selectors" data-restored="1">
                                    <?php $sorted_term_ids = sort_categories_by_hierarchy($selected_categories); ?>
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
                                
                            <!-- Языковые вкладки -->
                            <section class="form-group tabs">
                                <ul class="tab-buttons" role="tablist">
                                    <li class="tab-btn body-small-semibold <?php if ($language === 'ru') echo 'active'; ?>" data-tab="tab-ru">RU</li>
                                    <li class="tab-btn body-small-semibold <?php if ($language === 'en') echo 'active'; ?>" data-tab="tab-en">EN</li>
                                    <li class="tab-btn body-small-semibold <?php if ($language === 'ro') echo 'active'; ?>" data-tab="tab-ro">RO</li>
                                </ul>
                                
                                <?php foreach (['ru', 'en', 'ro'] as $lang): ?>
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
                                
                            <!-- Статус -->
                            <fieldset class="form-group">
                                <label class="form-label label-large" for="product_status"><?php echo t('Статус', 'Status', 'Stare'); ?></label>
                                <select id="product_status" name="product_status" class="form-select body-medium-regular">
                                    <option value="draft" <?php selected($status, 'draft'); ?>><?php echo t('Черновик', 'Draft', 'Schiță'); ?></option>
                                    <option value="publish" <?php selected($status, 'publish'); ?>><?php echo t('Опубликован', 'Published', 'Publicat'); ?></option>
                                </select>
                            </fieldset>
                                
                            <?php
                            // Получаем Featured Image
                            $thumbnail_id = get_post_thumbnail_id($product_id);

                            // Получаем галерею продукта
                            $gallery = get_field('product_gallery', $product_id);
                            $gallery_ids = [];

                            if (!empty($gallery)) {
                                if (is_array($gallery)) {
                                    foreach ($gallery as $image) {
                                        if (is_array($image) && isset($image['ID'])) {
                                            $gallery_ids[] = $image['ID'];
                                        } elseif (is_numeric($image)) {
                                            $gallery_ids[] = $image; // если ACF возвращает массив ID
                                        }
                                    }
                                }
                            }

                            // Если Featured Image есть и её нет в галерее, добавляем в начало
                            if ($thumbnail_id && !in_array($thumbnail_id, $gallery_ids)) {
                                array_unshift($gallery_ids, $thumbnail_id);
                            }
                            ?>

                            <fieldset class="form-group">
                                <label class="form-label label-large" for="product_gallery_input">
                                    <?php echo t('Изображения (до 6 шт., первое — миниатюра)', 'Images (up to 6, first is thumbnail)', 'Imagini (până la 6, prima este miniatura)'); ?>
                                </label>

                                <!-- Загрузка новых изображений -->
                                <input type="file" name="product_gallery_input[]" id="product_gallery_input" multiple accept="image/*" onchange="checkGalleryLimit(this)">

                                <!-- Порядок и удалённые -->
                                <input type="hidden" id="gallery_order_input" name="gallery_order_input" value="">
                                <input type="hidden" id="remove_gallery_ids_input" name="remove_gallery_ids_input" value="">

                                <!-- Превью галереи -->
                                <div id="gallery_preview" class="gallery-preview">
                                    <?php foreach (array_filter($gallery_ids) as $index => $id): ?>
                                        <div class="gallery-item<?php echo ($index === 0) ? ' thumbnail' : ''; ?>" data-id="<?php echo esc_attr($id); ?>">
                                            <?php echo wp_get_attachment_image($id, 'full'); ?>
                                            <input type="hidden" name="existing_gallery_ids[]" value="<?php echo esc_attr($id); ?>">
                                            <button type="button" class="gallery-remove link-small-default" title="<?php echo t('Удалить', 'Remove', 'Șterge'); ?>">✕</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>


                            <div class="form-group">
                                <label class="form-label label-large"><?php echo t('Цена (леи)', 'Price (lei)', 'Preț (lei)'); ?></label>
                                <input type="number" step="0.01" name="product_price" value="<?php echo esc_attr($price); ?>" class="form-input body-medium-regular" required>
                            </div>

                            <!-- ID продукта -->
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                            <!-- Кнопка отправки -->
                            <div class="form-group">
                                <input type="submit" name="submit_product" value="<?php echo t('Обновить', 'Update', 'Actualizează'); ?>" class="form-submit primary-button-large button-large">
                            </div>
                        </form>
                            
                        <!-- Прогресс -->
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
});


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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const gallery = document.getElementById('gallery_preview');

    // Инициализируем Sortable
    new Sortable(gallery, {
        animation: 150, // плавная анимация
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            // После каждого перетаскивания обновляем порядок
            updateGalleryOrder();
        }
    });
});
</script>



            <?php
        else:
            $lang = $GLOBALS['language'] ?? 'ru';

            $title_translations = [
                'ru' => get_the_title(),
                'en' => get_post_meta(get_the_ID(), '_title_en', true),
                'ro' => get_post_meta(get_the_ID(), '_title_ro', true),
            ];

            $content_translations = [
                'ru' => get_the_content(),
                'en' => get_post_meta(get_the_ID(), '_description_en', true),
                'ro' => get_post_meta(get_the_ID(), '_description_ro', true),
            ];

            $price = get_post_meta(get_the_ID(), 'product_price', true);

            $gallery_meta = get_post_meta(get_the_ID(), '_product_image_gallery', true);
            $gallery_ids = !empty($gallery_meta) ? explode(',', $gallery_meta) : [];
            ?>

            <div class="product__wrapper">
                <div class="container-medium">
                    <nav class="breadcrumbs body-small-regular" aria-label="<?= t('Хлебные крошки', 'Breadcrumb', 'Firimituri'); ?>">
                        <a class="link-small-underline" href="<?php echo home_url(); ?>"><?= t('Главная', 'Home', 'Pagina principală'); ?></a> &raquo;

                        <?php
                        global $language;
                                
                        $terms = get_the_terms(get_the_ID(), 'product_cat');
                                
                        if ($terms && !is_wp_error($terms)) {
                            $deepest_term = null;
                            $max_depth = -1;
                        
                            foreach ($terms as $term) {
                                $depth = 0;
                                $parent = $term->parent;
                                while ($parent) {
                                    $depth++;
                                    $parent_term = get_term($parent, 'product_cat');
                                    if (!$parent_term || is_wp_error($parent_term)) {
                                        break;
                                    }
                                    $parent = $parent_term->parent;
                                }
                            
                                if ($depth > $max_depth) {
                                    $max_depth = $depth;
                                    $deepest_term = $term;
                                }
                            }
                        
                            if ($deepest_term) {
                                $breadcrumbs = [];
                                $term = $deepest_term;
                                $visited = [];
                            
                                while ($term && !in_array($term->term_id, $visited)) {
                                    $visited[] = $term->term_id;
                                
                                    $translation_ro = get_term_meta($term->term_id, 'translation_ro', true);
                                    $translation_en = get_term_meta($term->term_id, 'translation_en', true);
                                
                                    if ($language === 'en') {
                                        $translated_name = $translation_en ?: $term->name;
                                    } elseif ($language === 'ro') {
                                        $translated_name = $translation_ro ?: $term->name;
                                    } else {
                                        $translated_name = $term->name;
                                    }
                                
                                    $breadcrumbs[] = '<a class="link-small-underline" href="' . get_term_link($term) . '">' . esc_html($translated_name) . '</a>';
                                    $term = $term->parent ? get_term($term->parent, 'product_cat') : false;
                                }
                            
                                echo implode(' &raquo; ', array_reverse($breadcrumbs)) . ' &raquo; ';
                            }
                        }
                        ?>

                        <span class="link-small-default"><?php echo esc_html($title_translations[$lang] ?? get_the_title()); ?></span>
                    </nav>
                    
                    <h1 class="product-card__title display-small">
                        <?php echo esc_html($title_translations[$lang] ?? get_the_title()); ?>
                    </h1>
                    <div class="product-card">
                        <main>
                            <article class="product-content">
                                <?php
                                $gallery_ids = get_post_meta(get_the_ID(), 'product_gallery', true);
                                $gallery_ids = is_array($gallery_ids) ? array_filter($gallery_ids) : [];

                                $thumbnail_id = get_post_thumbnail_id(get_the_ID());

                                if ($thumbnail_id && !in_array($thumbnail_id, $gallery_ids)) {
                                    array_unshift($gallery_ids, $thumbnail_id);
                                }
                                ?>

                                <?php if (!empty($gallery_ids)) : ?>
                                <section class="product-gallery-carousel" aria-label="<?= esc_attr(t('Галерея изображений товара', 'Product image gallery', 'Galerie de imagini ale produsului')); ?>">
                                    <div class="main-slider">
                                        <?php foreach ($gallery_ids as $index => $id): ?>
                                            <?php if ($id): ?>
                                                <figure>
                                                    <?= wp_get_attachment_image(
                                                        $id,
                                                        'large',
                                                        false,
                                                        ['alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: t('Изображение товара', 'Product image', 'Imagine produs')]
                                                    ); ?>
                                                </figure>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                                <?php endif; ?>

                                    
                                <section class="content body-small-regular" aria-label="<?= t('Описание товара', 'Product Description', 'Descriere produs'); ?>">
                                    <?php echo wpautop($content_translations[$lang] ?? get_the_content()); ?>
                                </section>
                                    
                                <section class="price title-medium" aria-label="<?= t('Цена', 'Price', 'Preț'); ?>">
                                    <p><strong><?= t('Цена:', 'Price:', 'Preț:'); ?></strong> <?php echo format_price_mdl_with_conversions($price); ?></p>
                                </section>
                                <?php
                                if (is_user_logged_in()) {
                                    $author_id = get_the_author_meta('ID');
                                    $current_user_id = get_current_user_id();

                                    if ($current_user_id !== $author_id) {
                                        echo '<button class="open-chat" data-receiver="' . esc_attr($author_id) . '">Написать автору</button>';
                                    }
                                }
                                ?>

                            </article>
                        </main>
                                    
                        <aside class="product-sidebar">
                            <?php
                            $author_id = get_the_author_meta('ID');
                            $author_avatar = get_avatar($author_id, 64);
                            $author_registered = get_the_author_meta('user_registered');
                            $author_url = get_author_posts_url($author_id);
                            $author_region = get_user_meta($author_id, 'region', true);
                            $product_type = get_post_meta(get_the_ID(), 'product_type', true);
                            $price = get_post_meta(get_the_ID(), 'product_price', true);
                                    
                            $lang = $GLOBALS['language'];
                            ?>

                            <section class="author" aria-label="<?= t('Информация об авторе', 'Author Info', 'Informații despre autor'); ?>">
                                <div class="author-avatar"><?php echo $author_avatar; ?></div>
                                <div class="author-profile">
                                    <a class="link-button" href="<?php echo esc_url($author_url); ?>">
                                        <strong><?= t('Автор:', 'Author:', 'Autor:'); ?></strong> <?php the_author(); ?>
                                    </a>
                                    <span class="body-small-regular">
                                        <?= t('На сайте с', 'On the site since', 'Pe site din'); ?> <?php echo date_i18n('d.m.Y', strtotime($author_registered)); ?>
                                    </span>
                                </div>
                            </section>
                                    
                            <section class="details" aria-label="<?= t('Детали товара', 'Product Details', 'Detalii produs'); ?>">
                                <div class="item body-small-regular"><?= t('Дата публикации', 'Published on', 'Data publicării'); ?>: <?php echo get_the_date('d.m.Y'); ?></div>
                                <div class="item body-small-regular"><?= t('Просмотры', 'Views', 'Vizualizări'); ?>: <?php echo (int)get_post_meta(get_the_ID(), 'product_views', true); ?></div>
                                <?php if ($product_type): ?>
                                    <div class="item body-small-regular"><?= t('Тип', 'Type', 'Tip'); ?>: <?php echo esc_html($product_type); ?></div>
                                <?php endif; ?>
                            </section>
                                
                            <section class="price title-medium" aria-label="<?= t('Цена', 'Price', 'Preț'); ?>">
                                <?php echo format_price_mdl_with_conversions($price); ?>
                            </section>
                                
                            <?php if ($author_region): ?>
                                <section class="author-region" aria-label="<?= t('Регион автора', 'Author Region', 'Regiunea autorului'); ?>">
                                    <div class="item body-small-regular">
                                        <strong><?= t('Регион:', 'Region:', 'Regiune:'); ?></strong> <?php echo esc_html($author_region); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <?php if ($current_user_id === $post_author_id): ?>
                                <section class="actions" aria-label="<?= t('Управление товаром', 'Manage Product', 'Gestionați produsul'); ?>">
                                    <a href="<?php echo esc_url(add_query_arg('edit', 1)); ?>" class="button primary-button-small">
                                        <?= t('Редактировать', 'Edit', 'Editați'); ?>
                                    </a>
                            
                                    <form method="post" onsubmit="return confirm('<?= t('Вы уверены, что хотите удалить этот товар?', 'Are you sure you want to delete this product?', 'Sunteți sigur că doriți să ștergeți acest produs?'); ?>');">
                                        <?php wp_nonce_field('delete_product_action', 'delete_product_nonce'); ?>
                                        <input type="hidden" name="delete_product_id" value="<?php echo get_the_ID(); ?>">
                                        <button type="submit" class="button secondary-button-small"><?= t('Удалить', 'Delete', 'Ștergeți'); ?></button>
                                    </form>
                                </section>
                            <?php endif; ?>
                        </aside>
                    </div>
                </div>
            </div>
        <?php
        endif;
    endwhile;
else :
    echo '<p>Товар не найден.</p>';
endif;

get_footer();
?>
