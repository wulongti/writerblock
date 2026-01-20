<?php

// ** MySQL settings - You can get this info from your web host ** //

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** The name of the database */
define('DB_NAME', 'chang_writerblock');

/** MySQL database username */
define('DB_USER', 'chang_wblock');

/** MySQL database password */
define('DB_PASSWORD', 'LJI+14FS27#D');

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);


if ($mysqli->connect_errno) {
	$json = array('Success' => false, 'priority' => 0,
		'message' => "{error: 'Unable to connect to MySQL.".PHP_EOL."',
			debug_errno: '".mysqli_connect_errno().PHP_EOL."',
			debug_error: '".mysqli_connect_error().PHP_EOL."'}" );
	echo json_encode($json);
    exit;
}

// echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
// echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;

//mysqli_close($link);


?>