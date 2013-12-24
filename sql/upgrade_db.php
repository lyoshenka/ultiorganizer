<?php
include_once $include_prefix.'lib/common.functions.php';
include_once $include_prefix.'lib/image.functions.php';

function upgrade46() {
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookEnabled", "false")');
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookAppId", "")');
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookAppKey", "")');
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookAppSecret", "")');
}

function upgrade47() {
	addColumn('uo_reservation', 'season', 'varchar(10) default NULL');
		
	$results = runQuery("SELECT DISTINCT pr.id, ser.season
			FROM uo_reservation pr
			LEFT JOIN uo_game pp ON (pp.reservation=pr.id)
			LEFT JOIN uo_pool ps ON (pp.pool=ps.pool_id)
			LEFT JOIN uo_series ser ON (ps.series=ser.series_id)
			LEFT JOIN uo_location pl ON (pr.location=pl.id)");

	while($row = mysql_fetch_assoc($results)){
		runQuery("UPDATE uo_reservation SET season='".$row['season']."'
			WHERE id='".$row['id']."'");
	}
	
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("GameRSSEnabled", "false")');
}

function upgrade48() {
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookGameMessage", "Game finished in pool $pool")');
}

function upgrade49() {
	addColumn('uo_season', 'timezone', 'varchar(50) default NULL');
}

function upgrade50() {
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("FacebookUpdatePage", "")');
}

function upgrade51() {
	addColumn('uo_urls', 'ordering', "varchar(2) default ''");
}

function upgrade52() {
	addColumn('uo_pool', 'forfeitscore', 'int(10) DEFAULT NULL');
	addColumn('uo_pool', 'forfeitagainst', 'int(10) DEFAULT NULL');
	addColumn('uo_pooltemplate', 'forfeitscore', 'int(10) DEFAULT NULL');
	addColumn('uo_pooltemplate', 'forfeitagainst', 'int(10) DEFAULT NULL');
}

function upgrade53() {
	if(!hasTable("uo_sms")){
		runQuery("CREATE TABLE `uo_sms` (
		`sms_id` INT(10) NOT NULL AUTO_INCREMENT,
		`to1` INT(15) NOT NULL,
		`to2` INT(15) NULL DEFAULT NULL,
		`to3` INT(15) NULL DEFAULT NULL,
		`to4` INT(15) NULL DEFAULT NULL,
		`to5` INT(15) NULL DEFAULT NULL,
		`msg` VARCHAR(400) NULL DEFAULT NULL,
		`created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`click_id` INT(10) NULL DEFAULT NULL,
		`sent` DATETIME NULL DEFAULT NULL,
		`delivered` DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (`sms_id`)
		)
		COLLATE='latin1_swedish_ci'
		ENGINE=MyISAM
		ROW_FORMAT=DEFAULT
		AUTO_INCREMENT=1000
		");
	}
}
function upgrade54() {

	if(hasTable("pelik_jasenet") && !hasTable("uo_license")){
	  dropField("pelik_jasenet", "joukkue");
	  dropField("pelik_jasenet", "email");
	  dropField("pelik_jasenet", "uusi");
	  renameTable("pelik_jasenet", "uo_license");
	  renameField("uo_license", "sukunimi", "lastname");
	  renameField("uo_license", "etunimi", "firstname");
	  renameField("uo_license", "jasenmaksu", "membership");   
	  renameField("uo_license", "ultimate_lisenssi", "license");
	  renameField("uo_license", "syntaika", "birthdate");
	  renameField("uo_license", "nainen", "women");
	  renameField("uo_license", "junnu", "junior");
	  renameField("uo_license", "jasennumero", "accreditation_id");
	  runQuery("ALTER TABLE uo_license MODIFY accreditation_id varchar(150)");
	  runQuery("ALTER TABLE uo_license MODIFY ultimate tinyint(1) DEFAULT NULL");
	  runQuery("ALTER TABLE uo_license MODIFY women tinyint(1) DEFAULT NULL");
	  runQuery("ALTER TABLE uo_license MODIFY junior tinyint(1) DEFAULT NULL");
	  runQuery("ALTER TABLE uo_license MODIFY membership smallint(5) DEFAULT NULL");
	  runQuery("ALTER TABLE uo_license MODIFY license smallint(5) DEFAULT NULL");
	  addColumn('uo_license', 'external_id', 'int(10) DEFAULT NULL');
	  addColumn('uo_license', 'external_type', 'int(10) DEFAULT NULL');
	  addColumn('uo_license', 'external_validity', 'int(10) DEFAULT NULL');
	}elseif(!hasTable("uo_license")){
		runQuery("CREATE TABLE `uo_license` (
		  `lastname` varchar(255) DEFAULT NULL,
		  `firstname` varchar(255) DEFAULT NULL,
		  `membership` smallint(5) DEFAULT NULL,
		  `birthdate` datetime DEFAULT NULL,
		  `accreditation_id` varchar(150) DEFAULT NULL,
		  `ultimate` tinyint(1) DEFAULT NULL,
		  `women` tinyint(1) DEFAULT NULL,
		  `junior` tinyint(1) DEFAULT NULL,
		  `license` smallint(5) DEFAULT NULL,
		  `external_id` int(10) DEFAULT NULL,
		  `external_type` int(10) DEFAULT NULL,
		  `external_validity` int(10) DEFAULT NULL,
		  KEY `etunimi` (`lastname`),
		  KEY `sukunimi` (`firstname`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	
	}
}

function upgrade55() {
	if(!hasColumn('uo_pool', 'follower')){
		addColumn('uo_pool', 'follower', "int(10) DEFAULT NULL");
	}
}

function upgrade56() {
	if(!hasColumn('uo_player_profile', 'email')){
		addColumn('uo_player_profile', 'email', "varchar(100) DEFAULT NULL");
		
		$results = runQuery("SELECT accreditation_id, email FROM uo_player WHERE email IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET email='%s' WHERE accreditation_id='%s'",
			  $row['email'],
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	    runQuery("alter table uo_player drop column email");
	}
}

function upgrade57() {
	if(!hasTable("uo_specialranking")){
		runQuery("CREATE TABLE `uo_specialranking` (
		  `frompool` int(10) NOT NULL,
		  `fromplacing` int(5) NOT NULL,
		  `torank` int(5) NOT NULL,
		  `scheduling_id` int(10) DEFAULT NULL,
		  PRIMARY KEY (`frompool`,`fromplacing`),
		  KEY `idx_scheduling_id` (`scheduling_id`)
		)
		ENGINE=MyISAM
		CHARSET=utf8
		ROW_FORMAT=DEFAULT");
	}	
}

function upgrade58() {
	if(!hasColumn('uo_player_profile', 'firstname')){
		addColumn('uo_player_profile', 'firstname', "varchar(40) DEFAULT NULL");
		
		//name from uo_player
		$results = runQuery("SELECT accreditation_id, firstname FROM uo_player WHERE firstname IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET firstname='%s' WHERE accreditation_id='%s'",
			  mysql_real_escape_string(trim($row['firstname'])),
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	    
	    //if uo_license has name use the one from there.
		$results = runQuery("SELECT accreditation_id, firstname FROM uo_license WHERE firstname IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET firstname='%s' WHERE accreditation_id='%s'",
			  mysql_real_escape_string(trim($row['firstname'])),
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	}
	if(!hasColumn('uo_player_profile', 'lastname')){
		addColumn('uo_player_profile', 'lastname', "varchar(40) DEFAULT NULL");
		
		//name from uo_player
		$results = runQuery("SELECT accreditation_id, lastname FROM uo_player WHERE lastname IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET lastname='%s' WHERE accreditation_id='%s'",
			  mysql_real_escape_string(trim($row['lastname'])),
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	    
	    //if uo_license has name use the one from there.
		$results = runQuery("SELECT accreditation_id, lastname FROM uo_license WHERE lastname IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET lastname='%s' WHERE accreditation_id='%s'",
			  mysql_real_escape_string(trim($row['lastname'])),
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	}
	if(!hasColumn('uo_player_profile', 'num')){
		addColumn('uo_player_profile', 'num', "tinyint(3) DEFAULT NULL");
		
		//num from uo_player
		$results = runQuery("SELECT accreditation_id, num FROM uo_player WHERE num IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_player_profile SET num='%s' WHERE accreditation_id='%s'",
			  trim($row['num']),
			  $row['accreditation_id']);
            runQuery($query);			  
	    }
	}	
	if(!hasColumn('uo_player_profile', 'profile_id')){
		addColumn('uo_player_profile', 'profile_id', "int(10) NOT NULL");
		
		runQuery("UPDATE uo_player_profile SET profile_id=accreditation_id");
        runQuery("ALTER TABLE uo_player_profile DROP PRIMARY KEY");
        runQuery("ALTER TABLE uo_player_profile MODIFY accreditation_id VARCHAR(50)");
        runQuery("ALTER TABLE uo_player_profile AUTO_INCREMENT=100000");
        runQuery("ALTER TABLE uo_player_profile change profile_id profile_id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY");
                
        addColumn('uo_player', 'profile_id', "int(10)");
        runQuery("UPDATE uo_player SET profile_id=accreditation_id");
        
        runQuery("ALTER TABLE uo_player_stats change accreditation_id profile_id int(10) NOT NULL");
        //runQuery("alter table uo_player drop column accreditation_id");
	}	
}

function upgrade59() {
	if(!hasColumn('uo_reservation', 'timeslots')){
		addColumn('uo_reservation', 'timeslots', "varchar(100) DEFAULT NULL");
	}
    if(!hasColumn('uo_reservation', 'date')){
		addColumn('uo_reservation', 'date', "datetime DEFAULT NULL");
        $results = runQuery("SELECT * FROM uo_reservation WHERE starttime IS NOT NULL");
	    while($row = mysql_fetch_assoc($results)){
	        $query = sprintf("UPDATE uo_reservation SET date='%s' WHERE id='%s'",
			  ToInternalTimeFormat(ShortDate($row['starttime'])),
			  $row['id']);
            runQuery($query);			  
	    }
	}
}

function upgrade60() {
  
  $dprofiles = runQuery("SELECT * FROM uo_player_profile WHERE accreditation_id!=profile_id");
  while($profile = mysql_fetch_assoc($dprofiles)){
    runQuery("DELETE FROM uo_player_profile WHERE accreditation_id='".$profile['accreditation_id']."'");
  }
  
  $licenses = runQuery("SELECT * FROM uo_license");
  while($license = mysql_fetch_assoc($licenses)){
    
    $hasprofile = runQuery("SELECT * FROM uo_player_profile WHERE accreditation_id='".$license['accreditation_id']."'");
    
    if(mysql_num_rows($hasprofile)==0){
        $query = sprintf("INSERT INTO uo_player_profile (profile_id,firstname,lastname,birthdate,accreditation_id) VALUES
				('%s','%s','%s','%s','%s')",
        mysql_real_escape_string($license['accreditation_id']),
        mysql_real_escape_string($license['firstname']),
        mysql_real_escape_string($license['lastname']),
        mysql_real_escape_string($license['birthdate']),
        mysql_real_escape_string($license['accreditation_id']));
      $profileId = DBQueryInsert($query);
    }
  }
  
  $players = runQuery("SELECT * FROM uo_player GROUP BY profile_id");
  while($player = mysql_fetch_assoc($players)){
    
    $hasprofile = runQuery("SELECT * FROM uo_player_profile WHERE profile_id='".$player['profile_id']."'");
   
    if(mysql_num_rows($hasprofile)==0){
        $query = sprintf("INSERT INTO uo_player_profile (profile_id,firstname,lastname,num) VALUES
				('%s','%s','%s','%s')",
        mysql_real_escape_string($player['profile_id']),
        mysql_real_escape_string($player['firstname']),
        mysql_real_escape_string($player['lastname']),
        mysql_real_escape_string($player['num']));
      $profileId = DBQueryInsert($query);
    }
  }
  
}

function upgrade61() {
  if(!hasColumn('uo_player_profile', 'ffindr_id')){
		addColumn('uo_player_profile', 'ffindr_id', "int(10) DEFAULT NULL");
  }
 if(!hasColumn('uo_team_profile', 'ffindr_id')){
		addColumn('uo_team_profile', 'ffindr_id', "int(10) DEFAULT NULL");
  }
}

function upgrade62() {
  runQuery("ALTER TABLE uo_player_profile MODIFY profile_image VARCHAR(30)");
}

function upgrade63() {
  if(!hasTable("uo_pageload_counter")){
    runQuery("CREATE TABLE uo_pageload_counter(
  		id int(11) NOT NULL auto_increment,
  		PRIMARY KEY(id),
  		page varchar(100) NOT NULL,
  		loads int(11))");
  }
  if(!hasTable("uo_visitor_counter")){
    runQuery("CREATE TABLE uo_visitor_counter(
  		id int(11) NOT NULL auto_increment,
  		ip varchar(15) NOT NULL default '',
  		visits int(11),
  		PRIMARY KEY (id))");
  }
}

function upgrade64() {
	runQuery('INSERT INTO uo_setting (name, value) VALUES ("PageTitle", "Ultiorganizer - ")');
}

function runQuery($query) {
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }
	return $result;
}

function addColumn($table, $column, $type) {
	if (hasColumn($table, $column)) {
		runQuery("alter table ".$table." drop column ".$column);
	}
	runQuery("alter table ".$table." add ".$column." ".$type);
		
}
function hasColumn($table, $column) {
	$query = "SELECT max(".$column.") FROM ".$table;
	$result = mysql_query($query);
	if (!$result) { return false; } else return true;
}

function hasTable($table) { 
	$query = "SHOW TABLES FROM ".DB_DATABASE;
	$tables = mysql_query($query); 
	while (list ($temp) = mysql_fetch_array ($tables)) {
		if ($temp == $table) {
			return TRUE;
		}
	}
	return FALSE;
}
function getPositions($pos) {
	$startingpos=explode("-", $pos);
	if (count($startingpos) == 2) {
		$temp = array();
		for ($j=(int)$startingpos[0]; $j<=(int)$startingpos[1]; $j++) {
			$temp[] = $j;
		}
		return $temp;
	} else {
		return explode(",", $pos);
	}
}

function renameTable($oldtable, $newtable) {
	$query = "SHOW COLUMNS FROM $newtable";
	$result = mysql_query($query);
	if ($result) return true;
	$query = "RENAME TABLE $oldtable TO $newtable";
	runQuery($query);
	return true;
}

function renameField($table, $oldfield, $newfield) {
	if (hasColumn($table, $newfield)) {
		return true;
	}
	$query = "SHOW COLUMNS FROM $table WHERE FIELD='".$oldfield."'";
	$result = mysql_query($query);
	if ($row = mysql_fetch_assoc($result)) {
		$query = "ALTER TABLE $table CHANGE $oldfield $newfield ".$row['Type'];
		if ($row['Null'] == "YES") {
			$query .= " NULL ";		
		} else {
			$query .= " NOT NULL ";
		}
		runQuery($query);
	}
	return true;
}

function changeToAutoIncrementField($table, $field) {
	$query = "SHOW COLUMNS FROM $table WHERE FIELD='".$field."'";
	$result = mysql_query($query);
	if ($row = mysql_fetch_assoc($result)) {
		$query = "ALTER TABLE $table CHANGE $field $field ".$row['Type']." NOT NULL auto_increment";
		runQuery($query);
	}
	return true;	
}

function dropField($table, $field) {
	if (hasColumn($table, $field)) {
		$query = "ALTER TABLE $table DROP $field";
		$result = mysql_query($query);
		if ($result) return true;
		else return false;
	}
	return true;
}

function copyProfileImages() {
	
	//club images
	$results = runQuery("SELECT * FROM uo_club WHERE image IS NOT NULL");
	while($row = mysql_fetch_assoc($results)){
		$image = GetImage($row['image']);
		if($image){
			$type = $image['image_type'];
			$data = $image['image'];
			$org = imagecreatefromstring($data);
			$target = "".UPLOAD_DIR."";
			if(!is_dir($target)){
				recur_mkdirs($target,0775);
			}
			 switch ($type){
				case "image/jpeg":
				case "image/pjpeg":
				$target .= "tmp.jpg";
				imagejpeg($org,$target);
				break;
				case "image/png":
				$target .= "tmp.png";
				imagepng($org,$target);
				break;
				case "image/gif":
				$target .= "tmp.gif";
				imagegif($org,$target);
				break;
			}
			$imgname = time().$row['club_id'].".jpg";
			$basedir = "".UPLOAD_DIR."clubs/".$row['club_id']."/";
			if(!is_dir($basedir)){
				recur_mkdirs($basedir,0775);
				recur_mkdirs($basedir."thumbs/",0775);
			}
		
			ConvertToJpeg($target, $basedir.$imgname);
			CreateThumb($basedir.$imgname, $basedir."thumbs/".$imgname, 160, 120);
			$query = sprintf("UPDATE uo_club SET profile_image='%s' WHERE club_id='%s'",
					mysql_real_escape_string($imgname),
					mysql_real_escape_string($row['club_id']));
			runQuery($query);	
			unlink($target);
		}
	}
	
	//team images
	$results = runQuery("SELECT * FROM uo_team_profile WHERE image IS NOT NULL");
	while($row = mysql_fetch_assoc($results)){
		$image = GetImage($row['image']);
		if($image){
			$type = $image['image_type'];
			$data = $image['image'];
			$org = imagecreatefromstring($data);
			$target = "".UPLOAD_DIR."";
			if(!is_dir($target)){
				recur_mkdirs($target,0775);
			}
			 switch ($type){
				case "image/jpeg":
				case "image/pjpeg":
				$target .= "tmp.jpg";
				imagejpeg($org,$target);
				break;
				case "image/png":
				$target .= "tmp.png";
				imagepng($org,$target);
				break;
				case "image/gif":
				$target .= "tmp.gif";
				imagegif($org,$target);
				break;
			}
			$imgname = time().$row['team_id'].".jpg";
			$basedir = "".UPLOAD_DIR."teams/".$row['team_id']."/";
			if(!is_dir($basedir)){
				recur_mkdirs($basedir,0775);
				recur_mkdirs($basedir."thumbs/",0775);
			}
		
			ConvertToJpeg($target, $basedir.$imgname);
			CreateThumb($basedir.$imgname, $basedir."thumbs/".$imgname, 320, 240);
			$query = sprintf("UPDATE uo_team_profile SET profile_image='%s' WHERE team_id='%s'",
					mysql_real_escape_string($imgname),
					mysql_real_escape_string($row['team_id']));
			runQuery($query);	
			unlink($target);
		}
	}
	
	//player images
	$results = runQuery("SELECT * FROM uo_player_profile WHERE image IS NOT NULL");
	while($row = mysql_fetch_assoc($results)){
		$image = GetImage($row['image']);
		if($image){
			$type = $image['image_type'];
			$data = $image['image'];
			$org = imagecreatefromstring($data);
			$target = "".UPLOAD_DIR."";
			if(!is_dir($target)){
				recur_mkdirs($target,0775);
			}
			 switch ($type){
				case "image/jpeg":
				case "image/pjpeg":
				$target .= "tmp.jpg";
				imagejpeg($org,$target);
				break;
				case "image/png":
				$target .= "tmp.png";
				imagepng($org,$target);
				break;
				case "image/gif":
				$target .= "tmp.gif";
				imagegif($org,$target);
				break;
			}
			$imgname = time().$row['accreditation_id'].".jpg";
			$basedir = "".UPLOAD_DIR."players/".$row['accreditation_id']."/";
			if(!is_dir($basedir)){
				recur_mkdirs($basedir,0775);
				recur_mkdirs($basedir."thumbs/",0775);
			}
		
			ConvertToJpeg($target, $basedir.$imgname);
			CreateThumb($basedir.$imgname, $basedir."thumbs/".$imgname, 120, 160);
			$query = sprintf("UPDATE uo_player_profile SET profile_image='%s' WHERE accreditation_id='%s'",
					mysql_real_escape_string($imgname),
					mysql_real_escape_string($row['accreditation_id']));
			runQuery($query);	
			unlink($target);
		}
	}
}
?>
