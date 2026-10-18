<?php

return [
    'attributes' => [
        'entity_id' => 'ID del cliente',
        'name_eu' => 'nombre en euskera',
        'name_es' => 'nombre en español',
        'name_fr' => 'nombre en francés',
        'name_en' => 'nombre en inglés',
    ],
    'name' => [
        'required' => 'El campo :attribute es obligatorio.',
        'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        'max' => 'El campo :attribute no puede ser mayor a :max caracteres.',
        'unique' => 'El :attribute ya ha sido tomado.',
    ],
];
