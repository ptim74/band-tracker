<?php

include_once('config.php');

function getClashData($tag) {
    global $config;
    $url = "https://api.clashofclans.com/v1/clans/".urlencode($tag);
    $context = stream_context_create(array(
        "http"=>array(
            "method"=>"GET",
            "header"=>"Authorization: Bearer " . $config->clash_api_token)));
    return file_get_contents($url,false,$context);
}

function getMembersFromClanData($json) {
    $obj = json_decode($json);
    if(property_exists($obj,"memberList"))
        return $obj->memberList;
    return null;
}

function getMemberFromArray($array,$tag) {
    foreach($array as $member)
        if($member->tag == $tag)
            return $member;
    return null;
}

function getChanges($oldJson, $newJson) {
    $old = getMembersFromClanData($oldJson);
    $new = getMembersFromClanData($newJson);
    $ret = "";

    foreach($old as $oldMember) {
        $newMember = getMemberFromArray($new,$oldMember->tag);
        if($newMember == null)
            $ret .= $oldMember->name." left clan".PHP_EOL;
    }
    foreach($new as $newMember) {
        $oldMember = getMemberFromArray($old,$newMember->tag);
        $oldDonations = $oldMember == null ? 0 : $oldMember->donations;
        $oldReceived = $oldMember == null ? 0 : $oldMember->donationsReceived;
        if($oldMember == null)
            $ret .= $newMember->name." joined clan".PHP_EOL;
        if($newMember->donations > $oldDonations)
            $ret .= $newMember->name." donated ".
                    ($newMember->donations-$oldDonations)." troops".PHP_EOL;
        if($newMember->donationsReceived > $oldReceived)
            $ret .= $newMember->name." received ".
                    ($newMember->donationsReceived-$oldReceived)." troops".PHP_EOL;
    }
    return $ret;
}

function createBandPost($band_key,$message) {
    global $config;
    $url = 'https://openapi.band.us/v2.2/band/post/create';
    $data = array(
        'access_token' => $config->band_access_token, 
        'band_key' => $band_key,
        'content' => $message);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data)));
    $context  = stream_context_create($options);
    $json = file_get_contents($url, false, $context);
}

function runClan($clan) {
    global $config;
    echo date(DATE_W3C)," ",$clan->tag,PHP_EOL;
    $datafile = $config->data_dir.'/'.$clan->tag.".json";
    $old = @file_get_contents($datafile);
    $new = getClashData($clan->tag);
    if(!empty($old) && !empty($new)) {
        $message = getChanges($old,$new);
        if(!empty($message)) {
            echo $message;
            createBandPost($clan->band_key,$message);
        }
    }
    if(!empty($new)) {
        file_put_contents($datafile,$new);
    }
}

function checkConfig() {
    global $config;
    if(!isset($config))
        die("Please setup \$config in config.php");
    if(!isset($config->clash_api_token))
        die("Please setup \$config->clash_api_token in config.php");
    if(!isset($config->band_access_token))
        die("Please setup \$config->band_access_token in config.php");
    if(!isset($config->data_dir))
        die("Please setup \$config->data_dir in config.php");
    if(!isset($config->clans))
        die("Please setup \$config->clans in config.php");
    if(!file_exists($config->data_dir))
        mkdir($config->data_dir);
    if(!file_exists($config->data_dir))
        die("Please create directory $config->data_dir");
}

function runOnce() {
    global $config;
    checkConfig();
    foreach($config->clans as $clan)
        runClan($clan);
}

runOnce();

?>