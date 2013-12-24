<?php

include_once $include_prefix.'lib/season.functions.php';
include_once $include_prefix.'lib/team.functions.php';
include_once $include_prefix.'lib/reservation.functions.php';
include_once $include_prefix.'lib/logging.functions.php';
include_once $include_prefix.'lib/common.functions.php';

$events = array("won" => _('and his team $team just won $opponent by $teamscore to $opponentscore. Huzzah!'),
				"lost" => _('and his team $team just lost to $opponent by $opponentscore to $teamscore. Bummer :('),
				"passed" => _('just passed point $teamscore for his team $team against $opponent. The game is now $teamscore to $opponentscore. The goal was caught by: $scorername.'),
				"scored" => _('just scored point $teamscore for his team $team against $opponent. The game is now $teamscore to $opponentscore. The goal was passed by: $passername.'));

$eventTranslations = array("won" => _('game won'),
				"lost" => _('game lost'),
				"passed" => _('game passes'),
				"scored" => _('game scores'));
if (version_compare(PHP_VERSION, '5.0.0', '>')) {
$CURL_OPTS = array(
    "CURLOPT_CONNECTTIMEOUT" => 10,
    "CURLOPT_RETURNTRANSFER" => true,
    "CURLOPT_TIMEOUT" => 60,
    "CURLOPT_USERAGENT" => 'ultiorganizer-php-1.0'
  );
}

function FBCookie($app_id, $application_secret) {
  $args = array();
  parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
  ksort($args);
  $payload = '';
  foreach ($args as $key => $value) {
    if ($key != 'sig') {
      $payload .= $key . '=' . $value;
    }
  }
  if (md5($payload . $application_secret) != $args['sig']) {
    return null;
  }
  return $args;
}

function ExistingFBUserId($fb_uid) {
	$query = sprintf("SELECT userid FROM uo_userproperties WHERE name='facebookuid' AND value='%s'",
		mysql_real_escape_string($fb_uid));
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	if ($row = mysql_fetch_assoc($result)) {
		return $row['userid'];
	} else {
		return false;
	}
}

function FBLoggedIn($fb_cookie, $fb_data) {
	return (isset($fb_cookie['uid']) &&	isset($fb_data['facebookuid']) && $fb_cookie['uid'] == $fb_data['facebookuid']);
}

function ReMapFBUserId($fb_cookie,$userid) {
	if ($_SESSION['uid'] == $userid) {
		UnMapFBUserId($userid);
		
		$existinguid = ExistingFBUserId($fb_cookie['uid']);
		while ($existinguid) {
			$query = sprintf("DELETE FROM uo_userproperties WHERE name LIKE 'facebook%%' AND userid='%s'",
				 	mysql_real_escape_string($existinguid));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }	 	
			$existinguid = ExistingFBUserId($fb_cookie['uid']);
		}
		
		$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
			VALUES ('%s', 'facebookuid', '%s')",
		 	mysql_real_escape_string($userid),
		 	mysql_real_escape_string($fb_cookie['uid']));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		UpdateFBAccessToken($userid, $fb_cookie['access_token']);
	} else { die ('User can only link facebook accounts for himself'); }
}

function UnMapFBUserId($userid) {
	if ($_SESSION['uid'] == $userid) {
		$query = sprintf("DELETE FROM uo_userproperties WHERE userid='%s' AND name LIKE 'facebook%%'",
			 	mysql_real_escape_string($userid));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }	 	
	} else { die ('User can only link facebook accounts for himself'); }
}

function MapFBUserId($fb_cookie) {
	$existingUid = ExistingFBUserId($fb_cookie['uid']);
	if ($existingUid) {
		UpdateFBAccessToken($existingUid, $fb_cookie['access_token']);
		return $existingUid;
	} else {
    	// See if the is an existing user to map
		$user = json_decode(file_get_contents('https://graph.facebook.com/me?access_token=' .
    		$fb_cookie['access_token']));
    	//print_r($user);
    	$query = sprintf("SELECT userid FROM uo_users WHERE LOWER(email)='%s' UNION ALL
    		SELECT userid FROM uo_extraemail WHERE LOWER(email)='%s'",
    		mysql_real_escape_string($user->email),
    		mysql_real_escape_string($user->email));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		if ($row = mysql_fetch_assoc($result)) {
			$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
				VALUES ('%s', 'facebookuid', '%s')",
			 	mysql_real_escape_string($row['userid']),
			 	mysql_real_escape_string($user->id));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
			UpdateFBAccessToken($row['userid'], $fb_cookie['access_token']);
			return $row['userid'];
		} else {
			// Create user
			$userid = CreateNewUsername($user->first_name, $user->last_name, $user->email);
			$password = CreateRandomPassword();
			$query = sprintf("INSERT INTO uo_users (name, userid, password, email) VALUES ('%s', '%s', '%s', '%s')",
				mysql_real_escape_string($user->name),
				mysql_real_escape_string($userid),
				mysql_real_escape_string($password),
				mysql_real_escape_string($user->email));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
			$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
				VALUES ('%s', 'facebookuid', '%s')",
			 	mysql_real_escape_string($userid),
			 	mysql_real_escape_string($user->id));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
			FinalizeNewUser($userid, $user->email);
			UpdateFBAccessToken($userid, $fb_cookie['access_token']);
			return $userid;
		}
		return false;
	}
}

