<?php
include_once $include_prefix.'lib/team.functions.php';
include_once $include_prefix.'lib/common.functions.php';
include_once $include_prefix.'lib/season.functions.php';
include_once $include_prefix.'lib/player.functions.php';
include_once $include_prefix.'lib/pool.functions.php';
include_once $include_prefix.'lib/reservation.functions.php';
include_once $include_prefix.'lib/url.functions.php';

$LAYOUT_ID = TEAMPROFILE;
$max_file_size = 5 * 1024 * 1024; //5 MB
$max_new_links = 3;
$html = "";

$teamId = intval($_GET["Team"]);

if(isset($_SERVER['HTTP_REFERER']))
	$backurl = utf8entities($_SERVER['HTTP_REFERER']);
else
	$backurl = "?view=user/teamplayers&Team=$teamId";

$team = TeamInfo($teamId);

$title = _("Team details").": ". utf8entities($team['name']);

//team profile
$tp = array(
	"team_id"=>$teamId,
	"profile_image"=>"",
	"abbreviation"=>"",
	"captain"=>"",
	"coach"=>"",
	"story"=>"",
	"achievements"=>""
	);

if(isset($_POST['save'])){
	$backurl = utf8entities($_POST['backurl']);
	$tp['captain'] = $_POST['captain'];
	$tp['abbreviation'] = $_POST['abbreviation'];
	if(strlen($tp['abbreviation'])<2){
		$tp['abbreviation'] = $team['abbreviation'];
		$html .= "<p class='warning'>"._("Abbreviation too short.")."</p>";
	}
	$allteams = SeriesTeams($team['series']);
	foreach($allteams as $t){
		if($tp['team_id']!=$teamId && $tp['abbreviation']==$t['abbreviation']){
			$tp['abbreviation'] = $team['abbreviation'];
			$html .= "<p class='warning'>"._("Abbreviation already used by")." ".utf8entities($t['name']).".</p>";
			break;
		}
	}
	$tp['coach'] = $_POST['coach'];
	$tp['story'] = $_POST['story'];
	$tp['achievements'] = $_POST['achievements'];
	SetTeamProfile($tp);
	
	for($i=0;$i<$max_new_links;$i++){

		if(!empty($_POST["url$i"])){
			$name = "";
			if(!empty($_POST["urlname$i"])){
				$name = $_POST["urlname$i"];
			}
			AddTeamProfileUrl($teamId, $_POST["urltype$i"], $_POST["url$i"], $name);
		}
	}

	if(is_uploaded_file($_FILES['picture']['tmp_name'])) {
		$html .= UploadTeamImage($teamId);
	}

}elseif(isset($_POST['remove_x'])){
	RemoveTeamProfileImage($teamId);
}elseif(isset($_POST['removeurl_x'])){
	$id = $_POST['hiddenDeleteId'];
	RemoveTeamProfileUrl($teamId, $id);
}
$team = TeamInfo($teamId);
$profile = TeamProfile($teamId);	
if($profile){
	$tp['captain'] = $profile['captain'];
	$tp['abbreviation'] = $team['abbreviation'];
	$tp['coach'] = $profile['coach'];
	$tp['story'] = $profile['story'];
	$tp['achievements'] = $profile['achievements'];
	$tp['profile_image'] = $profile['profile_image'];
}
	
//common page
pageTopHeadOpen($title);
include_once 'script/disable_enter.js.inc';
include_once 'script/common.js.inc';
pageTopHeadClose($title);
leftMenu($LAYOUT_ID);
contentStart();

//content
$html .= "<h1>". utf8entities($team['name'])."</h1>";

$html .= "<form method='post' enctype='multipart/form-data' action='?view=user/teamprofile&amp;Team=$teamId'>\n";
	
$html .= "<table>";
$html .= "<tr><td class='infocell'>"._("Abbreviation").":</td>";
$html .= "<td><input class='input' maxlength='15' size='10' name='abbreviation' value='".$tp['abbreviation']."'/></td></tr>\n";

$html .= "<tr><td class='infocell'>"._("Coach").":</td>";
$html .= "<td><input class='input' maxlength='100' size='50' name='coach' value='".$tp['coach']."'/></td></tr>\n";

$html .= "<tr><td class='infocell'>"._("Captain").":</td>";
$html .= "<td><input class='input' maxlength='100' size='50' name='captain' value='".$tp['captain']."'/></td></tr>\n";
	
