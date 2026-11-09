<?php

$config = new class extends PrestaShop\CodingStandards\CsFixer\Config {
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'php_unit_method_casing' => ['case' => 'snake_case'],
        ]);
    }
};

$config
    ->setUsingCache(true)
    ->getFinder()
    ->in(__DIR__)
    ->exclude('vendor');

return $config;
