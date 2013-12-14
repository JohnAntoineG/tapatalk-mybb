<?php

if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']))
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $app_referer = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
else
    $app_referer = $board_url;


// for those forum system which can not add js in html body, please set $functionCallAfterWindowLoad as 1
$functionCallAfterWindowLoad = isset($functionCallAfterWindowLoad) && $functionCallAfterWindowLoad ? 1 : 0;

$app_ios_id_default = '307880732';      // Tapatalk 1, 585178888 for Tapatalk 2
$app_ios_hd_id_default = '307880732';   // Tapatalk 1, 481579541 for Tapatalk HD
$app_android_id_default = 'com.quoord.tapatalkpro.activity';

$app_location_url = isset($app_location_url) && preg_match('#^tapatalk://#i', $app_location_url) ? $app_location_url : 'tapatalk://';
$app_location_url_byo = str_replace('tapatalk://', 'tapatalk-byo://', $app_location_url);
$tapatalk_dir_url = isset($tapatalk_dir_url) && $tapatalk_dir_url ? $tapatalk_dir_url : './mobiquo';
$app_forum_name = isset($app_forum_name) && $app_forum_name ? $app_forum_name : 'this forum';

$app_ios_id = isset($app_ios_id) && intval($app_ios_id) ? intval($app_ios_id) : '';
$app_android_id = isset($app_android_id) && $app_android_id ? preg_replace('/^.*?details\?id=([^\s,&]+).*?$/si', '$1', $app_android_id) : '';
$app_kindle_url = isset($app_kindle_url) ? $app_kindle_url : '';
$app_banner_message = isset($app_banner_message) && $app_banner_message ? preg_replace('/\r\n|\n|\r/si', '<br />', $app_banner_message) : '';
$is_mobile_skin = isset($is_mobile_skin) && $is_mobile_skin ? 1 : 0;


// display twitter card
$twitter_card_head = '';
if ($app_ios_id != -1 || $app_android_id != -1)
{
    $twitter_card_head .= '
        <!-- twitter app card start-->
        <!-- https://dev.twitter.com/docs/cards/types/app-card -->
        <meta name="twitter:card" content="app" />
    ';
    
    if ($app_ios_id != '-1')
    {
        $twitter_card_head .= '
        <meta name="twitter:app:id:iphone" content="'.($app_ios_id ? $app_ios_id : $app_ios_id_default).'" />
        <meta name="twitter:app:url:iphone" content="'.($app_ios_id ? $app_location_url_byo : $app_location_url).'" />
        <meta name="twitter:app:id:ipad" content="'.($app_ios_id ? $app_ios_id : $app_ios_hd_id_default).'">
        <meta name="twitter:app:url:ipad" content="'.($app_ios_id ? $app_location_url_byo : $app_location_url).'" />
        ';
    };
        
    if ($app_android_id != '-1')
    {
        $twitter_card_head .= '
        <meta name="twitter:app:id:googleplay" content="'.($app_android_id ? $app_android_id : $app_android_id_default).'" />
        <meta name="twitter:app:url:googleplay" content="'.($app_android_id ? $app_location_url_byo : $app_location_url).'" />
        ';
    };
    
    $twitter_card_head .= '
    <!-- twitter app card -->
    ';
}

// display smart banner and welcome page
$app_banner_head = '';
if (file_exists($tapatalk_dir . '/smartbanner/welcome.php') && file_exists($tapatalk_dir . '/smartbanner/appbanner.js'))
{
    $app_banner_head = '
        <!-- Tapatalk Banner&Welcome head start -->
        <link href="'.$tapatalk_dir_url.'/smartbanner/appbanner.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript">
            var is_mobile_skin     = '.$is_mobile_skin.';
            var app_ios_id         = "'.$app_ios_id.'";
            var app_android_id     = "'.addslashes($app_android_id).'";
            var app_kindle_url     = "'.addslashes(urlencode($app_kindle_url)).'";
            var app_banner_message = "'.addslashes($app_banner_message).'";
            var app_forum_name     = "'.addslashes($app_forum_name).'";
            var app_location_url   = "'.addslashes($app_location_url).'";
            var app_board_url      = "'.addslashes(urlencode($board_url)).'";
            var functionCallAfterWindowLoad = '.$functionCallAfterWindowLoad.';
            
            var app_forum_code = "'.(trim($api_key) ? md5(trim($api_key)) : '').'";
            var app_referer = "'.addslashes(urlencode($app_referer)).'";
            var app_welcome_url = "'.addslashes($tapatalk_dir_url.'/smartbanner/welcome.php').'";
            var app_welcome_enable = "'.($app_ads_enable ? 1 : 0).'";
        </script>
        <script src="'.$tapatalk_dir_url.'/smartbanner/appbanner.js" type="text/javascript"></script>
        <!-- Tapatalk Banner head end-->
    ';
}

// add App Indexing for Google Search
$app_indexing = '';
$host_path = preg_replace('#tapatalk://#si', '', $app_location_url);
if ($host_path)
{
    if ($app_android_id == $app_android_id_default)
    {
        $app_indexing = '
        <!-- App Indexing for Google Search -->
        <link href="android-app://com.quoord.tapatalkHD/tapatalk/'.urlencode($host_path).'" rel="alternate" />
        <link href="android-app://com.quoord.tapatalkpro.activity/tapatalk/'.urlencode($host_path).'" rel="alternate" />
        ';
    }
    else
    {
        $app_indexing = '
        <!-- App Indexing for Google Search -->
        <link href="android-app://'.urlencode($app_android_id).'/tapatalk-byo/'.urlencode($host_path).'" rel="alternate" />
        ';
    }
}

$app_head_include = $twitter_card_head.$app_banner_head.$app_indexing;
