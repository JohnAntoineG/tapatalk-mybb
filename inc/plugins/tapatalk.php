<?php

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('error', 'tapatalk_error');
$plugins->add_hook('redirect', 'tapatalk_redirect');
$plugins->add_hook('global_start', 'tapatalk_global_start');
$plugins->add_hook('fetch_wol_activity_end', 'tapatalk_fetch_wol_activity_end');
$plugins->add_hook('pre_output_page', 'tapatalk_pre_output_page');

// hook for push
$plugins->add_hook('newreply_do_newreply_end', 'tapatalk_push_reply');
$plugins->add_hook('newreply_do_newreply_end', 'tapatalk_push_quote');
$plugins->add_hook('newreply_do_newreply_end', 'tapatalk_push_tag');
$plugins->add_hook('private_do_send_end', 'tapatalk_push_pm');
$plugins->add_hook('newthread_do_newthread_end', 'tapatalk_push_newtopic');
$plugins->add_hook('newthread_do_newthread_end', 'tapatalk_push_quote');
$plugins->add_hook('newthread_do_newthread_end', 'tapatalk_push_tag');
$plugins->add_hook('online_user','tapatalk_online_user');
$plugins->add_hook('online_end','tapatalk_online_end');

function tapatalk_info()
{
    /**
     * Array of information about the plugin.
     * name: The name of the plugin
     * description: Description of what the plugin does
     * website: The website the plugin is maintained at (Optional)
     * author: The name of the author of the plugin
     * authorsite: The URL to the website of the author (Optional)
     * version: The version number of the plugin
     * guid: Unique ID issued by the MyBB Mods site for version checking
     * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
     */
    return array(
        "name"          => "Tapatalk",
        "description"   => "Tapatalk Plugin for MyBB",
        "website"       => "http://tapatalk.com",
        "author"        => "Quoord Systems Limited",
        "authorsite"    => "http://tapatalk.com",
        "version"       => "3.3.2",
        "guid"          => "e7695283efec9a38b54d8656710bf92e",
        "compatibility" => "16*"
    );
}

