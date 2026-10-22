<?php

return [
    'attributes' => [
        'entity_id' => 'bezeroaren ID',
        'name_eu' => 'euskarazko izena',
        'name_es' => 'gaztelaniazko izena',
        'name_fr' => 'frantsesezko izena',
        'name_en' => 'ingelesezko izena',
    ],
    'name' => [
        'required' => ':Attribute eremua nahitaezkoa da.',
        'min' => ':Attribute-(e)k gutxienez :min karaktere izan behar ditu.',
        'max' => ':Attribute-(e)k ezin du :max karaktere baino gehiago eduki.',
        'unique' => ':Attribute dagoeneko hartua izan da.',
    ],
];
