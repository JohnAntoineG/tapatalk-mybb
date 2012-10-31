<?php
define('IN_MYBB', 1);
require_once '../global.php';
error_reporting(E_ALL & ~E_NOTICE);

$return_status = do_post_request(array('test' => 1 , 'key' => $mybb->settings['tapatalk_push_key']));
$return_ip = do_post_request(array('ip' => 1));
$board_url = $mybb->settings['bburl'];
if(isset($mybb->settings['tapatalk_push']) && $mybb->settings['tapatalk_push'] == 1)
{
	$option_status = 'On';
}
elseif (isset($mybb->settings['tapatalk_push']) && $mybb->settings['tapatalk_push'] == 0)
{
	$option_status = 'Off';
}
else 
{
	$option_status = 'Unset';
}	
echo '<b>Tapatalk Push Notification Status Monitor</b><br/>';
echo '<br/>Push notification test: ' . (($return_status === '1') ? '<b>Success</b>' : '<font color="red">Failed('.$return_status.')</font>');
echo '<br/>Current server IP: ' . $return_ip;
echo '<br/>Current forum url: ' . $board_url;
echo '<br/>Tapatalk user table existence: ' . (($mybb->settings['tapatalk_push']) ? 'Yes' : 'On');
echo '<br/>Push Notification Option status: ' . $option_status;
echo '<br/><br/><a href="http://tapatalk.com/api/api.php" target="_blank">Tapatalk API for Universal Forum Access</a> | <a href="http://tapatalk.com/mobile.php" target="_blank">Tapatalk Mobile Applications</a><br>
    For more details, please visit <a href="http://tapatalk.com" target="_blank">http://tapatalk.com</a>';

function do_post_request($data)
{
	$push_url = 'http://push.tapatalk.com/push.php';

	$response = 'CURL is disabled and PHP option "allow_url_fopen" is OFF. You can enable CURL or turn on "allow_url_fopen" in php.ini to fix this problem.';
	if (function_exists('curl_init'))
	{
		$ch = curl_init($push_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);

		$response = curl_exec($ch);
		curl_close($ch);
	}
	elseif (ini_get('allow_url_fopen'))
	{
		$params = array('http' => array(
			'method' => 'POST',
			'content' => http_build_query($data, '', '&'),
		));

		$ctx = stream_context_create($params);
		$timeout = 10;
		$old = ini_set('default_socket_timeout', $timeout);
		$fp = @fopen($push_url, 'rb', false, $ctx);
		ini_set('default_socket_timeout', $old);
		stream_set_timeout($fp, $timeout);
		stream_set_blocking($fp, 0); 
		
		if (!$fp) return false;
		$response = @stream_get_contents($fp);
	}
	return $response;
}