function tapatalk_install()
{
    global $db;

    tapatalk_uninstall();
    if(!$db->table_exists('tapatalk_users'))
    {
        $db->query("
            CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "tapatalk_users (
              userid int(10) NOT NULL,
              announcement smallint(5) NOT NULL DEFAULT '1',
              pm smallint(5) NOT NULL DEFAULT '1',
              subscribe smallint(5) NOT NULL DEFAULT '1',
              newtopic smallint(5) NOT NULL DEFAULT '1',
              quote smallint(5) NOT NULL DEFAULT '1',
              tag smallint(5) NOT NULL DEFAULT '1',
              updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (userid)
            )
        ");
    }
    if(!$db->table_exists("tapatalk_push_data"))
    {
        $db->query("
            CREATE TABLE " . TABLE_PREFIX . "tapatalk_push_data (
              push_id int(10) NOT NULL AUTO_INCREMENT,
              author varchar(100) NOT NULL,
              user_id int(10) NOT NULL DEFAULT '0',
              data_type char(20) NOT NULL DEFAULT '',
              title varchar(200) NOT NULL DEFAULT '',
              data_id int(10) NOT NULL DEFAULT '0',
              create_time int(11) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (push_id),
              KEY user_id (user_id),
              KEY create_time (create_time)
            )
        ");
    }
    // Insert settings in to the database
    $query = $db->query("SELECT disporder FROM ".TABLE_PREFIX."settinggroups ORDER BY `disporder` DESC LIMIT 1");
    $disporder = $db->fetch_field($query, 'disporder')+1;

    $setting_group = array(
        'name'          =>    'tapatalk',
        'title'         =>    'Tapatalk Options',
        'description'   =>    'Optional Tapatalk Settings allow you to fine-tune the app behaviour with the forum',
        'disporder'     =>    0,
        'isdefault'     =>    0
    );
    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    $settings = array(
        'enable' => array(
            'title'         => 'Enable/Disable',
            'description'   => 'Enable/Disable the Tapatalk',
            'optionscode'   => 'onoff',
            'value'         => '1'
        ),
        'hide_forum' => array(
            'title'         => 'Hide Forums',
            'description'   => "Hide specific sub-forums from appearing in Tapatalk. Please enter a comma-separated sub-forum ID",
            'optionscode'   => 'text',
            'value'         => ''
        ),
        'reg_url' => array(
            'title'         => 'Registration URL',
            'description'   => "Default Registration URL: 'member.php?action=register'",
            'optionscode'   => 'text',
            'value'         => 'member.php?action=register'
        ),
        'allow_register' => array(
            'title'         => 'In-app Registration',
            'description'   => 'Allows Tapatalk users to create new account, change password and update email address in-app.',
            'optionscode'   => 'onoff',
            'value'         => '1'
        ),
        'directory' => array(
            'title'         => 'Tapatalk Plugin Directory',
            'description'   => 'Never change it if you did not rename the Tapatalk plugin directory. And the default value is \'mobiquo\'. If you renamed the Tapatalk plugin directory, you also need to update the same setting in Tapatalk Forum Owner Area.',
            'optionscode'   => 'text',
            'value'         => 'mobiquo'
        ),
        'push' => array(
            'title'         => 'Push Notification',
            'description'   => 'Instant notifications of subscription topics, forums and private messages',
            'optionscode'   => 'onoff',
            'value'         => '1'
        ),
        'datakeep' => array(
            'title'         => 'Uninstall Behaviour',
            'description'   => "Ability to retain 'tapatalk_' tables in DB. Useful if you're re-installing Tapatalk Plugin.",
            'optionscode'   => "radio\nkeep=Keep Data\ndelete=Delete all data and tables",
            'value'         => 'keep'
        ),
        'push_key' => array(
            'title'         => 'Tapatalk Push Key',
            'description'   => 'Push Notification may not be enabled if this key is missing. Visit Forum Owner Area in Tapatalk.com to obtain Push Key for your forum.',
            'optionscode'   => 'text',
            'value'         => ''
        ),
        'forum_read_only' => array(
            'title'         => 'Disable New Topic',
            'description'   => "Prevent Tapatalk users to create new topic in the selected sub-forums. This feature is useful if certain forums requires additional topic fields or permission that Tapatalk does not support,Separate multiple entries with a coma.",
            'optionscode'   => 'text',
            'value'         => ''
        ),
        'custom_replace'    => array(
            'title'         => 'Thread Content Replacement (Advanced)',
            'description'   => 'Ability to match and replace thread content using PHP preg_replace function(http://www.php.net/manual/en/function.preg-replace.php). E.g. "\'pattern\', \'replacement\'" . You can define more than one replace rule on each line.',
            'optionscode'   => 'textarea',
            'value'         => ''
        ),
        'ipad_msg' => array(
            'title'         => 'iPad Product Message',
            'description'   => 'Customize this message if you are Tapatalk BYO Customer and has published your App to Apple App Store.Default "This forum has an app for iPad! Click OK to learn more about Tapatalk."',
            'optionscode'   => 'text',
            'value'         => 'This forum has an app for iPad! Click OK to learn more about Tapatalk.',
        ),
        'ipad_url' => array(
            'title'         => 'iPad Product URL',
            'description'   => 'Change this URL if you are Tapatalk BYO Customer and has obtained your App URL from Apple App Store . Default "http://itunes.apple.com/us/app/tapatalk-hd-for-ipad/id481579541?mt=8"',
            'optionscode'   => 'text',
            'value'         => 'http://itunes.apple.com/us/app/tapatalk-hd-for-ipad/id481579541?mt=8',
        ),
        'iphone_msg' => array(
            'title'         => 'iPhone Product Message',
            'description'   => 'Customize this message if you are Tapatalk BYO Customer and has published your App to Apple App Store. Default "This forum has an app for iPhone! Click OK to learn more about Tapatalk."',
            'optionscode'   => 'text',
            'value'         => 'This forum has an app for iPhone! Click OK to learn more about Tapatalk.',
        ),
        'iphone_url' => array(
            'title'         => 'iPad Product URL',
            'description'   => 'Change this URL if you are Tapatalk BYO Customer and has obtained your App URL from Apple App Store. Default "http://itunes.apple.com/us/app/tapatalk-forum-app/id307880732?mt=8"',
            'optionscode'   => 'text',
            'value'         => 'http://itunes.apple.com/us/app/tapatalk-forum-app/id307880732?mt=8',
        ),
        'android_msg' => array(
            'title'         => 'Android Product Message',
            'description'   => 'Customize this message if you are Tapatalk BYO Customer and has published your App to Google Play. Default "This forum has an app for Android. Click OK to learn more about Tapatalk."',
            'optionscode'   => 'text',
            'value'         => 'This forum has an app for Android. Click OK to learn more about Tapatalk.',
        ),
        'android_url' => array(
            'title'         => 'Android Product URL',
            'description'   => 'Change this URL if you are Tapatalk BYO Customer and has obtained your App URL from Google Play. Default "market://details?id=com.quoord.tapatalkpro.activity"',
            'optionscode'   => 'text',
            'value'         => 'market://details?id=com.quoord.tapatalkpro.activity',
        ),
        'kindle_msg' => array(
            'title'         => 'Kindle Fire Product Message',
            'description'   => 'Customize this message if you are Tapatalk BYO Customer and has published your App to Amazon App Store. Default "This forum has an app for Kindle Fire! Click OK to learn more about Tapatalk."',
            'optionscode'   => 'text',
            'value'         => 'This forum has an app for Kindle Fire! Click OK to learn more about Tapatalk.',
        ),
        'kindle_url' => array(
            'title'         => 'Kindle Fire Product URL',
            'description'   => 'Change this URL if you are Tapatalk BYO Customer and has obtained your App URL from Amazon App Store. Default "http://www.amazon.com/gp/mas/dl/android?p=com.quoord.tapatalkpro.activity"',
            'optionscode'   => 'text',
            'value'         => 'http://www.amazon.com/gp/mas/dl/android?p=com.quoord.tapatalkpro.activity',
        ),
        'kindle_hd_msg' => array(
            'title'         => 'Kindle Fire HD Product Message',
            'description'   => 'Customize this message if you are Tapatalk BYO Customer and has published your App to Amazon App Store. Default "This forum has an app for Kindle Fire HD! Click OK to learn more about Tapatalk."',
            'optionscode'   => 'text',
            'value'         => 'This forum has an app for Kindle Fire HD! Click OK to learn more about Tapatalk.',
        ),
        'kindle_hd_url' => array(
            'title'         => 'Kindle Fire HD Product URL',
            'description'   => 'Change this URL if you are Tapatalk BYO Customer and has obtained your App URL from Amazon App Store. Default "http://www.amazon.com/gp/mas/dl/android?p=com.quoord.tapatalkpro.activity"',
            'optionscode'   => 'text',
            'value'         => 'http://www.amazon.com/gp/mas/dl/android?p=com.quoord.tapatalkHD',
        ),
        'smartbanner_notifier' => array(
            'title'         => 'Enable Smart Banner on Android Tablets',
            'description'   => "Allows user to open thread in Tapatalk directly from Web Browser on Android tablets.",
            'optionscode'   => 'onoff',
            'value'         => '1'
        ),
    );

    $s_index = 0;
    foreach($settings as $name => $setting)
    {
        $s_index++;
        $insert_settings = array(
            'name'        => $db->escape_string('tapatalk_'.$name),
            'title'       => $db->escape_string($setting['title']),
            'description' => $db->escape_string($setting['description']),
            'optionscode' => $db->escape_string($setting['optionscode']),
            'value'       => $db->escape_string($setting['value']),
            'disporder'   => $s_index,
            'gid'         => $gid,
            'isdefault'   => 0
        );
        $db->insert_query('settings', $insert_settings);
    }
    rebuild_settings();
}

function tapatalk_is_installed()
{
    global $mybb, $db;

    $result = $db->simple_select('settinggroups', 'gid', "name = 'tapatalk'", array('limit' => 1));
    $group = $db->fetch_array($result);

    return !empty($group['gid']) && $db->table_exists('tapatalk_users');
}

function tapatalk_uninstall()
{
    global $mybb, $db;
    if(($mybb->settings['tapatalk_datakeep'] == 'delete') && $db->table_exists('tapatalk_push_data'))
    {
        $db->drop_table('tapatalk_push_data');
    }
    if($db->table_exists('tapatalk_users') && ($mybb->settings['tapatalk_datakeep'] == 'delete' || !$db->field_exists('tag', 'tapatalk_users')))
    {
        $db->drop_table('tapatalk_users');
    }

    // Remove settings
    $result = $db->simple_select('settinggroups', 'gid', "name = 'tapatalk'", array('limit' => 1));
    $group = $db->fetch_array($result);

    if(!empty($group['gid']))
    {
        $db->delete_query('settinggroups', "gid='{$group['gid']}'");
        $db->delete_query('settings', "gid='{$group['gid']}'");
        rebuild_settings();
    }
}

/*
function tapatalk_activate()
{
    global $mybb, $db;

}

function tapatalk_deactivate()
{
    global $db;
}
*/
/* ============================================================================================ */

function tapatalk_error($error)
{
    if(defined('IN_MOBIQUO'))
    {
        global $lang, $include_topic_num, $search, $function_file_name;

        if ($error == $lang->error_nosearchresults)
        {
            if ($include_topic_num) {
                if($search['resulttype'] != 'posts') {
                    $response = new xmlrpcresp(new xmlrpcval(array(
                        'result'            => new xmlrpcval(true, 'boolean'),
                        'total_topic_num'   => new xmlrpcval(0, 'int'),
                        'topics'            => new xmlrpcval(array(), 'array'),
                    ), 'struct'));
                } else {
                    $response = new xmlrpcresp(new xmlrpcval(array(
                        'result'            => new xmlrpcval(true, 'boolean'),
                        'total_post_num'    => new xmlrpcval(0, 'int'),
                        'posts'             => new xmlrpcval(array(), 'array'),
                    ), 'struct'));
                }
            } else {
                $response = new xmlrpcresp(new xmlrpcval(array(), 'array'));
            }
        }
        else if ($function_file_name == 'thankyoulike' && strpos($error, $lang->tyl_redirect_back))
        {
            $response = new xmlrpcresp(new xmlrpcval(array(
                'result'        => new xmlrpcval(true, 'boolean'),
            ), 'struct'));
        }
        else
        {
            $response = new xmlrpcresp(new xmlrpcval(array(
                'result'        => new xmlrpcval(false, 'boolean'),
                'result_text'   => new xmlrpcval(trim(strip_tags($error)), 'base64'),
            ), 'struct'));
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$response->serialize('UTF-8');
        exit;
    }
}

function tapatalk_redirect($args)
{
    tapatalk_error($args['message']);
}

function tapatalk_global_start()
{
    global $mybb, $request_method, $function_file_name;

    header('Mobiquo_is_login: ' . ($mybb->user['uid'] > 0 ? 'true' : 'false'));

    if ($mybb->usergroup['canview'] != 1 && in_array($request_method, array('get_config', 'login')))
    {
        define("ALLOWABLE_PAGE", 1);
    }

    if (isset($mybb->settings['no_proxy_global']))
    {
        $mybb->settings['no_proxy_global'] = 0;
    }

    if ($function_file_name == 'thankyoulike')
    {
        $mybb->input['my_post_key'] = md5($mybb->user['loginkey'].$mybb->user['salt'].$mybb->user['regdate']);
    }
}

function tapatalk_fetch_wol_activity_end(&$user_activity)
{
    global $uid_list, $aid_list, $pid_list, $tid_list, $fid_list, $ann_list, $eid_list, $plugins, $user, $parameters;
    if($user_activity['activity'] == 'unknown' && strpos($user_activity['location'], 'mobiquo') !== false)
    {
        //$user_activity['activity'] = 'tapatalk';
        $method = 'unknown';
        $path_arr = parse_url($user_activity['location']);
        $param = -2;
        $unviewableforums = get_unviewable_forums();
        if(!empty($path_arr['query']))
        {
            $param_arr = explode('&amp;', $path_arr['query']);
            $method = str_replace('method=', '', $param_arr[0]);
            $param = str_replace('params=', '', $param_arr[1]);
        }
        switch ($method)
        {
            case 'get_config':
            case 'get_forum':
            case 'get_participated_forum':
            case 'login_forum':
            case 'get_forum_status':
            case 'get_topic':
                if(is_numeric($param))
                {
                    $fid_list[] = $param;
                }
                $user_activity['activity'] = "forumdisplay";
                $user_activity['fid'] =  $param;
                break;
            case 'logout_user':
                $user_activity['activity'] = "member_logout";
                break;
            case 'get_user_info':
                $user_activity['activity'] = "member_profile";
                break;
            case 'register':
                $user_activity['activity'] = "member_register";
                break;
            case 'forget_password':
                $user_activity['activity'] = "member_lostpw";
                break;
            case 'login':
                $user_activity['activity'] = "member_login";
                break;
            case 'get_online_users':
                $user_activity['activity'] = "wol";
                break;
            case 'get_user_topic':
            case 'get_user_reply_post':
                $user_activity['activity'] = "usercp";
                break;
            case 'new_topic':
                if(is_numeric($param))
                {
                    $fid_list[] = $param;
                }
                $user_activity['activity'] = "newthread";
                $user_activity['fid'] = $param;
                break;
            case 'search':
            case 'search_topic':
            case 'search_post':
            case 'get_unread_topic':
            case 'get_participated_topic':
            case 'get_latest_topic':
                $user_activity['activity'] = "search";
                break;
            case 'get_quote_post':
            case 'reply_post':
                $user_activity['activity'] = "newreply";
                break;
            case 'get_thread':
                if(is_numeric($param))
                {
                    $tid_list[] = $param;
                }
                $user_activity['activity'] = "showthread";
                $user_activity['tid'] = $param;
                break;
            case 'get_thread_by_post':
                if(is_numeric($param))
                {
                    $pid_list[] = $param;
                    $user_activity['activity'] = "showpost";
                    $user_activity['pid'] = $param;
                }
                break;
            case 'create_message':
            case 'get_box_info':
            case 'get_box':
            case 'get_quote_pm':
            case 'delete_message':
            case 'mark_pm_unread':
                $user_activity['activity'] = "private";
                break;
            case 'get_message':
                $user_activity['activity'] = "private_read";
                break;
            default:
                if(strpos($method, 'm_') === 0)
                {
                    $user_activity['activity'] = "moderation";
                }
                else if(strstr($method,'_post'))
                {
                    $user_activity['activity'] = "showpost";
                }
                else if(strpos($user_activity['location'], 'mobiquo') !== false)
                {
                    $user_activity['activity'] = "index";
                }
                break;
        }

    }
}

function tapatalk_online_user()
{
    global $user;
    
    if(strpos($user['location'], 'mobiquo') !== false)
    {
        $user['username'] = $user['username'] . '[tapatalk_user]';
    }
}

function tapatalk_online_end()
{
    global $online_rows,$mybb;
    $temp_online = $online_rows;
    $str = '&nbsp;<a title="Using Tapatalk" href="http://www.tapatalk.com" target="_blank" ><img src="'.$mybb->settings['bburl'].'/'.$mybb->settings['tapatalk_directory'].'/images/tapatalk-online.png" style="vertical-align:middle"></a>';
    $online_rows = preg_replace('/<a href="(.*)">(.*)\[tapatalk_user\](<\/em><\/strong><\/span>|<\/strong><\/span>|<\/span>|\s*)<\/a>/Usi', '<a href="$1">$2$3</a>'.$str, $online_rows);
    if(empty($online_rows))
    {
        $online_rows = str_replace('[tapatalk_user]','',$temp_online);
    }
}

function tapatalk_pre_output_page(&$page)
{
    global $mybb;

    $url = tapatalk_get_url();
    $icon_url = $mybb->settings['bburl'].'/'.$mybb->settings['tapatalk_directory'].'/images/tapatalk.png';
    $forumname = $mybb->settings['homename'];
    $tapatalk_detect_js_name = 'tapadetect.js';
    $settings = $mybb->settings;
    $str = '<!-- Tapatalk smart banner head start -->
<meta name="apple-itune-app" content="app-id=307880732">
<meta name="google-play-app" content="app-id=com.quoord.tapatalkpro.activity">
<link rel="stylesheet" href="'.$mybb->settings['bburl'].'/'.$mybb->settings['tapatalk_directory'].'/smartbanner/jquery.smartbanner.css" type="text/css" media="screen">
<!-- Tapatalk smart banner head end-->'.
"
<script type='text/javascript'>
        var tapatalk_ipad_msg = '{$settings['tapatalk_ipad_msg']}';
        var tapatalk_ipad_url  = '{$settings['tapatalk_ipad_url']}';
        var tapatalk_iphone_msg = '{$settings['tapatalk_iphone_msg']}';
        var tapatalk_iphone_url  = '{$settings['tapatalk_iphone_url']}';
        var tapatalk_android_msg = '{$settings['tapatalk_android_msg']}';
        var tapatalk_android_url  = '{$settings['tapatalk_android_url']}';
        var tapatalk_kindle_msg = '{$settings['tapatalk_kindle_msg']}';
        var tapatalk_kindle_url  = '{$settings['tapatalk_kindle_url']}';
        var tapatalk_kindle_hd_msg = '{$settings['tapatalk_kindle_hd_msg']}';
        var tapatalk_kindle_hd_url  = '{$settings['tapatalk_kindle_hd_url']}';
        var tapatalkdir = '{$settings['tapatalk_directory']}';
        var tapatalk_smartbanner_enable = '{$settings['tapatalk_smartbanner_notifier']}';
</script>\n";
    $tapatalk_smart_banner_body = '
    <!-- Tapatalk smart banner body start -->
    '."<script type='text/javascript' src='{$mybb->settings['bburl']}/{$mybb->settings['tapatalk_directory']}/tapatalkdetect/jquery-1.7.min.js'></script>\n".
    '<script type=\'text/javascript\'>jQuery.noConflict();</script>
    <script src="mobiquo/smartbanner/jquery.smartbanner.js"></script>
    <script type="text/javascript">
    if(navigator.userAgent.match(/Android/i) && !navigator.userAgent.match(/mobile/i) && tapatalk_smartbanner_enable == "1")
    {
      jQuery.smartbanner({
        title: "Tapatalk HD for Android Tablet",
        author: "'.$forumname.' is now on Tapatalk Forum App",
        icon: "'.$icon_url.'",
        url: "'.$url.'",
        iconGloss: 0,
        daysHidden: 90,
        daysReminder: 0,
        force:"android",
      });
    }
    </script>
    <!-- Tapatalk smart banner body end --> ';
    $page = str_ireplace("</head>", $str . "\n<script type='text/javascript' src='{$mybb->settings['bburl']}/{$mybb->settings['tapatalk_directory']}/{$tapatalk_detect_js_name}'></script></head>", $page);
    $page = str_ireplace("<body>", "<body>\n".$tapatalk_smart_banner_body, $page);
}

function tapatalk_get_url()
{
    global $mybb;
    $location = get_current_location();
    $split_loc = explode(".php", $location);
    $parameters = $param_arr = array();
    if($split_loc[0] == $location)
    {
        $filename = '';
    }
    else
    {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    if($split_loc[1])
    {
        $temp = explode("&amp;", my_substr($split_loc[1], 1));
        foreach($temp as $param)
        {
            $temp2 = explode("=", $param, 2);
            $parameters[$temp2[0]] = $temp2[1];
        }
    }
    switch ($filename)
    {
        case "forumdisplay":
            $param_arr['fid'] = $parameters['fid'];
            $param_arr['location'] = 'forum';
            break;
        case "index":
        case '':
            $param_arr['location'] = 'index';
            break;
        case "private":
            if($parameters['action'] == "read")
            {
                $param_arr['location'] = 'message';
                $param_arr['mid'] = $parameters['pmid'];
            }
            break;
        case "search":
            $param_arr['location'] = "search";
            break;
        case "showthread":
            if(!empty($parameters['pid']))
            {
                //$param_arr['fid'] = $parameters['fid'];
                $param_arr['location'] = 'post';
                $param_arr['tid'] = $parameters['tid'];
                $param_arr['pid'] = $parameters['pid'];
            }
            else
            {
                //$param_arr['fid'] = $parameters['fid'];
                $param_arr['location'] = 'topic';
                $param_arr['tid'] = $parameters['tid'];
            }
            break;
        case "member":
            if($parameters['action'] == "login" || $parameters['action'] == "do_login")
            {
                $param_arr['location'] = 'login';
            }
            elseif($parameters['action'] == "profile")
            {
                $param_arr['location'] = 'profile';
                $param_arr['uid'] = $parameters['uid'];
            }

            break;
        case "online":
            $param_arr['location'] = 'online';
            break;
        default:
            $param_arr['location'] = 'index';
            break;
    }
    $queryString = http_build_query($param_arr);
    $url = $mybb->settings['bburl'] . '/?' .$queryString;
    $url = preg_replace('/^(http|https)/isU', 'tapatalk', $url);
    return $url;
}

// push related functions
function tapatalk_push_reply()
{
    global $mybb, $db, $tid, $pid, $visible, $thread;
    if(!($tid && $pid && $visible == 1 && $db->table_exists('tapatalk_users')) )
    {
        return false;
    }
    $query = $db->query("
        SELECT ts.uid,tu.subscribe as sub
        FROM ".TABLE_PREFIX."threadsubscriptions ts
        RIGHT JOIN ".TABLE_PREFIX."tapatalk_users tu ON (ts.uid=tu.userid)
        WHERE ts.tid = '$tid'
    ");

    $ttp_push_data = array();
    while($user = $db->fetch_array($query))
    {
        if ($user['uid'] == $mybb->user['uid']) continue;
        $ttp_data[] = array(
            'userid'    => $user['uid'],
            'type'      => 'sub',
            'id'        => $tid,
            'subid'     => $pid,
            'title'     => tt_push_clean($thread['subject']),
            'author'    => tt_push_clean($mybb->user['username']),
            'dateline'  => TIME_NOW,
        );
        tt_insert_push_data($ttp_data[count($ttp_data)-1]);
        if($user['sub'] == 1)
        {
            $ttp_push_data[] = $ttp_data[count($ttp_data)-1];
        }
    }
    
    if(!empty($ttp_push_data) && $mybb->settings['tapatalk_push'])
    {
        $ttp_post_data = array(
            'url'  => $mybb->settings['bburl'],
            'data' => base64_encode(serialize($ttp_push_data)),
        );

        $return_status = tt_do_post_request($ttp_post_data);
        return true;
    }
    return false;
}

function tapatalk_push_quote()
{
    global $mybb, $db, $tid, $pid, $visible, $thread ,$post,$thread_info,$new_thread;
    
    if(!empty($new_thread))
    {
        $pid = $thread_info['pid'];
        $thread = $new_thread;
        $post = $new_thread;
    }
    
    if(!($tid && $pid && $visible == 1 && $db->table_exists('tapatalk_users')) )
    {
        return false;
    }
    
    if(!empty($post['message']))
    {
        $matches = array();
        preg_match_all('/\[quote=\'(.*)\' pid=\'(.*)\' dateline=\'(.*)\'\]/', $post['message'] , $matches);
        $matches = array_unique($matches[1]);
        foreach ($matches as $username)
        {
            $query = $db->query("SELECT tu.*,u.uid FROM " . TABLE_PREFIX . "tapatalk_users AS tu LEFT JOIN
            " . TABLE_PREFIX ."users AS u ON tu.userid = u.uid  WHERE u.username = '$username'");
            $user = $db->fetch_array($query);
            if(empty($user) || !tapatalk_double_push_check($user['uid'],$pid))
            {
                return false;
            }
            if ($user['uid'] == $mybb->user['uid']) continue;
            $ttp_push_data = array();
            $ttp_data[] = array(
                'userid'    => $user['uid'],
                'type'      => 'quote',
                'id'        => $tid,
                'subid'     => $pid,
                'title'     => tt_push_clean($thread['subject']),
                'author'    => tt_push_clean($mybb->user['username']),
                'dateline'  => TIME_NOW,
            );
            tt_insert_push_data($ttp_data[count($ttp_data)-1]);
            if($user['quote'] == 1)
            {
                $ttp_push_data[] = $ttp_data[count($ttp_data)-1];
            }
        }
        
        if(!empty($ttp_push_data) && $mybb->settings['tapatalk_push'])
        {
            $ttp_post_data = array(
                'url'  => $mybb->settings['bburl'],
                'data' => base64_encode(serialize($ttp_push_data)),
            );

            $return_status = tt_do_post_request($ttp_post_data);
            return true;
        }
    }
    return false;
}

function tapatalk_push_tag()
{
    global $mybb, $db, $tid, $pid, $visible, $thread ,$post,$thread_info,$new_thread;
    if(!empty($new_thread))
    {
        $pid = $thread_info['pid'];
        $thread = $new_thread;
        $post = $new_thread;
    }
    if(!($tid && $pid && $visible == 1 && $db->table_exists('tapatalk_users')) )
    {
        return false;
    }
    if(!empty($post['message']))
    {
        $matches = tt_get_tag_list($post['message']);
        foreach ($matches as $username)
        {
            $query = $db->query("SELECT tu.*,u.uid FROM " . TABLE_PREFIX . "tapatalk_users AS tu LEFT JOIN
            " . TABLE_PREFIX ."users AS u ON tu.userid = u.uid  WHERE u.username = '$username'");
            $user = $db->fetch_array($query);
            if(empty($user) || !tapatalk_double_push_check($user['uid'],$pid))
            {
                return false;
            }
            if ($user['uid'] == $mybb->user['uid']) continue;
            $ttp_push_data = array();
            $ttp_data[] = array(
                'userid'    => $user['uid'],
                'type'      => 'tag',
                'id'        => $tid,
                'subid'     => $pid,
                'title'     => tt_push_clean($thread['subject']),
                'author'    => tt_push_clean($mybb->user['username']),
                'dateline'  => TIME_NOW,
            );
            tt_insert_push_data($ttp_data[count($ttp_data)-1]);
            if($user['quote'] == 1)
            {
                $ttp_push_data[] = $ttp_data[count($ttp_data)-1];
            }
        }
        if(!empty($ttp_push_data) && $mybb->settings['tapatalk_push'])
        {
            $ttp_post_data = array(
                'url'  => $mybb->settings['bburl'],
                'data' => base64_encode(serialize($ttp_push_data)),
            );

            $return_status = tt_do_post_request($ttp_post_data);
            return true;
        }

    }
    return false;
}

function tapatalk_push_newtopic()
{
    global $mybb, $db, $tid,$visible, $thread_info,$fid,$new_thread;
    $pid = $thread_info['pid'];
    if(!($tid && $fid && $pid && $visible == 1 && $db->table_exists('tapatalk_users')) )
    {
        return false;
    }
    $query = $db->query("
        SELECT ts.uid,tu.newtopic as sub
        FROM ".TABLE_PREFIX."forumsubscriptions ts
        RIGHT JOIN ".TABLE_PREFIX."tapatalk_users tu ON (ts.uid=tu.userid)
        WHERE ts.fid = '$fid'
    ");

    $ttp_push_data = array();
    while($user = $db->fetch_array($query))
    {
        if ($user['uid'] == $mybb->user['uid']) continue;
        $ttp_data[] = array(
            'userid'    => $user['uid'],
            'type'      => 'newtopic',
            'id'        => $tid,
            'subid'     => $pid,
            'title'     => tt_push_clean($new_thread['subject']),
            'author'    => tt_push_clean($mybb->user['username']),
            'dateline'  => TIME_NOW,
        );
        tt_insert_push_data($ttp_data[count($ttp_data)-1]);
        if($user['newtopic'] == 1)
        {
            $ttp_push_data[] = $ttp_data[count($ttp_data)-1];
        }
    }
    if(!empty($ttp_push_data) && $mybb->settings['tapatalk_push'])
    {
        $ttp_post_data = array(
            'url'  => $mybb->settings['bburl'],
            'data' => base64_encode(serialize($ttp_push_data)),
        );

        $return_status = tt_do_post_request($ttp_post_data);
        return true;
    }
    return false;
}

function tapatalk_double_push_check($userid,$pid)
{
    global $db;
    $query = $db->query("SELECT * FROM " . TABLE_PREFIX ."tapatalk_push_data WHERE user_id = '$userid' AND data_id = '$pid'");
    $row = $db->fetch_array($query);
    if(empty($row))
    {
        return true;
    }
    return false;
}

function tt_get_tag_list($str)
{
    if ( preg_match_all( '/(?<=^@|\s@)(#(.{1,50})#|\S{1,50}(?=[,\.;!\?]|\s|$))/U', $str, $tags ) )
    {
        foreach ($tags[2] as $index => $tag)
        {
            if ($tag) $tags[1][$index] = $tag;
        }

        return array_unique($tags[1]);
    }

    return array();
}

function tapatalk_push_pm()
{
    global $mybb, $db, $pm, $pminfo;
    if(!($pminfo['messagesent'] &&$db->table_exists('tapatalk_users')))
    {
        return false;
    }
    $query = $db->query("
        SELECT p.pmid, p.toid ,tu.pm
        FROM ".TABLE_PREFIX."privatemessages p
        LEFT JOIN ".TABLE_PREFIX."tapatalk_users tu ON (p.toid=tu.userid)
        WHERE p.fromid = '{$mybb->user['uid']}' and p.dateline = " . TIME_NOW . " AND p.folder = 1
    ");

    $ttp_push_data = array();
    while($user = $db->fetch_array($query))
    {
        if ($user['toid'] == $mybb->user['uid']) continue;

        $ttp_data[] = array(
            'userid'    => $user['toid'],
            'type'      => 'pm',
            'id'        => $user['pmid'],
            'title'     => tt_push_clean($pm['subject']),
            'author'    => tt_push_clean($mybb->user['username']),
            'dateline'  => TIME_NOW,
        );
        tt_insert_push_data($ttp_data[count($ttp_data)-1]);
        if($user['pm'] == 1)
        {
            $ttp_push_data[] = $ttp_data[count($ttp_data)-1];
        }
    }
    if(!empty($ttp_push_data) && $mybb->settings['tapatalk_push'])
    {
        $ttp_post_data = array(
            'url'  => $mybb->settings['bburl'],
            'data' => base64_encode(serialize($ttp_push_data)),
        );

        $return_status = tt_do_post_request($ttp_post_data);
    }
}

function tt_do_post_request($data,$pushTest = false)
{
    global $mybb;
    
    if(empty($data['data']) && !isset($data['ip']) && !isset($data['test']))
    {
        return false;
    }
    
    if(!empty($mybb->settings['tapatalk_push_key']) && !$pushTest)
    {
        $data['key'] = $mybb->settings['tapatalk_push_key'];
    }
    
    $push_url = 'http://push.tapatalk.com/push.php';
    $push_host = 'push.tapatalk.com';
    $response = 'CURL is disabled and PHP option "allow_url_fopen" is OFF. You can enable CURL or turn on "allow_url_fopen" in php.ini to fix this problem.';

    if (@ini_get('allow_url_fopen'))
    {
        if(!$pushTest)
        {
            $fp = fsockopen($push_host, 80, $errno, $errstr, 5);

            if(!$fp)
                return false;

            $data =  http_build_query($data,'', '&');
            fputs($fp, "POST /push.php HTTP/1.1\r\n");
            fputs($fp, "Host: $push_host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ". strlen($data) ."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data);
            fclose($fp);
        }
        else
        {
            $params = array('http' => array(
                'method' => 'POST',
                'content' => http_build_query($data, '', '&'),
            ));

            $ctx = stream_context_create($params);
            $timeout = 10;
            $old = ini_set('default_socket_timeout', $timeout);
            $fp = @fopen($push_url, 'rb', false, $ctx);

            if (!$fp) return false;

            ini_set('default_socket_timeout', $old);
            stream_set_timeout($fp, $timeout);
            stream_set_blocking($fp, 0);

            $response = @stream_get_contents($fp);
        }
    }
    
    elseif (function_exists('curl_init'))
    {
        $ch = curl_init($push_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,1);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    return $response;
}

function tt_insert_push_data($data)
{
    global $mybb,$db;
    if(!$db->table_exists("tapatalk_push_data"))
    {
        return ;
    }
    if($data['type'] == 'pm')
    {
        $data['subid'] = $data['id'];
    }
    $sql_data = array(
        'author' => $data['author'],
        'user_id' => $data['userid'],
        'data_type' => $data['type'],
        'title' => $data['title'],
        'data_id' => $data['subid'],
        'create_time' => $data['dateline']
    );
    $db->insert_query('tapatalk_push_data', $sql_data);
}

function tt_push_clean($str)
{
    global $db;
    $str = strip_tags($str);
    $str = trim($str);
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    $str = $db->escape_string($str);
    return $str;
}