<?php
/**
 * Админские настройки пользователей (регионы и др.)
 */

// === Регистрируем страницу ===
add_action('admin_menu', function () {
    add_options_page(
        'Настройки пользователей',
        'Аккаунты',
        'manage_options',
        'user-settings',
        'render_user_settings_page'
    );
});

// === Дефолтные регионы ===
function get_default_moldova_regions() {
    return [
        ['ru' => 'Кишинёв', 'en' => 'Chisinau', 'ro' => 'Chișinău', 'parent' => ''],
        ['ru' => 'Ботаника', 'en' => 'Botanica', 'ro' => 'Botanica', 'parent' => 'Кишинёв'],
        ['ru' => 'Рышкановка', 'en' => 'Riscani', 'ro' => 'Rîșcani', 'parent' => 'Кишинёв'],
        ['ru' => 'Буюканы', 'en' => 'Buiucani', 'ro' => 'Buiucani', 'parent' => 'Кишинёв'],
        ['ru' => 'Центр', 'en' => 'Centru', 'ro' => 'Centru', 'parent' => 'Кишинёв'],
        ['ru' => 'Чеканы', 'en' => 'Ciocana', 'ro' => 'Ciocana', 'parent' => 'Кишинёв'],
    ];
}

// === Получаем регионы из БД (или дефолтные) ===
function get_moldova_regions() {
    $regions = get_option('available_regions_multi', []);
    if (empty($regions)) {
        $regions = get_default_moldova_regions();
        update_option('available_regions_multi', $regions);
    }
    return $regions;
}

// === Рендер страницы в админке ===
function render_user_settings_page() {
    if (!current_user_can('manage_options')) return;

    // Сохраняем регионы
    if (isset($_POST['regions_nonce']) && wp_verify_nonce($_POST['regions_nonce'], 'save_regions')) {
        $regions = [];
        if (!empty($_POST['regions'])) {
            foreach ($_POST['regions'] as $region) {
                if (empty($region['ru']) && empty($region['en']) && empty($region['ro'])) {
                    continue;
                }
                $regions[] = [
                    'ru' => sanitize_text_field($region['ru']),
                    'en' => sanitize_text_field($region['en']),
                    'ro' => sanitize_text_field($region['ro']),
                    'parent' => sanitize_text_field($region['parent']),
                ];
            }
        }
        update_option('available_regions_multi', $regions);
        echo '<div class="updated"><p>Список регионов сохранён.</p></div>';
    }

    $regions = get_moldova_regions();
    ?>
    <div class="wrap">
        <h1>Настройки пользователей — Регионы</h1>
        <form method="post">
            <?php wp_nonce_field('save_regions', 'regions_nonce'); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Русский</th>
                    <th>English</th>
                    <th>Română</th>
                    <th>Родитель</th>
                </tr>
                </thead>
                <tbody id="regions-table">
                <?php foreach ($regions as $i => $region): ?>
                    <tr>
                        <td><input type="text" name="regions[<?php echo $i; ?>][ru]" value="<?php echo esc_attr($region['ru']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][en]" value="<?php echo esc_attr($region['en']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][ro]" value="<?php echo esc_attr($region['ro']); ?>" class="widefat"></td>
                        <td><input type="text" name="regions[<?php echo $i; ?>][parent]" value="<?php echo esc_attr($region['parent']); ?>" class="widefat" placeholder="например: Кишинёв"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-region">+ Добавить регион</button></p>
            <p><button type="submit" class="button button-primary">Сохранить</button></p>
        </form>
    </div>

    <script>
        (function($){
            $('#add-region').on('click', function(){
                let rowCount = $('#regions-table tr').length;
                $('#regions-table').append(`
                    <tr>
                        <td><input type="text" name="regions[${rowCount}][ru]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][en]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][ro]" class="widefat"></td>
                        <td><input type="text" name="regions[${rowCount}][parent]" class="widefat" placeholder="например: Кишинёв"></td>
                    </tr>
                `);
            });
        })(jQuery);
    </script>
    <?php
}
