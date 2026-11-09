<?php

$config = new PrestaShop\CodingStandards\CsFixer\Config();

$config
    ->setUsingCache(true)
    ->getFinder()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('tests');

return $config;
