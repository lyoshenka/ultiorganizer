<?php 
include_once $include_prefix.'lib/season.functions.php';
include_once $include_prefix.'lib/standings.functions.php';
include_once $include_prefix.'lib/player.functions.php';
include_once $include_prefix.'lib/series.functions.php';

function IsSeasonStatsCalculated($season) {
	$query = sprintf("SELECT count(*) FROM uo_season_stats WHERE season='%s'",
		mysql_real_escape_string($season));
	return DBQueryToValue($query);
}

function IsStatsDataAvailable() {
	return DBQueryToValue("SELECT count(*) FROM uo_season_stats");
}

function SeriesStatistics($season) {
	$query = sprintf("SELECT ss.*, ser.name AS seriesname FROM uo_series_stats ss 
		LEFT JOIN uo_series ser ON(ser.series_id=ss.series_id)
		WHERE ss.season='%s'
		ORDER BY ss.season, ss.series_id",
		mysql_real_escape_string($season));
	return DBQueryToArray($query);
}

function SeriesStatisticsByType($seriestype, $seasontype) {
	$query = sprintf("SELECT ss.*, ser.name AS seriesname FROM uo_series_stats ss 
		LEFT JOIN uo_series ser ON(ser.series_id=ss.series_id)
		LEFT JOIN uo_season se ON(ss.season=se.season_id)
		WHERE ser.type='%s' AND se.type='%s'
		ORDER BY ss.season, ss.series_id",
		mysql_real_escape_string($seriestype),
		mysql_real_escape_string($seasontype));
	return DBQueryToArray($query);
}

