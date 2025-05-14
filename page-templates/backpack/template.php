<?php

/**
 * Szablon dla strony plecaka
 * 
 * Wyświetla przedmioty posiadane przez użytkownika z możliwością filtrowania po typach
 */
require_once('functions.php');

// Pobierz ID zalogowanego użytkownika
$user_id = get_current_user_id();

// Pobierz przedmioty z plecaka użytkownika
$user_items = game_get_user_items($user_id);

// Pobierz typy przedmiotów
$item_types = game_get_item_types();

// Grupuj przedmioty według typu
$items_by_type = game_get_items_by_type($user_items);

?>

<div class="backpack-container">


    <?php if (empty($user_items)): ?>
        <div class="empty-backpack">
            <p>Twój plecak jest pusty.</p>
        </div>
    <?php else: ?>
        <div class="backpack-layout" x-data="{ activeTab: 'all', activeSubtype: null }">
            <!-- Nawigacja (typy przedmiotów) -->
            <ul class="tabs-nav">
                <li class="tab-item"
                    :class="{ 'active': activeTab === 'all' }"
                    @click="activeTab = 'all'; activeSubtype = null">
                    Wszystkie
                </li>

                <?php foreach ($items_by_type as $type_id => $type_data): ?>
                    <?php if ($type_id !== 'all'): ?>
                        <li class="tab-item"
                            :class="{ 'active': activeTab === '<?php echo $type_id; ?>' }"
                            @click="activeTab = '<?php echo $type_id; ?>'; activeSubtype = null">
                            <?php echo esc_html($type_data['name']); ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Zawartość (przedmioty) -->
            <div class="items-content">
                <h1 class="page-title">Plecak</h1>
                <?php foreach ($items_by_type as $type_id => $type_data): ?>
                    <div id="tab-<?php echo $type_id; ?>"
                        class="items-tab"
                        :class="{ 'active': activeTab === '<?php echo $type_id; ?>' }"
                        x-show.transition.opacity="activeTab === '<?php echo $type_id; ?>'"
                        x-cloak>

                        <!-- Wyświetl podkategorie, jeśli istnieją -->
                        <?php if (isset($type_data['subtypes']) && !empty($type_data['subtypes'])): ?>
                            <div class="subtypes-nav">
                                <!-- Przycisk dla wszystkich przedmiotów w tej kategorii -->
                                <button class="subtype-button"
                                    @click="activeSubtype = null"
                                    :class="{ 'active': activeSubtype === null }">
                                    Wszystkie
                                </button>

                                <?php foreach ($type_data['subtypes'] as $subtype_id => $subtype_data): ?>
                                    <button class="subtype-button"
                                        @click="activeSubtype = activeSubtype === <?php echo $subtype_id; ?> ? null : <?php echo $subtype_id; ?>"
                                        :class="{ 'active': activeSubtype === <?php echo $subtype_id; ?> }">
                                        <?php echo esc_html($subtype_data['name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Lista przedmiotów -->
                        <div class="items-grid">
                            <?php foreach ($type_data['items'] as $item): ?>
                                <?php
                                // Określ do jakich podkategorii należy przedmiot (jeśli istnieją)
                                $item_subtypes = [];
                                if (isset($type_data['subtypes'])) {
                                    foreach ($type_data['subtypes'] as $subtype_id => $subtype_data) {
                                        foreach ($subtype_data['items'] as $subtype_item) {
                                            if ($subtype_item['id'] === $item['id']) {
                                                $item_subtypes[] = $subtype_id;
                                                break;
                                            }
                                        }
                                    }
                                }
                                $item_subtypes_json = json_encode($item_subtypes);
                                ?>
                                <div class="item-card"
                                    x-data="{ showDetails: false, subtypes: <?php echo $item_subtypes_json; ?> }"
                                    x-show="activeSubtype === null || subtypes.includes(activeSubtype)">
                                    <div class="item-image" @click="showDetails = !showDetails">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo esc_url($item['image']); ?>"
                                                alt="<?php echo esc_attr($item['title']); ?>" />
                                        <?php else: ?>
                                            <div class="no-image">
                                                <span class="no-image-text">Brak obrazka</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="item-basic-info">
                                        <h3 class="item-title"><?php echo esc_html($item['title']); ?></h3>
                                        <div class="item-quantity">
                                            <span class="quantity-label">Ilość:</span>
                                            <span class="quantity-value"><?php echo esc_html($item['quantity']); ?></span>
                                        </div>
                                    </div>

                                    <div class="item-details" x-show.transition.opacity="showDetails" x-cloak>
                                        <?php if (!empty($item['description'])): ?>
                                            <div class="item-description">
                                                <?php echo wp_kses_post($item['description']); ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="no-description">Brak opisu przedmiotu.</p>
                                        <?php endif; ?>

                                        <!-- Miejsce na ewentualne dodatkowe akcje dla przedmiotu (przyciski Użyj i Sprawdź zostały usunięte) -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>