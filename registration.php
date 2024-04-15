<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {

    //Escape special characters in inputs
    try {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        logError($error_message);
        header("Location: error.php");
        exit();
    }

    try {
        if (!($password === $confirm_password)) {
            $password_error = "Passwords does not match";
        } else {
            $password_error = verifyPassword($password);

            if ($password_error === "OK") {
                unset($password_error);
                $hashedPassword = hashPassword($password);

                //Checking if email already exists in the database
                $sql = "SELECT * FROM users WHERE email=?;";
                $stmt = mysqli_stmt_init($conn);
                if (!mysqli_stmt_prepare($stmt, $sql)) {
                    $error_message = "registration.php/line 22/mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
                    logError($error_message);
                } else {
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $data = mysqli_fetch_assoc($result);
                }

                if ($data['email'] == $email) {
                    $email_error = "Email already in use";
                } else {
                    $sql = "INSERT INTO users (username, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?);";
                    $stmt = mysqli_stmt_init($conn);

                    if (!mysqli_stmt_prepare($stmt, $sql)) {
                        $error_message = "registration.php/line 40/mysqli_stmt_prepare(\$stmt, \$sql) FAILED\n";
                        logError($error_message);
                    } else {
                        mysqli_stmt_bind_param($stmt, "sssss", $username, $first_name, $last_name, $email, $hashedPassword);
                    }

                    if (mysqli_stmt_execute($stmt)) {
?>
                        <script>
                            alert("Registration Successful.");

                            function navigateToPage() {
                                window.location.href = 'index.php';
                            }
                            window.onload = function() {
                                navigateToPage();
                            }
                        </script>
<?php
                    } else {
                        echo "<script> alert('Registration Failed. Try Again');</script>";
                    }
                }
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
    <title>Register</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        #container {
            margin-bottom: 10vh;
        }

        input[type=password]::-webkit-input-placeholder {
            color: rgb(176, 176, 176);
            text-transform: none;
        }
    </style>
</head>

<body>
    <div id="container">
        <form method="post" action="registration.php">
            <?php
            if (isset($email_error)) {
            ?>
                <p style="color: red;"><?= $email_error ?></p> <br>
            <?php
            }
            ?>
            <?php
            if (isset($password_error)) {
            ?>
                <p style="color: red;"><?= $password_error ?></p> <br>
            <?php
            }
            ?>
            <input type="text" name="username" placeholder="Enter Username" required><br><br>

            <input type="text" name="first_name" placeholder="Enter First Name" required><br><br>

            <input type="text" name="last_name" placeholder="Enter Last Name" required><br><br>

            <input type="email" name="email" placeholder="Enter Your Email" required><br><br>

            <input type="password" name="password" placeholder="ENTER PASSWORD (E.G. Z_454wi343)" required><br><br>

            <input type="password" name="confirm_password" placeholder="CONFIRM PASSWORD" required><br><br>

            <input type="submit" name="register" value="Register"><br><br>

            <label>Already have an account? <a href="index.php">Login</a></label>
        </form>
    </div>

</body>

</html>

<!-- 
    $sql = "INSERT INTO users (username, first_name, last_name, email, password) VALUES (
    'x', 'x', 'x', 'x', 'x'); drop table users;--,
);";
\'x\', \'x\', \'x\', \'x\', \'x\'); drop table users;--
 -->

