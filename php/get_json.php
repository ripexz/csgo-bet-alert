<?php

	header('Content-Type: application/json');

	$memcached = new Memcached();
	$memcached->addServer('localhost', 11211);

	$mdata = $memcached->get("csgo_match_data");
	if ($mdata) {
		echo $mdata;
		exit();
	}

	$db = mysqli_connect( 'localhost', 'username', 'password', 'dbname' );
	if ( !$db ) {
		die();
	}

	$json = "{\"matches\":[";

	$result = mysqli_query($db, "SELECT id, t1, t2, chance1, chance2 FROM csgo_match_data WHERE status = 'active' ORDER BY id DESC");
	if ( $result && mysqli_num_rows($result) > 0 ) {
		$first = true;
		while ( $row = mysqli_fetch_assoc($result) ) {
			if ( !$first ) {
				$json .= ', ';
			}
			$json .= '{';
			$json .= '"id":' . $row['id'] . ',';
			$json .= '"team1":"' . $row['t1'] . '",';
			$json .= '"team2":"' . $row['t2'] . '",';
			$json .= '"chance1":"' . $row['chance1'] . '",';
			$json .= '"chance2":"' . $row['chance2'] . '"';
			$json .= '}';
			$first = false;
		}
	} 

	$json .= "]}";
	$memcached->set("csgo_match_data", $json);
	echo $json;

?>