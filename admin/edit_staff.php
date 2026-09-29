<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;
use MongoDB\BSON\ObjectId;

$db = new Database();

$users = $db->getDatabase()->users;

$message = "";
$messageType = "";

$staffId = $_GET["id"] ?? "";

if ($staffId === "") {
    header("Location: manage_staff.php");
    exit;
}


/* =========================================================
   GET STAFF
   ========================================================= */

try {

    $staff = $users->findOne([
        "_id" => new ObjectId($staffId),
        "role" => "staff"
    ]);

} catch (Exception $e) {

    $staff = null;
}


if (!$staff) {
    header("Location: manage_staff.php");
    exit;
}


/* =========================================================
   UPDATE STAFF
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    if ($name === "" || $email === "") {

        $message = "Name and email are required.";
        $messageType = "error";

    } elseif (
        $password !== "" &&
        $password !== $confirmPassword
    ) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        /* =================================================
           CHECK EMAIL
           ================================================= */

        $existingUser = $users->findOne([
            "email" => $email,
            "_id" => [
                '$ne' => $staff["_id"]
            ]
        ]);


        if ($existingUser) {

            $message =
                "That email is already registered to another account.";

            $messageType = "error";

        } else {

            $updateData = [

                "name" => $name,

                "email" => $email

            ];


            /* =================================================
               PASSWORD
               ================================================= */

            if ($password !== "") {

                $updateData["password"] =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );
            }


            /* =================================================
               PROFILE PICTURE
               ================================================= */

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


                    if (
                        move_uploaded_file(
                            $file["tmp_name"],
                            $targetPath
                        )
                    ) {

                        $newProfileImage =
                            "uploads/staff/" .
                            $fileName;


                        $updateData["profile_picture"] =
                            $newProfileImage;


                        /* DELETE OLD PROFILE IMAGE */

                        $oldProfile =
                            $staff["profile_picture"] ?? "";


                        if ($oldProfile !== "") {

                            $oldPath =
                                __DIR__ .
                                "/../" .
                                ltrim(
                                    $oldProfile,
                                    "/"
                                );


                            if (
                                file_exists($oldPath)
                            ) {

                                @unlink($oldPath);
                            }
                        }

                    } else {

                        $message =
                            "Unable to upload the new profile picture.";

                        $messageType = "error";

                    }
                }
            }


            /* =================================================
               SAVE CHANGES
               ================================================= */

            if ($message === "") {

                $users->updateOne(

                    [
                        "_id" => $staff["_id"],
                        "role" => "staff"
                    ],

                    [
                        '$set' => $updateData
                    ]

                );


                $message =
                    "Staff account updated successfully.";

                $messageType = "success";


                /* REFRESH STAFF DATA */

                $staff = $users->findOne([
                    "_id" => $staff["_id"],
                    "role" => "staff"
                ]);

            }

        }

    }

}


/* =========================================================
   PROFILE IMAGE
   ========================================================= */

$profileImage =
    $staff["profile_picture"] ?? "";


if (
    $profileImage !== "" &&
    !str_starts_with($profileImage, "../")
) {

    $profileImage =
        "../" .
        ltrim(
            $profileImage,
            "/"
        );
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

<title>Edit Staff | Admin</title>


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


/* TOPBAR */

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

    margin: 0 0 7px;

    font-size: 30px;
}

.page-title p {

    margin: 0;

    color: #81758c;
}


/* CARD */

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


/* PROFILE */

.profile-header {

    display: flex;

    align-items: center;

    gap: 20px;

    padding-bottom: 25px;

    margin-bottom: 25px;

    border-bottom:
        1px solid #eee8f5;
}

.profile-picture {

    width: 100px;

    height: 100px;

    border-radius: 50%;

    object-fit: cover;

    border:
        5px solid #ede9fe;

    box-shadow:
        0 6px 20px
        rgba(124,58,237,.15);
}

.profile-placeholder {

    width: 100px;

    height: 100px;

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

    font-size: 35px;

    border:
        5px solid #ede9fe;
}

.profile-header h3 {

    margin: 0 0 5px;

    font-size: 20px;
}

.profile-header p {

    margin: 0;

    color: #81758c;

    font-size: 13px;
}


/* FORM */

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

    border:
        1px solid #ddd6e5;

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


/* PASSWORD INFO */

.password-info {

    margin-top: -10px;

    margin-bottom: 20px;

    color: #81758c;

    font-size: 12px;
}


/* BUTTONS */

.actions {

    display: flex;

    gap: 12px;

    margin-top: 25px;
}

.save-btn {

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


/* RESPONSIVE */

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

    .profile-header {

        align-items: flex-start;
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

            <h1>
                Admin Control Center
            </h1>

            <small>
                EDIT STAFF
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


    <a
        href="manage_staff.php"
        class="active"
    >

        <i class="fa-solid fa-users-gear"></i>

        Staff

    </a>


    <a href="create_staff.php">

        <i class="fa-solid fa-user-plus"></i>

        Create Staff

    </a>

</nav>


<main class="container">


    <div class="page-title">

        <h2>
            Edit Staff Account
        </h2>

        <p>
            Update this staff member's account information.
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


        <div class="profile-header">


            <?php if ($profileImage !== ""): ?>

                <img
                    class="profile-picture"
                    src="<?php
                        echo htmlspecialchars(
                            $profileImage
                        );
                    ?>"
                    alt="Staff profile"
                >

            <?php else: ?>

                <div class="profile-placeholder">

                    <i class="fa-solid fa-user"></i>

                </div>

            <?php endif; ?>


            <div>

                <h3>

                    <?php
                    echo htmlspecialchars(
                        $staff["name"]
                    );
                    ?>

                </h3>

                <p>

                    <i class="fa-solid fa-user-shield"></i>

                    Staff Account

                </p>

            </div>


        </div>


        <form
            method="POST"
            action="edit_staff.php?id=<?php echo htmlspecialchars($staffId); ?>"
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
                            $staff["name"] ?? ""
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
                            $staff["email"] ?? ""
                        );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Change Profile Picture
                </label>

                <input
                    type="file"
                    name="profile_picture"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                >

            </div>


            <div class="form-group">

                <label>
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Leave blank to keep current password"
                >

            </div>


            <div class="form-group">

                <label>
                    Confirm New Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Leave blank if password is unchanged"
                >

            </div>


            <p class="password-info">

                <i class="fa-solid fa-circle-info"></i>

                Leave the password fields empty if you don't want to change
                the current password.

            </p>


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
                    class="save-btn"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Changes

                </button>


            </div>


        </form>


    </div>


</main>


</body>

</html>