function UpdateFBAccessToken($userid, $token) {
	$query = sprintf("SELECT prop_id FROM uo_userproperties WHERE userid='%s' AND name='facebooktoken'",
		mysql_real_escape_string($userid));
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	if ($row = mysql_fetch_row($result)) {
		$query = sprintf("UPDATE uo_userproperties SET value='%s' WHERE prop_id=%d",
		 	mysql_real_escape_string($token),
			(int)$row[0]);
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
	} else {
		$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
			VALUES ('%s', 'facebooktoken', '%s')",
		 	mysql_real_escape_string($userid),
		 	mysql_real_escape_string($token));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
	}
}

function LinkFBPlayer($userid, $playerid, $selectedevents) {
	if ($_SESSION['uid'] == $userid && isPlayerAdmin($playerid)) {
		$value= $playerid;
		$query = sprintf("SELECT prop_id FROM uo_userproperties WHERE userid='%s' AND name='facebookplayer' AND value LIKE '%s%%'",
			mysql_real_escape_string($userid), mysql_real_escape_string($value));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		
		$events = implode(":", $selectedevents);
		if (strlen($events)>0) {
			$value .= ":".$events;
		}
		
		if ($row = mysql_fetch_row($result)) {
			$query = sprintf("UPDATE uo_userproperties SET value='%s' WHERE prop_id=%d",
				mysql_real_escape_string($value),(int)$row[0]);
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
			return;
		} else {
			$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
				VALUES ('%s', 'facebookplayer', '%s')",
			 	mysql_real_escape_string($userid),
			 	mysql_real_escape_string($value));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
		}	
	} else { die ('User can only link facebook accounts for himself'); }
}

function UnLinkFBPlayer($userid, $playerid) {
	if ($_SESSION['uid'] == $userid && isPlayerAdmin($playerid)) {
		$query = sprintf("DELETE FROM uo_userproperties WHERE userid='%s' AND name='facebookplayer' AND value LIKE '%s%%'",
			mysql_real_escape_string($userid), mysql_real_escape_string($playerid));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		$query = sprintf("DELETE FROM uo_userproperties WHERE userid='%s' AND name LIKE 'facebookmessage%%%s'",
			mysql_real_escape_string($userid), mysql_real_escape_string($playerid));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
	} else { die ('User can only link facebook accounts for himself'); }
}

function getFacebookUserProperties($userid) {
	global $events;
	$ret = array();
	$query = sprintf("SELECT name, value FROM uo_userproperties WHERE userid='%s' and name LIKE 'facebook%%'",
		mysql_real_escape_string($userid));
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	while ($property = mysql_fetch_assoc($result)) {
		if ($property['name'] == 'facebookplayer') {
			if (!isset($ret['facebookplayer'])) {
				$ret['facebookplayer'] = array();
			}
			$playerarr = explode(":", $property['value']);
			$playerid = $playerarr[0];
			if(!isset($ret['facebookplayer'][$playerid])) {
				$ret['facebookplayer'][$playerid] = array();
			}
			if (count($playerarr) > 1) {
				$ret['facebookplayer'][$playerid] = array_merge($ret['facebookplayer'][$playerid],array_slice(array_flip($playerarr), 1));
			}
		} elseif (strpos($property['name'], "facebookmessage")===0) {
			foreach ($events as $event => $message) {
				if (strpos($property['name'], "facebookmessage".$event)===0) {
					$playerid = substr($property['name'], strlen("facebookmessage".$event));
					if (!isset($ret['facebookplayer'])) {
						$ret['facebookplayer'] = array();
					}
					if(!isset($ret['facebookplayer'][$playerid])) {
						$ret['facebookplayer'][$playerid] = array();
					}
					$ret['facebookplayer'][$playerid][$event."message"] = $property['value'];
				}
			}
		} else {
			$ret[$property['name']] = $property['value'];
		}
	}
	foreach ($ret['facebookplayer'] as $playerid => $playerSettings) {
		foreach ($events as $event => $message) {
			if (!isset($playerSettings[$event."message"])) {
				$ret['facebookplayer'][$playerid][$event."message"] = $message;
			}
			if (!isset($playerSettings[$event])) {
				$ret['facebookplayer'][$playerid][$event] = 0;
			}
		}
	}
	return $ret;
}
function GetFacebookAppToken($page) {
	$ch = curl_init();
	global $serverConf;
	global $fb_cookie;
	$url = "https://graph.facebook.com/";
	$url .= $fb_cookie['uid'];
	$url .= "/accounts?access_token=";
	$url .= $fb_cookie['access_token'];
	$token = json_decode(file_get_contents($url));
	foreach ($token->data as $next) {
		if ($next->id == "$page") {
			return $next->access_token;
		}
	}
}

