<?php

include_once('config.php');

function getClashData($tag) {
    global $config,$http_response_header;
    $api_url = isset($config->clash_api_url) ? $config->clash_api_url : "https://api.clashofclans.com/v1";
    $url = $api_url."/clans/".urlencode($tag);
    $header = "";
    if(!empty($config->clash_api_compress)) {
        $url = "compress.zlib://".$url;
        $header .= "Accept-Encoding: gzip\r\n";
    }
    if(isset($config->clash_api_token))
        $header .= "Authorization: Bearer " . $config->clash_api_token . "\r\n";
    $http_array = array("method" => "GET", "ignore_errors" => TRUE);
    if(isset($header))
        $http_array["header"] = $header;
    $context = stream_context_create(array(
        "http"=> $http_array));
    $json = file_get_contents($url,false,$context);
    $obj = json_decode($json);
    if(empty($obj->memberList)) {
        foreach($http_response_header as $hdr)
            echo($hdr.PHP_EOL);
        echo($json.PHP_EOL);
        return null;
    }
    return $json;
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

function checkResultSuccess($obj, $comment) {
    if($obj == null) {
        echo($comment." failed, result is null".PHP_EOL);
        return false;
    }
    if(!isset($obj->result_code)) {
        echo($comment." failed, result_code is null".PHP_EOL);
        return false;
    }
    if($obj->result_code != 1) {
        echo($comment." failed, result_code is ".$obj->result_code.PHP_EOL);
        print_r($obj);
        return false;
    }
    return true;
}

function bandCall($url,$options=null) {
    $context = null;
    if($options != null)
        $context = stream_context_create($options);
    do {
        $cooldown = 0;
        $context = null;
        if($options != null)
            $context = stream_context_create($options);
        $json = file_get_contents($url, false, $context);
        $obj = json_decode($json);
        if(isset($obj->result_code) && $obj->result_code == 1003) {
            print_r($obj);
            $cooldown = $obj->result_data->cool_time->expired_in;
            echo("Cooldown detected, retrying after ".$cooldown." seconds".PHP_EOL);
            sleep($cooldown);
        }
    } while($cooldown > 0);
    return $obj;
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
    $obj = bandCall($url,$options);
    if(!checkResultSuccess($obj,"create post"))
        return null;
    return $obj->result_data->post_key;
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
    $obj = bandCall($url,$options);
    return checkResultSuccess($obj,"create comment");
}

function getBandPostCommentCount($band_key,$post_key) {
    global $config;
    $url = "https://openapi.band.us/v2.1/band/post?access_token=".$config->band_access_token.
           "&band_key=".$band_key."&post_key=".$post_key;
    $obj = bandCall($url);
    if(!checkResultSuccess($obj,"get comment count"))
        return -1;
    return $obj->result_data->post->comment_count;
}

function deleteOldestBandPostComment($band_key,$post_key) {
    global $config;
    $url = "https://openapi.band.us/v2/band/post/comments?access_token=".$config->band_access_token.
           "&band_key=".$band_key."&post_key=".$post_key;
    $json = file_get_contents($url);
    $obj = json_decode($json);
    if(!checkResultSuccess($obj,"get comments"))
        return -1;
    $url = "https://openapi.band.us/v2/band/post/comment/remove";
    foreach($obj->result_data->items as $comment) {
        $data = array(
            'access_token' => $config->band_access_token, 
            'band_key' => $band_key,
            'post_key' => $post_key,
            'comment_key' => $comment->comment_key);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => http_build_query($data)));
        $obj = bandCall($url,$options);
        return checkResultSuccess($obj,"get comments"); //Delete only one comment
    }
}

function listBands() {
    global $config;
    echo("Available Bands:".PHP_EOL);
    $url = "https://openapi.band.us/v2.1/bands?access_token=".$config->band_access_token;
    $obj = bandCall($url);
    if(!checkResultSuccess($obj,"list bands"))
        return;
    foreach($obj->result_data->bands as $band)
        echo($band->band_key."  ".$band->name.PHP_EOL);
    echo(PHP_EOL);
}

function runClan($clan) {
    global $config;
    echo date(DATE_W3C)," ",$clan->tag,PHP_EOL;
    $datafile = $config->data_dir.'/'.$clan->band_key.$clan->tag.".json";
    $postfile = $config->data_dir.'/'.$clan->band_key.$clan->tag.".post_key";
    $countfile = $config->data_dir.'/'.$clan->band_key.$clan->tag.".count";
    $comment_limit = $config->comment_limit;
    if(!empty($clan->comment_limit))
        $comment_limit = $clan->comment_limit;
    $old = @file_get_contents($datafile);
    $new = getClashData($clan->tag);
    if(!empty($old) && !empty($new)) {
        $message = getChanges($old,$new);
        if(!empty($message)) {
            echo $message;
            if(empty($clan->use_comments)) {
                createBandPost($clan->band_key,$message);
            } else {
                $post_key = @file_get_contents($postfile);
                $comment_count = -1; //Post doesn't exist
                if(!empty($post_key))
                    $comment_count = getBandPostCommentCount($clan->band_key,$post_key);
                if($comment_count == -1) {
                    $clan_obj = json_decode($new);
                    $post_key = createBandPost($clan->band_key,"Donation Tracker for ".$clan_obj->name);
                    if(!empty($post_key))
                        file_put_contents($postfile,$post_key);
                }
                if(createBandComment($clan->band_key,$post_key,$message))
                    $comment_count++;
                file_put_contents($countfile,$comment_count);
            }
        } elseif(!empty($clan->use_comments) && $comment_limit > 0) {
            $comment_count = @file_get_contents($countfile) + 0;
            $post_key = @file_get_contents($postfile);
            if(!empty($post_key) && $comment_count > $comment_limit)
                if(deleteOldestBandPostComment($clan->band_key,$post_key))
                    file_put_contents($countfile,--$comment_count);
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
    if(!isset($config->comment_limit))
        die("Please setup \$config->comment_limit in config.php");
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