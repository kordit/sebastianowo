<?php
// Pobierz ID zalogowanego użytkownika
require_once('functions.php');
$user_id = get_current_user_id();

// Sprawdź czy użytkownik jest zalogowany
if (!$user_id) {
    wp_redirect(home_url('/login'));
    exit;
}

merge_duplicate_items($user_id);

// Pobierz przedmioty użytkownika z pola ACF 'items'
$user_items = get_field('items', 'user_' . $user_id);

// Pobierz wszystkie kategorie przedmiotów (taksonomia item_type)
$item_categories = get_terms([
    'taxonomy' => 'item_type',
    'hide_empty' => false,
    'parent' => 0
]);

// Tablica do przechowywania przedmiotów pogrupowanych według kategorii
$items_by_category = [];

// Jeśli użytkownik ma przedmioty, przygotuj strukturę danych
if ($user_items && is_array($user_items)) {
    foreach ($user_items as $user_item) {
        if (isset($user_item['item']) && $user_item['item'] instanceof WP_Post) {
            $item = $user_item['item'];
            $quantity = isset($user_item['quantity']) ? $user_item['quantity'] : 1;

            // Pobierz kategorie dla tego przedmiotu
            $item_terms = wp_get_post_terms($item->ID, 'item_type');

            // Przypisz przedmiot do odpowiednich kategorii
            if (!empty($item_terms)) {
                foreach ($item_terms as $term) {
                    // Sprawdź czy to kategoria główna czy podkategoria
                    $parent_id = $term->parent;

                    if ($parent_id == 0) {
                        // To główna kategoria
                        $category_id = $term->term_id;
                        $subcategory_id = 0;
                    } else {
                        // To podkategoria
                        $category_id = $parent_id;
                        $subcategory_id = $term->term_id;
                    }

                    // Dodaj przedmiot do odpowiedniej kategorii i podkategorii
                    if (!isset($items_by_category[$category_id])) {
                        $items_by_category[$category_id] = ['main' => [], 'sub' => []];
                    }

                    if ($subcategory_id) {
                        if (!isset($items_by_category[$category_id]['sub'][$subcategory_id])) {
                            $items_by_category[$category_id]['sub'][$subcategory_id] = [];
                        }
                        $items_by_category[$category_id]['sub'][$subcategory_id][] = [
                            'item' => $item,
                            'quantity' => $quantity
                        ];
                    } else {
                        $items_by_category[$category_id]['main'][] = [
                            'item' => $item,
                            'quantity' => $quantity
                        ];
                    }
                }
            } else {
                // Jeśli przedmiot nie ma kategorii, dodaj do "Inne"
                if (!isset($items_by_category['uncategorized'])) {
                    $items_by_category['uncategorized'] = ['main' => [], 'sub' => []];
                }
                $items_by_category['uncategorized']['main'][] = [
                    'item' => $item,
                    'quantity' => $quantity
                ];
            }
        }
    }
}

// Pobierz założone przedmioty
$equipped_items = get_user_equipped_items($user_id);
?>

