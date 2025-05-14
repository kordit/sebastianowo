<?php
class InstanceManager
{
    private $instance_name;
    private $instance_title;
    private $template_dir;
    private $load_assets;

    public function __construct($instance_name, $load_assets = false)
    {
        $this->instance_name = sanitize_title($instance_name);
        $this->instance_title = $instance_name;
        $this->template_dir = get_template_directory() . '/page-templates/' . $this->instance_name;
        $this->load_assets = $load_assets;

        $this->register_rewrite_rule();
        $this->add_hooks();
    }

    private function register_rewrite_rule()
    {
        add_rewrite_rule('^' . $this->instance_name . '/?', 'index.php?instance_name=' . $this->instance_name . '&instance_title=' . urlencode($this->instance_title), 'top');
    }

    private function add_hooks()
    {
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_template'], 99);
        add_filter('body_class', [$this, 'add_body_class']);

        if ($this->load_assets) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        }
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'instance_name';
        $vars[] = 'instance_title';
        return $vars;
    }

    public function enqueue_assets()
    {
        $instance_name = get_query_var('instance_name');

        if ($instance_name === $this->instance_name) {
            // Używamy ścieżki bezpośrednio z $this->template_dir, która jest ustawiona poprawnie w konstruktorze
            $style_path = get_template_directory_uri() . '/page-templates/' . $this->instance_name . '/style.css';

            // Dodajemy unikalny timestamp aby uniknąć problemów z cache
            $style_version = file_exists($this->template_dir . '/style.css') ? filemtime($this->template_dir . '/style.css') : null;

            // Ładujemy CSS jeśli istnieje
            if (file_exists($this->template_dir . '/style.css')) {
                wp_enqueue_style($this->instance_name . '-style', $style_path, [], $style_version);
            }
        }
    }
    public function load_template($template)
    {
        $instance_name = get_query_var('instance_name');
        $instance_title = get_query_var('instance_title');

        if ($instance_name === $this->instance_name && file_exists($this->template_dir . '/template.php')) {
            set_query_var('instance_name', $instance_name);
            set_query_var('instance_title', $instance_title);

            // Lokalizacja i ładowanie template'u homepage.php
            $homepage_template = locate_template('homepage.php');
            if ($homepage_template) {
                ob_start();
                load_template($homepage_template, false);
                $content = ob_get_clean();
                echo $content;
                exit;
            }
        }

        return $template;
    }


    public function add_body_class($classes)
    {
        $instance_name = get_query_var('instance_name');

        if ($instance_name === $this->instance_name) {
            $classes[] = 'template-' . $this->instance_name;
        }

        return $classes;
    }
}

function get_all_instance()
{
    global $wpdb;
    $option_name_pattern = 'options_instance_%_nazwa_instancji';
    $query = $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name LIKE %s", $option_name_pattern);
    $results = $wpdb->get_results($query, ARRAY_A); // Zwracanie wyników jako tablica asocjacyjna

    // Wyciągnij tylko wartości z wyników
    $instances = array_map(function ($result) {
        return $result['option_value']; // Pobieramy tylko wartość opcji
    }, $results);

    return $instances; // Zwracamy tablicę wartości
}

add_action('init', function () {
    new InstanceManager('backpack', true);
    new InstanceManager('zadania', true);

    $all_instance = get_all_instance();
    foreach ($all_instance as $istance) {
        new InstanceManager(sanitize_title($istance), true); // Ustawienie parametru load_assets na true dla wszystkich instancji
    }
});
flush_rewrite_rules();
