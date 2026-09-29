<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lost & Found Management System</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

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

        <a href="index.php" class="nav-link active">
            🏠 Home
        </a>

        <a href="#about" class="nav-link">
            ℹ️ About
        </a>

        <a href="#features" class="nav-link">
            📦 Features
        </a>

        <a href="register.php" class="nav-login">
            👤 Create User
        </a>

    </div>

</nav>


<!-- ================= MAIN ================= -->

<main class="main-container">


    <!-- ================= LEFT SIDE ================= -->

    <section class="welcome-section">


        <!-- Logo / Illustration -->

        <div class="hero-icon">

            <div class="box-icon">
                📦
            </div>

            <div class="search-icon">
                🔎
            </div>

            <div class="tag-icon">
                🏷️
            </div>

        </div>


        <!-- Title -->

        <h1>

            Lost and Found

            <span>
                Management System
            </span>

        </h1>


        <!-- Description -->

        <p class="hero-description">

            Reuniting people with their lost belongings.

            <br>

            <strong>
                Simple. Secure. Reliable.
            </strong>

        </p>


        <!-- ================= FEATURES ================= -->

        <div class="features" id="features">


            <!-- SAFE -->

            <div class="feature">

                <div class="feature-icon blue">
                    🛡️
                </div>

                <h3>
                    Safe
                </h3>

                <p>
                    Your information is protected
                </p>

            </div>


            <!-- EFFICIENT -->

            <div class="feature">

                <div class="feature-icon purple">
                    👥
                </div>

                <h3>
                    Efficient
                </h3>

                <p>
                    Fast and easy process
                </p>

            </div>


            <!-- COMMUNITY -->

            <div class="feature">

                <div class="feature-icon green">
                    ❤️
                </div>

                <h3>
                    Community
                </h3>

                <p>
                    Help return lost items
                </p>

            </div>


        </div>


        <!-- ================= DECORATION ================= -->

        <div class="campus">

            <div class="campus-building">

                <div class="roof"></div>

                <div class="building-body">

                    <div class="window"></div>

                    <div class="window"></div>

                    <div class="window"></div>

                    <div class="door"></div>

                </div>

            </div>


            <div class="tree tree-one"></div>

            <div class="tree tree-two"></div>

            <div class="tree tree-three"></div>

        </div>


    </section>



    <!-- ================= LOGIN ================= -->

    <section class="login-section">


        <div class="login-card">


            <!-- LOGIN HEADER -->

            <div class="login-header">


                <div class="user-icon">
                    👤
                </div>


                <div>

                    <h2>
                        Welcome Back!
                    </h2>

                    <p>
                        Log in to your account to continue.
                    </p>

                </div>


            </div>



            <!-- LOGIN FORM -->

            <form method="POST" action="login.php">


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            ✉️
                        </span>


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

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>

                </div>



                <!-- OPTIONS -->

                <div class="login-options">


                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        <span>
                            Remember me
                        </span>

                    </label>


                    <a href="#" class="forgot">
                        Forgot password?
                    </a>


                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <span>
                        ↪
                    </span>

                    Login

                </button>


            </form>



            <!-- REGISTER DIVIDER -->

            <div class="register-divider">

                <span></span>

                <p>
                    Don't have an account?
                </p>

                <span></span>

            </div>



            <!-- CREATE USER -->

            <a
                href="register.php"
                class="create-user"
            >

                <span>
                    👤+
                </span>

                Create User

            </a>


        </div>


    </section>


</main>



<!-- ================= ABOUT ================= -->

<section
    id="about"
    style="
        max-width: 1100px;
        margin: 0 auto 60px;
        padding: 30px;
        text-align: center;
    "
>

    <h2>
        About Our System
    </h2>

    <p style="color:#64748b;">

        The Lost and Found Management System helps students
        and staff report, search, and claim lost or found items
        within the organization.

    </p>

</section>



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