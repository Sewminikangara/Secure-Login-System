<?php
require_once 'config.php';
require_once 'vendor/autoload.php';


// authenticate code from Google OAuth Flow
try {
    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token['access_token']);

        // get profile info
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        //Save the user data into session
        $_SESSION['user_id'] = $google_account_info['id'];
    } else {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    logError($error_message);
    header("Location: error.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f0f0; /* Set a light gray background color */
            padding: 20px;
            color: #333; /* Set the text color to dark gray */
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin-bottom: 20px;
            color: #007bff; /* Set the heading color to blue */
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin-bottom: 10px;
        }

        img {
            display: block;
            margin: 0 auto;
            border-radius: 50%; /* Make the profile picture round */
            margin-bottom: 20px;
        }

        a {
            display: block;
            text-align: center;
            color: #007bff; /* Set the link color to blue */
        }

        a:hover {
            text-decoration: underline; /* Add underline on hover */
        }
    </style>
</head>
<body>
    <h1>Welcome to the Dashboard!</h1>
    <?php
    if (isset($google_account_info['id'])) {
        $first_name = htmlspecialchars($google_account_info['givenName']);
        $last_name = htmlspecialchars($google_account_info['familyName']);
        $email = htmlspecialchars($google_account_info['email']);
    ?>
        <img src="<?= $google_account_info['picture'] ?>" alt="Profile Picture" width="150px" height="150px">
        <ul>
            <li><strong>First Name:</strong> <?= $first_name ?></li>
            <li><strong>Last Name:</strong> <?= $last_name ?></li>
            <li><strong>Email Address:</strong> <?= $email ?></li>
        </ul>
    <?php
    } else {
        $id = $_SESSION['user_id'];

        $sql = "SELECT * FROM users WHERE id=?;";
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            $error_message = "dashboard.php/line 62/mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
            logError($error_message);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);

            $first_name = htmlspecialchars($data['first_name']);
            $last_name = htmlspecialchars($data['last_name']);
            $email = htmlspecialchars($data['email']);
        }
    ?>
        <ul>
            <li><strong>First Name:</strong> <?= $first_name ?></li>
            <li><strong>Last Name:</strong> <?= $last_name ?></li>
            <li><strong>Email Address:</strong> <?= $email ?></li>
        </ul>
    <?php
    }
    ?>
    <br><br><br>
    <a href="logout.php">Logout</a>
</body>

</html>
