<?php
	ob_start();
	
	$start = microtime(true);
	
	// SELECT ROUND(AVG(newkills), 2) as average, AVG(servercount) as servers, HOUR(timestamp) as hour, DATE(timestamp) as day
	// FROM `logs`
	// WHERE DATE_SUB(timestamp, INTERVAL 1 HOUR) AND HOUR(timestamp) = 16
	// GROUP BY DATE(timestamp), HOUR(timestamp)
	// ORDER BY `day` DESC
	
	// https://gameinfo.albiononline.com/api/gameinfo/events?limit=51&offset=51
	// Seems like there are parameters to pull the list, maybe if on prime times, kills are missing we need to pull the data twice and combine it...?
	
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	// include_once "vendor/autoload.php";
	include_once "Client.php";
	include_once "Embed.php";
	
	use \DiscordWebhooks\Client;
	use \DiscordWebhooks\Embed;
	
	function get_string_between($string, $start, $end) {
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}
	
	$logs_array = array();
	function save_logs($logs) {
		$mysqli = new mysqli("localhost", "***USER***", "***PASSWORD***", "***DATABASE***");
		$log_date = date("m-d_H:i:s");
		$query = "INSERT INTO `logs` (`id`, `timestamp`, `runtime`, `downloadtime`, `servercount`, `newkills`, `newbattles`, `discordpushes`, `type`)
		VALUES (NULL, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?);";
		if ($stmt = $mysqli->prepare($query)) {
			$stmt->bind_param("iiiiiis", $logs['runtime'], $logs['downloadtime'], $logs['servercount'], $logs['newkills'], $logs['newbattles'], $logs['discordpushes'], $logs['type']);
			$stmt->execute();
			$stmt->fetch();
			
			// The logs were saved succesfully
			return true;
		} else {
			return false;
		}
	}
	
	function status_update($type = "start") {
		$mysqli = new mysqli("localhost", "***USER***", "***PASSWORD***", "***DATABASE***");
		$query = "SELECT `id`, `start`, `end`
						FROM `status`
						WHERE `day` = CURRENT_DATE";
		if ($stmt = $mysqli->prepare($query)) {
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $start, $end);
			$stmt->fetch();
			if ($stmt->num_rows == 1) {
				// There is a row for today already, update the existing row
				$query = "UPDATE `status`
								SET `" . $type . "` = CURRENT_TIMESTAMP
								WHERE `status`.`id` = " . $id . ";";
			} else {
				// No row for today found, we need to make a new row
				$query = "INSERT INTO `status` (`day`, `" . $type . "`)
								VALUES (CURRENT_DATE , CURRENT_TIMESTAMP);";
			}
			
			if ($stmt = $mysqli->prepare($query)) {
				$stmt->execute();
				$stmt->fetch();
				return true;
			} else {
				return false;
			}
			
		} else {
			// Pulling information from database wasn't succesfull
			return false;
		}
	}
	
	function status_get() {
		$mysqli = new mysqli("localhost", "***USER***", "***PASSWORD***", "***DATABASE***");
		$query = "SELECT `id`, `start`, `end`
						FROM `status`
						WHERE `day` = CURRENT_DATE";
		if ($stmt = $mysqli->prepare($query)) {
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id, $start, $end);
			$stmt->fetch();
			if ($stmt->num_rows == 1) {
				// We found information, push it back to requester
				$return['start'] = $start;
				$return['end'] = $end;
				return $return;
			} else {
				// No information found of today?
				return false;
			}
		}
	}
	
	echo "<pre>";
	
	include_once(__DIR__ . "/initiate_servers.php");
	
	// print_r($servers);
	
	// print_r($servers);
	$logs_array['discordpushes'] = 0;
	$logs_array['servercount'] = count($servers);
	

	
	// Make a function where I can push update information to all the channels connected using a browser...
	if (isset($_GET["msg"])) {
		// $cst = "The following update(s) have been done to the killboard discord bot:\n\n> Added 3 new discord servers to the list (the larger the testing group, the more chances I can find bugs)\n> Please keep reporting bugs to me on Discord (iShot#9303)\n\n> **The bot will now __UNDERLINE__ all the members of your guild / alliance / playerlist depending on what the filter is so it's easier to figure out who of your group got the assist!**\n\nThanks for reading! ~ iShot\n*Contact me on Discord if you have questions!*";
		//$cst = "The following update(s) have been done to the killboard discord bot:\n\n> A bug has been fixed where some kills would not appear if there were more than 20 kills per minute on albion.\n\nThanks for reading! ~ iShot\n\n\n---------------------------------\n*Official killboard bot Discord:*\nhttps://discord.gg/pNHTWvk\n---------------------------------";
		//$cst = "The first 5 extra free slots for today have been filled in under 30 minutes! There are only two paid slots open for guilds / alliances who are willing to pay ingame silver to have this bot added to their discord.\n\nWe are reaching the limit of what I think the bot can handle so more issues might start appearing. **Keep in mind to report these issues** to iShot#9303 so I can debug them.\n\n*Thanks for reading, take care and happy killing! ~iShot*";
		//$cst = "**Please be informed we have now set up a discord (text only) server for all killboard bot feedback!**\n\nhttps://discord.gg/pNHTWvk\n\nMake sure to join even if everything goes ok, we accept suggestions, showcases, bugreports and kitty pictures!\n*Thanks to curry for the suggestion!*";
		//$cst = "The reddit post has proven to be a much larger success than I anticipated. Of the open 3 slots I was able to fill 20 (...).\nThank you for the support and the kind words. The bot will remain active and will be worked on.\n\nI take any and all suggestions, hit me up on Discord (iShot#9303) if you feel like helping with a suggestion of your own!\n**The applications for the bot are now closed until further notice**, those who got in got lucky because I had about 100 requests to have the bot added.\n\n__What this bot does:__\n- It posts any kill / death of your filter (guild, alliance or specific players) (*This includes assists!*)\n- It posts a nice message on your discord with the information of the kill\n\n*Take care and happy killing! ~iShot*";
		//$cst = "I've made a new reddit post to advertise the Killboard & (the new) Battleboard bots.\nCheck it out at:\n\nhttps://www.reddit.com/r/albiononline/comments/6wbitg/testing_2_herald_of_death_a_discord_webhook_that/\n*Feel free to share your current experiences with this bot in the reddit topic!*\n\nThanks for reading! ~ iShot";
		//$cst = "Be informed that SBI (AO devs) have released a list of possible buffs / nerfs for next patch. Read it for yourselves at:\n\nhttps://goo.gl/JrHqss\n\n*Take care and happy killing! ~iShot*";
		//$cst = "@everyone test";
		//$cst = "@Antimated: VERGIT JE KAROTEN NIET!";
		$cst = "We are looking for a graphical artist / designer to create a logo for this discord bot.\n\n**Prize pool:** __750,000 silver__\n**How to enter:** Join our discord (link below) and post your design in the 'design-contest' channel\n**Extra info:** Can be found on the same channel (pinned)\n\nhttps://discord.gg/pNHTWvk\n\nThanks for reading! ~ iShot";
		// Is a msg parameter exists we are only going to run this part and ignore the whole killboard feed
		// Run through each of our servers
		foreach ($servers as $servers_id => $servers_value) {
			if (isset($_GET["dbg"])) {
				if ($servers_id == 1) {
					$webhook = new Client($servers[$servers_id]['Webhook']['Killboard']);
					$embed = new Embed();
					$embed->title("Killboard - Update information");
					if (isset($_GET["cst"])) {
						$embed->description($cst);
						} else {
						$embed->description($_GET["msg"]);
					}
					$embed->thumbnail('http://www.heraldofdeath.com/bot/img/EmptyPlayers.png');
					$embed->footer(date("D M dS, Y \a\t g:i A"));
					$embed->author("Herald of Death","https://www.heraldofdeath.com", "https://heraldofdeath.com/wp-content/uploads/2017/09/logo-medium.png");
					$embed->color(16711935);
					$webhook->embed($embed);
					try {
						sleep(0.5);
						$webhook->send();
						$logs_array['discordpushes'] += 1;
					} catch (Exception $e) {
						echo 'Caught exception: ',  $e->getMessage(), "<br />";
					}
				}
				} else {
				$webhook = new Client($servers[$servers_id]['Webhook']['Killboard']);
				$embed = new Embed();
				$embed->title("Killboard - Update information");
				if (isset($_GET["cst"])) {
					$embed->description($cst);
					} else {
					$embed->description($_GET["msg"]);
				}
				$embed->thumbnail('http://www.heraldofdeath.com/bot/img/EmptyPlayers.png');
				$embed->author("Herald of Death","https://www.heraldofdeath.com", "https://heraldofdeath.com/wp-content/uploads/2017/09/logo-medium.png");
				$embed->footer(date("D M dS, Y \a\t g:i A"));
				$embed->color(16711935);
				$webhook->embed($embed);
				try {
					sleep(0.5);
					$webhook->send();
					$logs_array['discordpushes'] += 1;
				} catch (Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "<br />";
				}
			}
		}
	}
	
	// use Albion\AlbionApi;
	// $albion = new AlbionApi();
	// $client = $albion->gameInfoClient();
	
	// Read previous file for comparison
	$recentevents_old = json_decode(file_get_contents(__DIR__ . '/global_killboard.json'), true);
	$recentbattles_old = json_decode(file_get_contents(__DIR__ . '/global_battleboard.json'), true);
	$weapons_arrays_old = json_decode(file_get_contents(__DIR__ . '/weapons.json'), true);
	
	// Calculate most recent old data timestamp
	$most_recent_old_data = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentevents_old[0]['TimeStamp'])[0]);
	
	$start_download = microtime(true);	
	if (isset($_GET["kill"])) {
		$recentevents = json_decode("[" . file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/events/' . $_GET["kill"]) . "]", true);
	} else {
		$oldfile_recentevents = $recentevents = json_decode(file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/events?limit=51'), true);
		// $recentevents_old[0]['TimeStamp'] -> Most recent data we already had.
		$stop = false;
		do {
			$oldest_new_data = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentevents[count($recentevents)-1]['TimeStamp'])[0]);
			if ($oldest_new_data <= $most_recent_old_data) {
				$stop = true;
			} else {
				// The oldest timestemp in our new data does not preceed the newest timestamp of the old data. We need to pull another events page.
				$recentevents_extra = json_decode(file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/events?limit=51&offset=' . count($recentevents)), true);
				$recentevents = array_merge($recentevents, $recentevents_extra);
				echo "Pulled an extra file to get more kills<br />";
			}
		} while (!$stop);
	}
	
	if (isset($_GET["battle"])) {
		$recentbattles = json_decode("[" . file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/battles/' . $_GET["battle"]) . "]", true);
	} else {
		$oldfile_recentbattles = $recentbattles = json_decode(file_get_contents('https://gameinfo.albiononline.com/api/gameinfo/battles?sort=recent&limit=51'), true);
	}
	
	// Pull the old status page information
	$status_old = json_decode(file_get_contents(__DIR__ . '/global_status.json'), true);
	
	// Pull the new status page information
	// http://live.albiononline.com/status.txt
	$status = stripslashes(html_entity_decode(file_get_contents('http://live.albiononline.com/status.txt')));
	$status_stripped = preg_replace('/\s(?=([^"]*"[^"]*")*[^"]*$)/', '', $status);
	$status_stripped = preg_replace('/\p{C}+/u', '', $status_stripped);
	$status = json_decode($status_stripped, true);
	
	$microtime = round((microtime(true) - $start_download), 4);
	$logs_array['downloadtime'] = ($microtime * 10000);
	
	// Check if the previous status included 'maintenance', if the new status says online we can inform those who applied for status updates.
	// if ((strpos($status_old['message'], 'maintenance') !== false) && ($status['status'] == "online")) {
	// 	// Previous check was maintenance, this check is online!
	// 	$backaftermaintenance = true;
	// } else {
	// 	$backaftermaintenance = false;
	// }
	
	// Track when the maintenance starts
	if ((strpos($status_old['message'], 'maintenance') !== false) && ($status['status'] == "online")) {
		// Previous check was maintenance, this check is online!
		$backaftermaintenance = true;
		if (status_update("end")) {
			// The database update worked.
			file_put_contents(__DIR__ . "/global_status.json",json_encode($status));
		} else {
			// The database update failed.
			echo "ERROR: Database update for status end failed.";
		}
	} else {
		$backaftermaintenance = false;
	}
	
	// Last time online, now offline
	if ($status_old['status'] == "online" && $status['status'] == "offline") {
		// The servers went offline for some reason, check if it involves maintenance
		if (strpos($status['message'], 'maintenance') !== false) {
				// The reason we went offline is because of maintenance. Track the time of this in a seperate document.
				if (status_update("start")) {
					// The database update worked.
					file_put_contents(__DIR__ . "/global_status.json",json_encode($status));
				} else {
					// The database update failed.
					echo "ERROR: Database update for status start failed.";
				}
		}
	}
	
	if (isset($_GET["status"])) {
		$backaftermaintenance = true;
	}
	
	// Only do when we don't manualy push this kill / battle
	$recentevents_array_eventids_new = [];
	$recentevents_array_battleids_new = [];
	$recentevents_array_eventids_old = [];
	$recentevents_array_battleids_old = [];
	$recentevents_array_eventids_diff = array();
	$recentevents_array_eventids_diff = array();
	if (!isset($_GET["kill"]) && !isset($_GET["battle"])) {
		// Save the new file
		file_put_contents(__DIR__ . "/global_killboard.json",json_encode($oldfile_recentevents));
		file_put_contents(__DIR__ . "/global_battleboard.json",json_encode($oldfile_recentbattles));
		
		// Set up recent events array for compare
		foreach ($recentevents as $key => $value) {
			$recentevents_array_eventids_new[$key] = $recentevents[$key]['EventId'];
		}
		foreach ($recentbattles as $key => $value) {
			if (array_key_exists($key, $recentbattles)) {
				$recentevents_array_battleids_new[$key] = $recentbattles[$key]['id'];
			}
		}
		
		// Set up old events array for compare
		foreach ($recentevents_old as $key => $value) {
			$recentevents_array_eventids_old[$key] = $recentevents_old[$key]['EventId'];
		}
		foreach ($recentbattles_old as $key => $value) {
			$recentevents_array_battleids_old[$key] = $recentbattles_old[$key]['id'];
		}
		
		// The resulting list are all the new Event ID's
		$recentevents_array_eventids_diff = array_diff($recentevents_array_eventids_new, $recentevents_array_eventids_old);
		$recentbattles_array_battleids_diff = array_diff($recentevents_array_battleids_new, $recentevents_array_battleids_old);
	} elseif (isset($_GET["kill"])) {
		// We're trying to pull a specific kill
		$recentevents_array_eventids_diff[0] = $_GET["kill"];
	} elseif (isset($_GET["battle"])) {
		// We're trying to pulla specific battle
		$recentbattles_array_battleids_diff[0] = $_GET["battle"];
	}
	
	echo "New kills this itteration: " . (isset($recentevents_array_eventids_diff) ? count($recentevents_array_eventids_diff) : 0) . "<br />";
	$logs_array['newkills'] = (isset($recentevents_array_eventids_diff) ? count($recentevents_array_eventids_diff) : 0);
	echo "New battles this itteration: " . (isset($recentbattles_array_battleids_diff) ? count($recentbattles_array_battleids_diff) : 0) . "<br />";
	$logs_array['newbattles'] = (isset($recentbattles_array_battleids_diff) ? count($recentbattles_array_battleids_diff) : 0);
	// if (count($recentbattles_array_battleids_diff) != 0) { print_r($recentbattles_array_battleids_diff); }
	echo "<br />";
	
	// Create an empty feedback variable to fill during the process.
	$feedback = "";
	
	// If the array of difference is not empty we run through the steps below for every $servers ID with it's appropriate parameters
	if (!empty($recentevents_array_eventids_diff) || !empty($recentbattles_array_battleids_diff)) {
		$title = "PART #1: Killboard";
		echo $title . "<br />";
		echo str_repeat("-", strlen($title)) . " <br />";
		
		// For each statement in the $servers
		foreach ($servers as $servers_id => $servers_value) {
			
			// The if clausule for the killboard bot. If it's not set or empty we should skip this.
			if (isset($servers[$servers_id]['Webhook']['Killboard']) && $servers[$servers_id]['Webhook']['Killboard'] != "") {
				
				// Set up a variable that will determine if we actually need to send something to the discord or not...
				$continue = false;
				
				// Set up the webhook for this specific server
				$webhook = new Client($servers[$servers_id]['Webhook']['Killboard']);
				
				// The loop for the killboard bot
				$feed = "";
				foreach ($recentevents as $key => $value) {
					if (in_array($recentevents[$key]['EventId'], $recentevents_array_eventids_diff)) {
						// Apply filter
						// Loop through participants and check if any filter applies.
						$participantfilter = false;
						
						// DEBUG
						// print_r($recentevents[0]);
						
						if (in_array("*",$servers[$servers_id]['Filters']['Killboard']['Players'])) {
							$participantfilter = true;
						}
						
						foreach ($recentevents[$key]['Participants'] as $key4 => $value4) {
							if (in_array($recentevents[$key]['Participants'][$key4]['GuildName'],$servers[$servers_id]['Filters']['Killboard']['Guilds'])) {
								$participantfilter = true;
								} elseif (in_array($recentevents[$key]['Participants'][$key4]['Name'],$servers[$servers_id]['Filters']['Killboard']['Players'])) {
								$participantfilter = true;
								}
						}
						
						if(in_array($recentevents[$key]['Killer']['AllianceName'],$servers[$servers_id]['Filters']['Killboard']['Alliances'])
						|| in_array($recentevents[$key]['Victim']['AllianceName'],$servers[$servers_id]['Filters']['Killboard']['Alliances'])
						|| in_array($recentevents[$key]['Killer']['GuildName'],$servers[$servers_id]['Filters']['Killboard']['Guilds'])
						|| in_array($recentevents[$key]['Victim']['GuildName'],$servers[$servers_id]['Filters']['Killboard']['Guilds'])
						|| in_array($recentevents[$key]['Killer']['Name'],$servers[$servers_id]['Filters']['Killboard']['Players'])
						|| in_array($recentevents[$key]['Victim']['Name'],$servers[$servers_id]['Filters']['Killboard']['Players'])
						|| $participantfilter
						) {
							
							$continue = true;
							
							if (isset($servers[$servers_id]['Filters']['Killboard']['MinFame']) && ($servers[$servers_id]['Filters']['Killboard']['MinFame'] > $recentevents[$key]['TotalVictimKillFame'])) {
								$continue = false;
							}
							
							if ($continue) {
								
								$feed .= "    " . $recentevents[$key]['Killer']['Name'] . " killed " . $recentevents[$key]['Victim']['Name'] . "<br/>";
								
								// Parse the text we need
								
								
								$title = $recentevents[$key]['Killer']['Name'] . ($recentevents[$key]['numberOfParticipants'] > 1 ? ' [+' . ($recentevents[$key]['numberOfParticipants'] - 1) . ']' : '') . ' has killed ' . $recentevents[$key]['Victim']['Name']. '!';
								$killerguild = (empty($recentevents[$key]['Killer']['AllianceName']) ? '' : '[' . $recentevents[$key]['Killer']['AllianceName'] . '] ') . (empty($recentevents[$key]['Killer']['GuildName']) ? "*- none -*" : $recentevents[$key]['Killer']['GuildName']);
								$killerguild = strlen($killerguild) > 23 ? substr($killerguild,0,23)."..." : $killerguild;
								$victimguild = (empty($recentevents[$key]['Victim']['AllianceName']) ? '' : '[' . $recentevents[$key]['Victim']['AllianceName'] . '] ') . (empty($recentevents[$key]['Victim']['GuildName']) ? "*- none -*" : $recentevents[$key]['Victim']['GuildName']);
								$victimguild = strlen($victimguild) > 23 ? substr($victimguild,0,23)."..." : $victimguild;
								
								// Pull weapon information
								if (!array_key_exists(explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0], $weapons_arrays_old)) {
									// Weapon is not yet known in our json database, pull info and add to the json file.
									// https://www.albiononline2d.com/en/item/id/T4_2H_WARBOW
									// 
									$html = file_get_contents('http://www.albionchest.com/items/detail/' . explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]);
									if ( $html === false ) {
										$weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]] = explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0];
										} else {
										$weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]] = get_string_between($html,"<h4>","</h4>");
									}
									
									echo "Downloaded new weapon full name: " . $weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]] . "<br />\n"; 
									
									} else {
									// Weapon name can be pulled from the database (json)
									// $weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]];
									
								}
								
								if (!array_key_exists(explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0], $weapons_arrays_old)) {
									// Weapon is not yet known in our json database, pull info and add to the json file.
									// https://www.albiononline2d.com/en/item/id/T4_2H_WARBOW
									// 
									$html = file_get_contents('http://www.albionchest.com/items/detail/' . explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0]);
									if ( $html === false ) {
										$weapons_arrays_old[explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0]] = explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0];
										} else {
										$weapons_arrays_old[explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0]] = get_string_between($html,"<h4>","</h4>");
									}
									
									echo "Downloaded new weapon full name: " . $weapons_arrays_old[explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0]] . "<br />\n"; 
									
									} else {
									// Weapon name can be pulled from the database (json)
									$weaponname = $weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]];
									
								}
								
								//$killerweapon = empty($recentevents[$key]['Killer']['Equipment']['MainHand']['Type']) ? "*- none -*" : str_replace("@", "+", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type']);
								$killerenchant = empty(explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[1]) ? "0" : explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[1];
								$killerweapon = empty($recentevents[$key]['Killer']['Equipment']['MainHand']['Type']) ? "*- none -*" : explode("_", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0].'.'.$killerenchant . ' ' . explode("&#39;s ", $weapons_arrays_old[explode("@", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'])[0]])[1];
								
								// $victimweapon = empty($recentevents[$key]['Victim']['Equipment']['MainHand']['Type']) ? "*- none -*" : str_replace("@", "+", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type']);
								$victimenchant = empty(explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[1]) ? "0" : explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[1];
								$victimweapon = empty($recentevents[$key]['Victim']['Equipment']['MainHand']['Type']) ? "*- none -*" : explode("_", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0].'.'.$victimenchant . ' ' . explode("&#39;s ", $weapons_arrays_old[explode("@", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'])[0]])[1];
								
								$embed = new Embed();
								$embed->title($title, 'https://albiononline.com/en/killboard/kill/' . $recentevents[$key]['EventId']);
								
								if ($recentevents[$key]['numberOfParticipants'] > 1) {
									
									
									$assistants = array();
									$assistants_string = array();
									
									// total damage
									$damagedone = 0;
									foreach ($recentevents[$key]['Participants'] as $key2 => $value2) {
										$damagedone += $recentevents[$key]['Participants'][$key2]['DamageDone'];
									}
									
									$i = 0;
									foreach ($recentevents[$key]['Participants'] as $key2 => $value2) {
										// Calc the % of assistants damage
										// Total damage
										$assistants[$i]['Name'] = $recentevents[$key]['Participants'][$key2]['Name'];
										$assistants[$i]['Participation'] = ($damagedone > 0 ? round($recentevents[$key]['Participants'][$key2]['DamageDone'] / ($damagedone)*100) : 0);
										$assistants[$i]['Guild'] = $recentevents[$key]['Participants'][$key2]['GuildName'];
										$assistants[$i]['Alliance'] = $recentevents[$key]['Participants'][$key2]['AllianceName'];
										$i += 1;
									}
									
									// Sort the assistants array based on the top -> bottom value of participation
									usort($assistants, function($a, $b) {
										return $a['Participation'] - $b['Participation'];
									});
									
									//print_r($assistants);
									
									foreach ($assistants as $key3 => $value3) {
										// Check if participant is included in the assistants.
										// $assistants[$key3]['Name']
										// $assistants[$key3]['Guild']
										// $assistants[$key3]['Alliance']
										if(in_array($assistants[$key3]['Name'],$servers[$servers_id]['Filters']['Killboard']['Players'])
										|| in_array($assistants[$key3]['Guild'],$servers[$servers_id]['Filters']['Killboard']['Guilds'])
										|| in_array($assistants[$key3]['Alliance'],$servers[$servers_id]['Filters']['Killboard']['Alliances']))
										{
											$assistants_string[] =  "__" . $assistants[$key3]['Name'] . " (" . $assistants[$key3]['Participation'] . "%)__";
											} else {
											$assistants_string[] =  $assistants[$key3]['Name'] . " (" . $assistants[$key3]['Participation'] . "%)";
										}
									}
									
									$in = implode(", ", array_reverse($assistants_string));
									// $out = strlen($in) > 45 ? substr($in,0,45)."..." : $in;
									$out = $in;
									$embed->description('Assisted by ' . $out);
									
									} else {
									
									$embed->description('SOLO KILL!');
									
								}
								
								$embed->field('Killer Guild', $killerguild, true);
								$embed->field('Victim Guild', $victimguild, true);
								$embed->field('Killer Weapon', $killerweapon, true);
								// echo str_replace("@", "+", $recentevents[$key]['Killer']['Equipment']['MainHand']['Type']). "<br />\n";
								$embed->field('Victim Weapon', $victimweapon, true);
								// echo str_replace("@", "+", $recentevents[$key]['Victim']['Equipment']['MainHand']['Type']). "<br />\n";
								$embed->field('Killer Power', explode(".", $recentevents[$key]['Killer']['AverageItemPower'])[0], true);
								// echo $recentevents[$key]['Killer']['AverageItemPower'] . "<br />\n";
								$embed->field('Victim Power', explode(".", $recentevents[$key]['Victim']['AverageItemPower'])[0], true);
								// echo $recentevents[$key]['Victim']['AverageItemPower'] . "<br />\n";
								$embed->field('Fame', number_format($recentevents[$key]['TotalVictimKillFame']), false);
								
								// BattleId
								// if ($recentevents[$key]['BattleId'] != "" && $key == 0) {
								//  	$embed->field('Battle ID', $recentevents[$key]['BattleId'], false);
								// }
								
								// Convert timestamp
								$date = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentevents[$key]['TimeStamp'])[0]);
								// echo $date->format('d-m gA') . "<br /><br />\n";
								$embed->author("Herald of Death","https://www.heraldofdeath.com", "https://heraldofdeath.com/wp-content/uploads/2017/09/logo-medium.png");
								$embed->footer($date->format('D M dS, Y \a\t g:i A') . " UTC");
								
								if (isset($_GET["kill"])) {
									$embed->author("MANUAL PUSH - MIGHT BE OLD DATA");
								}
								
								// Color if kill (positive / green)
								$color = 3340850;
								if(in_array($recentevents[$key]['Victim']['AllianceName'],$servers[$servers_id]['Filters']['Killboard']['Alliances'])
								|| in_array($recentevents[$key]['Victim']['GuildName'],$servers[$servers_id]['Filters']['Killboard']['Guilds'])
								|| in_array($recentevents[$key]['Victim']['Name'],$servers[$servers_id]['Filters']['Killboard']['Players'])
								) {
									// Color if death (negative / red)
									$color = 16396850;
								}
								
								$embed->color($color);
								
								// System needed to download thumbnails if none are found locally.
								// Check if file exists localy
								
								if (isset($servers[$servers_id]['Filters']['Killboard']['ShowVictimWeaponThumbnail']) && ($servers[$servers_id]['Filters']['Killboard']['ShowVictimWeaponThumbnail'] == true)) {
									$item = $recentevents[$key]['Victim']['Equipment']['MainHand']['Type'];
								} else {
									$item = $recentevents[$key]['Killer']['Equipment']['MainHand']['Type'];
								}
								
								if(file_exists(__DIR__ . '/cache/' . $item . ".png")) {
									// File exists, no need to download anything...
									} else {
									// File isn't in our cache yet? Download it from those other guys...
									file_put_contents(__DIR__ . '/cache/' . $item . ".png", fopen('https://gameinfo.albiononline.com/api/gameinfo/items/' . $item, 'r'));
								}
								
								$embed->thumbnail('http://www.heraldofdeath.com/bot/cache/' . $item  . ".png");
								
								$webhook->embed($embed);
							}
						}
					}
				}
				// If above if doesn't trigger, no new data is found...
				
				
				if ($continue == true) {
					// TESTING
					$webhook->send();
					$logs_array['discordpushes'] += 1;
					sleep(0.5);
					echo '> Sent to (' . $servers[$servers_id]['ID'] . ') '. $servers[$servers_id]['Title'] .':<br/>';
					echo $feed;
					} else {
					echo '> No output sent to (' . $servers[$servers_id]['ID'] . ') ' . $servers[$servers_id]['Title'] .'<br/>';
				}
				
			}
			
			// End of the killboard part, scan the battleboard now.
			
		}
		// ^ End of foreach $servers
		
		// For each statement in the $servers
		$title = "PART #2: Battleboard";
		echo "<br /><br />" . $title . "<br />";
		echo str_repeat("-", strlen($title)) . " <br />";
		foreach ($servers as $servers_id => $servers_value) {
			if (isset($servers[$servers_id]['Webhook']['Battleboard']) && $servers[$servers_id]['Webhook']['Battleboard'] != "" && !isset($_GET["kill"])) {
				
				
				// Set up a variable that will determine if we actually need to send something to the discord or not...
				$continue = false;
				
				// Set up the webhook for this specific server
				$webhook = new Client($servers[$servers_id]['Webhook']['Battleboard']);
				
				// The loop for the battleboard bot
				$feed = "";
				foreach ($recentbattles as $key => $value) {
					if (in_array($recentbattles[$key]['id'], $recentbattles_array_battleids_diff)) {
						// Apply filter
						// Loop through participants and check if any filter applies.
						$participantfilter = false;
						
						if (in_array("*",$servers[$servers_id]['Filters']['Battleboard']['Players'])) {
							$participantfilter = true;
						}
						
						foreach ($recentbattles[$key]['players'] as $key4 => $value4) {
							if (in_array($recentbattles[$key]['players'][$key4]['guildName'],$servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
								$participantfilter = true;
								} elseif (in_array($recentbattles[$key]['players'][$key4]['name'],$servers[$servers_id]['Filters']['Battleboard']['Players'])) {
								$participantfilter = true;
								} elseif (in_array($recentbattles[$key]['players'][$key4]['allianceName'],$servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
								$participantfilter = true;
							}
						}
						
						if($participantfilter) {
							// Continue working on the discord message.
							$continue = true;
							
							if (isset($servers[$servers_id]['Filters']['Battleboard']['MinFame']) && ($servers[$servers_id]['Filters']['Battleboard']['MinFame'] > $recentbattles[$key]['totalFame'])) {
								$continue = false;
							}
							
							if (isset($servers[$servers_id]['Filters']['Battleboard']['MinGroupPlayers']) && ($servers[$servers_id]['Filters']['Battleboard']['MinGroupPlayers'] > 0)) {
								// Count the amount of participants fit your filters
								$i = 0;
								foreach ($recentbattles[$key]['players'] as $key2 => $value2) {
									if (
									in_array($recentbattles[$key]['players'][$key2]['guildName'], $servers[$servers_id]['Filters']['Battleboard']['Guilds']) ||
									in_array($recentbattles[$key]['players'][$key2]['allianceName'], $servers[$servers_id]['Filters']['Battleboard']['Alliances']) ||
									in_array($recentbattles[$key]['players'][$key2]['name'], $servers[$servers_id]['Filters']['Battleboard']['Players'])
									) {
										$i += 1;
									}
								}
								if ($i < $servers[$servers_id]['Filters']['Battleboard']['MinGroupPlayers']) {
									$continue = false;
								}
							}
														
							if ($continue) {
								
								$feed .= "    Battle found for " .  $servers[$servers_id]['Title'] . ": Battle#" . $recentbattles[$key]['id'] . "<br />";
								$feed .= "        -> Guilds: " . count($recentbattles[$key]['guilds']) . "<br />";
								$feed .= "        -> Alliances: " . count($recentbattles[$key]['alliances']) . "<br />";
								$feed .= "        -> Players: " . count($recentbattles[$key]['players']) . "<br />";
								
								// Determine lenght of battle
								$starttime = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentbattles[$key]['startTime'])[0]);
								$endtime = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentbattles[$key]['endTime'])[0]);
								// $interval = $starttime->diff($endtime);
								// $duration = $interval->format('g:i:s');
								// echo $duration . "<br />";
								
								// Determine the type of battle (alliance, guild or players)
								// 1. If only two guilds the type is guild battle
								// 2. If two or more alliances the type is alliance battle
								// 3. If no alliances and more than two guilds type is guild battle
								// 4. If else it's a multitude battle
								if (count($recentbattles[$key]['guilds']) == 2) {
									// 1. If only two guilds the type is guild battle
									$type = "guilds";
									} elseif (count($recentbattles[$key]['alliances']) >= 2) {
									// 2. If two or more alliances the type is alliance battle
									$type = "alliances";
									} elseif ((count($recentbattles[$key]['alliances']) < 2) && (count($recentbattles[$key]['guilds']) > 2)) {
									// 3. If no alliances and more than two guilds type is guild battle
									$type = "guilds";
									} else {
									// 4. If else it's a multitude battle
									$type = "players";
								}
								
								// Count the amount of members of each group (guilds and alliances)
								$playerlist = [];
								foreach ($recentbattles[$key]['players'] as $key2 => $value2) {
									$playerlist['guilds'][$recentbattles[$key]['players'][$key2]['guildName']][] = $recentbattles[$key]['players'][$key2]['name'];
									$playerlist['alliances'][$recentbattles[$key]['players'][$key2]['allianceName']][] = $recentbattles[$key]['players'][$key2]['name'];
								}
								
								// Elements of the embed.
								// $recentbattles[$key]['alliances']
								$list_names = array();
								$i = 0;
								foreach ($recentbattles[$key][$type] as $key3 => $value3) {
									if ($type == 'alliances') {
										$list_names[$i]['Name'] =  strtoupper($recentbattles[$key][$type][$key3]['name']) . " (" . count($playerlist['alliances'][$recentbattles[$key][$type][$key3]['name']]) . ")";
									} else {
										if (isset($recentbattles[$key][$type][$key3]['alliance']) && $recentbattles[$key][$type][$key3]['alliance'] != "") {
											$list_names[$i]['Name'] =  "[" . $recentbattles[$key][$type][$key3]['alliance'] . "] " . $recentbattles[$key][$type][$key3]['name'] . " (" . count($playerlist['guilds'][$recentbattles[$key][$type][$key3]['name']]) . ")";
										} else {
											$list_names[$i]['Name'] =  $recentbattles[$key][$type][$key3]['name'] . " (" . count($playerlist['guilds'][$recentbattles[$key][$type][$key3]['name']]) . ")";
										}
									}
									$list_names[$i]['Fame'] = $recentbattles[$key][$type][$key3]['killFame'];
									$list_names[$i]['Purename'] = $recentbattles[$key][$type][$key3]['name'];
									$i += 1;
								}
								
								// print_r($list_names);
								
								// Sort so most fame gets listed first
								usort($list_names, function($a, $b) {
									return $a['Fame'] - $b['Fame'];
								});
								
								$list_names = array_reverse($list_names);
								
								// $list_names[0]['Purename'];
								// OOPS
								
								$color = 16751360;
								if (!empty($servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
									if ($type == 'guilds') {
										if(in_array($list_names[0]['Purename'],$servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
											// The best guild is in our filter!
											$color = 3340850;
										} elseif (in_array($list_names[(count($list_names) - 1)]['Purename'],$servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
											// The worst guild is in our filter!
											$color = 16396850;
										}
									} elseif ($type == 'alliances') {
										foreach ($recentbattles[$key]['guilds'] as $key3 => $value3) {
											if ($recentbattles[$key]['guilds'][$key3]['alliance'] == $list_names[0]['Purename'] && in_array($recentbattles[$key]['guilds'][$key3]['name'],$servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
												// The guild we are filtering is part of the alliance that is the best.
												$color = 3340850;
											} elseif ($recentbattles[$key]['guilds'][$key3]['alliance'] == $list_names[(count($list_names) - 1)]['Purename'] && in_array($recentbattles[$key]['guilds'][$key3]['name'],$servers[$servers_id]['Filters']['Battleboard']['Guilds'])) {
												$color = 16396850;
											}
										}
									}
								}
								
								if (!empty($servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
									if ($type == 'alliances') {
										if(in_array($list_names[0]['Purename'],$servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
											// The best guild is in our filter!
											$color = 3340850;
										} elseif (in_array($list_names[(count($list_names) - 1)]['Purename'],$servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
											// The worst guild is in our filter!
											$color = 16396850;
										}
									} elseif ($type == 'guilds') {
										foreach ($recentbattles[$key]['guilds'] as $key3 => $value3) {
											if ($recentbattles[$key]['guilds'][$key3]['name'] == $list_names[0]['Purename'] && in_array($recentbattles[$key]['guilds'][$key3]['alliance'],$servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
												// The guild we are filtering is part of the alliance that is the best.
												$color = 3340850;
											} elseif ($recentbattles[$key]['guilds'][$key3]['name'] == $list_names[(count($list_names) - 1)]['Purename'] && in_array($recentbattles[$key]['guilds'][$key3]['alliance'],$servers[$servers_id]['Filters']['Battleboard']['Alliances'])) {
												$color = 16396850;
											}
										}
									}
								}

								
								
								$embed = new Embed();
								
								// Color, 1.000.000 fame = red / 0 fame = green
								// LONG = B * 65536 + G * 256 + R
								// R = (255 * n) / 100
								// G = (255 * (100 - n)) / 100 
								// B = 0
								// n = 0 -> 100
								// 1000000 = 100
								// 0 = 0
								// $percentage = round((count($recentbattles[$key]['players']) / 40) * 100);
								// if ($percentage > 100) { $percentage = 100; }
								// $red = round((255 * $percentage) / 100);
								// $green = round((255 * (100 - $percentage)) / 100);
								// $blue = 0;
								// $color = $red;
								// $color = ($color << 8) + $green;
								// $color = ($color << 8) + $blue;
								// $feed .= "        -> Color: " . $color . "<br />";
								$embed->color($color);
								
								$list_names_title = array();
								foreach ($list_names as $key6 => $value6) {
									$list_names_title[] = $list_names[$key6]['Name'];
								}
								
								$title = implode(" -vs- ", $list_names_title);
								$feed .= "        -> " . $title . "<br />";
								$embed->title($title, 'https://albiononline.com/en/killboard/battles/' . $recentbattles[$key]['id']);
								
								// A total of # players participated, # players died for a total of # fame.
								$description = "Players: " . count($recentbattles[$key]['players']) . ", Deaths: " . $recentbattles[$key]['totalKills'] . ", Fame: " . number_format($recentbattles[$key]['totalFame']);
								$feed .= "        -> " . $description . " <br />";
								$embed->description($description);
								
								$amountofplayers = count($recentbattles[$key]['players']);
								switch (true) {
										case $amountofplayers < 20:
											$thumb = 'http://www.heraldofdeath.com/bot/img/EmptyPlayers.png';
											break;
										case $amountofplayers < 40:
											$thumb = 'http://www.heraldofdeath.com/bot/img/20PlusPlayers.png';
											break;
										case $amountofplayers < 100:
											$thumb = 'http://www.heraldofdeath.com/bot/img/40PlusPlayers.png';
											break;
										default:
											$thumb = 'http://www.heraldofdeath.com/bot/img/100PlusPlayers.png';
											break;
								}
								$embed->thumbnail($thumb);
								
								// Add the fields for each alliance / guild
								$topgroup = array();
								$i = 0;
								if ($type == 'alliances' || $type == 'guilds') {
									foreach ($recentbattles[$key][$type] as $key2 => $value2) {
										// Calc the % of assistants damage
										// Total damage
										if ($type == "alliances") {
											$topgroup[$i]['Name'] = strtoupper($recentbattles[$key][$type][$key2]['name']);
											} else {
											$topgroup[$i]['Name'] = $recentbattles[$key][$type][$key2]['name'];
										}
										$topgroup[$i]['Fame'] = $recentbattles[$key][$type][$key2]['killFame'];
										$topgroup[$i]['Kills'] = $recentbattles[$key][$type][$key2]['kills'];
										$topgroup[$i]['Deaths'] = $recentbattles[$key][$type][$key2]['deaths'];
										$i += 1;
									}
									
									usort($topgroup, function($a, $b) {
										return $a['Fame'] - $b['Fame'];
									});
									
									$topgroup = array_reverse($topgroup);
									
									foreach ($topgroup as $key2 => $value2) {
										// Loop trough the different alliances to drop an element / field for the embed
										// $topgroup[$key2]['Name']
										$embed->field($topgroup[$key2]['Name'], "Fame: " . number_format($topgroup[$key2]['Fame']) . "\nKills: " . $topgroup[$key2]['Kills'] . "\nDeaths: " . $topgroup[$key2]['Deaths'], true);
									}
									
									} else {
									
								}
								
								// Calculate the top player.
								$topplayer = array();
								$i = 0;
								foreach ($recentbattles[$key]['players'] as $key2 => $value2) {
									// Calc the % of assistants damage
									// Total damage
									$topplayer[$i]['Name'] = $recentbattles[$key]['players'][$key2]['name'];
									$topplayer[$i]['Alliance'] = strtoupper($recentbattles[$key]['players'][$key2]['allianceName']);
									$topplayer[$i]['Fame'] = $recentbattles[$key]['players'][$key2]['killFame'];
									$topplayer[$i]['Kills'] = $recentbattles[$key]['players'][$key2]['kills'];
									$topplayer[$i]['Deaths'] = $recentbattles[$key]['players'][$key2]['deaths'];
									$i += 1;
								}
								
								// Sort so the most fame comes out on top
								usort($topplayer, function($a, $b) {
									return $a['Fame'] - $b['Fame'];
								});
								
								$topplayer = array_reverse($topplayer);
								
								$topplayer_with_kills = array();
								// Only keep all the players with equal top fame.
								$i = 0;
								foreach ($topplayer as $key5 => $value5) {
									if ($topplayer[$key5]['Fame'] >= $topplayer[0]['Fame']) {
											// Push to next topplayer array
											$topplayer_with_kills[$i] = $topplayer[$key5];
											$i += 1;
									}
								}
								
								// Sort so the most fame AND MOST KILLS comes out on top
								usort($topplayer_with_kills, function($a, $b) {
									return $a['Kills'] - $b['Kills'];
								});
								
								$topplayer_with_kills = array_reverse($topplayer_with_kills);
								
								$username = $topplayer_with_kills[0]['Name'];
								if ($topplayer_with_kills[0]['Alliance'] != "") {
									$username = "[" . $topplayer_with_kills[0]['Alliance'] . "] " . $username;
								}
								$embed->field("Most valuable player", $username, false);
								
								if ($type == "alliances") {
									// Calculate the top guild.
									$topguild = array();
									$i = 0;
									foreach ($recentbattles[$key]['guilds'] as $key2 => $value2) {
										// Calc the % of assistants damage
										// Total damage
										$topguild[$i]['Name'] = $recentbattles[$key]['guilds'][$key2]['name'];
										$topguild[$i]['Fame'] = $recentbattles[$key]['guilds'][$key2]['killFame'];
										$topguild[$i]['Kills'] = $recentbattles[$key]['guilds'][$key2]['kills'];
										$topguild[$i]['Deaths'] = $recentbattles[$key]['guilds'][$key2]['deaths'];
										$topguild[$i]['Alliance'] = $recentbattles[$key]['guilds'][$key2]['alliance'];
										
										$i += 1;
									}
									
									usort($topguild, function($a, $b) {
										return $a['Fame'] - $b['Fame'];
									});
									
									$topguild = array_reverse($topguild);
										
									$guildname = $topguild[0]['Name'];
									if ($topguild[0]['Alliance'] != "") {
										$guildname = "[" . $topguild[0]['Alliance'] . "] " . $guildname;
									}
									$embed->field("Most valuable guild", $guildname, false);
								}
								
								// Convert timestamp
								$date = DateTime::createFromFormat('Y-m-d\TH:i:s', explode(".", $recentbattles[$key]['startTime'])[0]);
								// echo $date->format('d-m gA') . "<br /><br />\n";
								$embed->author("Herald of Death","https://www.heraldofdeath.com", "https://heraldofdeath.com/wp-content/uploads/2017/09/logo-medium.png");
								$embed->footer($date->format('D M dS, Y \a\t g:i A') . " UTC");
								if (isset($_GET["battle"])) {
									$embed->author("MANUAL PUSH - MIGHT BE OLD DATA");
								}
								
								$webhook->embed($embed);
							}
						}
						// ^ End of in_array filter
					}
					
					
					// ^ End of if in_array recentbattles
				}
				
				if ($continue == true) {
					// TESTING
					try {
						$webhook->send();
						$logs_array['discordpushes'] += 1;
						} catch (Exception $e) {
						echo 'Caught exception: ',  $e->getMessage(), "<br />";
					}
					sleep(0.5);
					echo '> Sent to (' . $servers[$servers_id]['ID'] . ') '. $servers[$servers_id]['Title'] .':<br/>';
					echo $feed;
					} else {
					echo '> No output sent to (' . $servers[$servers_id]['ID'] . ') ' . $servers[$servers_id]['Title'] .'<br/>';
				}
				// ^ End of foreach recentevent
				} else {
				echo '> No battleboard URL found for (' . $servers[$servers_id]['ID'] . ') ' . $servers[$servers_id]['Title'] .'<br/>';
			}
		}
		// End of the battleboard part, done scanning!
		
		} else {
		$feedback .= "No differences were found in the array compared to last pulled information" . "<br />\n";
	}
	
	// Save the name data of the $weapons_array
	file_put_contents(__DIR__ . "/weapons.json",json_encode($weapons_arrays_old));
	
	// Print
	// 1. The info we want to display from the array
	// 2. The general feedback variable
	// 3. The timestamp
	
	echo $feedback;
	echo "<br /><br />";
	$title = "PART #3: Statuspage";
	echo $title . "<br />";
	echo str_repeat("-", strlen($title)) . " <br />";
	
	// For each statement in the $servers
	if ($backaftermaintenance) {
		foreach ($servers as $servers_id => $servers_value) {
			if (isset($servers[$servers_id]['Status']) && $servers[$servers_id]['Status']) {
				// Status option exists and is set to true.
				if (isset($servers[$servers_id]['Webhook']['Statusboard']) && $servers[$servers_id]['Webhook']['Statusboard'] != "") {
					$webhook = new Client($servers[$servers_id]['Webhook']['Statusboard']);
				} else {
					$webhook = new Client($servers[$servers_id]['Webhook']['Killboard']);
				}
				$embed = new Embed();
				
				$embed->title("Killboard - Albion Online Status");
				$embed->description("The servers are now **online** after daily maintenance!");
				$embed->thumbnail('http://www.heraldofdeath.com/bot/img/EmptyPlayers.png');
				
				// Add some usefull information about the maintenance.
				// Get the status information of today
				$statusinfo = status_get();
				if ($statusinfo !== false) {
					// Status data found ['start'] ['end']
					// Y-m-d H:i:s
					$start_date = DateTime::createFromFormat('Y-m-d H:i:s', $statusinfo['start']);
					//$start_date->modify('-1 hour');
					$end_date = DateTime::createFromFormat('Y-m-d H:i:s', $statusinfo['end']);
					//$end_date->modify('-1 hour');
					
					$embed->field("Start", $start_date->format('h:i A') . " UTC", true);
					$embed->field("End", $end_date->format('h:i A') . " UTC", true);
					
					$interval = $end_date->diff($start_date);
					$days = $interval->format('%d');
					$hours = 24 * $days + $interval->format('%h');
					$date_diff = $hours.' hour(s), '.$interval->format('%i').' minute(s).';
					$embed->author("Herald of Death","https://www.heraldofdeath.com", "https://heraldofdeath.com/wp-content/uploads/2017/09/logo-medium.png");
					$embed->field("Duration", $date_diff, false);
				} else {
					// No status data of today found?!
				}
				$embed->color(16777010);
				
				// echo $date->format('d-m gA') . "<br /><br />\n";
				$embed->footer(gmdate('D M dS, Y \a\t g:i A') . " UTC");
				
				$webhook->embed($embed);
				try {
					$webhook->send();
					$logs_array['discordpushes'] += 1;
				} catch (Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "<br />";
				}
				sleep(0.5);
				echo '> Sent status to (' . $servers_id . ') '. $servers[$servers_id]['Title'] .'.<br />';
			} else {
				echo '> No status updated needed for (' . $servers_id . ') '. $servers[$servers_id]['Title'] .'.<br />';
			}
		}
	} else {
		echo "> No need to push status update to servers.<br />";
	}
	// End of the statuspage part, done scanning!
	echo "<br />\n";
	$timestamper = date("Y-m-d h:i:sa");
	echo str_repeat("-", strlen($timestamper)) . " <br />";
	echo $timestamper . "<br />";
	$microtime = round((microtime(true) - $start), 4);
	$logs_array['runtime'] = ($microtime * 10000);
	// Check if run from CRON or from browser
	if (php_sapi_name() == "cli") {
		$logs_array['type'] = "crontab";
	} else {
		if (!isset($_GET["kill"]) && !isset($_GET["battle"])) {
			$logs_array['type'] = "browser";
		} else {
			$logs_array['type'] = "forcepush";
		}
	}
	echo $microtime . " sec";
	
	if (save_logs($logs_array)) {
		echo "<br /><i>Logs saved succesfully</i>";
	} else {
		echo "<br /><i>Logs FAILED TO SAVE!!!</i>";
	}
	
	echo "</pre>";
	
	//  Return the contents of the output buffer
	$htmlStr = ob_get_contents();
	// Clean (erase) the output buffer and turn off output buffering
	ob_end_clean(); 
	// Write final string to file
	$log_date = date("m-d_H:i:s");
	file_put_contents(__DIR__ . "/logs/log-" . $log_date . ".html",$htmlStr);
	echo $htmlStr;
	
	
	
	// Temp print
	// print_r($recentevents_array_battleids);
	
	
?>