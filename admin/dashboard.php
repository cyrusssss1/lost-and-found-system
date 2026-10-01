<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();

$database = $db->getDatabase();

$users = $database->users;
$reports = $database->reports;
$claims = $database->claims;


/* =========================================================
   STAFF
   ========================================================= */

$totalStaff = $users->countDocuments([
    "role" => "staff"
]);


/* =========================================================
   REPORTS
   ========================================================= */

$totalReports = $reports->countDocuments();

$pendingReports = $reports->countDocuments([
    "status" => "pending"
]);

$approvedReports = $reports->countDocuments([
    "status" => "approved"
]);

$rejectedReports = $reports->countDocuments([
    "status" => "rejected"
]);

$totalLost = $reports->countDocuments([
    "type" => "lost"
]);

$totalFound = $reports->countDocuments([
    "type" => "found"
]);


/* =========================================================
   CLAIMS
   ========================================================= */

$totalClaims = $claims->countDocuments();

$pendingClaims = $claims->countDocuments([
    "status" => "pending"
]);

$approvedClaims = $claims->countDocuments([
    "status" => "approved"
]);

$rejectedClaims = $claims->countDocuments([
    "status" => "rejected"
]);


/* =========================================================
   RECENT REPORTS
   ========================================================= */

$recentReports = $reports->find(
    [],
    [
        "sort" => [
            "created_at" => -1
        ],
        "limit" => 6
    ]
);


/* =========================================================
   RECENT CLAIMS
   ========================================================= */

