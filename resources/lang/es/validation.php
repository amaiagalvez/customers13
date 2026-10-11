<?php

return [
    'attributes' => [
        'customer_id' => 'ID del cliente',
    ],
    'name' => [
        'required' => 'El campo :attribute es obligatorio.',
        'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        'max' => 'El campo :attribute no puede ser mayor a :max caracteres.',
        'unique' => 'El :attribute ya ha sido tomado.',
    ],
];
