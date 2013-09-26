<?php

$board_url = isset($_GET['board_url']) ? $_GET['board_url'] : '';
$referer = isset($_GET['referer']) ? $_GET['referer'] : '';
$code = isset($_GET['app_forum_code']) ? $_GET['app_forum_code'] : '';

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
    <title>Stay in touch with us via Tapatalk app</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <style>
        *{margin:0;padding:0;border:0;}html,iframe,body{height:100%;width:100%;}body{background-color:#ddd;}#web_bg{position:absolute; width:100%; z-index:-1}#web_bg img{position:fixed;width:100%;}
    </style>
</head>
<body>
    <div id="web_bg"><img src="ads_bg.jpg" /></div>
    <iframe src="http://tapatalk.com/ads.php<?php echo '?referer='.urlencode($referer).'&code='.urlencode($code).'&board_url='.urlencode($board_url) ?>" seamless></iframe>
</body>
</html>