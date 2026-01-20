<?php
require_once('connection.php');

// http://php.net/manual/en/mysqli.examples-basic.php

//collect the POST vars, I could probably use extract() but being explicit like this offers some security
if(stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $_POST = json_decode(file_get_contents("php://input"), true);
}
$post = array_map('htmlentities',$_POST);
// $post = array_map('htmlentities',$_GET);
$action = $post['action']; // newAccount
$error = array();
$message = array();
//var_dump($post);

if ($action == 'newAccount'){
	//creating a new user account
	$email = $post['email'];
	$name = $post['name'];
	$pass = md5($post['pass']);
	$newsletter = $post['newsletter'];
	$books = json_decode($post['books']);

	//check if email or name are already in the DB
	$emailCheck = "SELECT `ID` FROM `users` WHERE `email` = '$email' OR `name` = '$name'";
	if (!$result = $mysqli->query($emailCheck)) {
		// Oh no! The query failed.
		$error[1] = array('text' => "Something went wrong with the dataBase");
		$message = "{query: '$emailCheck',
				debug_errno: '".$mysqli->errno."',
				debug_error: '".$mysqli->error."'}";

		$json = array('Success' => false, 'priority' => 0,
			'error' => $error,
			'message' => $message );
		echo json_encode($json);
		exit;
	}
	// query succeeded, but do we have a result?
	if ($result->num_rows > 0) {
		// email already in db
		$error[1] = array('text' => "This email address or display name are already in our database, please use the login form instead");
		$json = array('success' => false, 'priority' => 1,
			'error' => $error,
			'message' => "email already in DB"
		);
		echo json_encode($json);
		exit;
	}

	//checks out, so let's create the account
	$d = new DateTime();
	$date = $d->format('u');
	if ($newsletter !== 'true') $newsletter == 'false';
	$sql = "INSERT INTO `users` (`email`, `name`, `password`, `newsletter`, `sync`, `logindate`) VALUES ('$email', '$name', '$pass', '$newsletter', 'T:T:T', '$date')";
	if ($mysqli->query($sql) === TRUE) {
		//successfully added entry
		$userid = $mysqli->insert_id;
		$user = array(
			'id' => $userid,
			'email' => $email,
			'name' => $name,
			'status' => 'online',
			'sync' => 'true',
			'wipe' => 'true',
			'share' => 'true',
			'newsletter' => $newsletter,
			'supporter' => 'false'
		);
		$message[] = "added new user to db w/ id of $userid";

		//lets check if we need to push some local changes into the db
		if (count($books)>0){
			for ($i=1; $i < (count($books) + 1); $i++) { 
				$sql = "INSERT INTO `books` (`bookID`, `name`, `userID`, `target`, `total`) VALUES ('".$books[$i]['id']."', '".$books[$i]['name']."', '$userid', '".$books[$i]['target']."', '".$books[$i]['total']."')";
				if ($mysqli->query($sql) === TRUE) {
					//successfully added entry
					$bookid = $mysqli->insert_id;
					$message[] = "added book - ".$books[$i]['name']." - to db w/ id of $bookid";

					//add any entries the book has
					foreach ($books[$i]['entry'][$e] as &$entry) {
						$sql = "INSERT INTO `entries` (`bookID`, `date`, `words`) VALUES ('$bookid', '".$entry['timestamp']."', '".$entry['words']."')";
						if ($mysqli->query($sql) === TRUE) {
							//successfully added entry
							$entryid = $mysqli->insert_id;
							$message[] = "added entry for book $bookid to db with id of $entryid";
						}else{
							$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
						}
					}
					
				}else{
					$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
				}
			}	
		}

		$json = array('success' => true,
			'message' => $message,
			'user' = > $user
		);
	}else{
		// Oh no! The query failed.
		$error[1] = array('text' => "Something went wrong with the dataBase");
		$json = array('success' => false, 'priority' => 0,
			'error' => $error,
			'message' => "{query: '$sql',
				_errno: '".$mysqli->errno."',
				debug_error: '".$mysqli->error."'}" );
		echo json_encode($json);
		exit;
	}
}

echo json_encode($json);

$mysqli->close();
?>