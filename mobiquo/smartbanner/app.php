<?php

error_reporting(0);

$title = isset($_GET['name']) ? $_GET['name'] : 'Stay in touch with us via Tapatalk app';
$board_url = isset($_GET['board_url']) ? $_GET['board_url'] : '';
$referer = isset($_GET['referer']) ? $_GET['referer'] : '';
$redirect_url = $referer ? $referer : ($board_url ? $board_url : dirname(dirname(dirname($_SERVER['REQUEST_URI']))));
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
if (!preg_match('#^https?://#si', $redirect_url)) $redirect_url = '/';

if (!file_exists('images/close-50x50.png'))
{
    header('Location: '.$redirect_url);
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?php echo htmlspecialchars($title); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="format-detection" content="telephone=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="white" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://s3.amazonaws.com/welcome-screen/welcome_screen.css"/>
<script src="https://s3.amazonaws.com/welcome-screen/welcome.js"></script>
<script>
    $(document).ready(function() {
        check_device();
        // Detect whether device supports orientationchange event, otherwise fall back to
        // the resize event.
        var supportsOrientationChange = "onorientationchange" in window,
            orientationEvent = supportsOrientationChange ? "orientationchange" : "resize";
          
        window.addEventListener(orientationEvent, function() {
            check_device();
            $("#close_icon img").click(function() {
                localStorage.hide = true;
                window.location.href='<?php echo htmlspecialchars($redirect_url)?>';
            });
        }, false);

        $("#web_bg img").css("max-height",$(window).height() + 'px');
        //$("body").height(($(window).height()*2- $(document).height() )+ 'px');
        $("#close_icon img").click(function() {
            localStorage.hide = true;
            window.location.href='<?php echo htmlspecialchars($redirect_url)?>';
        });
        $("#button a").attr("href","http://tapatalk.com/m?id=23&referer=<?php echo urlencode($board_url)?>");
    })
</script>
</head>
<body scroll="no">
<script>
$("body").append(body);
</script>
</body>
</html>