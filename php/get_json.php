<?php

	$db = mysqli_connect( 'localhost', 'username', 'password', 'dbname' );
	if ( !$db ) {
		die();
	}

	$json = "{\"matches\":[";

	$result = mysqli_query($db, "SELECT id, t1, t2 FROM match_data WHERE status = 'active' ORDER BY id DESC");
	if ( $result && mysqli_num_rows($result) > 0 ) {
		$first = true;
		while ( $row = mysqli_fetch_assoc($result) ) {
			if ( !$first ) {
				$json .= ', ';
			}
			$json .= '{';
			$json .= '"id":' . $row['id'] . ',';
			$json .= '"team1":"' . $row['t1'] . '",';
			$json .= '"team2":"' . $row['t2'] . '"';
			$json .= '}';
			$first = false;
		}
	} 

	$json .= "]}";
	echo $json;

?>