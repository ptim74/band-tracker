<?php

$config = (object) [
    'clash_api_token' => 'PUT_YOUR_CLASH_API_TOKEN_HERE',
    'band_access_token' => 'PUT_YOUR_BAND_ACCESS_TOKEN_HERE',
    'data_dir' => 'data',
    'run_continuously' => 1,
    'list_bands_on_startup' => 1
];

$config->clans[] = (object) [
    'tag' => '#PUT_CLANTAG_OF_FIRST_CLAN_HERE',
    'band_key' => 'PUT_BAND_KEY_FOR_FIRST_CLAN_HERE'
];

$config->clans[] = (object) [
    'tag' => '#YOU_CAN_TRACK_AS_MANY_CLANS_AS_YOU_WANT',
    'band_key' => 'PUT_BAND_KEY_FOR_SECOND_CLAN_HERE'
];

?>