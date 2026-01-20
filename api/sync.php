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
$json = array();
//var_dump($post);

$d = new DateTime();
$timestamp = $d->format('u');

function multiDiffCheck ($array1, $array2) {
    $result = array();
    $a = array();
    $b = array();
    foreach ($array1 as $key => $value) {
    	$a[] = json_encode($value);
    }
    foreach ($array2 as $key => $value) {
    	$b[] = json_encode($value);
    }
    $r = array_diff($a, $b);
    foreach ($r as $key => $value) {
    	$result[] = json_decode($value, true);
    }

    return $result;
}

function syncBooks () {
	//this function assumes that $books array is already defined as well as $userid
	global $mysqli;
	global $userid;
	global $books;
	global $message;
	global $error;
	$message[] = "local books count: ".count($books);
	if (count($books) > 0) {
		foreach ($books as $bid => $book) {
		//loop through books
			//DB PULL book
			$message[] = "looking up book ".$book['name'];
			$sql = "SELECT * FROM `books` WHERE `bookID` = '".$book['id']."' AND `userID` = '$userid'";
			if (!$result = $mysqli->query($sql)) {
				// Oh no! The query failed.
				$error[] = array('text' => "Something went wrong getting book info from the database");
				$message[] = "{query: '$sql', debug_errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
			}else{
				if ($result->num_rows === 0) {
					//IF empty DB PUSH book
					$sql = "INSERT INTO `books` (`bookID`, `userID`, `name`, `total`, `target`) VALUES ('".$book['id']."', '$userid', '".$book['name']."', '".$book['total']."', '".$book['target']."')";
					if ($mysqli->query($sql) === TRUE) {
						//get ID
						$ID = $mysqli->insert_id;
						$message[] = "added book ".$book['name']." to db with id of $ID";
					}else{
						$error[] = array('text' => 'Something updating the database with your local books');
						$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
					}
					
				}else{
					//ELSE there, compare latest.timestamp against result.update
					while ($res = $result->fetch_assoc()) {
						$ID = $res['ID'];
						if ($book['latest']['timestamp'] > $res['update'] || $book['name'] !== $res['name'] || $book['total'] !== $res['total'] || $book['target'] !== $res['target']){
							//IF latest.timestamp DB UPDATE book
							$sql = "UPDATE `books` SET `name` = '".$book['name']."', `total` = '".$book['total']."', `target` = '".$book['target']."', `update` = '".$book['latest']['timestamp']."' WHERE `ID` = '$ID'";
							if ($mysqli->query($sql) === TRUE) {
								$message[] = "updated book ".$book['name']." to db with id of $ID";
							}else{
								$error[] = array('text' => 'Something went wrong updating your book info in the database');
								$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
							}
						}
					}
				}
				//DB PULL entries for book
				$dbEntries = array();
				$sql = "SELECT * FROM `entries` WHERE `bookID` = '$ID'";
				if (!$result = $mysqli->query($sql)) {
					$error[] = array('text' => 'Something went wrong trying to access book entries in the database');
					$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
				}else{
					while ($res = $result->fetch_assoc()) {
						$dbEntries[] = array(
							'timestamp' => $res['date'],
							'words' => $res['words']
						);
					}
				}
				if (count($book['entries']) > 0) {
					//array_diff results against book.entries
					$localChanges = multiDiffCheck($book['entries'], $dbEntries);
					$message[] = array('local' => $book['entries'], 'db' => $dbEntries, 'diff' => $localChanges);
					if (count($localChanges) > 0){
						//DB PUSH new entries
						$pushLocal = array();
						foreach ($localChanges as $key => $ent) {
							$pushLocal[] = "('".$book['id']."', '".$ent['timestamp']."', '".$ent['words']."')";
						}
						$pushLocal = implode(", ", $pushLocal);
						$sql = "INSERT INTO `entries` (`bookID`, `date`, `words`) VALUES ".$pushLocal;
						if (!$result = $mysqli->query($sql)) {
							$error[] = array('text' => 'Something went wrong trying to push local entries into the database');
							$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
						}else{
							$message[] = "pushed local entries into db for book ".$book['name'];
						}
					}
				}
			}	
		}
	}
	$message[] = "Pulling all books in db for user";
	//DB PULL books and entries
	$sql = "SELECT `books`.`bookID` AS `bid`, `books`.`name`, `books`.`target`, `books`.`total`, `books`.`update`, `entries`.`date` AS `timestamp`, `entries`.`words` FROM `books` LEFT JOIN `entries` ON `entries`.`bookID` = `books`.`ID` WHERE `books`.`userID` = '$userid' ORDER BY `bid` ASC, `timestamp` DESC";
	if (!$result = $mysqli->query($sql)) {
		$error[] = array('text' => 'Something went wrong trying to access book data in the database');
		$message[] = "{query: '$sql', _errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
	}else{
		$message[] ="building newBooks";
		$bid = 0;
		$newBooks = array();
		while ($res = $result->fetch_assoc()) {
			if ($bid !== $res['bid']){
				$bid = $res['bid'];
				$newBooks[$bid] = array(
					'id' => $res['bid'],
					'name' => $res['name'],
					'target' => $res['target'],
					'total' => $res['total'],
					'latest' => array('timestamp' => $res['update'], 'words' => $res['words']),
					'entries' => array()
				);
			}
			if ($res['timestamp'] > 0) $newBooks[$bid]['entries'][] = array('timestamp' => $res['timestamp'], 'words' => $res['words']);
		}
	}
	
	//return new books array to send back to app
	if (count($newBooks) > 0){
		return $newBooks;
	}else{
		if (count($error) > 0){
			return false;
		}else{
			$message[] = "No books found in the db";
			return array();
		}
	}
}

