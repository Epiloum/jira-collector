<?php
if (file_exists('.env')) {
    $env = array_reduce(explode("\n", file_get_contents('.env')), function($carry, $item) {
        $key = strstr($item, '=', true);
        $value = substr(strstr($item, '='), 1);
        $carry[$key] = trim($value, '\'"');
        return $carry;
    });
} else {
    $env = [];
}

$db = new mysqli($env['DB_CONTAINER_NAME'], $env['DB_USER'], $env['DB_PASSWORD'], $env['DB_DATABASE']);