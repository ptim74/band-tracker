<?php

$config = (object) [
    'clash_api_token' => 'CLASH_API_TOKEN',
    'band_access_token' => 'BAND_ACCESS_TOKEN',
    'data_dir' => 'data'
];

$config->clans[] = (object) [
    'tag' => '#CLANTAG1',
    'band_key' => 'BAND_KEY1'
];

$config->clans[] = (object) [
    'tag' => '#CLANTAG2',
    'band_key' => 'BAND_KEY2'
];

?>