function jsonOut ($success, $priority, $inclParams = false) {
	global $json;
	global $message;
	global $error;
	$json['success'] = $success;
	$json['priority'] = $priority;
	$json['message'] = $message;
	if ($inclParams) $json['params'] = $post;
	if (count($error) > 0) $json['error'] = $error;
	echo json_encode($json);
}



#############################
#          ACTIONS
#############################

if($action == 'login') {
	####################
	#  LOGIN
	//logging in an existing user and returning their user and book data
	$email = $post['email'];
	$pass = md5($post['pass']);
	$books = json_decode(html_entity_decode($post['books']), true);
	$remember = $post['remember'];
	// $message[] = array(
	// 	'received the following',
	// 	'email' => $email,
	// 	'pass' => $pass,
	// 	'books' => $books,
	// 	'remember' => $remember
	// );

	//get user info
	$sql = "SELECT * FROM `users` WHERE `email` = '$email' AND `password` = '$pass'";
	if (!$result = $mysqli->query($sql)) {
		// Oh no! The query failed.
		$error[1] = array('text' => "Something went wrong with the dataBase");
		$message[] = "{query: '$sql', debug_errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
		jsonOut(false, 0);
		exit;
	}
	// query succeeded, but do we have a result?
	if ($result->num_rows === 0) {
		// Oh, no rows!
		$error[1] = array('text' => "No account found matching these credentials");
		$message[] = "No user info found";
		jsonOut(false, 1);
		exit;
	}
	while ($res = $result->fetch_assoc()) {
		//we have a return so let's craft the user model
		$sws = explode(":", $res['sync']);
		$sync = ($sws[0]=='T')? 'true' : 'false';
		$wipe = ($sws[1]=='T')? 'true' : 'false';
		$share = ($sws[2]=='T')? 'true' : 'false';
		$userid = $res['ID'];
		$user = array(
			'id' => $userid,
			'email' => $res['email'],
			'name' => $res['name'],
			'status' => 'online',
			'sync' => $sync,
			'wipe' => $wipe,
			'share' => $share,
			'newsletter' => $res['newsletter'],
			'supporter' => $res['supporter']
		);
		$json['user'] = $user;
		$message[] = "user info retrieved";
	}
	//sync up the books and entries
	$newBooks = syncBooks();
	if (!$newBooks){
		//errors happened, but we got the user stuff
		jsonOut(true, 0);
		exit;
	}else{
		$json['books'] = $newBooks;
		jsonOut(true, 2);
		exit;
	}

}elseif ($action == 'newAccount') {
	###############################
	#  NEW ACCOUNT
	//creating a new user account
	$email = $post['email'];
	$name = $post['name'];
	$pass = md5($post['pass']);
	$newsletter = $post['newsletter'];
	$books = json_decode(html_entity_decode($post['books']), true);
	// $message[] = array(
	// 	'received the following',
	// 	'email' => $email,
	// 	'name' => $name,
	// 	'pass' => $pass,
	// 	'newsletter' => $newsletter,
	// 	'books' => $books
	// );

	//check if email or name are already in the DB
	$emailCheck = "SELECT `ID` FROM `users` WHERE `email` = '$email' OR `name` = '$name'";
	if (!$result = $mysqli->query($emailCheck)) {
		// Oh no! The query failed.
		$error[1] = array('text' => "Something went wrong with the dataBase");
		$message[] = "{query: '$emailCheck', debug_errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
		jsonOut(false, 0);
		exit;
	}
	// query succeeded, but do we have a result?
	if ($result->num_rows > 0) {
		// email already in db
		$error[1] = array('text' => "This email address or display name are already in our database, please use the login form instead");
		$message[] = "email or name already in DB";
		jsonOut(false, 1);
		exit;
	}

	if ($newsletter !== 'true') $newsletter == 'false';
	$sql = "INSERT INTO `users` (`email`, `name`, `password`, `newsletter`, `sync`, `logindate`) VALUES ('$email', '$name', '$pass', '$newsletter', 'T:T:T', '$timestamp')";
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
		$json['user'] = $user;
		$message[] = "added new user to db w/ id of $userid";

		//sync up any books that might have been local
		$newBooks = syncBooks();
		if (!$newBooks){
			//errors happened, but we got the user stuff
			jsonOut(true, 0);
			exit;
		}else{
			$json['books'] = $newBooks;
			jsonOut(true, 2);
			exit;
		}
	}else{
		// Oh no! The query failed.
		$error[1] = array('text' => "Something went wrong with the dataBase");
		$message[] = "{query: '$emailCheck', debug_errno: '".$mysqli->errno."', debug_error: '".$mysqli->error."'}";
		jsonOut(false, 0);
		exit;
	}
}elseif ($action == 'sync'){
	##############################
	# SYNC LOCAL DATA
	$userid = $post['userid'];
	$books = json_decode(html_entity_decode($post['books']), true);
	$user = json_decode(html_entity_decode($post['user']), true);
	$target = $post['target'];
	$book = $post['book'];
	$entry = $post['entry'];

	if ($target == 'books'){
		$newBooks = syncBooks();
		if (!$newBooks){
			//errors happened, but we got the user stuff
			jsonOut(true, 0);
			exit;
		}else{
			$json['books'] = $newBooks;
			jsonOut(true, 2);
			exit;
		}		
	}elseif ($target == 'kill'){
		//removing the target book
		$sql = "DELETE FROM `books` WHERE "
	}


}else{
	//something's wrong, there's no action.. so output an error object
	$message[] = 'Nothing to do, no action set';
	jsonOut(false, 0, true);
	exit;
}

$result->free();
$mysqli->close();

?>