$recentClaims = $claims->find(
    [],
    [
        "sort" => [
            "created_at" => -1
        ],
        "limit" => 6
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

<title>Admin Dashboard</title>

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

.user {

    display: flex;

    align-items: center;

    gap: 15px;
}

.user a {

    color: white;

    text-decoration: none;

    font-size: 18px;
}

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

.container {

    max-width: 1450px;

    margin: auto;

    padding: 35px;
}

.hero {

    background:
        linear-gradient(
            135deg,
            #24103d,
            #5b2181,
            #7c3aed
        );

    color: white;

    border-radius: 25px;

    padding: 35px;

    margin-bottom: 25px;

    box-shadow:
        0 15px 35px
        rgba(76,29,112,.18);
}

.hero h2 {

    margin: 0 0 8px;

    font-size: 30px;
}

.hero p {

    margin: 0;

    opacity: .8;
}

.stats {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 18px;

    margin-bottom: 25px;
}

.stat {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 18px;

    padding: 22px;

    box-shadow:
        0 8px 25px
        rgba(40,20,60,.06);
}

.stat-icon {

    width: 42px;
    height: 42px;

    border-radius: 12px;

    background: #f0e9ff;

    color: #7c3aed;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 15px;
}

.stat small {

    color: #81758c;
}

.stat strong {

    display: block;

    font-size: 29px;

    margin-top: 5px;
}

.stat.green strong {

    color: #16a34a;
}

.stat.orange strong {

    color: #d97706;
}

.stat.red strong {

    color: #dc2626;
}

.quick {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 15px;

    margin-bottom: 25px;
}

.quick a {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 16px;

    padding: 18px;

    color: #382649;

    text-decoration: none;

    box-shadow:
        0 7px 22px
        rgba(40,20,60,.05);
}

.quick a:hover {

    transform: translateY(-2px);

    border-color: #c4b5fd;
}

.quick i {

    color: #7c3aed;

    margin-right: 8px;
}

.activity-grid {

    display: grid;

    grid-template-columns:
        repeat(2,1fr);

    gap: 22px;
}

.activity {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 20px;

    overflow: hidden;

    box-shadow:
        0 8px 25px
        rgba(40,20,60,.06);
}

.activity-head {

    padding: 20px;

    border-bottom:
        1px solid #eee8f5;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.activity-head h3 {

    margin: 0;

    font-size: 17px;
}

.activity-head a {

    color: #7c3aed;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none;
}

.activity-list {

    padding: 0 20px;
}

.activity-item {

    padding: 16px 0;

    border-bottom:
        1px solid #f0ecf4;
}

.activity-item:last-child {

    border-bottom: 0;
}

.activity-item strong {

    display: block;

    margin-bottom: 4px;
}

.activity-item span {

    color: #81758c;

    font-size: 12px;
}

.badge {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 10px;

    font-weight: 800;

    margin-top: 7px;
}

.pending {

    background: #fff4d6;

    color: #a16207;
}

.approved {

    background: #dcfce7;

    color: #166534;
}

.rejected {

    background: #fee2e2;

    color: #991b1b;
}

.empty {

    color: #8b8193;

    text-align: center;

    padding: 30px 10px;
}

@media(max-width:1000px) {

    .stats,
    .quick {

        grid-template-columns:
            repeat(2,1fr);
    }

    .activity-grid {

        grid-template-columns: 1fr;
    }
}

@media(max-width:600px) {

    .stats,
    .quick {

        grid-template-columns: 1fr;
    }

    .topbar {

        padding: 18px;
    }

    .nav {

        padding: 0 10px;
    }

    .container {

        padding: 20px 15px;
    }

    .hero {

        padding: 25px;
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
                SYSTEM ADMINISTRATION
            </small>

        </div>

    </div>

    <div class="user">

        <span>
            <?php
            echo htmlspecialchars(
                $_SESSION["name"] ?? "Admin"
            );
            ?>
        </span>

        <a href="../logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</header>

<nav class="nav">

    <a
        href="dashboard.php"
        class="active"
    >

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

    <a href="create_staff.php">

        <i class="fa-solid fa-user-plus"></i>

        Create Staff

    </a>

</nav>

<main class="container">

    <section class="hero">

        <h2>
            Welcome, <?php
            echo htmlspecialchars(
                $_SESSION["name"] ?? "Admin"
            );
            ?>
        </h2>

        <p>
            Monitor reports, claims and staff activity from one place.
        </p>

    </section>


    <section class="stats">

        <div class="stat">

            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <small>Total Staff</small>

            <strong>
                <?php echo $totalStaff; ?>
            </strong>

        </div>


        <div class="stat">

            <div class="stat-icon">
                <i class="fa-solid fa-file-lines"></i>
            </div>

            <small>Total Reports</small>

            <strong>
                <?php echo $totalReports; ?>
            </strong>

        </div>


        <div class="stat orange">

            <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <small>Pending Reports</small>

            <strong>
                <?php echo $pendingReports; ?>
            </strong>

        </div>


        <div class="stat green">

            <div class="stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <small>Approved Reports</small>

            <strong>
                <?php echo $approvedReports; ?>
            </strong>

        </div>

    </section>


    <section class="quick">

        <a href="reports.php">

            <i class="fa-solid fa-file-lines"></i>

            Review Reports

        </a>

        <a href="claim.php">

            <i class="fa-solid fa-hand-holding"></i>

            Review Claims

        </a>

        <a href="manage_staff.php">

            <i class="fa-solid fa-users-gear"></i>

            Manage Staff

        </a>

        <a href="create_staff.php">

            <i class="fa-solid fa-user-plus"></i>

            Create Staff

        </a>

    </section>


    <section class="activity-grid">


        <div class="activity">

            <div class="activity-head">

                <h3>
                    Recent Reports
                </h3>

                <a href="reports.php">
                    View All
                </a>

            </div>

            <div class="activity-list">

                <?php

                $hasRecentReports = false;

                foreach ($recentReports as $report):

                    $hasRecentReports = true;

                ?>

                    <div class="activity-item">

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $report["item_name"]
                                ?? "Unknown Item"
                            );
                            ?>

                        </strong>

                        <span>

                            <?php
                            echo htmlspecialchars(
                                $report["student_name"]
                                ?? "Unknown Student"
                            );
                            ?>

                        </span>

                        <br>

                        <span>

                            <?php
                            echo htmlspecialchars(
                                ucfirst(
                                    $report["type"]
                                    ?? ""
                                )
                            );
                            ?>

                        </span>

                        <br>

                        <span class="badge <?php
                            echo htmlspecialchars(
                                $report["status"]
                                ?? "pending"
                            );
                        ?>">

                            <?php
                            echo htmlspecialchars(
                                ucfirst(
                                    $report["status"]
                                    ?? "pending"
                                )
                            );
                            ?>

                        </span>

                    </div>

                <?php endforeach; ?>


                <?php if (!$hasRecentReports): ?>

                    <div class="empty">
                        No reports yet.
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div class="activity">

            <div class="activity-head">

                <h3>
                    Recent Claims
                </h3>

                <a href="claim.php">
                    View All
                </a>

            </div>

            <div class="activity-list">

                <?php

                $hasRecentClaims = false;

                foreach ($recentClaims as $claim):

                    $hasRecentClaims = true;

                ?>

                    <div class="activity-item">

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $claim["student_name"]
                                ?? $claim["name"]
                                ?? "Unknown Student"
                            );
                            ?>

                        </strong>

                        <span>

                            Claim submitted

                        </span>

                        <br>

                        <span class="badge <?php
                            echo htmlspecialchars(
                                $claim["status"]
                                ?? "pending"
                            );
                        ?>">

                            <?php
                            echo htmlspecialchars(
                                ucfirst(
                                    $claim["status"]
                                    ?? "pending"
                                )
                            );
                            ?>

                        </span>

                    </div>

                <?php endforeach; ?>


                <?php if (!$hasRecentClaims): ?>

                    <div class="empty">
                        No claims yet.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>

</body>

</html>