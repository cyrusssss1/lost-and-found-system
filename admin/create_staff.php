<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

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


    /* -------------------------
       BASIC VALIDATION
       ------------------------- */

    if ($name === "" || $email === "" || $password === "") {

        $message = "Please complete all required fields.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        $db = new Database();

        $users = $db->getDatabase()->users;


        /* -------------------------
           CHECK EMAIL
           ------------------------- */

        $existingUser = $users->findOne([
            "email" => $email
        ]);


        if ($existingUser) {

            $message = "That email is already registered.";
            $messageType = "error";

        } else {


            /* -------------------------
               PROFILE IMAGE
               ------------------------- */

            $profileImage = "";


            if (
                isset($_FILES["profile_picture"]) &&
                $_FILES["profile_picture"]["error"] === UPLOAD_ERR_OK
            ) {

                $file = $_FILES["profile_picture"];

                $allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp",
                    "image/gif"
                ];


                if (!in_array($file["type"], $allowedTypes)) {

                    $message =
                        "Please upload a JPG, PNG, WEBP, or GIF image.";

                    $messageType = "error";

                } elseif ($file["size"] > 5 * 1024 * 1024) {

                    $message =
                        "Profile picture must be smaller than 5MB.";

                    $messageType = "error";

                } else {


                    $uploadDirectory =
                        __DIR__ . "/../uploads/staff/";

                    if (!is_dir($uploadDirectory)) {

                        mkdir(
                            $uploadDirectory,
                            0777,
                            true
                        );

                    }


                    $extension =
                        strtolower(
                            pathinfo(
                                $file["name"],
                                PATHINFO_EXTENSION
                            )
                        );


                    $fileName =
                        "staff_" .
                        uniqid() .
                        "." .
                        $extension;


                    $targetPath =
                        $uploadDirectory .
                        $fileName;


                    if (move_uploaded_file(
                        $file["tmp_name"],
                        $targetPath
                    )) {

                        $profileImage =
                            "uploads/staff/" .
                            $fileName;

                    } else {

                        $message =
                            "Unable to upload the profile picture.";

                        $messageType = "error";

                    }

                }

            }


            /* -------------------------
               CREATE ACCOUNT
               ------------------------- */

            if ($message === "") {

                $users->insertOne([

                    "name" =>
                        $name,

                    "email" =>
                        $email,

                    "password" =>
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),

                    "role" =>
                        "staff",

                    "profile_picture" =>
                        $profileImage,

                    "created_at" =>
                        new MongoDB\BSON\UTCDateTime()

                ]);


                $message =
                    "Staff account created successfully!";

                $messageType = "success";

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

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at top right,
            rgba(124,58,237,.13),
            transparent 30%
        ),
        #f5f3f8;

    color: #281b36;
}


/* TOP */

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

    background:
        rgba(255,255,255,.15);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
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


/* NAV */

.nav {

    background: white;

    border-bottom:
        1px solid #e9e2f2;

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

    border-bottom:
        3px solid #7c3aed;
}


/* CONTENT */

.container {

    max-width: 850px;

    margin: auto;

    padding: 40px 25px;
}

.page-title {

    margin-bottom: 25px;
}

.page-title h2 {

    margin: 0 0 6px;

    font-size: 30px;
}

.page-title p {

    margin: 0;

    color: #81758c;
}


/* FORM CARD */

.card {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 24px;

    padding: 30px;

    box-shadow:
        0 12px 35px
        rgba(40,20,60,.07);
}

.profile-preview {

    display: flex;

    align-items: center;

    gap: 18px;

    margin-bottom: 28px;
}

.preview {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    object-fit: cover;

    background: #eee8f5;

    border:
        4px solid #ede9fe;

    display: none;
}

.preview-placeholder {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #ede9fe,
            #ddd6fe
        );

    display: flex;

    align-items: center;

    justify-content: center;

    color: #7c3aed;

    font-size: 32px;
}

.form-group {

    margin-bottom: 20px;
}

label {

    display: block;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 8px;

    color: #51465d;
}

input {

    width: 100%;

    padding: 13px 15px;

    border:
        1px solid #ddd6e5;

    border-radius: 12px;

    outline: none;

    font-size: 14px;

    background: #fcfbfd;
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


/* BUTTON */

.create-btn {

    width: 100%;

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

.create-btn:hover {

    transform:
        translateY(-1px);
}


/* MESSAGE */

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


/* BACK */

.back {

    display: inline-block;

    margin-top: 20px;

    color: #7c3aed;

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

            <small>STAFF MANAGEMENT</small>

        </div>

    </div>


    <a
        class="logout"
        href="../logout.php"
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
        class="active"
        href="create_staff.php"
    >
        <i class="fa-solid fa-user-plus"></i>
        Create Staff
    </a>

</nav>


<main class="container">


    <div class="page-title">

        <h2>
            Create Staff Account
        </h2>

        <p>
            Add a new staff member to the Lost & Found system.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div
            class="message
            <?php
            echo $messageType === "success"
                ? "success"
                : "error";
            ?>"
        >

            <i class="fa-solid
            <?php
            echo $messageType === "success"
                ? "fa-circle-check"
                : "fa-circle-exclamation";
            ?>"></i>

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <div class="card">


        <form
            method="POST"
            action="create_staff.php"
            enctype="multipart/form-data"
        >


            <div class="profile-preview">

                <div class="preview-placeholder" id="placeholder">

                    <i class="fa-solid fa-user"></i>

                </div>

                <img
                    id="preview"
                    class="preview"
                    alt="Profile preview"
                >

                <div>

                    <strong>
                        Staff Profile Picture
                    </strong>

                    <p style="color:#81758c;margin:5px 0;font-size:12px;">
                        JPG, PNG, WEBP or GIF • Maximum 5MB
                    </p>

                </div>

            </div>


            <div class="form-group">

                <label>
                    Staff Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter staff name"
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
                    placeholder="staff@example.com"
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


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create password"
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
                    placeholder="Confirm password"
                    required
                >

            </div>


            <button
                type="submit"
                class="create-btn"
            >

                <i class="fa-solid fa-user-plus"></i>

                Create Staff Account

            </button>


        </form>

    </div>


    <a
        href="manage_staff.php"
        class="back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Manage Staff

    </a>


</main>


<script>

const fileInput =
    document.querySelector(
        'input[name="profile_picture"]'
    );

const preview =
    document.getElementById("preview");

const placeholder =
    document.getElementById("placeholder");


fileInput.addEventListener(
    "change",
    function() {

        const file =
            this.files[0];

        if (!file) {

            preview.style.display = "none";

            placeholder.style.display = "flex";

            return;
        }

        const reader =
            new FileReader();

        reader.onload =
            function(e) {

                preview.src =
                    e.target.result;

                preview.style.display =
                    "block";

                placeholder.style.display =
                    "none";

            };

        reader.readAsDataURL(file);

    }
);

</script>


</body>

</html>