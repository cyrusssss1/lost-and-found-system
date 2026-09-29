<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    try {

        $db = new Database();
        $users = $db->getDatabase()->users;

        $user = $users->findOne([
            "email" => $email
        ]);

        if ($user && password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = (string) $user["_id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "student") {
                header("Location: student/dashboard.php");
                exit;
            }

            if ($user["role"] === "staff") {
                header("Location: staff/dashboard.php");
                exit;
            }

            if ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
                exit;
            }

        } else {

            $message = "Invalid email or password.";

        }

    } catch (Exception $e) {

        $message = "Login error: " . $e->getMessage();

    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>

<body>

<h1>Login</h1>

<?php if ($message !== ""): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>

<form method="POST" action="login.php">

    <label>Email:</label><br>
    <input
        type="email"
        name="email"
        required
    >

    <br><br>

    <label>Password:</label><br>
    <input
        type="password"
        name="password"
        required
    >

    <br><br>

    <button type="submit">Login</button>

</form>

<br>

<a href="register.php">Create User</a>

</body>
</html>