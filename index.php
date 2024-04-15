<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

$limit = 5;
if (isset($_SESSION['unlock_time']) && $_SESSION['unlock_time'] >= time()) {
    unset($_SESSION['login_attempts']);
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    //Find user from databse using email
    try {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $_SESSION['email'] = $email;

        //Create a template 
        $sql = "SELECT * FROM users WHERE email=?;";

        //Create a prepared statement
        $stmt = mysqli_stmt_init($conn);

        //Prepare the prepared statement
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            $error_message = "index.php/line 26/ mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
            logError($error_message);
        } else {

            //Bind parameters to placeholder
            mysqli_stmt_bind_param($stmt, "s", $email); //s means String

            //Run parameters inside database
            mysqli_stmt_execute($stmt);

            //Get query results
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        logError($error_message);
        header("Location: error.php");
        exit();
    }

    //Send OTP to email. Save OTP and OTP expiry time to database
    try {
        if (validateCredentials($data, $password)) {;
            //Generate OTP
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));
            $subject = "Your OTP for Login";
            $message = "Your OTP is: $otp";

            //Send Verification Email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'xyzabcdefghijklmno99@gmail.com'; //host email 
            $mail->Password = '12345Cat'; //App password of your host email
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->isHTML(true);
            $mail->setFrom('hardbreaker91960@gmail.com', 'SS CW'); //Sender's Email & Name
            $mail->addAddress($email, $name); //Receiver's Email and Name
            $mail->Subject = ("$subject");
            $mail->Body = $message;
            $mail->send();

            //Update user data
            $id = $data['id'];
            $sql = "UPDATE users SET otp=?, otp_expiry=? WHERE id=?;";
            $stmt = mysqli_stmt_init($conn);

            if (!mysqli_stmt_prepare($stmt, $sql)) {
                $error_message = "index.php/line 73/mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
                logError($error_message);
            } else {
                mysqli_stmt_bind_param($stmt, "ssi", $otp, $otp_expiry, $id);
                mysqli_stmt_execute($stmt);
            }
            unset($_SESSION['login_attempts']);

            //Proceed to OTP verificaion
            $_SESSION['temp_user'] = ['id' => $id, 'otp' => $otp];
            header("Location: otp_verification.php");
            exit();
        } else {
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 1;
            } else {
                $_SESSION['login_attempts']++;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        logError($error_message);
        header("Location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="CSS/style.css">
    <?php
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $limit) {
        $_SESSION['login_attempts'] == $limit;
        $_SESSION['unlock_time'] = time() + 5;
    ?>
        <style>
            input[type=submit] {
                display: none;
            }

            #login_attempts {
                display: block;
            }
        </style>
    <?php
    } else {
    ?>
        <style>
            input[type=submit] {
                display: block;
            }

            #login_attempts {
                display: none;
            }
        </style>
    <?php
    }
    ?>
</head>

<body>
    <div id="container">
        <form method="post" action="index.php">
            <?php
            if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] < $limit) {
            ?>
                <p style="color: red;">Invalid credentials. Attempts remaining: <?= $limit - $_SESSION['login_attempts'] ?></p> <br>
            <?php
            }
            ?>
            <p id="login_attempts" style="color: red;">Login attempts exceeded. Try again in 5 seconds.</p> <br>

            <input type="email" name="email" placeholder="email" required><br><br>

            <input type="password" name="password" placeholder="password" required><br><br>

            <input type="submit" name="login" value="Login"><br><br>

            <label>Don't have an account? <a href="registration.php">Sign Up</a></label><br><br>

            <?php echo "<a href='" . $client->createAuthUrl() . "'>Google Login</a>"; ?>
        </form>
    </div>
</body>

</html>