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
    'includes/core',
    'includes/register-cpt',
    'includes/class',
    'includes/functions',
    'includes/dynamic-fields',
    'includes/oop',
]);
