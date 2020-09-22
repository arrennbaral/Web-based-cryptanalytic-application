<?php

require_once "login.php";
$connection=new mysqli($hn,$un,$pw,$db);
//if any errors
if (!$connection) {
	die( "Could not connect");
}


Userlogin($connection);

/* Authenticates user and directs user to another page.
 * @param $connection a connection.
 */
function Userlogin($connection) {
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $un_temp = mysql_entities_fix_string($connection, $_SERVER['PHP_AUTH_USER']);
        $pw_temp = mysql_entities_fix_string($connection, $_SERVER['PHP_AUTH_PW']);
        $query = "SELECT * FROM users WHERE username = '$un_temp'";
        $result = $connection->query($query);
        if (!$result) die($connection->error);
        elseif ($rows = $result->num_rows) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $result->close();
            $salt1 = $row[salt1];
            $salt2 = $row[salt2];
            $token = hash('ripemd128', "$salt1$pw_temp$salt2");
            if ($token == $row[password]) {
                session_start();
                $_SESSION['username'] = $un_temp;
                $_SESSION['password'] = $pw_temp;
                echo "Welcome $row[username] </br>";
                header("location: continue.php");
            }
            else{
                die ("<p>Invalid Unsername or Password. Please Sign up if you have not.<a href=signup.php> SignUp</a></p>");
            }
        } else die("Invalid Username or Password.Please Sign up if you have not.<a href=signup.php> SignUp</a></p></br>");
    } else {
        header("WWW-Authenticate: Basic realm=\"Restricted Section\"");
        header("HTTP\ 1.0 401 Unauthorized");
        die("Please enter your username and password </br>");
    }

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


$connection->close();
?>
