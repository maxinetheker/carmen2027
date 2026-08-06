<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plantillas de brochure
    |--------------------------------------------------------------------------
    |
    | Cada plantilla define la portada (resources/views/brochures/templates/{key}/cover.blade.php)
    | y un tema de colores/tipografía que también visten las páginas de contenido
    | genéricas (resources/views/brochures/pages/*), para que todo el documento se
    | vea a juego sin tener que rediseñar cada página por plantilla.
    |
    */
    'templates' => [
        'plantilla-1' => [
            'label' => 'Clásico Azul y Oro',
            'source' => 'storage/planitllas/Plantilla 1.HTM',
            'hero_box' => ['w' => 210, 'h' => 90],
            'title_size' => 26,
            'bg' => '#ffffff',
            'panel' => '#f2f5f9',
            'primary' => '#071b35',
            'secondary' => '#0a2a4d',
            'accent' => '#c9a227',
            'text' => '#3d4c60',
            'muted' => '#5a6b80',
            'on_primary' => '#ffffff',
            'heading' => '#071b35',
            'on_accent' => '#071b35',
            'font' => "'DejaVu Sans', sans-serif",
        ],
        'plantilla-2' => [
            'label' => 'Marca RE/MAX (azul y rojo)',
            'source' => 'storage/planitllas/Plantilla 2.HTM',
            'hero_box' => ['w' => 210, 'h' => 90],
            'title_size' => 25,
            'bg' => '#ffffff',
            'panel' => '#f5f8fc',
            'primary' => '#003da5',
            'secondary' => '#012a73',
            'accent' => '#dc1c2e',
            'text' => '#48566a',
            'muted' => '#6b7686',
            'on_primary' => '#ffffff',
            'heading' => '#003da5',
            'on_accent' => '#ffffff',
            'font' => "'DejaVu Sans', sans-serif",
        ],
        'plantilla-3' => [
            'label' => 'Minimalista blanco',
            'source' => 'storage/planitllas/Plantilla 3.HTM',
            'hero_box' => ['w' => 210, 'h' => 82],
            'title_size' => 30,
            'bg' => '#ffffff',
            'panel' => '#f4f5f6',
            'primary' => '#202020',
            'secondary' => '#3a3a3a',
            // accent is deliberately a light gray, not black: this theme is monochrome
            // by design (no separate hue), and reusing `primary` as the accent would make
            // accent-background elements (hook bar, step numbers) show invisible dark-on-dark text.
            'accent' => '#e3e6ea',
            'text' => '#4a5058',
            'muted' => '#9aa0a8',
            'on_primary' => '#ffffff',
            'heading' => '#202020',
            'on_accent' => '#202020',
            'font' => "'DejaVu Sans', sans-serif",
        ],
        'plantilla-4' => [
            'label' => 'Impacto con foto a página completa',
            'source' => 'storage/planitllas/Plantilla 4.HTM',
            'hero_box' => ['w' => 210, 'h' => 170],
            'title_size' => 32,
            'bg' => '#0a0f16',
            'panel' => '#141b26',
            'primary' => '#0a0f16',
            'secondary' => '#1c2532',
            'accent' => '#ffd24a',
            'text' => '#dbe4ee',
            'muted' => '#a9b6c6',
            'on_primary' => '#ffffff',
            'heading' => '#ffffff',
            'on_accent' => '#0a0f16',
            'font' => "'DejaVu Sans', sans-serif",
        ],
        'plantilla-5' => [
            'label' => 'Elegante crema y serif',
            'source' => 'storage/planitllas/Plantilla 5.HTM',
            'hero_box' => ['w' => 178, 'h' => 82],
            'title_size' => 27,
            'bg' => '#f6f1e7',
            'panel' => '#efe8d8',
            'primary' => '#2b2620',
            'secondary' => '#4a4030',
            'accent' => '#9c1f24',
            'text' => '#5d5344',
            'muted' => '#8a7a5f',
            'on_primary' => '#ffffff',
            'heading' => '#2b2620',
            'on_accent' => '#ffffff',
            'font' => "'DejaVu Serif', serif",
        ],
    ],

    'default_template' => 'plantilla-1',

    'max_pages' => ['min' => 1, 'max' => 3, 'default' => 3],

    'max_images' => ['min' => 1, 'max' => 8, 'default' => 4],

    /*
    |--------------------------------------------------------------------------
    | Logos de la agencia
    |--------------------------------------------------------------------------
    |
    | Archivos en storage/logos_remax (fuera de los discos de Laravel a propósito:
    | son activos de marca fijos, no contenido subido por el usuario). "symbol" es
    | el símbolo solo sin letras, el que se usa por defecto en modo automático.
    |
    */
    'logos_path' => 'logos_remax',

    'default_logo' => 'symbol',

    'logos' => [
        'symbol' => ['file' => 'logo_06_simbolo_color.png', 'label' => 'Símbolo (sin letras)'],
        'horizontal_color' => ['file' => 'logo_01_color.png', 'label' => 'Horizontal a color'],
        'horizontal_black' => ['file' => 'logo_02_negro.png', 'label' => 'Horizontal negro'],
        'horizontal_white' => ['file' => 'logo_03_blanco.png', 'label' => 'Horizontal blanco'],
        'horizontal_color_white_text' => ['file' => 'logo_04_color_texto_blanco.png', 'label' => 'Horizontal color, texto blanco'],
        'vertical_silver' => ['file' => 'logo_05_vertical_plata (1).png', 'label' => 'Vertical plata'],
        'vertical_color_silver_text' => ['file' => 'logo_07_vertical_color_letras_plata.png', 'label' => 'Vertical color, texto plata'],
    ],
];