$html .= "<tr><td class='infocell' style='vertical-align:top'>"._("Description").":</td>";
$html .= "<td><textarea class='input' rows='10' cols='80' name='story'>".$tp['story']."</textarea> </td></tr>\n";

$html .= "<tr><td class='infocell' style='vertical-align:top'>"._("Achievements").":</td>";
$html .= "<td><textarea class='input' rows='10' cols='80' name='achievements'>".$tp['achievements']."</textarea> </td></tr>\n";

$html .= "<tr><td class='infocell' colspan='2'>"._("Web pages (homepage, blogs, images, videos)").":</td></tr>";
$html .= "<tr><td colspan='2'>";
$html .= "<table border='0'>";

$urls = GetUrlList("team", $teamId);

foreach($urls as $url){
	$html .= "<tr style='border-bottom-style:solid;border-bottom-width:1px;'>";
	$html .= "<td colspan='3'><img width='16' height='16' src='images/linkicons/".$url['type'].".png' alt='".$url['type']."'/> ";
	if(!empty($url['name'])){
		$html .="<a href='". $url['url']."'>". $url['name']."</a> (".$url['url'].")";
	}else{
		$html .="<a href='". $url['url']."'>". $url['url']."</a>";
	}
	
	$html .= "</td>";
	$html .= "<td class='right'><input class='deletebutton' type='image' src='images/remove.png' name='removeurl' value='X' alt='X' onclick='setId(".$url['url_id'].");'/></td>";
	$html .= "</tr>";
}

//empty line
if(count($urls)){
	$html .= "<tr>";
	$html .= "<td colspan='3'>&nbsp;</td>";
	$html .= "</tr>";
}

$html .= "<tr>";
$html .= "<td>"._("Type")."</td>";
$html .= "<td>"._("URL")."</td>";
$html .= "<td>"._("Name")." ("._("optional").")</td>";
$html .= "</tr>";

$urltypes = GetUrlTypes();
for($i=0;$i<$max_new_links;$i++){
	$html .= "<tr>";
	$html .= "<td><select class='dropdown' name='urltype$i'>\n";
	foreach($urltypes as $type){
		$html .= "<option value='".$type['type']."'>". $type['name'] ."</option>\n";
	}
	$html .= "</select></td>";
	$html .= "<td><input class='input' maxlength='500' size='40' name='url$i' value=''/></td>";
	$html .= "<td><input class='input' maxlength='500' size='40' name='urlname$i' value=''/></td>";
	$html .= "</tr>";
}

$html .= "</table>";
$html .= "</td></tr>\n";


$html .= "<tr><td class='infocell' style='vertical-align:top'>"._("Current image").":</td>";
if(!empty($tp['profile_image'])){
	$html .= "<td><a href='".UPLOAD_DIR."teams/$teamId/".$tp['profile_image']."'>";
	$html .= "<img src='".UPLOAD_DIR."teams/$teamId/thumbs/".$tp['profile_image']."' alt='"._("Profile image")."'/></a></td></tr>";
	$html .= "<tr><td class='infocell'></td>";
	$html .= "<td><input class='button' type='submit' name='remove' value='"._("Delete image")."' /></td></tr>\n";

}else{
	$html .= "<td>"._("No image")."</td></tr>\n";
}

$html .= "<tr><td class='infocell'>"._("New image").":</td>";
$html .= "<td><input class='input' type='file' size='50' name='picture'/></td></tr>\n";


$html .=  "<tr><td colspan = '2' align='right'><br/>
	  <input class='button' type='submit' name='save' value='"._("Save")."' />
	  <input class='button' type='button' name='takaisin'  value='"._("Return")."' onclick=\"window.location.href='$backurl'\"/>
	  <input type='hidden' name='backurl' value='$backurl'/>
	  <input type='hidden' name='MAX_FILE_SIZE' value='$max_file_size'/>
	  </td></tr>\n";
$html .= "</table>\n";
$html .= "<div><input type='hidden' id='hiddenDeleteId' name='hiddenDeleteId'/></div>";
$html .= "</form>";
$html .= "<p><a href='?view=teamcard&amp;Team=". $teamId."'>"._("Check Team card")."</a></p>";	

echo $html;

//common end
contentEnd();
pageEnd();
?>
