<?php

defined('IN_MOBIQUO') or exit;

class Tapatalk_Input {

    const INT = 'INT';
    const STRING = 'STRING';
    const ALPHASTRING = 'ALPHASTRING';
    const RAW = 'RAW';

    static public function filterXmlInput(array $filters, $xmlrpc_params){
        global $db, $mybb;

        $params = php_xmlrpc_decode($xmlrpc_params);

        // handle upload requests etc.
        if(empty($params) && !empty($_POST['method_name'])){
            $params = array();
            foreach($filters as $name => $type){
                if(isset($_POST[$name])){
                    $params[]=$_POST[$name];
                }
            }
        }

        $data = array();
        $i = 0;
        foreach($filters as $name => $type){
            switch($type){
                case self::INT:
                    if(isset($params[$i]))
                        $data[$name] = intval($params[$i]);
                    else
                        $data[$name] = 0;
                    break;
                case self::ALPHASTRING:
                    if(isset($params[$i]))
                        $data[$name] = preg_replace("#[^a-z\.\-_]#i", "", $params[$i]);
                    else
                        $data[$name] = '';
                    $data[$name.'_esc'] = $db->escape_string($data[$name]);
                    break;
                case self::STRING:
                    if(isset($params[$i]))
                        if($name == 'subject' || $name == 'post_title' || $name == 'title')
                        {
                        	$data[$name] = Tapatalk_Input::covertUnifiedToEmpty($params[$i]);
                        }
                        else 
                        {
                        	$data[$name] = Tapatalk_Input::covertEmojiToName($params[$i]);
                        }
                    else
                        $data[$name] = '';
                    $data[$name.'_esc'] = $db->escape_string($data[$name]);
                    break;
                case self::RAW:
                    $data[$name] = $params[$i];
                    break;
            }
            $i ++;
        }

        return $data;
    }
    
    static public function covertEmojiToName($data) {
    	global $mybb;
    	require_once MYBB_ROOT.$mybb->settings['tapatalk_directory'].'/emoji/emoji.php';
    	$data = emoji_docomo_to_unified($data);   # DoCoMo devices
    	$data = emoji_kddi_to_unified($data);     # KDDI & Au devices
    	$data = emoji_softbank_to_unified($data); # Softbank & (iPhone) Apple devices
    	$data = emoji_google_to_unified($data);   # Google Android devices
		$data = emoji_unified_to_name($data);

		return $data;
    }
	static public function covertNameToEmoji($data) {
		global $mybb;
    	require_once MYBB_ROOT.$mybb->settings['tapatalk_directory'].'/emoji/emoji.php';
		$data = emoji_name_to_unified($data);
		//$data = emoji_unified_to_google($data);
		return $data;
	}
	static public function covertNameToEmpty($data) {
		global $mybb;
    	require_once MYBB_ROOT.$mybb->settings['tapatalk_directory'].'/emoji/emoji.php';
		$data = emoji_name_to_empty($data);
		return $data;
	}
	static public function covertUnifiedToEmpty($data) {
		global $mybb;
    	require_once MYBB_ROOT.$mybb->settings['tapatalk_directory'].'/emoji/emoji.php';
		$data = emoji_unified_to_empty($data);
		return $data;
	}	
	static public function covertHtmlToEmoji($data) {
		global $mybb;
		require_once MYBB_ROOT.$mybb->settings['tapatalk_directory'].'/emoji/emoji.php';
		$data = emoji_html_to_unified($data);
		return $data;
	}
}