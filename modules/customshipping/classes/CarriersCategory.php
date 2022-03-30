<?php

class CarriersCategory extends ObjectModel
{
 
    public $id;
    public $id_category;
    public $id_carrier;
 
    public static $definition = [
        'table' => 'custom_shipping',
        'primary' => 'id',
        'multilang' => true,
        'fields' => [
            // Champs Standards
            'category' => ['type' => Category::class, 'validate' => 'isInt', 'size' => 11, 'required' => true],
            'carrier' => ['type' => Carrier::class, 'validate' => 'isInt', 'size' => 11, 'required' => true],
        ],
    ];
}