function ALLSeriesStatistics() {
	$query = sprintf("SELECT ss.*, ser.name AS seriesname, 
		ser.type AS seriestype, s.name AS seasonname, s.type AS seasontype 
		FROM uo_series_stats ss 
		LEFT JOIN uo_series ser ON(ser.series_id=ss.series_id)
		LEFT JOIN uo_season s ON(ser.season=s.season_id)
		ORDER BY ser.type, s.type, ss.series_id");
	return DBQueryToArray($query);
}

function SeasonStatistics($season) {
	$query = sprintf("SELECT ss.*, s.name AS seasonname, s.type AS seasontype 
		FROM uo_season_stats ss 
		LEFT JOIN uo_season s ON(s.season_id=ss.season)
		WHERE ss.season='%s'
		ORDER BY ss.season",
		mysql_real_escape_string($season));
	return DBQueryToRow($query);
}

function AllSeasonStatistics() {
	$query = sprintf("SELECT ss.*, s.name AS seasonname, s.type AS seasontype 
		FROM uo_season_stats ss 
		LEFT JOIN uo_season s ON(s.season_id=ss.season)
		ORDER BY s.type, s.name");
	return DBQueryToArray($query);
}

function SeasonTeamStatistics($season) {
	$query = sprintf("SELECT ts.*, ser.name AS seriesname, t.name AS teamname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype 
		FROM uo_team_stats ts 
		LEFT JOIN uo_series ser ON(ser.series_id=ts.series)
		LEFT JOIN uo_season s ON(s.season_id=ts.season)
		LEFT JOIN uo_team t ON(t.team_id=ts.team_id)
		WHERE ts.season='%s'
		ORDER BY ts.series,ts.standing",
		mysql_real_escape_string($season));
	return DBQueryToArray($query);
}

function TeamStatistics($team) {
	$query = sprintf("SELECT ts.*, ser.name AS seriesname, t.name AS teamname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype
		FROM uo_team_stats ts 
		LEFT JOIN uo_series ser ON(ser.series_id=ts.series)
		LEFT JOIN uo_season s ON(s.season_id=ts.season)
		LEFT JOIN uo_team t ON(t.team_id=ts.team_id)
		WHERE ts.team_id='%s'
		ORDER BY ts.series,ts.standing",
		mysql_real_escape_string($team));
	return DBQueryToArray($query);
}

function TeamStandings($season, $seriestype) {
	$query = sprintf("SELECT ts.*, ser.name AS seriesname, t.name AS teamname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype,
		t.country, c.flagfile
		FROM uo_team_stats ts 
		LEFT JOIN uo_series ser ON(ser.series_id=ts.series)
		LEFT JOIN uo_season s ON(s.season_id=ts.season)
		LEFT JOIN uo_team t ON(t.team_id=ts.team_id)
		LEFT JOIN uo_country c ON(t.country=c.country_id)
		WHERE ts.season='%s' AND ser.type='%s'
		ORDER BY ts.series,ts.standing",
		mysql_real_escape_string($season),
		mysql_real_escape_string($seriestype));
	return DBQueryToArray($query);
}

function TeamStatisticsByName($teamname, $seriestype) {
	$query = sprintf("SELECT ts.*, ser.name AS seriesname, t.name AS teamname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype
		FROM uo_team_stats ts 
		LEFT JOIN uo_series ser ON(ser.series_id=ts.series)
		LEFT JOIN uo_season s ON(s.season_id=ts.season)
		LEFT JOIN uo_team t ON(t.team_id=ts.team_id)
		WHERE t.name='%s' AND ser.type='%s'
		ORDER BY s.starttime DESC, ts.series,ts.standing",
		mysql_real_escape_string($teamname),
		mysql_real_escape_string($seriestype));
	return DBQueryToArray($query);
}

function PlayerStatistics($profile_id) {
	$query = sprintf("SELECT ps.*, ser.name AS seriesname, t.name AS teamname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype
		FROM uo_player_stats ps 
		LEFT JOIN uo_series ser ON(ser.series_id=ps.series)
		LEFT JOIN uo_season s ON(s.season_id=ps.season)
		LEFT JOIN uo_team t ON(t.team_id=ps.team)
		WHERE ps.profile_id='%s'
		ORDER BY s.starttime DESC, ps.season,ps.series",
		mysql_real_escape_string($profile_id));
	return DBQueryToArray($query);
}

function AlltimeScoreboard($season, $seriestype) {
	$query = sprintf("SELECT ps.*, ser.name AS seriesname, t.name AS teamname,
		(COALESCE(ps.goals,0) + COALESCE(ps.passes,0)) AS total,
		p.firstname, p.lastname,
		s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype
		FROM uo_player_stats ps 
		LEFT JOIN uo_series ser ON(ser.series_id=ps.series)
		LEFT JOIN uo_season s ON(s.season_id=ps.season)
		LEFT JOIN uo_team t ON(t.team_id=ps.team)
		LEFT JOIN uo_player p ON(p.player_id=ps.player_id)
		WHERE ps.season='%s' AND ser.type='%s'
		ORDER BY total DESC, ps.games ASC, lastname ASC LIMIT 5",
		mysql_real_escape_string($season),
		mysql_real_escape_string($seriestype));
	return DBQueryToArray($query);
}

function ScoreboardAllTime($limit, $seasontype="", $seriestype="") {

	$query = "SELECT ps.*, ser.name AS seriesname, t.name AS teamname,
			SUM(ps.goals) as goalstotal, SUM(passes) as passestotal,
			SUM(ps.games) as gamestotal, MAX(ser.series_id) as last_series,
			 MAX(t.team_id) as last_team,
			SUM(COALESCE(ps.goals,0) + COALESCE(ps.passes,0)) AS total,
			pp.firstname, pp.lastname,
			s.name AS seasonname, s.type AS seasontype, ser.type AS seriestype
			FROM uo_player_stats ps 
			LEFT JOIN uo_series ser ON(ser.series_id=ps.series)
			LEFT JOIN uo_season s ON(s.season_id=ps.season)
			LEFT JOIN uo_team t ON(t.team_id=ps.team)
			LEFT JOIN uo_player p ON(p.player_id=ps.player_id)
			LEFT JOIN uo_player_profile pp ON(pp.profile_id=ps.profile_id) ";
			
	if(!empty($seasontype) && !empty($seriestype)){
		$query .= sprintf("WHERE s.type='%s' AND ser.type='%s' ",
			mysql_real_escape_string($seasontype),
			mysql_real_escape_string($seriestype));
	}elseif(!empty($seasontype)){
		$query .= sprintf("WHERE s.type='%s' ",
			mysql_real_escape_string($seasontype));
	}elseif(!empty($seriestype)){
		$query .= sprintf("WHERE ser.type='%s' ",
			mysql_real_escape_string($seriestype));
	}
	
	$query .= sprintf("GROUP BY ps.profile_id 
			ORDER BY total DESC, ps.games ASC, lastname ASC 
			LIMIT %d",
			(int)$limit);
			
	return DBQueryToArray($query);
}


function SetTeamSeasonStanding($teamId, $standing) {
  $teaminfo = TeamInfo($teamId);
  if (isSeasonAdmin($teaminfo['season'])){
		$query = sprintf("UPDATE uo_team_stats SET
						standing='%d' 
						WHERE team_id='%d'",
						(int)($standing),(int)($teamId));
		
		DBQuery($query);
	} else { die('Insufficient rights to archive season'); }
}
	
	
function CalcSeasonStats($season) {
	if (isSeasonAdmin($season)) {
		$season_info = SeasonInfo($season);
		$teams = SeasonTeams($season);
		$teams_total = count($teams);
		$allgames = SeasonAllGames($season);
		$games_total = count($allgames);
		$goals_total = 0;
		$home_wins = 0;
	
		$players = SeasonAllPlayers($season);
		$played_players = 0;
		foreach($players as $player){
			$playedgames = PlayerSeasonPlayedGames($player['player_id'], $season_info['season_id']);
			if($playedgames){
				$played_players++;
			}
		}
		
		foreach($allgames as $game_info){
			$goals_total += $game_info['homescore']+$game_info['visitorscore'];
			if($game_info['homescore'] > $game_info['visitorscore']){
				$home_wins++;
			}
		}
		//save season stats
		$query = sprintf("INSERT IGNORE INTO uo_season_stats (season) VALUES ('%s')",
				mysql_real_escape_string($season));
		
		DBQuery($query);
		$query = "UPDATE uo_season_stats SET
				teams=$teams_total, 
				games=$games_total, 
				goals_total=$goals_total, 
				home_wins=$home_wins, 
				players=$played_players
				WHERE season='".$season_info['season_id']."'";
		DBQuery($query);
	} else { die('Insufficient rights to archive season'); }
}

function CalcSeriesStats($season) {
  if (isSeasonAdmin($season)){
		$season_info = SeasonInfo($season);
		$series_info = SeasonSeries($season);
		
		foreach($series_info as $series){
		
			$teams = SeriesTeams($series['series_id']);
			$teams_total = count($teams);
			$allgames = SeriesAllGames($series['series_id']);
			$games_total = count($allgames);
			$goals_total = 0;
			$home_wins = 0;
			
			$players = SeriesAllPlayers($series['series_id']);
			$played_players = 0;
			foreach($players as $player){
				$playedgames = PlayerSeasonPlayedGames($player['player_id'], $season_info['season_id']);
				if($playedgames){
					$played_players++;
				}
			}

			foreach($allgames as $game){
				$game_info = GameResult($game['game']);
				$goals_total += $game_info['homescore']+$game_info['visitorscore'];
				if($game_info['homescore'] > $game_info['visitorscore']){
					$home_wins++;
				}
			}
			//save season stats
			$query = sprintf("INSERT IGNORE INTO uo_series_stats (series_id) VALUES ('%s')",
					mysql_real_escape_string($series['series_id']));
			
			DBQuery($query);
			$query = "UPDATE uo_series_stats SET
					season='".$season_info['season_id']."',
					teams=$teams_total, 
					games=$games_total, 
					goals_total=$goals_total, 
					home_wins=$home_wins, 
					players=$played_players
					WHERE series_id=".$series['series_id'];
			DBQuery($query);
		}
	} else { die('Insufficient rights to archive season'); }
}

function CalcPlayerStats($season) {
    if (isSeasonAdmin($season)){
		$season_info = SeasonInfo($season);
		$players = SeasonAllPlayers($season);
		
		foreach($players as $player){
			$player_info = PlayerInfo($player['player_id']);
			$allgames = PlayerSeasonPlayedGames($player['player_id'], $season_info['season_id']);
			
			if($allgames){
				$games = $allgames;
				$goals = PlayerSeasonGoals($player['player_id'], $season_info['season_id']);
				$passes = PlayerSeasonPasses($player['player_id'], $season_info['season_id']);
				$wins = PlayerSeasonWins($player['player_id'], $player_info['team'], $season_info['season_id']);
				$callahans = PlayerSeasonCallahanGoals($player['player_id'], $season_info['season_id']);
				$breaks = 0;
				$offence_turns = 0;
				$defence_turns = 0;
				$offence_time = 0;
				$defence_time = 0;
				
				//save player stats
				$query = "INSERT IGNORE INTO uo_player_stats (player_id) VALUES (".$player['player_id'].")";
				
				DBQuery($query);
				$query = "UPDATE uo_player_stats SET
						profile_id=".intval($player_info['profile_id']).", 
						team=".$player_info['team'].", 
						season='".$season_info['season_id']."', 
						series=".$player_info['series'].", 
						games=$games, 
						wins=$wins,
						goals=$goals, 
						passes=$passes, 
						callahans=$callahans, 
						breaks=$breaks, 
						offence_turns=$offence_turns,
						defence_turns=$defence_turns,
						offence_time=$offence_time,
						defence_time=$defence_time
						WHERE player_id=".$player['player_id'];
				DBQuery($query);
			}
		}
	} else { die('Insufficient rights to archive season'); }
}

function CalcTeamStats($season) {
    if (isSeasonAdmin($season)){
		$season_info = SeasonInfo($season);
		$series_info = SeasonSeries($season);
		
		foreach($series_info as $series){
			$teams = SeriesTeams($series['series_id']);
			
			foreach($teams as $team){
				$team_info = TeamFullInfo($team['team_id']);
				$goals_made = 0;
				$goals_against = 0;
				$wins = 0;
				$loses = 0;
				$standing = TeamSeriesStanding($team['team_id']);
				$allgames = TeamGames($team['team_id']);
				
				while($game = mysql_fetch_assoc($allgames)){
					if (!is_null($game['homescore']) && !is_null($game['visitorscore'])){
			
						if ($team['team_id'] == $game['hometeam']) {
								$goals_made += intval($game['homescore']);
								$goals_against += intval($game['visitorscore']);
					
							if (intval($game['homescore']) > intval($game['visitorscore'])){
								$wins++;
							}else{
								$loses++;
							}
						}else {
							$goals_made += intval($game['visitorscore']);
							$goals_against += intval($game['homescore']);
							if (intval($game['homescore']) < intval($game['visitorscore'])){
								$wins++;
							}else{
								$loses++;
							}
						}
					}
				}
								
				//save team stats
				$query = "INSERT IGNORE INTO uo_team_stats (team_id) VALUES (".$team['team_id'].")";
				
				DBQuery($query);
				$query = "UPDATE uo_team_stats SET
						season='".$season_info['season_id']."', 
						series=".$team_info['series'].", 
						goals_made=$goals_made, 
						goals_against=$goals_against, 
						standing=$standing, 
						wins=$wins, 
						loses=$loses
						WHERE team_id=".$team['team_id'];
				DBQuery($query);
			}
		}
	} else { die('Insufficient rights to archive season'); }
}
?>
