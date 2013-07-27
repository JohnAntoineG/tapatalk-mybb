<?php
defined('IN_MOBIQUO') or exit;

function get_recommended_user_func()
{
	global $mybb,$db,$lang,$users;
	// Load global language phrases
	$lang->load("memberlist");
	
	// get pm users 
	$sql = "SELECT u.uid,u.username,u.avatar 
	FROM ".TABLE_PREFIX."privatemessages p 
	LEFT JOIN ".TABLE_PREFIX."users u 
	ON p.toid = u.uid 
	WHERE p.uid = ".$mybb->user['uid']."
	GROUP BY p.toid
	LIMIT 0,1000";	
	get_recommended_user_list($sql,'contact');
	
	//get sub topic users 
	$sql = "SELECT u.uid,u.username,u.avatar
	FROM " . TABLE_PREFIX . "threadsubscriptions ts 
	LEFT JOIN " . TABLE_PREFIX . "threads t ON ts.tid = t.tid 
	LEFT JOIN " . TABLE_PREFIX . "users u ON t.uid = u.uid
	WHERE ts.uid = " . $mybb->user['uid'] . "
	GROUP BY u.uid
	LIMIT 0,1000";
	get_recommended_user_list($sql,'watch');

	//get like or thank users
	$prefix = "g33k_thankyoulike_";
	if(file_exists('thankyoulike.php') && $db->table_exists($prefix.'thankyoulike'))
	{
		$sql = "SELECT u.uid,u.username,u.avatar
		FROM " . TABLE_PREFIX . $prefix .  "thankyoulike thl 
		LEFT JOIN " . TABLE_PREFIX . "users u ON thl.puid = u.uid
		WHERE thl.uid = ".$mybb->user['uid']."
		GROUP BY thl.puid
		LIMIT 0,100";

		get_recommended_user_list($sql,'like');
		
		$sql = "SELECT u.uid,u.username,u.avatar
		FROM " . TABLE_PREFIX . $prefix .  "thankyoulike thl 
		LEFT JOIN " . TABLE_PREFIX . "users u ON thl.uid = u.uid
		WHERE thl.puid = ".$mybb->user['uid']."
		GROUP BY thl.uid
		LIMIT 0,100";

		get_recommended_user_list($sql,'liked');
	}
	
	$total = count($users);
    
    $page =  intval($_POST['page']);
    $perpage = intval($_POST['perpage']);
    $start = ($page-1) * $perpage;
    $return_user_lists = array();
    $users = array_slice($users, $start,$perpage);
    if(!empty($users))
    {
        foreach ($users as $user)
        {
            $return_user_lists[] = new xmlrpcval(array(
                'username'     => new xmlrpcval(basic_clean($user['username']), 'base64'),
                'user_id'       => new xmlrpcval($user['uid'], 'string'),
                'icon_url'      => new xmlrpcval($user['avatar'],'string'),
                'type'          => new xmlrpcval('', 'string'),
            ), 'struct');
        }
    }
    $suggested_users = new xmlrpcval(array(
        'total' => new xmlrpcval($total, 'int'),
        'list'         => new xmlrpcval($return_user_lists, 'array'),
    ), 'struct');

    return new xmlrpcresp($suggested_users);
}

function get_recommended_user_list($sql,$type)
{
	global $db,$users,$mybb;
	$query = $db->query($sql);
	while($user = $db->fetch_array($query))
	{
		//$user = $plugins->run_hooks("memberlist_user", $user);
		
		if(!$user['username'])
		{
			continue;
		}
		$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);		
		if($user['avatar'] != '')
		{
			$user['avatar'] = absolute_url($user['avatar']);
		}
		else
		{
			$user['avatar'] = "";
		}
		if($user['uid'] == $mybb->user['uid'])
		{
			continue;
		}		
		if(!in_array($user, $users))
		{
			$users[] = $user;
		}		
	}
}

