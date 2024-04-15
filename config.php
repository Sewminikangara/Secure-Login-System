<?php
require_once 'vendor/autoload.php';

//Secure Session Management
try {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => 1800, //cookie will be destroyed after 1800 seconds (30 minutes) 
        'domain' => 'localhost', //cookie will only work inside localhost
        'path' => '/',
        'secure' => true,
        'httponly' => true
    ]);
    session_start();
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}

//Regenerate Session ID every 30 minutes
try {
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else {
        $interval = 60 * 30;
        if (time() - $_SESSION['last_regeneration'] >= $interval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}

// init configuration
try {
    $clientID = '83898140080-7j9dfsnerbaddbiro0s83akh2ee8dm32.apps.googleusercontent.com';
    $clientSecret = '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';
    $redirectUri = 'http://localhost:8000/dashboard.php';
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}

// create Client Request to access Google API
try {
    $client = new Google_Client();
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope("email");
    $client->addScope("profile");
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}


//Connect to database
try {
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $database = "two_step_verification";
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}

$conn = mysqli_connect($hostname, $username, $password, $database);

function validateCredentials($data, $password)
{
    return ($data && password_verify($password, $data['password']));
}

function isUserLockedOut()
{
    return isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3;
}

function logError($error_message)
{
    // Specify the file path
    $file_path = 'ErrorLog/error_log.txt';

    // Create a timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Concatenate the timestamp and error message
    $log_entry = $timestamp . ' - ' . $error_message . PHP_EOL;

    // Open the file in append mode or create if it doesn't exist
    $file_handle = fopen($file_path, 'a');

    // Write the log entry to the file
    fwrite($file_handle, $log_entry);

    // Close the file handle
    fclose($file_handle);
}

function verifyPassword($password)
{
    $minLength = 8;
    if (strlen($password) < $minLength) { // Minimum length check
        return "Password should be at least $minLength characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) { // Check for at least one uppercase letter
        return "Password should contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) { // Check for at least one lowercase letter
        return "Password should contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) { // Check for at least one digit
        return "Password should contain at least one digit.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) { // Check for at least one special character
        return "Password should contain at least one special character.";
    } else { // If all checks pass, the password is strong
        return "OK";
    }
}
  
function hashPassword($password)
{
    $options = [
        'cost' => 12
    ];
    return password_hash($password, PASSWORD_BCRYPT, $options); // Hash the password
}