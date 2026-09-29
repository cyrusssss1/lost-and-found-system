<?php

require_once __DIR__ . '/vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $db = new Database();
        $users = $db->getDatabase()->users;

        $existingUser = $users->findOne([
            "email" => $email
        ]);

        if ($existingUser) {

            $message = "An account with that email already exists.";

        } else {

            $users->insertOne([
                "name" => $name,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role" => "student"
            ]);

            $message = "Student account created successfully!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Student Account</title>

    <link rel="stylesheet" href="style.css">

</head>

<body class="register-page">


<!-- ================= NAVBAR ================= -->

<nav class="navbar">

    <div class="brand">

        <div class="brand-icon">
            🔎
        </div>

        <div class="brand-text">
            Lost & Found <strong>System</strong>
        </div>

    </div>


    <div class="nav-links">

        <a href="index.php" class="nav-link">
            🏠 Home
        </a>

        <a href="index.php" class="nav-login">
            ↩ Back to Login
        </a>

    </div>

</nav>


<!-- ================= REGISTER CONTAINER ================= -->

<main class="register-container">


    <!-- LEFT SIDE -->

    <section class="register-info">

        <div class="register-illustration">

            <div class="register-box">
                📦
            </div>

            <div class="register-search">
                🔎
            </div>

            <div class="register-tag">
                🏷️
            </div>

        </div>


        <h1>
            Join the
            <span>Lost & Found</span>
            Community
        </h1>


        <p>
            Create your student account and start reporting,
            finding, and claiming lost belongings.
        </p>


        <div class="register-benefits">

            <div class="benefit">
                <span>🛡️</span>
                <div>
                    <strong>Safe & Secure</strong>
                    <small>Your account information is protected.</small>
                </div>
            </div>


            <div class="benefit">
                <span>🔎</span>
                <div>
                    <strong>Find Lost Items</strong>
                    <small>Search for belongings reported by others.</small>
                </div>
            </div>


            <div class="benefit">
                <span>🤝</span>
                <div>
                    <strong>Help Others</strong>
                    <small>Help return lost items to their owners.</small>
                </div>
            </div>

        </div>

    </section>


    <!-- RIGHT SIDE -->

    <section class="register-section">

        <div class="register-card">


            <!-- HEADER -->

            <div class="register-header">

                <div class="register-user-icon">
                    👤+
                </div>

                <h2>
                    Create Student Account
                </h2>

                <p>
                    Fill in your information to get started.
                </p>

            </div>


            <!-- MESSAGE -->

            <?php if ($message !== ""): ?>

                <div class="register-message
                    <?php
                    echo (
                        strpos($message, "successfully") !== false
                    )
                    ? "success"
                    : "error";
                    ?>">

                    <span>
                        <?php
                        echo (
                            strpos($message, "successfully") !== false
                        )
                        ? "✓"
                        : "!";
                        ?>
                    </span>

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form method="POST" action="register.php">


                <!-- NAME -->

                <div class="register-form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="register-input">

                        <span>👤</span>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="register-form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="register-input">

                        <span>✉️</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="register-form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="register-input">

                        <span>🔒</span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            required
                        >

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="register-form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="register-input">

                        <span>🔐</span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            required
                        >

                    </div>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="register-button"
                >

                    <span>👤+</span>

                    Create Account

                </button>


            </form>


            <!-- LOGIN LINK -->

            <div class="register-login">

                <span>
                    Already have an account?
                </span>

                <a href="index.php">
                    Login here
                </a>

            </div>


        </div>

    </section>

</main>


<!-- ================= FOOTER ================= -->

<footer>

    <p>
        © <?php echo date("Y"); ?>
        Lost & Found Management System
    </p>

    <p>
        Helping reunite people with their belongings.
    </p>

</footer>


</body>
</html>