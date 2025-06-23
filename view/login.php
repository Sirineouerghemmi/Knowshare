<?php
session_start();
include("../config/database.php");
include("../controller/traitement.php");


$result = Login($cnx);
$error = $result['error'] ?? '';



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Form</title>
    <link rel="stylesheet" href="login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <div class="form-box login">
            <form action="" method="POST">
                <h1>Login</h1>
                <div class="input-box">
                <input type="email" name="email" placeholder="email" required>
                <i class='bx bxs-user'></i>
                
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="password" required>
                    <i class='bx bxs-lock-alt' ></i>
                </div>
                

                
                <div class="forgot-link">
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" class="btn" name="login">Login</button>
                <?php if (!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
                <p>or login with social platforms</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google' ></i></a>
                    <a href="#"><i class='bx bxl-facebook' ></i></a>
                    <a href="#"><i class='bx bxl-github' ></i></a>
                    <a href="#"><i class='bx bxl-linkedin' ></i></a>
                </div>
            </form>
        </div>

        <div class="form-box register">
            <form action="login.php" method="POST">
                <h1>Sign Up</h1>
                <div class="input-box">
                    <input type="text" name="user_name" placeholder="user_name" required>
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="email" required>
                    <i class='bx bxs-envelope' ></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="password" required>
                    <i class='bx bxs-lock-alt' ></i>
                </div>
                <button type="submit" class="btn" name="sign_up">Register</button>
                
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello, Welcome!</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn">Register</button>
            </div>

            <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Already have an account?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>