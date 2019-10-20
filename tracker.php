<?php

include_once('config.php');

function getClashData($tag) {
    global $config;
    $api_url = isset($config->clash_api_url) ? $config->clash_api_url : "https://api.clashofclans.com/v1";
    $url = $api_url."/clans/".urlencode($tag);
    $header = "";
    if(isset($config->clash_api_compress)) {
        $url = "compress.zlib://".$url;
        $header .= "Accept-Encoding: gzip\r\n";
    }
    if(isset($config->clash_api_token))
        $header .= "Authorization: Bearer " . $config->clash_api_token . "\r\n";
    $http_array = array("method" => "GET");
    if(isset($header))
        $http_array["header"] = $header;
    $context = stream_context_create(array(
        "http"=> $http_array));
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
    $obj = json_decode($json);
    if(!empty($obj->result_data) && !empty($obj->result_data->post_key))
        return $obj->result_data->post_key;
    return null;
}

function createBandComment($band_key,$post_key,$message) {
    global $config;
    $url = "https://openapi.band.us/v2/band/post/comment/create";
    $data = array(
        'access_token' => $config->band_access_token, 
        'band_key' => $band_key,
        'post_key' => $post_key,
        'body' => $message);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data)));
    $context  = stream_context_create($options);
    $json = file_get_contents($url, false, $context);
    $obj = json_decode($json);
    if(!empty($obj->result_data) && !empty($obj->result_data->message))
        return $obj->result_data->message;
    return null;
}

function checkPostExists($band_key,$post_key) {
    global $config;
    $url = "https://openapi.band.us/v2.1/band/post?access_token=".$config->band_access_token.
           "&band_key=".$band_key."&post_key=".$post_key;
    $json = file_get_contents($url);
    $obj = json_decode($json);
    if(!empty($obj->result_data) && !empty($obj->result_data->post))
        return true;
    return false;
}

function listBands() {
    global $config;
    echo("Available Bands:".PHP_EOL);
    $url = "https://openapi.band.us/v2.1/bands?access_token=".$config->band_access_token;
    $json = file_get_contents($url);
    $obj = json_decode($json);
    for($i = 0; $i < count($obj->result_data->bands); $i++) {
        $band = $obj->result_data->bands[$i];
        echo($band->band_key."  ".$band->name.PHP_EOL);
    }
    echo(PHP_EOL);
}

function runClan($clan) {
    global $config;
    echo date(DATE_W3C)," ",$clan->tag,PHP_EOL;
    $datafile = $config->data_dir.'/'.$clan->band_key.$clan->tag.".json";
    $old = @file_get_contents($datafile);
    $new = getClashData($clan->tag);
    if(!empty($old) && !empty($new)) {
        $message = getChanges($old,$new);
        if(!empty($message)) {
            echo $message;
            if(empty($clan->use_comments)) {
                createBandPost($clan->band_key,$message);
            } else {
                $postfile = $config->data_dir.'/'.$clan->band_key.$clan->tag.".post_key";
                $post_key = @file_get_contents($postfile);
                if(empty($post_key) || checkPostExists($clan->band_key,$post_key) == false) {
                    $clan_obj = json_decode($new);
                    $post_key = createBandPost($clan->band_key,"Donation Tracker for ".$clan_obj->name);
                    file_put_contents($postfile,$post_key);
                }
                createBandComment($clan->band_key,$post_key,$message);
            }
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
    if(!file_exists($config->data_dir))
        mkdir($config->data_dir);
    if(!file_exists($config->data_dir))
        die("Please create directory $config->data_dir");
}

function runOnce() {
    global $config;
    if(!isset($config->clans))
        die("Please setup \$config->clans in config.php");
    foreach($config->clans as $clan)
        runClan($clan);
}

function run() {
    global $config;
    checkConfig();
    if(!empty($config->list_bands_on_startup))
        listBands();
    runOnce();
    while(!empty($config->run_continuously)) {
        sleep(60);
        runOnce();
    }
}

run();

?>