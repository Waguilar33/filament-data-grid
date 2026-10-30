<?php

declare(strict_types=1);

return [
    'route' => [
        'descriptions' => [
            'browser' => 'Entregas de esta ruta, filtradas en el navegador.',
            'server' => 'Las mismas entregas, cargadas página por página desde el servidor.',
        ],
        'sections' => [
            'browser' => 'Filas enviadas al navegador',
            'server' => 'Filas cargadas desde el servidor',
        ],
        'navigation' => 'Ruta de entrega',
        'columns' => [
            'delivery' => 'Entrega',
            'customer' => 'Cliente',
            'warehouse' => 'Bodega',
            'product' => 'Producto',
            'code' => 'Código',
            'requested' => 'Solicitado',
            'handed_over' => 'Entregado por bodega',
        ],
        'fill_statuses' => [
            'complete' => 'Completa',
            'partial' => 'Parcial',
        ],
    ],
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
        'stock' => 'Existencias',
        'salesperson' => 'Vendedor',
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
        'kind' => 'Tipo',
        'status' => 'Estado',
        'date' => 'Fecha',
        'color' => 'Color',
        'available' => 'Disponibles',
        'salesperson' => 'Vendedor',
        'approved' => 'Aprobado',
    ],
    'kinds' => [
        'credit' => 'Crédito',
        'debit' => 'Débito',
    ],
    'statuses' => [
        'draft' => 'Borrador',
        'sent' => 'Enviado',
    ],
    'units' => [
        'box' => 'Caja',
        'kg' => 'Kilogramo',
        'unit' => 'Unidad',
    ],
    'warehouse_option' => '{0} :zone > :place · sin disponibilidad|{1} :zone > :place · 1 disponible|[2,*] :zone > :place · :count disponibles',
    'saved_lines' => '{0} Guardado sin líneas|{1} Guardada 1 línea|[2,*] Guardadas :count líneas',
];
