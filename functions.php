<?php
function load_theme_files(array $dirs)
{
    foreach ($dirs as $dir) {
        $path = get_template_directory() . '/' . trim($dir, '/');
        foreach (glob($path . '/*.php') as $file) {
            require_once $file;
        }
    }
}

load_theme_files([
    'class',
    'functions',
    'register-cpt',
    'inc/core',
    'inc/dynamic-fields',
]);
