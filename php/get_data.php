<?php

	$memcached = new Memcached();
	$memcached->addServer('localhost', 11211);

	//check for this message and stop work if servers are overloaded
	$errMsg = "Servers are under very heavy load or item draft is under progress.";
	$nfMsg = "Looks like there's no site that you´re looking for.";

	//get entry with highest id
	$db = mysqli_connect( 'localhost', 'username', 'password', 'dbname' );
	if ( !$db ) {
		die();
	}
	$result = mysqli_query($db, "SELECT id FROM csgo_match_data ORDER BY id DESC LIMIT 1");
	if ( !$result ) {
		die();
	}

	$next = 0;
	if ( mysqli_num_rows($result) == 0 ) {
		$next = 1;
	}
	else {
		$data = mysqli_fetch_assoc($result);
		$next = $data['id'];
	}

	$last = $next;
	$baseUrl = 'http://csgolounge.com/match?m=';
	$notFoundCount = 0;

	while ( page_found( $baseUrl . $next ) ){
		$page = file_get_contents_curl($baseUrl.$next);

		if (strlen($page) == 0) {
			break;
		}
		if (strpos($page, $errMsg) !== false) {
			break;
		}

		if (strpos($page, $nfMsg) !== false) {
			// Page is not found, check ahead to see if they've been deleted
			// then stop if it happens 5 times in a row
			if ($notFoundCount >= 4) {
				// five 404s in a row, break loop:
				break;
			}
			else {
				$notFoundCount += 1;
			}
		}
		else {
			// Reset notFoundCount
			if ($notFoundCount > 0) {
				$notFoundCount = 0;
			}
		}

		$doc = new DomDocument;
		// We need to validate our document before refering to the id
		$doc->validateOnParse = true;
		libxml_use_internal_errors(true);
		$doc->loadHtml($page);
		libxml_clear_errors();

		$teams = $doc->getElementsByTagName('b');
		$t1node = $teams->item(0);
		$t2node = $teams->item(1);

		$team1 = $t1node->textContent;
		$team2 = $t2node->textContent;

		$team1 = mysqli_real_escape_string($db, $team1);
		$team2 = mysqli_real_escape_string($db, $team2);
		
		$chances = $doc->getElementsByTagName('i');
		$chance1node = $chances->item(0);
		$chance2node = $chances->item(1);

		$chance1 = $chance1node->textContent;
		$chance2 = $chance2node->textContent;

		$chance1 = mysqli_real_escape_string($db, $chance1);
		$chance2 = mysqli_real_escape_string($db, $chance2);

		$status = 'inactive';
		if ( strpos($page, ' ago<') === false ) {
			$status = 'active';
		}

		$sql = mysqli_query($db, "INSERT INTO csgo_match_data (id, status, t1, t2, chance1, chance2) VALUES ($next, '{$status}', '{$team1}', '{$team2}', '{$chance1}', '{$chance2}')");
		$next++;
	}

	$lower_limit = $last - 10;
	//then check any currently active ones for changes as well as last 10 (in case of downtime or errors)
	$result2 = mysqli_query($db, "SELECT id FROM csgo_match_data WHERE status = 'active' OR (id > {$lower_limit} AND id < {$last})");
	if ( $result2 && mysqli_num_rows($result2) > 0 ) {
		while ( $row = mysqli_fetch_assoc($result2) ) {
			$page = file_get_contents_curl($baseUrl.$row['id']);

			if (strlen($page) == 0) {
				break;
			}
			if (strpos($page, $errMsg) !== false) {
				break;
			}

			//update team names:
			$doc = new DomDocument;
			// We need to validate our document before refering to the id
			$doc->validateOnParse = true;
			libxml_use_internal_errors(true);
			$doc->loadHtml($page);
			libxml_clear_errors();

			$teams = $doc->getElementsByTagName('b');
			$t1node = $teams->item(0);
			$t2node = $teams->item(1);

			$team1 = $t1node->textContent;
			$team2 = $t2node->textContent;

			$team1 = mysqli_real_escape_string($db, $team1);
			$team2 = mysqli_real_escape_string($db, $team2);

			$chances = $doc->getElementsByTagName('i');
			$chance1node = $chances->item(0);
			$chance2node = $chances->item(1);

			$chance1 = $chance1node->textContent;
			$chance2 = $chance2node->textContent;

			$chance1 = mysqli_real_escape_string($db, $chance1);
			$chance2 = mysqli_real_escape_string($db, $chance2);
			
			$curr_id = $row['id'];
			$status = 'active';
			if ( strpos($page, ' ago<') !== false ) {
				$status = 'inactive';
			}
			$sql2 = mysqli_query($db, "UPDATE csgo_match_data SET status = '{$status}', t1 = '{$team1}', t2 = '{$team2}', chance1 = '{$chance1}', chance2 = '{$chance2}' WHERE id = {$curr_id}");
		}
	}

	if ($next != $last || mysqli_num_rows($result2) > 0) {
		// Invalidate cache:
		$memcached->delete("csgo_match_data");
	}

	function file_get_contents_curl($url, $timeout = 0) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       

		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}

	function page_found($url) {
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_HEADER, TRUE); 
		curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		$head = curl_exec($ch); 
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode == '200') {
			return true;
		}		
		return false;
	}
?>