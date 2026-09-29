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

/* ==============================
   STATISTICS
   ============================== */

$totalStaff = $users->countDocuments([
    "role" => "staff"
]);

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

$totalLost = $reports->countDocuments([
    "type" => "lost"
]);

$totalFound = $reports->countDocuments([
    "type" => "found"
]);

/* ==============================
   RECENT REPORTS
   ============================== */

$recentReports = $reports->find(
    [],
    [
        "sort" => [
            "created_at" => -1
        ],
        "limit" => 6
    ]
);

/* ==============================
   RECENT CLAIMS
   ============================== */

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Control Center</title>

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
        radial-gradient(circle at top right, rgba(124, 58, 237, .18), transparent 30%),
        #f5f3f8;
    color: #241b35;
}

.topbar {
    height: 76px;
    background: linear-gradient(135deg, #24103d, #4c1d70 55%, #6d28a8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 8px 25px rgba(37, 17, 60, .22);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-icon {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background: rgba(255,255,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.brand h1 {
    margin: 0;
    font-size: 19px;
}

.brand span {
    font-size: 11px;
    opacity: .7;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #d8b4fe;
    color: #3b0764;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}

.logout {
    color: white;
    text-decoration: none;
    margin-left: 18px;
    opacity: .8;
}

.logout:hover {
    opacity: 1;
}

.nav {
    background: white;
    border-bottom: 1px solid #e9e2f2;
    padding: 0 35px;
    display: flex;
    gap: 8px;
    overflow-x: auto;
}

.nav a {
    padding: 17px 18px;
    text-decoration: none;
    color: #665b73;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
}

.nav a:hover,
.nav a.active {
    color: #6d28a8;
    border-bottom: 3px solid #7c3aed;
}

.container {
    max-width: 1450px;
    margin: auto;
    padding: 35px;
}

.hero {
    background:
        linear-gradient(135deg, #2e1248, #5b2181 60%, #7c3aed);
    border-radius: 24px;
    padding: 35px;
    color: white;
    box-shadow: 0 18px 40px rgba(76,29,112,.22);
    position: relative;
    overflow: hidden;
}

.hero:after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    right: -80px;
    top: -100px;
}

.hero h2 {
    margin: 0 0 8px;
    font-size: 30px;
}

.hero p {
    margin: 0;
    opacity: .8;
}

.section-title {
    margin: 32px 0 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title h2 {
    margin: 0;
    font-size: 20px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.card {
    background: white;
    border: 1px solid #eee8f5;
    border-radius: 20px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(48, 28, 67, .07);
    transition: .2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 32px rgba(48, 28, 67, .12);
}

.card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.purple {
    background: #f1e8ff;
    color: #7c3aed;
}

.blue {
    background: #e8f1ff;
    color: #2563eb;
}

.green {
    background: #e7f8ef;
    color: #15803d;
}

.orange {
    background: #fff2df;
    color: #c2410c;
}

.card-label {
    margin-top: 18px;
    color: #766b81;
    font-size: 13px;
}

.card-number {
    font-size: 30px;
    font-weight: 800;
    margin-top: 4px;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.quick {
    text-decoration: none;
    color: #291b39;
    background: white;
    border-radius: 18px;
    padding: 22px;
    border: 1px solid #eee8f5;
    box-shadow: 0 7px 22px rgba(48,28,67,.06);
}

.quick:hover {
    border-color: #c4b5fd;
    transform: translateY(-2px);
}

.quick i {
    color: #7c3aed;
    font-size: 22px;
    margin-bottom: 14px;
}

.quick strong {
    display: block;
    margin-bottom: 5px;
}

.quick span {
    font-size: 13px;
    color: #7b7184;
}

.dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.panel {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee8f5;
    box-shadow: 0 8px 25px rgba(48,28,67,.06);
    overflow: hidden;
}

.panel-header {
    padding: 20px 22px;
    border-bottom: 1px solid #eee8f5;
    display: flex;
    justify-content: space-between;
}

.panel-header h3 {
    margin: 0;
}

.panel-header a {
    color: #7c3aed;
    text-decoration: none;
    font-size: 13px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid #f0ecf4;
    font-size: 13px;
}

th {
    color: #756a80;
    font-size: 11px;
    text-transform: uppercase;
}

.badge {
    display: inline-flex;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}

.badge.pending {
    background: #fff4d6;
    color: #a16207;
}

.badge.approved {
    background: #dcfce7;
    color: #166534;
}

.badge.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.type-lost {
    color: #dc2626;
    font-weight: 700;
}

.type-found {
    color: #15803d;
    font-weight: 700;
}

.footer {
    text-align: center;
    padding: 30px;
    color: #8b8292;
    font-size: 12px;
}

@media (max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .quick-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .dashboard-columns {
        grid-template-columns: 1fr;
    }

    .topbar {
        padding: 0 18px;
    }

    .admin-user span {
        display: none;
    }

    .container {
        padding: 20px;
    }
}

@media (max-width: 550px) {
    .cards {
        grid-template-columns: 1fr;
    }

    .hero {
        padding: 25px;
    }

    .hero h2 {
        font-size: 24px;
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
            <h1>Lost & Found</h1>
            <span>ADMIN CONTROL CENTER</span>
        </div>

    </div>

    <div class="admin-user">

        <div class="avatar">
            <?php echo strtoupper(substr($_SESSION["name"], 0, 1)); ?>
        </div>

        <span>
            <?php echo htmlspecialchars($_SESSION["name"]); ?>
        </span>

        <a class="logout" href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

    </div>

</header>

<nav class="nav">

    <a class="active" href="dashboard.php">
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
            Welcome back,
            <?php echo htmlspecialchars($_SESSION["name"]); ?> 👋
        </h2>

        <p>
            Manage your Lost & Found system from one centralized control center.
        </p>

    </section>


    <div class="section-title">
        <h2>System Overview</h2>
    </div>

    <section class="cards">

        <div class="card">
            <div class="card-top">
                <span>Reports</span>
                <div class="card-icon purple">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>

            <div class="card-label">Total reports</div>
            <div class="card-number">
                <?php echo $totalReports; ?>
            </div>
        </div>


        <div class="card">
            <div class="card-top">
                <span>Pending</span>
                <div class="card-icon orange">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>

            <div class="card-label">Reports awaiting review</div>
            <div class="card-number">
                <?php echo $pendingReports; ?>
            </div>
        </div>


        <div class="card">
            <div class="card-top">
                <span>Claims</span>
                <div class="card-icon blue">
                    <i class="fa-solid fa-hand-holding"></i>
                </div>
            </div>

            <div class="card-label">Total claims</div>
            <div class="card-number">
                <?php echo $totalClaims; ?>
            </div>
        </div>


        <div class="card">
            <div class="card-top">
                <span>Staff</span>
                <div class="card-icon green">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>

            <div class="card-label">Staff accounts</div>
            <div class="card-number">
                <?php echo $totalStaff; ?>
            </div>
        </div>

    </section>


    <div class="section-title">
        <h2>Report & Claim Status</h2>
    </div>

    <section class="cards">

        <div class="card">
            <div class="card-label">Approved Reports</div>
            <div class="card-number">
                <?php echo $approvedReports; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-label">Rejected Reports</div>
            <div class="card-number">
                <?php echo $rejectedReports; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-label">Approved Claims</div>
            <div class="card-number">
                <?php echo $approvedClaims; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-label">Rejected Claims</div>
            <div class="card-number">
                <?php echo $rejectedClaims; ?>
            </div>
        </div>

    </section>


    <div class="section-title">
        <h2>Quick Administration</h2>
    </div>

    <section class="quick-grid">

        <a class="quick" href="reports.php">
            <i class="fa-solid fa-folder-open"></i>
            <strong>Review Reports</strong>
            <span>
                View all lost and found reports.
            </span>
        </a>

        <a class="quick" href="claim.php">
            <i class="fa-solid fa-list-check"></i>
            <strong>Review Claims</strong>
            <span>
                Inspect student claims and item details.
            </span>
        </a>

        <a class="quick" href="manage_staff.php">
            <i class="fa-solid fa-users-gear"></i>
            <strong>Manage Staff</strong>
            <span>
                View and remove staff accounts.
            </span>
        </a>

    </section>


    <div class="section-title">
        <h2>Recent Activity</h2>
    </div>

    <section class="dashboard-columns">

        <div class="panel">

            <div class="panel-header">

                <h3>Recent Reports</h3>

                <a href="reports.php">
                    View all
                </a>

            </div>

            <div class="table-wrap">

                <table>

                    <thead>

                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($recentReports as $report): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($report["item_name"] ?? "Unknown"); ?>
                            </td>

                            <td>

                                <span class="<?php echo ($report["type"] ?? "") === "lost" ? "type-lost" : "type-found"; ?>">

                                    <?php echo htmlspecialchars(ucfirst($report["type"] ?? "")); ?>

                                </span>

                            </td>

                            <td>

                                <span class="badge <?php echo htmlspecialchars($report["status"] ?? "pending"); ?>">

                                    <?php echo htmlspecialchars(ucfirst($report["status"] ?? "pending")); ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="panel">

            <div class="panel-header">

                <h3>Recent Claims</h3>

                <a href="claim.php">
                    View all
                </a>

            </div>

            <div class="table-wrap">

                <table>

                    <thead>

                    <tr>
                        <th>Student</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($recentClaims as $claim): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($claim["student_name"] ?? "Unknown"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(substr($claim["reason"] ?? "", 0, 25)); ?>
                            </td>

                            <td>

                                <span class="badge <?php echo htmlspecialchars($claim["status"] ?? "pending"); ?>">

                                    <?php echo htmlspecialchars(ucfirst($claim["status"] ?? "pending")); ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</main>

<footer class="footer">
    Lost & Found Management System • Administrator Control Center
</footer>

</body>
</html>