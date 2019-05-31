<?php
declare(strict_types=1);

use \Yahiru\EntityFactory\Factory;

Factory::define(
    'define2',
    function (array $attr) {
        return $attr['name'];
    },
    ['name' => 'define2']
);