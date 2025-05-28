<?php
$skills = [
    'name' => 'skills',
    'fields' => [
        'combat' => ['label' => 'Walka', 'instructions' => 'Zwiększa obrażenia, inicjatywę', 'default' => 0],
        'steal' => ['label' => 'Kradzież', 'instructions' => 'Większa skuteczność, mniejsze ryzyko', 'default' => 0],
        'craft' => ['label' => 'Produkcja', 'instructions' => 'Krótszy czas, więcej towaru', 'default' => 0],
        'trade' => ['label' => 'Handel', 'instructions' => 'Lepsze ceny, więcej zarobku', 'default' => 0],
        'relations' => ['label' => 'Relacje', 'instructions' => 'Bonusy, unikalne misje', 'default' => 0],
        'street' => ['label' => 'Uliczna wiedza', 'instructions' => 'Dostęp do sekretnych przejść, schowków', 'default' => 0],
    ],
];

$progress = [
    'name' => 'progress',
    'fields' => [
        'exp' => ['label' => 'Doświadczenie', 'instructions' => 'Całkowity exp zdobyty przez gracza', 'default' => 0],
        'learning_points' => ['label' => 'Punkty nauki', 'instructions' => 'Punkty do rozdania na statystyki', 'default' => 3],
        'reputation' => ['label' => 'Reputacja', 'instructions' => 'Reputacja gracza w mieście', 'default' => 1],
    ],
];

$vitality = [
    'name' => 'vitality',
    'fields' => [
        'life' => ['label' => 'Życie', 'instructions' => 'Aktualne życie gracza', 'default' => 100],
        'max_life' => ['label' => 'Maksymalne życie', 'instructions' => 'Limit życia', 'default' => 100],
        'energy' => ['label' => 'Energia', 'instructions' => 'Aktualna energia', 'default' => 100],
        'max_energy' => ['label' => 'Maksymalna energia', 'instructions' => 'Limit energii', 'default' => 100],
    ],
];

define('SKILLS', $skills);
define('PROGRESS', $progress);
define('VITALITY', $vitality);

define('BACKPACK', [
    'name' => 'backpack',
    'fields' => [
        'gold' => ['label' => 'Złoty', 'instructions' => 'Główna waluta', 'default' => 0],
        'cigarettes' => ['label' => 'Papierosy', 'instructions' => 'Alternatywna waluta', 'default' => 0],
    ],
]);

define('STATS', [
    'name' => 'stats',
    'fields' => [
        'strength' => ['label' => 'Siła', 'instructions' => 'Zwiększa obrażenia w walce wręcz i dominację w bójkach', 'default' => 1],
        'defense' => ['label' => 'Wytrzymałość', 'instructions' => 'Zwiększa liczbę ciosów jakie możemy przyjąć', 'default' => 1],
        'dexterity' => ['label' => 'Zręczność', 'instructions' => 'Odpowiada za skuteczność kradzieży, uniki, ucieczki', 'default' => 1],
        'perception' => ['label' => 'Percepcja', 'instructions' => 'Szansa na wykrycie ukrytych przedmiotów, NPC, opcji', 'default' => 1],
        'technical' => ['label' => 'Zdolności manualne', 'instructions' => 'Efektywność produkcji (używki, towary), hakowanie, techniczne akcje', 'default' => 1],
        'charisma' => ['label' => 'Cwaniactwo', 'instructions' => 'Umiejętność bajerowania NPC, prowadzenia układów, handlu', 'default' => 1],
    ],
]);

define('TEMPLATE_PATH', get_stylesheet_directory() . '/templates/');
define('SITE_URL', get_home_url());
define('THEME_URL', get_stylesheet_directory_uri());
define('THEME_SRC', get_stylesheet_directory());
define('ASSETS_DIR', get_stylesheet_directory() . '/assets');
define('ASSETS_URL', get_stylesheet_directory_uri() . '/assets');
define('IMAGES', ASSETS_DIR . "/images/");
define('PNG', ASSETS_URL . "/images/png");
define('SVG', ASSETS_URL . "/images/svg/");
define('CSS', ASSETS_DIR . "/css/");
define('JS', ASSETS_DIR . "/js/");