<div class="plecak-container">
    <div class="plecak-tabs">
        <div class="plecak-tab active" data-tab="equipped">Ogólne</div>
        <div class="plecak-tab" data-tab="inventory">Przedmioty</div>
    </div>


    <!-- Zawartość zakładek -->
    <div class="plecak-tabs-content">
        <!-- Zakładka z założonymi przedmiotami -->
        <div class="plecak-tab-content active" id="tab-equipped">
            <h2 class="plecak-title">Ogólne</h2>
            <div class="equipped-items-container">
                <div class="equipped-character">
                    <div class="character-avatar">
                        <?php
                        $avatar_full = get_field('avatar_full', 'user_' . $user_id);
                        if ($avatar_full && is_array($avatar_full)) {
                            echo '<img src="' . esc_url($avatar_full['url']) . '" alt="' . esc_attr($avatar_full['alt'] ?: 'Avatar') . '">';
                        } else {
                            echo '<img src="' . get_template_directory_uri() . '/assets/images/png/postac.png" alt="Postać">';
                        }
                        ?>
                    </div>
                </div>

                <!-- Wyświetl założone przedmioty -->
                <?php echo generate_equipped_items_html($user_id); ?>
            </div>
        </div>

        <!-- Zakładka z wszystkimi przedmiotami -->
        <div class="plecak-tab-content" id="tab-inventory">
            <h2 class="plecak-title">Twoje przedmioty</h2>
            <?php if (empty($user_items)) : ?>
                <div class="plecak-empty">
                    <p>Twój plecak jest pusty.</p>
                </div>
            <?php else : ?>
                <ul class="plecak-categories">

                    <li class="category-item active" data-category="all">Wszystkie przedmioty</li>
                    <?php foreach ($item_categories as $category) : ?>
                        <li class="category-item" data-category="<?php echo $category->term_id; ?>">
                            <?php echo $category->name; ?>

                            <?php
                            // Sprawdź, czy ta kategoria ma podkategorie
                            $subcategories = get_terms([
                                'taxonomy' => 'item_type',
                                'hide_empty' => false,
                                'parent' => $category->term_id
                            ]);

                            if (!empty($subcategories)) : ?>
                                <ul class="plecak-subcategories">
                                    <?php foreach ($subcategories as $subcategory) : ?>
                                        <li class="subcategory-item" data-subcategory="<?php echo $subcategory->term_id; ?>">
                                            <?php echo $subcategory->name; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>

                    <?php if (isset($items_by_category['uncategorized'])) : ?>
                        <li class="category-item" data-category="uncategorized">Inne</li>
                    <?php endif; ?>
                </ul>

                <div class="plecak-items-container">
                    <!-- Sekcja "Wszystkie przedmioty" -->
                    <div class="category-section" id="category-all">
                        <h3 class="category-title">Wszystkie przedmioty</h3>
                        <div class="items-grid">
                            <?php
                            // Wyświetl wszystkie przedmioty dla sekcji "Wszystkie przedmioty"
                            if ($user_items && is_array($user_items)) {
                                foreach ($user_items as $user_item) {
                                    if (isset($user_item['item']) && $user_item['item'] instanceof WP_Post) {
                                        $item = $user_item['item'];
                                        $quantity = isset($user_item['quantity']) ? $user_item['quantity'] : 1;

                                        // Użyj funkcji do generowania karty przedmiotu
                                        echo generate_item_card($item, $quantity, false);
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <?php foreach ($item_categories as $category) : ?>
                        <div class="category-section" id="category-<?php echo $category->term_id; ?>">
                            <h3 class="category-title"><?php echo $category->name; ?></h3>

                            <?php if (isset($items_by_category[$category->term_id])) : ?>
                                <?php
                                // Sprawdź, czy kategoria ma podkategorie
                                $has_subcategories = get_terms([
                                    'taxonomy' => 'item_type',
                                    'hide_empty' => false,
                                    'parent' => $category->term_id,
                                    'fields' => 'count'
                                ]) > 0;

                                // Wyświetl przedmioty w głównej kategorii tylko jeśli nie ma podkategorii
                                if (!$has_subcategories && !empty($items_by_category[$category->term_id]['main'])) : ?>
                                    <div class="items-grid main-items">
                                        <?php foreach ($items_by_category[$category->term_id]['main'] as $item_data) :
                                            echo generate_item_card($item_data['item'], $item_data['quantity'], false);
                                        endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Wyświetlanie przedmiotów z podkategorii
                                if (!empty($items_by_category[$category->term_id]['sub'])) :
                                    $subcategories = get_terms([
                                        'taxonomy' => 'item_type',
                                        'hide_empty' => false,
                                        'parent' => $category->term_id
                                    ]);

                                    foreach ($subcategories as $subcategory) :
                                        if (isset($items_by_category[$category->term_id]['sub'][$subcategory->term_id])) :
                                ?>
                                            <div class="subcategory-section" id="subcategory-<?php echo $subcategory->term_id; ?>">
                                                <h3 class="subcategory-title"><?php echo $subcategory->name; ?></h3>
                                                <div class="items-grid">
                                                    <?php foreach ($items_by_category[$category->term_id]['sub'][$subcategory->term_id] as $item_data) : ?>
                                                        <?php echo generate_item_card($item_data['item'], $item_data['quantity'], false); ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                <?php
                                        endif;
                                    endforeach;
                                endif;
                                ?>
                            <?php else : ?>
                                <p class="no-items">Brak przedmiotów w tej kategorii.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (isset($items_by_category['uncategorized'])) : ?>
                        <div class="category-section" id="category-uncategorized">
                            <h3 class="category-title">Inne przedmioty</h3>
                            <div class="items-grid">
                                <?php foreach ($items_by_category['uncategorized']['main'] as $item_data) : ?>
                                    <?php echo generate_item_card($item_data['item'], $item_data['quantity'], false); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
        </div>
    </div>
<?php endif; ?>
</div>