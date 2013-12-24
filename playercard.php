<?php
include_once 'lib/team.functions.php';
include_once 'lib/common.functions.php';
include_once 'lib/season.functions.php';
include_once 'lib/series.functions.php';
include_once 'lib/player.functions.php';
include_once 'lib/game.functions.php';
include_once 'lib/statistical.functions.php';

$LAYOUT_ID = PLAYERCARD;
$html = "";
if(!empty($_GET["profile"])){
  $playerId = PlayerLatestId(intval($_GET["profile"]));
}else{
  $playerId = intval($_GET["Player"]);
}

$profile = "";

$player = PlayerInfo($playerId);
if(!empty($player['profile_id'])){
	$profile = PlayerProfile($player['profile_id']);
}else{
	$profile = PlayerProfile($playerId);
}
	
$curseason = CurrentSeason();

if ($player['num'])
	$title = "#". $profile['num'] ." ". utf8entities($profile['firstname'] ." ". $profile['lastname']);
else
	$title = utf8entities($profile['firstname'] ." ". $profile['lastname']);

//common page
pageTop($title);
leftMenu($LAYOUT_ID);
contentStart();


if ($player['num'])
	$html .= "<h1>#". $profile['num'] ." ". utf8entities($profile['firstname'] ." ". $profile['lastname']) ."</h1>";
else
	$html .= "<h1>". utf8entities($profile['firstname'] ." ". $profile['lastname']) ."</h1>";

$html .= "<p>"._("Team").": <a class='headerlink' href='?view=teamcard&amp;Team=". $player['team']."'>".utf8entities($player['teamname'])."</a></p>";
	
