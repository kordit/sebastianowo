<?php

/**
 * Przykład użycia funkcji get_task_title_by_slug
 * 
 * Plik demonstrujący, jak używać funkcji pobierającej tytuł zadania na podstawie sluga
 */

// Przykładowe użycie:
$slug = 'jak-poruszac-sie-po-grze';
$task_title = get_task_title_by_slug($slug);

echo "Slug: " . $slug . "<br>";
echo "Tytuł zadania: " . $task_title . "<br><br>";

// Inny przykład:
$slug2 = 'jak-si-tu-porusza';
$task_title2 = get_task_title_by_slug($slug2);

echo "Slug: " . $slug2 . "<br>";
echo "Tytuł zadania: " . $task_title2 . "<br><br>";

// Przykład z nieistniejącym slugiem:
$slug3 = 'nieistniejacy-slug';
$task_title3 = get_task_title_by_slug($slug3);

echo "Slug: " . $slug3 . "<br>";
echo "Tytuł zadania (sformatowany slug): " . $task_title3 . "<br>";
