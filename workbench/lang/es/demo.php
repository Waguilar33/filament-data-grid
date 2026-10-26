<?php

declare(strict_types=1);

return [
    'navigation' => 'Tabla de datos',
    'sections' => [
        'lines' => 'Productos',
        'saved_state' => 'Último estado guardado',
        'adjustments' => 'Ajustes',
    ],
    'actions' => [
        'save' => 'Guardar',
        'edit_in_slide_over' => 'Editar en panel lateral',
        'add_sample_lines' => 'Agregar líneas de ejemplo',
        'add_adjustment' => 'Agregar ajuste',
    ],
    'columns' => [
        'product' => 'Producto',
        'quantity' => 'Cantidad',
        'unit' => 'Unidad',
        'warehouse' => 'Bodega',
        'gift' => 'Regalo',
        'comment' => 'Comentario',
        'total' => 'Total',
        'reason' => 'Motivo',
        'amount' => 'Monto',
        'approved' => 'Aprobado',
    ],
    'units' => [
        'box' => 'Caja',
        'kg' => 'Kilogramo',
        'unit' => 'Unidad',
    ],
    'warehouse_option' => '{0} :zone > :place · sin disponibilidad|{1} :zone > :place · 1 disponible|[2,*] :zone > :place · :count disponibles',
    'saved_lines' => '{0} Guardado sin líneas|{1} Guardada 1 línea|[2,*] Guardadas :count líneas',
];