if($profile){
	$publicfields = explode("|", $profile['public']);
	$html .= "<table style='width:100%'>";
	
	if(!empty($profile['profile_image']) && in_array("profile_image",$publicfields)){
		$html .= "<tr><td style='width:125px'><a href='".UPLOAD_DIR."players/".$player['profile_id']."/".$profile['profile_image']."'>";
		$html .= "<img src='".UPLOAD_DIR."players/".$player['profile_id']."/thumbs/".$profile['profile_image']."' alt='"._("Profile image")."'/></a></td>\n";
	}else{
		$html .= "<tr><td></td>";
	}
			
	$html .= "<td style='vertical-align:top;text-align:left'><table>";
	$html .= "<tr><td></td></tr>";
	if(!empty($profile['nickname']) && in_array("nickname",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Nickname").":</td>";
		$html .= "<td>".utf8entities($profile['nickname'])."</td></tr>\n";
	}
	if(!isEmptyDate($profile['birthdate']) && in_array("birthdate",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Date of birth").":</td>";
		$html .= "<td>".ShortDate($profile['birthdate'])."</td></tr>\n";
	}
	if(!empty($profile['birthplace']) && in_array("birthplace",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Place of birth").":</td>";
		$html .= "<td>".utf8entities($profile['birthplace'])."</td></tr>\n";
	}
	if(!empty($profile['nationality']) && in_array("nationality",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Nationality").":</td>";
		$html .= "<td>".utf8entities($profile['nationality'])."</td></tr>\n";
	}
	if(!empty($profile['throwing_hand']) && in_array("throwing_hand",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Hand").":</td>";
		$html .= "<td>".U_($profile['throwing_hand'])."</td></tr>\n";
	}
	if(!empty($profile['height']) && in_array("height",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Height").":</td>";
		$html .= "<td>".utf8entities($profile['height'])." "._("cm")."</td></tr>\n";
	}
	if(!empty($profile['weight']) && in_array("weight",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Weight").":</td>";
		$html .= "<td>".utf8entities($profile['weight'])." "._("kg")."</td></tr>\n";
	}
	if(!empty($profile['position']) && in_array("position",$publicfields)){
		$html .= "<tr><td class='profileheader'>"._("Position").":</td>";
		$html .= "<td>".utf8entities($profile['position'])."</td></tr>\n";
	}
	$html .= "</table>";
	$html .= "</td></tr>";
	
	if(!empty($profile['story']) && in_array("story",$publicfields)){
		$story = utf8entities($profile['story']);
		$story = str_replace("\n",'<br/>',$story);
		$html .= "<tr><td colspan='2'>".$story."</td></tr>\n";
	}
	if(!empty($profile['achievements']) && in_array("achievements",$publicfields)){
		$html .= "<tr><td colspan='2'>&nbsp;</td></tr>\n";
		$html .= "<tr><td colspan='2'  class='profileheader'>"._("Achievements").":</td></tr>\n";
		$html .= "<tr><td colspan='2'></td></tr>\n";
		$achievements = utf8entities($profile['achievements']);
		$achievements = str_replace("\n",'<br/>',$achievements);
		$html .= "<tr><td colspan='2'>".$achievements."</td></tr>\n";
	}
	$html .= "</table>";	
}

$urls = GetUrlList("player", $player['profile_id']);
if(count($urls)){
	$html .= "<table style='width:600px'>";
	$html .= "<tr><td colspan='2' class='profileheader' style='vertical-align:top'>"._("Player pages").":</td></tr>";	
	foreach($urls as $url){
		$html .= "<tr>";
		$html .= "<td style='width:18px'><img width='16' height='16' src='images/linkicons/".$url['type'].".png' alt='".$url['type']."'/> ";
		$html .= "</td><td>";
		if(!empty($url['name'])){
			$html .="<a href='". $url['url']."'>". $url['name']."</a>";
		}else{
			$html .="<a href='". $url['url']."'>". $url['url']."</a>";
		}
		$html .= "</td>";
		$html .= "</tr>";
	}
	$html .= "</table>";	
}

$urls = GetMediaUrlList("player", $player['profile_id']);
if(count($urls)){
	$html .= "<table style='width:100%'>";
	$html .= "<tr><td colspan='2' class='profileheader' style='vertical-align:top'>"._("Photos and Videos").":</td></tr>";
	foreach($urls as $url){
		$html .= "<tr>";
		$html .= "<td style='width:18px'><img width='16' height='16' src='images/linkicons/".$url['type'].".png' alt='".$url['type']."'/> ";
		$html .= "</td><td>";
		if(!empty($url['name'])){
			$html .="<a href='". $url['url']."'>". $url['name']."</a>";
		}else{
			$html .="<a href='". $url['url']."'>". $url['url']."</a>";
		}
		if(!empty($url['mediaowner'])){
			$html .=" "._("from")." ". $url['mediaowner'];
		}
		$html .= "</td>";
		$html .= "</tr>";
	}
	$html .= "</table>";	
}

$games = PlayerSeasonPlayedGames($playerId, $curseason);
if($games){
	$goals = PlayerSeasonGoals($playerId, $curseason);
	$passes = PlayerSeasonPasses($playerId, $curseason);
	$callahans = PlayerSeasonCallahanGoals($playerId, $curseason);
	$wins = PlayerSeasonWins($playerId, $player['team'],$curseason);
	
	$html .= "<h2>".U_(CurrentSeasonName()).":</h2>\n";
	$html .= "<table border='1' width='100%'><tr>";
	$html .= "<th>"._("Games")."</th><th>"._("Passes")."</th><th>"._("Goals")."</th><th>"._("Tot.")."</th><th>"._("Pass avg.")."</th>";
	$html .= "<th>"._("Goals avg.")."</th><th>"._("Point avg.")."</th><th>"._("Wins")."</th><th>"._("Win-%")."</th></tr>\n";
	
	$total = $passes+$goals;
	$dblPassAvg = SafeDivide($passes, $games);
	$dblGoalAvg = SafeDivide($goals, $games);
	$dblScoreAvg = SafeDivide($total, $games);
	$dblWinsAvg = SafeDivide($wins, $games);
	
	$html .= "<tr>
	<td>".$games."</td>
	<td>".$passes."</td>
	<td>".$goals."</td>
	<td>".$total."</td>
	<td>".number_format($dblPassAvg,2)."</td>
	<td>".number_format($dblGoalAvg,2)."</td>
	<td>".number_format($dblScoreAvg,2)."</td>
	<td>".$wins."</td>
	<td>".number_format($dblWinsAvg*100,1)."%</td></tr>\n";
	$html .= "</table>\n";
}

$html_tmp = "";
$stats = array();


if(!empty($player['profile_id'])){	
	
	$prevseason="";
	$seasoncounter=0;
	
	$playedSeasons = PlayerStatistics($player['profile_id']);
		
	if(count($playedSeasons)){
		$html .= "<h2>"._("History").":</h2>\n";

		
		$html_tmp .= "<table style='white-space: nowrap;' border='1' cellspacing='0' width='100%'>\n
			<tr><th>"._("Event")."</th><th>"._("Division")."</th><th>"._("Team")."</th><th>"._("Games")."</th><th>"._("Passes")."</th><th>"._("Goals")."</th>
			<th>"._("Cal.")."</th><th>"._("Tot.")."</th><th>"._("Pass avg.")."</th><th>"._("Goal avg.")."</th><th>"._("Point avg.")."</th><th>"._("Wins")."</th><th>"._("Win-%")."</th></tr>\n";

		
		foreach ($playedSeasons as $season) {
			
			if($season['season'] != $prevseason){
				$seasoncounter++;
				$prevseason = $season['season'];
			}
			//played series
			$pp = array(
				"season_type"=>"",
				"series_type"=>"",
				"games"=>0,
				"goals"=>0,
				"passes"=>0,
				"wins"=>0
			);
			$pp['season_type'] = $season['seasontype'];
			$pp['series_type'] = $season['seriestype'];
			$pp['games'] = $season['games'];
			$pp['passes'] = $season['passes'];
			$pp['goals'] = $season['goals'];
			$pp['callahans'] = $season['callahans'];
			$pp['wins'] = $season['wins'];
				
			$stats[] = $pp;
				
				$total = $pp['goals'] + $pp['passes'];

				$dblPassAvg = SafeDivide($pp['passes'], $pp['games']);
				$dblGoalAvg = SafeDivide($pp['goals'], $pp['games']);
				$dblScoreAvg = SafeDivide($total, $pp['games']);
				$dblWinAvg = SafeDivide($pp['wins'], $pp['games']);

				if($seasoncounter%2){
					$html_tmp .= "<tr class='highlight'>";
				}else{
					$html_tmp .= "<tr>";
				}
				$html_tmp .= "<td>". utf8entities(U_($season['seasonname'])) ."</td>
						<td>". utf8entities(U_($season['seriesname'])) ."</td>
						<td>". utf8entities(U_($season['teamname'])) ."</td>
						<td>". $pp['games'] ."</td>
						<td>". $pp['passes'] ."</td>
						<td>". $pp['goals'] ."</td>
						<td>". $pp['callahans'] ."</td>
						<td>". $total ."</td>
						<td>". number_format($dblPassAvg,2) ."</td>
						<td>". number_format($dblGoalAvg,2) ."</td>
						<td>". number_format($dblScoreAvg,2) ."</td>
						<td>". $pp['wins'] ."</td>
						<td>". number_format($dblWinAvg*100,1) ."%</td></tr>\n";
			}
		$html_tmp .= "</table>\n";
	}
}
// sort results according season and pool type
if(count($stats)){
	foreach ($stats as $key => $row) {
		$s[$key]  = $row['season_type'];
		$p[$key] = $row['series_type'];
	}
	array_multisort($s, SORT_DESC, $p, SORT_DESC, $stats);

	//seasons total
	$html .= "<table border='1' width='100%'><tr>
		<th>"._("Event type")."</th><th>"._("Division")."</th><th>"._("Games")."</th><th>"._("Passes")."</th><th>"._("Goals")."</th><th>"._("Cal.")."</th><th>"._("Tot.")."</th><th>"._("Pass avg.")."</th>
		<th>"._("Goal avg.")."</th><th>"._("Point avg.")."</th><th>"._("Wins")."</th><th>"._("Win-%")."</th></tr>\n";

	$total_games=0;
	$total_goals=0;
	$total_cal=0;
	$total_passes=0;
	$total_wins=0;

	for($i=0;$i<count($stats);){
		$season_type = $stats[$i]['season_type'];
		$series_type= $stats[$i]['series_type'];
		$games = $stats[$i]['games'];
		$goals = $stats[$i]['goals'];
		$cal = $stats[$i]['callahans'];
		$passes = $stats[$i]['passes'];
		$wins = $stats[$i]['wins'];
		for($i=$i+1;$i<count($stats)&& $season_type==$stats[$i]['season_type'] && $series_type==$stats[$i]['series_type'];$i++){
			$games += $stats[$i]['games'];
			$goals += $stats[$i]['goals'];
			$passes += $stats[$i]['passes'];
			$wins += $stats[$i]['wins'];
		}
		$total_games += $games;
		$total_passes += $passes;
		$total_goals += $goals;
		$total_cal += $cal;
		$total_wins += $wins;
		
		$total = $passes+$goals;
		$dblPassAvg = SafeDivide($passes, $games);
		$dblGoalAvg = SafeDivide($goals, $games);
		$dblScoreAvg = SafeDivide($total, $games);
		$dblWinsAvg = SafeDivide($wins, $games);
		
		$html .= "<tr>
		<td>".U_($season_type)."</td>	
		<td>".U_($series_type)."</td>	
		<td>".$games."</td>
		<td>".$passes."</td>
		<td>".$goals."</td>
		<td>".$cal."</td>
		<td>".$total."</td>
		<td>".number_format($dblPassAvg,2)."</td>
		<td>".number_format($dblGoalAvg,2)."</td>
		<td>".number_format($dblScoreAvg,2)."</td>
		<td>".$wins."</td>
		<td>".number_format($dblWinsAvg*100,1)."%</td></tr>\n";
	}

		$total = $total_passes+$total_goals;
		$dblPassAvg = SafeDivide($total_passes, $total_games);
		$dblGoalAvg = SafeDivide($total_goals, $total_games);
		$dblScoreAvg = SafeDivide($total, $total_games);
		$dblWinsAvg = SafeDivide($total_wins, $total_games);
		
		$html .= "<tr class='highlight'>
		<td colspan='2'>"._("Total")."</td>
		<td>".$total_games."</td>
		<td>".$total_passes."</td>
		<td>".$total_goals."</td>
		<td>".$total_cal."</td>
		<td>".$total."</td>
		<td>".number_format($dblPassAvg,2)."</td>
		<td>".number_format($dblGoalAvg,2)."</td>
		<td>".number_format($dblScoreAvg,2)."</td>
		<td>".$total_wins."</td>
		<td>".number_format($dblWinsAvg*100,1)."%</td></tr>\n";

		
	$html .= "</table>\n";
}
$html .= $html_tmp;

$html .= "<p></p>\n";

//Current season stats

$games = PlayerSeasonGames($playerId, $curseason);

if(count($games)){
	$html .= "<h2>".utf8entities(CurrentSeasonName())." "._("game events").":</h2>\n";

	foreach($games as $game){
		
		$result = GameResult($game['game_id']);
		
		$html .= "<table border='1' style='width:75%'>";
		$html .= "<tr><th colspan='4'><b>". ShortDate($result['time']) ."&nbsp;&nbsp;". utf8entities($result['hometeamname']) ." - ". utf8entities($result['visitorteamname']) ."&nbsp;
			&nbsp;".$result['homescore']. " - ".$result['visitorscore']."</b></th></tr>\n";
			
		$events = PlayerGameEvents($playerId,$game['game_id']);
			
	   	foreach($events as $event){		
			$html .= "<tr><td style='width:10%'>". SecToMin($event['time']) ."</td><td style='width:10%'>". $event['homescore'] ." - ". $event['visitorscore'] ."</td>";
				
			if($event['assist'] == $playerId){
				$html .="<td class='highlight' style='width:40%'>". utf8entities($player['firstname'] ." ". $player['lastname']) ."</td>\n";
			}else{
				if(intval($event['iscallahan'])){
					$html .= "<td class='callahan' style='width:40%'>"._("Callahan-goal")."&nbsp;</td>";
				}else{
					$p = PlayerInfo($event['assist']);
					if ($p)
						$html .= "<td style='width:40%'>". utf8entities($p['firstname'] ." ". $p['lastname']) ."</td>";
					else
						$html .= "<td style='width:40%'>&nbsp;</td>";
				}
			}
			
			if($event['scorer'] == $playerId){
				$html .="<td class='highlight' style='width:40%'>". utf8entities($player['firstname'] ." ". $player['lastname']) ."</td>\n";
			}else{
			$p = PlayerInfo($event['scorer']);
    			if ($p)
    				$html .= "<td style='width:40%'>". utf8entities($p['firstname'] ." ". $p['lastname']) ."</td>";
    			else
    				$html .= "<td style='width:40%'>&nbsp;</td>";
			}
							
			$html .= "</tr>";
			}
		$html .= "</table>";
		}
	}
if ($_SESSION['uid'] != 'anonymous') {
$html .= "<div style='float:left;'><hr/><a href='?view=user/addmedialink&amp;player=".$player['profile_id']."'>"._("Add media")."</a></div>";
}
echo $html;
contentEnd();
pageEnd();
?>
