<?php

return [
    'attributes' => [
        'customer_id' => 'ID du client',
        'name_eu' => 'nom en basque',
        'name_es' => 'nom en espagnol',
        'name_fr' => 'nom en français',
        'name_en' => 'nom en anglais',
    ],
    'name' => [
        'required' => 'Le champ :attribute est requis.',
        'min' => 'Le champ :attribute doit faire au moins :min caractères.',
        'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'unique' => 'Le :attribute a déjà été pris.',
    ],
];
