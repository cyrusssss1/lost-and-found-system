<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

$db = new Database();

$users = $db->getDatabase()->users;

$message = "";
$messageType = "";


/* =========================================================
   CREATE STAFF
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {

        $message = "Name, email and password are required.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "error";

    } else {

        $existingUser = $users->findOne([
            "email" => $email
        ]);

        if ($existingUser) {

            $message = "That email is already registered.";
            $messageType = "error";

        } else {

            $profileImage = "";


            /* =================================================
               PROFILE PICTURE → CLOUDINARY
               ================================================= */

            if (
                isset($_FILES["profile_picture"]) &&
                $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES["profile_picture"];

                if ($file["error"] !== UPLOAD_ERR_OK) {

                    $message =
                        "Unable to upload the profile picture. Upload error code: "
                        . $file["error"];

                    $messageType = "error";

                } elseif ($file["size"] > 5 * 1024 * 1024) {

                    $message =
                        "Profile picture must be smaller than 5MB.";

                    $messageType = "error";

                } else {

                    $allowedTypes = [
                        "image/jpeg",
                        "image/png",
                        "image/webp",
                        "image/gif"
                    ];

                    $mimeType = mime_content_type(
                        $file["tmp_name"]
                    );

                    if (!in_array($mimeType, $allowedTypes, true)) {

                        $message =
                            "Please upload a JPG, PNG, WEBP, or GIF image.";

                        $messageType = "error";

                    } else {

                        try {

                            /* =========================================
                               CLOUDINARY CONFIGURATION

                               Environment variables are used first.
                               Local fallback values are used if XAMPP
                               does not load the environment variables.
                               ========================================= */

                            $cloudName =
                                getenv("CLOUDINARY_CLOUD_NAME")
                                ?: "di5zie7e";

                            $apiKey =
                                getenv("CLOUDINARY_API_KEY")
                                ?: "789654872826227";

                            $apiSecret =
                                getenv("CLOUDINARY_API_SECRET")
                                ?: "iKlrC1ZfIuussv5oLXPyBpiFhZ4";


                            if (
                                $cloudName === "YOUR_CLOUD_NAME" ||
                                $apiKey === "YOUR_API_KEY" ||
                                $apiSecret === "YOUR_API_SECRET"
                            ) {

                                throw new Exception(
                                    "Cloudinary is not configured. Please enter your Cloudinary Cloud Name, API Key, and API Secret in create_staff.php."
                                );
                            }


                            Configuration::instance([
                                "cloud" => [
                                    "cloud_name" => $cloudName,
                                    "api_key" => $apiKey,
                                    "api_secret" => $apiSecret
                                ],
                                "url" => [
                                    "secure" => true
                                ]
                            ]);


                            $uploadApi = new UploadApi();


                            $uploadResult = $uploadApi->upload(
                                $file["tmp_name"],
                                [
                                    "folder" => "lost_and_found/staff"
                                ]
                            );


                            $profileImage =
                                $uploadResult["secure_url"] ?? "";


                            if ($profileImage === "") {

                                throw new Exception(
                                    "Cloudinary did not return an image URL."
                                );
                            }

                        } catch (Exception $e) {

                            $message =
                                "Image upload failed: "
                                . $e->getMessage();

                            $messageType = "error";
                        }
                    }
                }
            }


            /* =================================================
               SAVE STAFF
               ================================================= */

            if ($message === "") {

                try {

                    $users->insertOne([

                        "name" => $name,

                        "email" => $email,

                        "password" =>
                            password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            ),

                        "role" => "staff",

                        "profile_picture" => $profileImage,

                        "created_at" =>
                            new MongoDB\BSON\UTCDateTime()

                    ]);


                    $message =
                        "Staff account created successfully.";

                    $messageType = "success";

                    $_POST = [];

                } catch (Exception $e) {

                    $message =
                        "Unable to create staff account: "
                        . $e->getMessage();

                    $messageType = "error";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Create Staff | Admin</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background:
        radial-gradient(
            circle at top right,
            rgba(124,58,237,.13),
            transparent 30%
        ),
        #f5f3f8;
    color: #281b36;
}

