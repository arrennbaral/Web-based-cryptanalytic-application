<?php

require_once "login.php";

$connection=new mysqli($hn,$un,$pw,$db);
//if any errors
if (!$connection) {
	die( "Could not connect");
}

createTable($connection);

echo <<<_END
<html><head><title>Sign Up</title></head><body>
<form method="post" action="signup.php">
<pre>
Username:<input type="text" name="name" maxlength="30">
Password:<input type="text" name="password" maxlength="30">
Re-Enter Password: <input type="text" name="repassword" maxlength="30">
Email:<input type="text" name="email" maxlength="30">

<input type="submit" name="signup" value = "SignUp">
</pre>
</form>
_END;

echo "</body></html>";

if (isset($_POST['signup'])) {

	$salt1 = random_salt();
	$salt2 = random_salt();
	$un = mysql_fix_string($connection, $_POST['name']);
	$pw = mysql_fix_string($connection, $_POST['password']);
	$rpw = mysql_fix_string($connection, $_POST['repassword']);
	$email = mysql_fix_string($connection, $_POST['email']);

	if (preg_match('/^[a-z0-9_-]/', $un)) {
		$un=trim($un);
	} else {
		die("Try a different username");
	}

// checks for valid email.
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo " ";
	} else {
		die ("Please enter a valid email address.");
	}

// checks if the entered and re-entered password are same.
	if ($pw==$rpw) {
		$token = hash('ripemd128', "$salt1$pw$salt2");

		$stmt = $connection->prepare("INSERT INTO users VALUES (?,?,?,?,?)");
		$stmt->bind_param('sssss',$un,$token,$salt1,$salt2, $email);
		$stmt->execute();

		if (!$stmt) {
			die("Oops");
		}
	}
}

function random_salt() {
	$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()_+';
	return substr(
		str_shuffle($str_result),
		0,7);
}

/* Creates a table in the database to store user credentials.
 * @param $connection a connection.
 */
function createTable($connection) {
	$query="SELECT * FROM users";
	$result=$connection->query($query);

	if (empty($result)) {
		$query="CREATE TABLE users (
		username VARCHAR(64),
		password VARCHAR(64),
		salt1 VARCHAR(64),
		salt2 VARCHAR(64),
		email VARCHAR(64))";

		$result=$connection->query($query);
	}
	if(!$result) {
		die ("OOPS");
	}
}
/* Sanitizes the string.
 * @param $string a string to sanitize.
 * @return returns sanitized variable.
 */
function sanitizeString($var) {
	$var = stripslashes($var);
	$var = strip_tags($var);
	$var = htmlentities($var);
	return $var;
}
/* Sanitizes the sql codes.
 * @param $connection a connection
 * @param $string a string to sanitize.
 * @return returns sanitized variable.
 */
function sanitizeMySQL($connection, $var) {
	$var = $connection->real_escape_string($var);
	$var = sanitizeString($var);
	return $var;
}

/* Sanitizes user inputs
 * @param $connection a connection
 * @param $string a string to sanitize.
 * @return returns sanitized variable.
 */
function mysql_fix_string($connection, $string) {
	if (get_magic_quotes_gpc()) $string = stripslashes($string);
	return $connection->real_escape_string($string);
}

$stmt->close();
$result->close();
$connection->close();
?>