function FacebookFeedPost($fb_params, $params) {
	$url = 'https://graph.facebook.com/'.$fb_params['facebookuid'].'/links';
	$params['access_token'] = $fb_params['facebooktoken'];
    $ch = curl_init();
 	
    global $CURL_OPTS;
 	
    $opts = $CURL_OPTS;
    
    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
    $opts[CURLOPT_URL] = $url;

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function SetFacebookPublishing($userid, $playerid, $pubEvents, $pubMessages) {
	if ($_SESSION['uid'] == $userid && isPlayerAdmin($playerid)) {
		$query = sprintf("SELECT prop_id FROM uo_userproperties WHERE userid='%s' AND name='facebookplayer' AND VALUE LIKE '%s%%'",
			mysql_real_escape_string($userid),
			mysql_real_escape_string($playerid));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		$value = $playerid.":".implode($pubEvents, ":");
		if ($row = mysql_fetch_row($result)) {
			$query = sprintf("UPDATE uo_userproperties SET value='%s' WHERE prop_id=%d",
			 	mysql_real_escape_string($value),
				(int)$row[0]);
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
		} else {
			$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
				VALUES ('%s', 'facebookplayer', '%s')",
			 	mysql_real_escape_string($userid),
			 	mysql_real_escape_string($value));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
		}
		global $events;
		foreach ($events as $event => $message) {
			if (isset($pubMessages[$event])) {
				SetFacebookPublishingMessage($userid, $playerid, $event, $pubMessages[$event]);				
			}
		}
	} else { die ('User can only manage facebook options for himself'); }	
}

function SetFacebookPublishingMessage($userid, $playerid, $event, $message) {
	if ($_SESSION['uid'] == $userid && isPlayerAdmin($playerid)) {
		$query = sprintf("SELECT prop_id FROM uo_userproperties WHERE userid='%s' AND name='facebookmessage%s%s'",
			mysql_real_escape_string($userid),
			mysql_real_escape_string($event),
			mysql_real_escape_string($playerid));
		$result = mysql_query($query);
		if (!$result) { die('Invalid query: ' . mysql_error()); }
		if ($row = mysql_fetch_row($result)) {
			$query = sprintf("UPDATE uo_userproperties SET value='%s' WHERE prop_id=%d",
			 	mysql_real_escape_string($message),
				(int)$row[0]);
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
		} else {
			$query = sprintf("INSERT INTO uo_userproperties (userid, name, value) 
				VALUES ('%s', 'facebookmessage%s%s', '%s')",
			 	mysql_real_escape_string($userid),
			 	mysql_real_escape_string($event),
			 	mysql_real_escape_string($playerid),
			 	mysql_real_escape_string($message));
			$result = mysql_query($query);
			if (!$result) { die('Invalid query: ' . mysql_error()); }
		}
	} else { die ('User can only manage facebook options for himself'); }	
}


function GetGameFacebookUsers($teamId, $event) {
	$query = sprintf("SELECT userid FROM uo_userproperties WHERE name='facebookplayer' AND value LIKE '%%:%s%%' AND SUBSTRING_INDEX(value, ':', 1) IN (SELECT profile_id FROM uo_player WHERE team=%d AND accredited=1)",
		mysql_real_escape_string($event),
		(int)$teamId);	
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	$ret = array();
	while ($row = mysql_fetch_row($result)) {
		$ret[] = $row[0];
	}
	return $ret;
}

function GetScoreFacebookUsers($passer, $scorer) {
	$query = sprintf("SELECT userid, SUBSTRING_INDEX(value, ':', 1) AS profile_id FROM uo_userproperties WHERE name='facebookplayer' AND (value LIKE '%s:%%passed%%' OR value LIKE '%s:%%scored%%')",
		mysql_real_escape_string($passer),
		mysql_real_escape_string($scorer));	
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	$ret = array();
	while ($row = mysql_fetch_assoc($result)) {
		$ret[$row['profile_id']] = $row['userid'];
	}
	return $ret;
}

function TriggerFacebookEvent($gameId, $event, $num) {
	$url = GetURLBase()."/ext/facebookevent.php?game=".intval($gameId)."&event=".$event;
	if ($event == "goal") {
		$url .= "&num=".intval($num);
	}
	$ch = curl_init();
 	
    global $CURL_OPTS;
 	
    $opts = $CURL_OPTS;
    
    $opts[CURLOPT_URL] = $url;

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function FBUnauthorizeApp() {
	if (isSuperAdmin()) {
		$query = "DELETE FROM uo_setting WHERE name='FacebookUpdateToken'";
		$result = mysql_query($query);	
		if (!$result) { die('Invalid query: ' . mysql_error()); }
	} else { die('Insufficient rights to configure server'); }
}
	
?>
