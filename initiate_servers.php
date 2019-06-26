<?php
	
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	// Alliances = caps sensitive alliance tag
	// Guilds = caps sensitive guild name
	// Players = caps sensitive player username	
	$servers = array();
	
	$mysqli = new mysqli("localhost", "***USER***", "***PASSWORD***", "***DATABASE***");
	$query = "SELECT servers.id, servers.title, servers.status, servers.premium, servers_killboard.alliances, servers_killboard.guilds, servers_killboard.players, servers_killboard.minfame, servers_killboard.victimthumb, servers_battleboard.alliances, servers_battleboard.guilds, servers_battleboard.players, servers_battleboard.minfame, servers_battleboard.mingroupplayers, servers_killboard.webhook, servers_battleboard.webhook, servers_statusboard.webhook
					FROM `servers`
					LEFT JOIN servers_killboard ON servers.id = servers_killboard.serverid
					LEFT JOIN servers_battleboard ON servers.id = servers_battleboard.serverid
					LEFT JOIN servers_statusboard ON servers.id = servers_statusboard.serverid
					WHERE servers.status = 1
					ORDER BY servers.premium DESC, servers.id ASC";
	if ($stmt = $mysqli->prepare($query)) {
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($serverid, $servertitle, $serverstatus, $serverpremium, $killboard_alliances, $killboard_guilds, $killboard_players, $killboard_minfame, $killboard_victimthumb, $battleboard_alliances, $battleboard_guilds, $battleboard_players, $battleboard_minfame, $battleboard_mingroupplayers, $killboard_webhook, $battleboard_webhook, $statusboard_webhook);
		while ($stmt->fetch()) {
			$servers[] = array(
				'ID' => $serverid,
				'Title' => $servertitle,
				'Status' => ( $serverstatus == 1 ? true : false ),
				'Premium' => ( $serverpremium == 1 ? true : false ),
				'Filters' => array(
					'Killboard' => array(
						'Alliances' => json_decode($killboard_alliances, true),
						'Guilds' => json_decode($killboard_guilds, true),
						'Players' => json_decode($killboard_players, true),
						'MinFame' => $killboard_minfame,
						'ShowVictimWeaponThumbnail' => ( $killboard_victimthumb == 1 ? true : false ),
					),
					'Battleboard' => array(
						'Alliances' => json_decode($battleboard_alliances, true),
						'Guilds' => json_decode($battleboard_guilds, true),
						'Players' => json_decode($battleboard_players, true),
						'MinFame' => $battleboard_minfame,
						'MinGroupPlayers' => $battleboard_mingroupplayers,
					)
				),
				'Webhook' => array(
					'Killboard' => $killboard_webhook,
					'Battleboard' => $battleboard_webhook,
					'Statusboard' => $statusboard_webhook,
				),
			);
		}
	} else {
		// Query failed
	}
	
?>