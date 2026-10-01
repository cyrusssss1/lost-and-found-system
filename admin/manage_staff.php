```php
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
$reports = $db->getDatabase()->reports;
$claims = $db->getDatabase()->claims;

$message = "";


/* =========================================================
   DELETE STAFF
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $staffId = $_POST["staff_id"] ?? "";

    if ($staffId !== "") {

        try {

            $staff = $users->findOne([
                "_id" => new ObjectId($staffId),
                "role" => "staff"
            ]);

            if ($staff) {

                $users->deleteOne([
                    "_id" => $staff["_id"],
                    "role" => "staff"
                ]);

                $message =
                    "Staff account deleted successfully.";

            } else {

                $message =
                    "Staff account not found.";

            }

        } catch (Exception $e) {

            $message =
                "Unable to delete staff account.";

        }
    }
}


/* =========================================================
   GET ALL STAFF
   ========================================================= */

$staffUsers = $users->find(
    [
        "role" => "staff"
    ],
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Manage Staff | Admin</title>

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
            rgba(124,58,237,.14),
            transparent 30%
        ),
        #f5f3f8;

    color: #291b38;
}


/* =========================================================
   TOPBAR
   ========================================================= */

.topbar {

    background:
        linear-gradient(
            135deg,
            #21102f,
            #4c1d70,
            #7c3aed
        );

    color: white;

    padding: 20px 35px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 8px 30px
        rgba(40,20,60,.20);
}


.brand {

    display: flex;

    align-items: center;

    gap: 14px;
}


.brand-icon {

    width: 46px;
    height: 46px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.14);

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

    font-size: 19px;

    text-decoration: none;

    opacity: .9;
}


/* =========================================================
   NAVIGATION
   ========================================================= */

.nav {

    background: white;

    border-bottom:
        1px solid #e9e2f2;

    padding: 0 35px;

    display: flex;

    overflow-x: auto;
}


.nav a {

    color: #766a80;

    text-decoration: none;

    padding: 17px 18px;

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;

    transition: .2s;
}


.nav a:hover {

    color: #7c3aed;

}


.nav a.active {

    color: #7c3aed;

    border-bottom:
        3px solid #7c3aed;
}


/* =========================================================
   MAIN
   ========================================================= */

.container {

    max-width: 1250px;

    margin: auto;

    padding: 40px 25px;
}


.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}


.page-header h2 {

    margin: 0 0 6px;

    font-size: 30px;
}


.page-header p {

    margin: 0;

    color: #82758e;

    font-size: 14px;
}


/* =========================================================
   CREATE BUTTON
   ========================================================= */

.create-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 12px 18px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #6d28a8,
            #7c3aed
        );

    color: white;

    text-decoration: none;

    font-size: 13px;

    font-weight: 800;

    box-shadow:
        0 8px 20px
        rgba(124,58,237,.20);

    transition: .2s;
}


.create-btn:hover {

    transform:
        translateY(-1px);

}


/* =========================================================
   MESSAGE
   ========================================================= */

.message {

    background: #dcfce7;

    color: #166534;

    padding: 14px 18px;

    border-radius: 13px;

    margin-bottom: 25px;

    font-weight: 700;
}


/* =========================================================
   STAFF GRID
   ========================================================= */

.staff-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(320px, 1fr)
        );

    gap: 22px;
}


/* =========================================================
   STAFF CARD
   ========================================================= */

.staff-card {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 22px;

    padding: 23px;

    box-shadow:
        0 12px 35px
        rgba(40,20,60,.07);

    transition:
        transform .2s,
        box-shadow .2s;
}


.staff-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 18px 40px
        rgba(40,20,60,.11);
}


/* =========================================================
   PROFILE
   ========================================================= */

.profile {

    display: flex;

    align-items: center;

    gap: 15px;

    margin-bottom: 22px;
}


.profile-picture {

    width: 68px;

    height: 68px;

    border-radius: 50%;

    object-fit: cover;

    border:
        4px solid #ede9fe;

    display: block;

    background: #f3effb;
}


.profile-picture-fallback {

    width: 68px;

    height: 68px;

    border-radius: 50%;

    object-fit: cover;

    border:
        4px solid #ede9fe;

    background: #f3effb;

    display: none;
}


.profile-placeholder {

    width: 68px;

    height: 68px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #ede9fe,
            #ddd6fe
        );

    color: #7c3aed;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    border:
        4px solid #ede9fe;
}


.profile-info h3 {

    margin: 0 0 5px;

    font-size: 18px;
}


.profile-info p {

    margin: 0;

    color: #83778e;

    font-size: 12px;
}


.role-badge {

    display: inline-block;

    margin-top: 7px;

    padding: 4px 9px;

    border-radius: 20px;

    background: #f0e9ff;

    color: #6d28a8;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;
}


/* =========================================================
   ACTIVITY
   ========================================================= */

.activity-title {

    font-size: 11px;

    font-weight: 800;

    color: #81758c;

    letter-spacing: 1px;

    margin-bottom: 10px;
}


.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 8px;

    margin-bottom: 20px;
}


.stat {

    background: #faf8fc;

    border:
        1px solid #eee8f5;

    border-radius: 12px;

    padding: 11px 5px;

    text-align: center;
}


.stat strong {

    display: block;

    font-size: 19px;

    color: #3a2948;
}


.stat span {

    display: block;

    margin-top: 3px;

    font-size: 9px;

    color: #8b7e95;

    font-weight: 700;

    text-transform: uppercase;
}


.stat.approved strong {

    color: #16a34a;
}


.stat.rejected strong {

    color: #dc2626;
}


/* =========================================================
   BUTTONS
   ========================================================= */

.card-actions {

    display: flex;

    gap: 8px;

    flex-wrap: wrap;

    align-items: center;

    padding-top: 17px;

    border-top:
        1px solid #eee8f5;
}


.action-btn {

    border: 0;

    padding: 10px 13px;

    border-radius: 10px;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    display: inline-flex;

    align-items: center;

    gap: 6px;
}


.activity-btn {

    background: #f1eafd;

    color: #6d28a8;
}


.edit-btn {

    background: #e0e7ff;

    color: #3730a3;
}


.edit-btn:hover {

    background: #c7d2fe;
}


.delete-btn {

    background: #fee2e2;

    color: #b91c1c;
}


.delete-btn:hover {

    background: #fecaca;
}


.delete-form {

    margin-left: auto;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 20px;

    padding: 60px 20px;

    text-align: center;

    color: #81758c;
}


.empty i {

    font-size: 45px;

    color: #c4b5fd;

    margin-bottom: 15px;
}


/* =========================================================
   MOBILE
   ========================================================= */

@media(max-width:700px) {

    .topbar {

        padding: 18px;
    }


    .nav {

        padding: 0 10px;
    }


    .container {

        padding: 25px 15px;
    }


    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 18px;
    }


    .page-header h2 {

        font-size: 25px;
    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .delete-form {

        margin-left: 0;

        width: 100%;
    }


    .delete-form .action-btn {

        width: 100%;

        justify-content: center;
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
                STAFF MANAGEMENT
            </small>

        </div>

    </div>


    <a
        href="../logout.php"
        class="logout"
        title="Logout"
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


    <div class="page-header">

        <div>

            <h2>
                Staff Management
            </h2>

            <p>
                Manage staff accounts, activity and access.
            </p>

        </div>


        <a
            href="create_staff.php"
            class="create-btn"
        >

            <i class="fa-solid fa-user-plus"></i>

            Create Staff

        </a>

    </div>


    <?php if ($message !== ""): ?>

        <div class="message">

            <i class="fa-solid fa-circle-check"></i>

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php endif; ?>


    <div class="staff-grid">


    <?php

    $hasStaff = false;


    foreach ($staffUsers as $staff):

        $hasStaff = true;


        /* =================================================
           STAFF ID
           ================================================= */

        $staffId =
            (string)$staff["_id"];


        /* =================================================
           PROFILE PICTURE
           ================================================= */

        $profileImage =
            $staff["profile_picture"] ?? "";

        $profileImage =
            trim((string)$profileImage);


        /*
         * IMPORTANT:
         *
         * Cloudinary images already contain:
         *
         * https://res.cloudinary.com/...
         *
         * Therefore DO NOT add "../"
         *
         * Old local images such as:
         *
         * uploads/staff/photo.jpg
         *
         * still receive "../"
         */

        if (
            $profileImage !== "" &&
            !preg_match(
                '/^https?:\/\//i',
                $profileImage
            )
        ) {

            $profileImage =
                "../" .
                ltrim(
                    $profileImage,
                    "/\\"
                );
        }


        /* =================================================
           APPROVED / REJECTED REPORTS
           ================================================= */

        $approvedReports =
            $reports->countDocuments([
                "reviewed_by" => $staffId,
                "status" => "approved"
            ]);


        $rejectedReports =
            $reports->countDocuments([
                "reviewed_by" => $staffId,
                "status" => "rejected"
            ]);


        /* =================================================
           APPROVED / REJECTED CLAIMS
           ================================================= */

        $approvedClaims =
            $claims->countDocuments([
                "reviewed_by" => $staffId,
                "status" => "approved"
            ]);


        $rejectedClaims =
            $claims->countDocuments([
                "reviewed_by" => $staffId,
                "status" => "rejected"
            ]);

    ?>


        <div class="staff-card">


            <!-- PROFILE -->

            <div class="profile">


                <?php if ($profileImage !== ""): ?>

                    <img
                        src="<?php
                            echo htmlspecialchars(
                                $profileImage,
                                ENT_QUOTES,
                                "UTF-8"
                            );
                        ?>"
                        class="profile-picture"
                        alt="Staff profile picture"
                        loading="lazy"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <div
                        class="profile-placeholder"
                        style="display:none;"
                    >

                        <i class="fa-solid fa-user"></i>

                    </div>

                <?php else: ?>

                    <div class="profile-placeholder">

                        <i class="fa-solid fa-user"></i>

                    </div>

                <?php endif; ?>


                <div class="profile-info">

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $staff["name"] ?? "Staff"
                        );

                        ?>

                    </h3>


                    <p>

                        <i class="fa-solid fa-envelope"></i>

                        <?php

                        echo htmlspecialchars(
                            $staff["email"] ?? ""
                        );

                        ?>

                    </p>


                    <span class="role-badge">

                        <i class="fa-solid fa-user-shield"></i>

                        Staff

                    </span>

                </div>


            </div>


            <!-- ACTIVITY -->

            <div class="activity-title">

                REVIEW ACTIVITY

            </div>


            <div class="stats">


                <div class="stat approved">

                    <strong>

                        <?php

                        echo $approvedReports;

                        ?>

                    </strong>

                    <span>

                        Approved Reports

                    </span>

                </div>


                <div class="stat rejected">

                    <strong>

                        <?php

                        echo $rejectedReports;

                        ?>

                    </strong>

                    <span>

                        Rejected Reports

                    </span>

                </div>


                <div class="stat approved">

                    <strong>

                        <?php

                        echo $approvedClaims;

                        ?>

                    </strong>

                    <span>

                        Approved Claims

                    </span>

                </div>


                <div class="stat rejected">

                    <strong>

                        <?php

                        echo $rejectedClaims;

                        ?>

                    </strong>

                    <span>

                        Rejected Claims

                    </span>

                </div>


            </div>


            <!-- ACTIONS -->

            <div class="card-actions">


                <!-- EDIT -->

                <a
                    href="edit_staff.php?id=<?php echo urlencode($staffId); ?>"
                    class="action-btn edit-btn"
                >

                    <i class="fa-solid fa-pen-to-square"></i>

                    Edit Staff

                </a>


                <!-- ACTIVITY -->

                <a
                    href="reports.php?staff=<?php echo urlencode($staffId); ?>"
                    class="action-btn activity-btn"
                >

                    <i class="fa-solid fa-chart-simple"></i>

                    Activity

                </a>


                <!-- DELETE -->

                <form
                    method="POST"
                    class="delete-form"
                >

                    <input
                        type="hidden"
                        name="staff_id"
                        value="<?php
                            echo htmlspecialchars(
                                $staffId
                            );
                        ?>"
                    >


                    <button
                        type="submit"
                        class="action-btn delete-btn"
                        onclick="return confirm('Are you sure you want to delete this staff account?');"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete

                    </button>

                </form>


            </div>


        </div>


    <?php endforeach; ?>


    </div>


    <?php if (!$hasStaff): ?>

        <div class="empty">

            <i class="fa-solid fa-users"></i>

            <h3>
                No Staff Accounts
            </h3>

            <p>
                You haven't created any staff accounts yet.
            </p>


            <br>


            <a
                href="create_staff.php"
                class="create-btn"
            >

                <i class="fa-solid fa-user-plus"></i>

                Create First Staff

            </a>

        </div>

    <?php endif; ?>


</main>


</body>

</html>
```
