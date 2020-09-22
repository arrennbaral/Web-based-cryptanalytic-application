	<?php

	require_once "login.php";
	$connection=new mysqli($hn,$un,$pw,$db);
//if any errors
	createTable($connection);

	if (!$connection) {
		die( "Could not connect");
	}

	session_start();

	if (isset($_SESSION['username'])) {
		$username = $_SESSION['username'];
		$password = $_SESSION['password'];

		destroy_session_and_data();

		echo "Welcome back $username.";
	}
	else echo "Please <a href='userlogin.php'>click here</a> to log in.";


	echo <<<_END
	<html><head><title>PHP File Upload</title></head><body>
	<form method='post' action='continue.php' enctype='multipart/form-data'>
	Key: <input type='text' name='key' size='30'>

	Select a TXT file:
	<input type='file' name='filename' size='10'>
	<input type='submit' value='SUBMIT'>
	<pre>

	Enter Substitution/XOR: <input type='text' name="cipher">
	Enter Encrypt/Decrypt: <input type='text' name="translate">
	</pre>
	</form>
_END;


	if ($_FILES) {
		$name = $_FILES['filename']['name'];
		$name=preg_replace("/[^A-Za-z0-9.]/", "", $name);

		$ext= substr($name, strrpos($name, '.')+1);
	//checks of the extention is txt
		if (($ext=='txt') && ($_FILES['filename']['type']=='text/plain')) {
			$line=file_get_contents($_FILES['filename']['tmp_name']);
			$line = str_replace("\n", '', $line);

			if (isset($_POST['translate'])&&isset($_POST['key'])) {
				$input=mysql_entities_fix_string($connection,$_POST['translate']);
				$input=strtolower($input);
				$cipher=mysql_entities_fix_string($connection,$_POST['cipher']);
				$key=mysql_entities_fix_string($connection,$_POST['key']);
				$key=strtoupper($key);

				$dat = date('Y-m-d');

				if (isset($_POST['cipher'])) {
					if (strtolower($cipher) == 'substitution') {
						simpleSubstitution($connection,$dat,$key,$cipher,$input,$line);
					} else if (strtolower($cipher) == 'xor') {
						xorcp($connection,$dat,$key,$cipher,$input,$line);
				 }
			 }
		 }
	 }
 }
	echo "</body></html";

	/* Handles the simple substitution cipher encrypt and decrypt.
	 * @param $connection a connection.
	 * @param $dat a date.
	 * @param $key a key used to encrypt and decrypt.
	 * @param $cipher a type of cipher user wants to encode the string in.
	 * @param $input an input telling  to encrypt/decrypt.
	 * @param $line a string/ user input  to encrypt/decrypt.
	 */
	function simpleSubstitution($connection,$dat,$key,$cipher,$input,$line) {
		$plainAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$uppercase = strtoupper($line);
		$stmt = $connection->prepare("INSERT INTO data VALUES (?,?,?)");
		$stmt->bind_param('sss',$dat,$key,$cipher);

		$stmt->execute();
		if(!$stmt){
			die("Oops");
		}

		if ($input=="encrypt") {
			$enc=strtr($uppercase, $plainAlphabet, $key);
			$enc=strtolower($enc);
			echo "$enc";
		} elseif ($input=="decrypt") {
			$enc=strtr($uppercase,$key,$plainAlphabet);
			$enc=strtolower($enc);
			echo "$enc";
		}else
			die("Please enter encrypt or decrypt.");
	}


	/* Handles the XOR cipher encrypt and decrypt.
	 * @param $connection a connection.
	 * @param $dat a date.
	 * @param $key a key used to encrypt and decrypt.
	 * @param $cipher a type of cipher user wants to encode the string in.
	 * @param $input an input telling  to encrypt/decrypt.
	 * @param $line a string/ user input  to encrypt/decrypt.
	 */
	function xorcp($connection,$dat,$key,$cipher,$input,$line) {
		$stmt1 = $connection->prepare("INSERT INTO data VALUES (?,?,?)");
		$stmt1->bind_param('sss',$dat,$key,$cipher);

		$stmt1->execute();
		if(!$stmt1){
			die("Oops");
		}

		if ($input=="encrypt") {
			$enc= XORCipher($line,$key);
			echo "$enc";
		}elseif ($input=="decrypt") {
			$enc=XORCipher($line,$key);
			echo "$enc";
		}else
			die("Please enter encrypt or decrypt. ");
	}

	/*  XOR cipher  implementation.
	* @param $data a string to encrypt / decrypt.
	* @param $key a key used to encrypt and decrypt.
	* @return $output an encrpted string based on XOR cipher.
	*/
	function XORCipher($data, $key) {
		$dataLen = strlen($data);
		$keyLen = strlen($key);
		$output = $data;

		for ($i = 0; $i < $dataLen; ++$i) {
			$output[$i] = $data[$i] ^ $key[$i % $keyLen];
		}

		return $output;
	}

	/* Creates a table in the database to store cipher information.
	 * @param $connection a connection.
	 */
	function createTable($connection) {
		$query="SELECT * FROM data";
		$result=$connection->query($query);
		if (empty($result)) {
			$query="CREATE TABLE data(
			tstamp DATETIME,
			input VARCHAR(64),
			cipher VARCHAR(64))";
			$result=$connection->query($query);
		}
		if(!$result){
			echo "Can't create a table";
		}
	}
	/* destroys a session and data after a fixed time.
	 */
	function destroy_session_and_data() {
		$_SESSION = array();
		setcookie(session_name(), '', time() - 2592000, '/');
		session_destroy();
	}

	/* Sanitizes the user inputs and sql.
	 * @param $connection a connection.
	 * @param $string a string to sanitize.
	 * @return returns sanitized value of variable.
	 */
	function mysql_entities_fix_string($connection, $string) {
		return htmlentities(mysql_fix_string($connection, $string));
	}

	/* Sanitizes the user inputs and sql.
	 * @param $connection a connection.
	 * @param $string a string to sanitize.
	 * @return returns sanitized value of variable.
	 */
	function mysql_fix_string($connection, $string) {
		if (get_magic_quotes_gpc()) $string = stripslashes($string);
		return $connection->real_escape_string($string);
	}
	
	$result->close();
	$stmt->close();
	$stmt1->close();
	$connection->close();
	?>
