<?php
ob_start();
?>
<!--
[CLASSIFICATION]
category=database
type=simulator
format=any
security=superadmin
customization=all

[DESCRIPTION]
title = "Game play simulator"
description = "Automatically plays all games selected."
-->
<?php
ob_end_clean();
if (!isSuperAdmin()){die('Insufficient user rights');}
	
include_once 'lib/season.functions.php';
include_once 'lib/series.functions.php';
include_once 'lib/standings.functions.php';

$html = "";
$title = ("Game simulator");
$seasonId = "";

if(!empty($_POST['season'])){
	$seasonId = $_POST['season'];
}

if (isset($_POST['simulate']) && !empty($_POST['pools'])) {

	$pools = $_POST["pools"];
	
	foreach($pools as $poolId){
		
		$poolinfo = PoolInfo($poolId);
		$games = PoolGames($poolId);
		set_time_limit(300); //game simulation takes time because so much inserts
		
			foreach($games as $game){
				$info = GameInfo($game['game_id']);
				
				//all players in roster are playing
				$home_playerlist = TeamPlayerList($info['hometeam']);
				$hplayers = array();
				while($player = mysql_fetch_assoc($home_playerlist)){
					GameAddPlayer($game['game_id'], $player['player_id'], intval($player['num']));
					$hplayers[] = intval($player['num']);
				}
				$hplayers[] = 'xx';//callahan
				$away_playerlist = TeamPlayerList($info['visitorteam']);
				$aplayers = array();
				while($player = mysql_fetch_assoc($away_playerlist)){
					GameAddPlayer($game['game_id'], $player['player_id'], intval($player['num']));
					$aplayers[] = intval($player['num']);
				}
				$aplayers[] = 'xx';//callahan
				
				GameSetStartingTeam($game['game_id'],rand(0,1));
				
				$h=0;
				$a=0;
				$time = 0;
				for($i=0;$h<$poolinfo['winningscore'] && $a<$poolinfo['winningscore'];$i++){
					
					$home = rand(0,1);
					$pass = 0;
					$goal = 0;
					$iscallahan = 0;
					$time = $time + rand(30,200);
					
					if($home){
						$h++;
						$pass = $hplayers[rand(0,count($hplayers)-1)];
						
						if(strcasecmp($pass,'xx')==0 || strcasecmp($pass,'x')==0){
							$iscallahan = 1;
							$pass=-1;
						}else{
							$pass = GamePlayerFromNumber($game['game_id'], $info['hometeam'], $pass);
						}
						$goal = $hplayers[rand(0,count($hplayers)-2)];//-2 removes callahan
						$goal = GamePlayerFromNumber($game['game_id'], $info['hometeam'], $goal);
					}else{
						$a++;
						$pass = $aplayers[rand(0,count($aplayers)-1)];
						
						if(strcasecmp($pass,'xx')==0 || strcasecmp($pass,'x')==0){
							$iscallahan = 1;
							$pass=-1;
						}else{
							$pass = GamePlayerFromNumber($game['game_id'], $info['visitorteam'], $pass);
						}
						$goal = $aplayers[rand(0,count($aplayers)-1)];//-1 removes callahan
						$goal = GamePlayerFromNumber($game['game_id'], $info['visitorteam'], $goal);
					}
					GameAddScore($game['game_id'],$pass,$goal,$time,$i+1,$h,$a,$home,$iscallahan);
					if($h==$poolinfo['halftimescore'] || $a==$poolinfo['halftimescore']){
						$time = $time + $poolinfo['halftime'];
						GameSetHalftime($game['game_id'], $time);
					}
				}
				
				//home team timeouts
				$timeouts = rand(0,$poolinfo['timeouts']);
				$timeoutstime = array();
				for($i=0;$i<=$timeouts;$i++){
					$timeoutstime[] = rand(0,$time);
				}
				sort($timeoutstime, SORT_NUMERIC);
				
				for($i=0;$i<=$timeouts;$i++){
					GameAddTimeout($game['game_id'], $i+1, $timeoutstime[$i], 1);	
				}
				
				//away team timeouts
				$timeouts = rand(0,$poolinfo['timeouts']);
				$timeoutstime = array();
				for($i=0;$i<=$timeouts;$i++){
					$timeoutstime[] = rand(0,$time);
				}
				sort($timeoutstime, SORT_NUMERIC);
				
				for($i=0;$i<=$timeouts;$i++){
					GameAddTimeout($game['game_id'], $i+1, $timeoutstime[$i], 0);	
				}
				
				//game official
				GameSetScoreSheetKeeper($game['game_id'],"Game Simulator");
				
				GameSetResult($game['game_id'], $h, $a);
			}
			ResolvePoolStandings($poolId);
			PoolResolvePlayed($poolId);
	}
}

//season selection
$html .= "<form method='post' id='tables' action='?view=plugins/simulate_games'>\n";

if(empty($seasonId)){
	$html .= "<p>".("Select event").": <select class='dropdown' name='season'>\n";

	$seasons = Seasons();
			
	while($row = mysql_fetch_assoc($seasons)){
		$html .= "<option class='dropdown' value='". $row['season_id'] . "'>". utf8entities($row['name']) ."</option>";
	}

	$html .= "</select></p>\n";
	$html .= "<p><input class='button' type='submit' name='select' value='".("Select")."'/></p>";
}else{
	
	$html .= "<p>".("Select pools to play").":</p>\n";
	$html .= "<table>";
	$html .= "<tr><th><input type='checkbox' onclick='checkAll(\"tables\");'/></th>";
	$html .= "<th>".("Pool")."</th>";
	$html .= "<th>".("Series")."</th>";
	$html .= "<th>".("Teams")."</th>";
	$html .= "<th>".("Played/Total")."</th>";
		$html .= "</tr>\n";
	
	$series = SeasonSeries($seasonId);
	foreach($series as $row){

		$pools = SeriesPools($row['series_id']);
		foreach($pools as $pool){
			$html .= "<tr>";
			if(PoolTotalPlayedGames($pool['pool_id']) < count(PoolGames($pool['pool_id']))
				&& PoolIsMoveFromPoolsPlayed($pool['pool_id'])){
				$html .= "<td class='center'><input type='checkbox' checked='checked' name='pools[]' value='".$pool['pool_id']."' /></td>";				
			}else{
				$html .= "<td class='center'><input type='checkbox' name='pools[]' value='".$pool['pool_id']."' /></td>";
			}
			$html .= "<td>". $pool['name'] ."</td>";
			$html .= "<td>". $row['name'] ."</td>";
			$html .= "<td class='center'>". count(PoolTeams($pool['pool_id'])) ."</td>";
			$html .= "<td class='center'>". PoolTotalPlayedGames($pool['pool_id']);
			$html .= "/". count(PoolGames($pool['pool_id'])) ."</td>";
			$html .= "</tr>\n";
		}
	}
	$html .= "</table>\n";
	$html .= "<p><input class='button' type='submit' name='simulate' value='".("Simulate")."'/></p>";
	$html .= "<div>";
	$html .= "<input type='hidden' name='season' value='$seasonId' />\n";
	$html .= "</div>\n";
}

$html .= "</form>";

showPage(0, $title, $html);
?>
