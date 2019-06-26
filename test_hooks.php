<?php

include_once(__DIR__ . "/initiate_servers.php");

set_error_handler(
    create_function(
        '$severity, $message, $file, $line',
        'throw new ErrorException($message, $severity, $severity, $file, $line);'
    )
);

function status_update($serverid = 0, $value = 1) {
	$mysqli = new mysqli("localhost", "***USER***", "***PASSWORD***", "***DATABASE***");
	$query = "UPDATE `servers`
			SET `servers`.`status` = ?
			WHERE `servers`.`id` = ?;";
	if ($stmt = $mysqli->prepare($query)) {
		$stmt->bind_param("ii", $value, $serverid);
		$stmt->execute();
		$stmt->fetch();
		return true;
	} else {
		return false;
	}
}

foreach ($servers as $servers_id => $servers_value) {
	echo "Testing (" . $servers[$servers_id]['ID'] . ") " . $servers[$servers_id]['Title'] . "<br />";

	if (isset($servers[$servers_id]['Webhook']['Killboard']) && $servers[$servers_id]['Webhook']['Killboard'] != "") {
		try {
			$killboard = json_decode(file_get_contents($servers[$servers_id]['Webhook']['Killboard']), true);
			sleep(0.5);
			echo "Killboard webhook is valid.<br />";
			// echo "<pre>";
			// print_r($killboard);
			// echo "</pre><br />";
		} catch (Exception $e) {
			echo "<strong>Killboard webhook is not valid.</strong><br />";
			status_update($servers[$servers_id]['ID'], 0);
		}
	} else {
		echo "<em>Killboard webhook is not set.</em><br />";
	}

	if (isset($servers[$servers_id]['Webhook']['Battleboard']) && $servers[$servers_id]['Webhook']['Battleboard'] != "") {
		try {
			$killboard = json_decode(file_get_contents($servers[$servers_id]['Webhook']['Battleboard']), true);
			sleep(0.5);
			echo "Battleboard webhook is valid.<br />";
			// echo "<pre>";
			// print_r($killboard);
			// echo "</pre><br />";
		} catch (Exception $e) {
			echo "<strong>Battleboard webhook is not valid.</strong><br />";
		}
	} else {
		echo "<em>Battleboard webhook is not set.</em><br />";
	}

	if (isset($servers[$servers_id]['Webhook']['Statusboard']) && $servers[$servers_id]['Webhook']['Statusboard'] != "") {
		try {
			$killboard = json_decode(file_get_contents($servers[$servers_id]['Webhook']['Statusboard']), true);
			sleep(0.5);
			echo "Statusboard webhook is valid.<br />";
			// echo "<pre>";
			// print_r($killboard);
			// echo "</pre><br />";
		} catch (Exception $e) {
			echo "<strong>Statusboard webhook is not valid.</strong><br />";
		}
	} else {
		echo "<em>Statusboard webhook is not set.</em><br />";
	}

	echo "<br />";
}


?>