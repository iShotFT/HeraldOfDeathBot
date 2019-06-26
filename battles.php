<?php
	
	// https://gameinfo.albiononline.com/api/gameinfo/events?limit=51&offset=51
	// Seems like there are parameters to pull the list, maybe if on prime times, kills are missing we need to pull the data twice and combine it...?
	
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	$battles[0] = json_decode(file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/battles/6333883'));
	
	foreach ($battles as $key => $value) {
		$battle_arrays[$key] = json_decode(json_encode($battles[$key]), true);
	}
	
	echo "<pre>";
	print_r($battle_arrays);
	echo "</pre>";
	
?>