.topbar {
    background:
        linear-gradient(
            135deg,
            #24103d,
            #5b2181,
            #7c3aed
        );
    color: white;
    padding: 20px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow:
        0 8px 25px
        rgba(40,20,60,.2);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: rgba(255,255,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand h1 {
    margin: 0;
    font-size: 19px;
}

.brand small {
    opacity: .7;
    font-size: 10px;
    letter-spacing: 1px;
}

.logout {
    color: white;
    text-decoration: none;
    font-size: 18px;
}

.nav {
    background: white;
    border-bottom: 1px solid #e9e2f2;
    padding: 0 35px;
    display: flex;
    overflow-x: auto;
}

.nav a {
    color: #71667c;
    text-decoration: none;
    padding: 17px;
    font-weight: 600;
    white-space: nowrap;
}

.nav a:hover,
.nav a.active {
    color: #7c3aed;
    border-bottom: 3px solid #7c3aed;
}

.container {
    max-width: 850px;
    margin: auto;
    padding: 40px 25px;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h2 {
    margin: 0 0 7px;
    font-size: 30px;
}

.page-title p {
    margin: 0;
    color: #81758c;
}

.card {
    background: white;
    border: 1px solid #eee8f5;
    border-radius: 24px;
    padding: 30px;
    box-shadow:
        0 12px 35px
        rgba(40,20,60,.07);
}

.message {
    padding: 14px 17px;
    border-radius: 13px;
    margin-bottom: 20px;
    font-weight: 700;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #51465d;
}

input {
    width: 100%;
    padding: 13px 15px;
    border: 1px solid #ddd6e5;
    border-radius: 12px;
    background: #fcfbfd;
    font-size: 14px;
    outline: none;
}

input:focus {
    border-color: #7c3aed;
    box-shadow:
        0 0 0 3px
        rgba(124,58,237,.10);
}

input[type="file"] {
    padding: 10px;
}

.info {
    margin-top: -5px;
    margin-bottom: 20px;
    color: #81758c;
    font-size: 12px;
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.create-submit {
    flex: 1;
    border: 0;
    padding: 14px;
    border-radius: 13px;
    background:
        linear-gradient(
            135deg,
            #6d28a8,
            #7c3aed
        );
    color: white;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    box-shadow:
        0 8px 20px
        rgba(124,58,237,.22);
}

.cancel-btn {
    padding: 14px 20px;
    border-radius: 13px;
    background: #f3edf8;
    color: #5b4b68;
    text-decoration: none;
    font-weight: 700;
}

@media(max-width:600px) {

    .topbar {
        padding: 18px;
    }

    .nav {
        padding: 0 10px;
    }

    .container {
        padding: 25px 15px;
    }

    .card {
        padding: 22px;
    }

    .actions {
        flex-direction: column;
    }

    .cancel-btn {
        text-align: center;
    }

}

</style>

</head>

<body>

<header class="topbar">

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-crown"></i>
        </div>

        <div>

            <h1>Admin Control Center</h1>

            <small>
                CREATE STAFF
            </small>

        </div>

    </div>

    <a
        href="../logout.php"
        class="logout"
    >
        <i class="fa-solid fa-right-from-bracket"></i>
    </a>

</header>

<nav class="nav">

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="reports.php">
        <i class="fa-solid fa-file-lines"></i>
        Reports
    </a>

    <a href="claim.php">
        <i class="fa-solid fa-hand-holding"></i>
        Claims
    </a>

    <a href="manage_staff.php">
        <i class="fa-solid fa-users-gear"></i>
        Staff
    </a>

    <a
        href="create_staff.php"
        class="active"
    >
        <i class="fa-solid fa-user-plus"></i>
        Create Staff
    </a>

</nav>

<main class="container">

    <div class="page-title">

        <h2>Create Staff Account</h2>

        <p>
            Create a new staff account for the Lost and Found System.
        </p>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?php echo htmlspecialchars($messageType); ?>">

            <i class="fa-solid
                <?php
                echo $messageType === "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation";
                ?>">
            </i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <div class="card">

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-group">

                <label>
                    Staff Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["name"] ?? ""
                        );
                    ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Staff Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["email"] ?? ""
                        );
                    ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Profile Picture
                </label>

                <input
                    type="file"
                    name="profile_picture"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                >

            </div>

            <div class="info">

                <i class="fa-solid fa-cloud-arrow-up"></i>

                Profile pictures are securely uploaded to Cloudinary.
                Maximum size: 5MB.

            </div>

            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    required
                >

            </div>

            <div class="actions">

                <a
                    href="manage_staff.php"
                    class="cancel-btn"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Cancel
                </a>

                <button
                    type="submit"
                    class="create-submit"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Create Staff
                </button>

            </div>

        </form>

    </div>

</main>

</body>
</html>