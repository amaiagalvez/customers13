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
        'required' => 'Herrialde Laguna deskribapena beharrezkoa da.',
        'min' => 'Herrialde Laguna gutxienez :min Letrak egon behar da.',
        'max' => 'Herrialde Laguna gabeez :max Letrak ez sorto da.',
        'unique' => 'Herrialde Laguna lehenetsik ez duten izena izan da.',
    ],
];
