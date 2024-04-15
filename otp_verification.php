<?php
include 'config.php';
if (!isset($_SESSION['temp_user'])) {
    header("Location: index.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Grab variables necessary for OTP verifiaction
    try {
        $user_otp = $_POST['otp'];
        $stored_otp = $_SESSION['temp_user']['otp'];
        $user_id = $_SESSION['temp_user']['id'];
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        logError($error_message);
        header("Location: error.php");
        exit();
    }

    //Retreive users based on id and OTP 
    try {
        $sql = "SELECT * FROM users WHERE id=? AND otp=?;";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            $error_message = "otp_verification.php/line 14/mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
            logError($error_message);
        } else {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $user_otp);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        logError($error_message);
        header("Location: error.php");
        exit();
    }

    //Verify OTP
    try {
        if ($data) {
            $otp_expiry = strtotime($data['otp_expiry']);
            if ($otp_expiry >= time()) {
                $_SESSION['user_id'] = $data['id'];
                unset($_SESSION['temp_user']);
                header("Location: dashboard.php");
                exit();
            } else {
?>
                <script>
                    alert("OTP has expired. Please try again.");

                    function navigateToPage() {
                        window.location.href = 'index.php';
                    }
                    window.onload = function() {
                        navigateToPage();
                    }
                </script>
<?php
            }
        } else {
            echo "<script> alert('Invalid OTP. Please try again.');</script>";
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
    <title>OTP Verification</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        #container {
            text-align: center;
        }
    </style>
</head>

<body>
    <div id="container">
        <h1>Two-Factor Authentication</h1><br>
        <p>Check your email address for the OTP.</p><br>
        <form method="post" action="otp_verification.php">
            <input type="number" name="otp" pattern="\d{6}" placeholder="Six-Digit OTP" required><br><br>
            <button type="submit">Verify OTP</button>
        </form>
    </div>
</body>

</html>