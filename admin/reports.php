<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();

$reports = $db->getDatabase()->reports;


function getAdminImageUrl($imagePath): string
{
    if (empty($imagePath)) {
        return "";
    }

    $imagePath = trim((string)$imagePath);

    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    return "../" . ltrim($imagePath, "/\\");
}


$filter = $_GET["status"] ?? "all";

$query = [];

if (in_array($filter, ["pending", "approved", "rejected"])) {
    $query["status"] = $filter;
}


$allReports = $reports->find(
    $query,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


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

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Reports</title>

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
    background: #f5f3f8;
    color: #261b35;
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
    align-items: center;
    justify-content: space-between;
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
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
}

.user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user a {
    color: white;
    text-decoration: none;
}

.nav {
    background: white;
    border-bottom: 1px solid #e9e2f2;
    padding: 0 35px;
    display: flex;
    overflow-x: auto;
}

.nav a {
    padding: 17px;
    color: #6d6475;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
}

.nav a:hover,
.nav a.active {
    color: #7c3aed;
    border-bottom: 3px solid #7c3aed;
}

.container {
    max-width: 1450px;
    margin: auto;
    padding: 35px;
}

.page-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    margin-bottom: 25px;
}

.page-head h2 {
    margin: 0 0 7px;
    font-size: 28px;
}

.page-head p {
    margin: 0;
    color: #80768a;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.stat {
    background: white;
    padding: 20px;
    border-radius: 18px;
    border: 1px solid #eee8f5;
    box-shadow: 0 7px 22px rgba(40,20,60,.06);
}

.stat small {
    color: #81758c;
}

.stat strong {
    display: block;
    margin-top: 7px;
    font-size: 28px;
}

.filters {
    background: white;
    border: 1px solid #eee8f5;
    padding: 10px;
    border-radius: 16px;
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    overflow-x: auto;
}

.filters a {
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 11px;
    color: #71667c;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.filters a:hover,
.filters a.active {
    background: #7c3aed;
    color: white;
}

.table-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eee8f5;
    box-shadow: 0 8px 25px rgba(40,20,60,.06);
    overflow: hidden;
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
    padding: 15px 18px;
    text-align: left;
    border-bottom: 1px solid #f0ecf4;
    font-size: 13px;
}

th {
    background: #faf8fc;
    color: #766b80;
    font-size: 11px;
    text-transform: uppercase;
}

.item-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-photo {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 12px;
    background: #eee;
}

.no-photo {
    width: 55px;
    height: 55px;
    border-radius: 12px;
    background: #f0ebf5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9b90a5;
}

.badge {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
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

.lost {
    color: #dc2626;
    font-weight: 800;
}

.found {
    color: #15803d;
    font-weight: 800;
}

.view-btn {
    border: 0;
    background: #f1e8ff;
    color: #6d28a8;
    padding: 8px 12px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: 700;
}

.view-btn:hover {
    background: #7c3aed;
    color: white;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(25,12,37,.72);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-box {
    background: white;
    max-width: 650px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 22px;
    padding: 28px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    border: 0;
    background: #f3edf8;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
}

.modal-photo {
    width: 100%;
    max-height: 330px;
    object-fit: contain;
    border-radius: 15px;
    background: #f4f1f7;
    margin: 15px 0;
}

.detail {
    background: #faf8fc;
    border-radius: 13px;
    padding: 14px;
    margin-top: 10px;
}

.detail strong {
    display: block;
    font-size: 11px;
    color: #81758c;
    text-transform: uppercase;
    margin-bottom: 4px;
}

@media(max-width:900px) {

    .stats {
        grid-template-columns: repeat(2,1fr);
    }

    .container {
        padding: 20px;
    }
}

@media(max-width:550px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .topbar {
        padding: 18px;
    }

    .nav {
        padding: 0 10px;
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
                REPORT MANAGEMENT
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

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a
        class="active"
        href="reports.php"
    >
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

    <div class="page-head">

        <div>

            <h2>
                Report Management
            </h2>

            <p>
                Review every lost and found report in the system.
            </p>

        </div>

    </div>

    <section class="stats">

        <div class="stat">

            <small>
                All Reports
            </small>

            <strong>
                <?php echo $totalReports; ?>
            </strong>

        </div>

        <div class="stat">

            <small>
                Pending
            </small>

            <strong>
                <?php echo $pendingReports; ?>
            </strong>

        </div>

        <div class="stat">

            <small>
                Approved
            </small>

            <strong>
                <?php echo $approvedReports; ?>
            </strong>

        </div>

        <div class="stat">

            <small>
                Rejected
            </small>

            <strong>
                <?php echo $rejectedReports; ?>
            </strong>

        </div>

    </section>

    <div class="filters">

        <a
            class="<?php
                echo $filter === 'all'
                    ? 'active'
                    : '';
            ?>"
            href="reports.php?status=all"
        >
            All
        </a>

        <a
            class="<?php
                echo $filter === 'pending'
                    ? 'active'
                    : '';
            ?>"
            href="reports.php?status=pending"
        >
            Pending
        </a>

        <a
            class="<?php
                echo $filter === 'approved'
                    ? 'active'
                    : '';
            ?>"
            href="reports.php?status=approved"
        >
            Approved
        </a>

        <a
            class="<?php
                echo $filter === 'rejected'
                    ? 'active'
                    : '';
            ?>"
            href="reports.php?status=rejected"
        >
            Rejected
        </a>

    </div>

    <section class="table-card">

        <div class="table-wrap">

            <table>

                <thead>

                <tr>

                    <th>Item</th>

                    <th>Type</th>

                    <th>Student</th>

                    <th>Location</th>

                    <th>Status</th>

                    <th>Details</th>

                </tr>

                </thead>

                <tbody>

                <?php $hasReports = false; ?>

                <?php foreach ($allReports as $report): ?>

                    <?php

                    $hasReports = true;

                    $imagePath =
                        getAdminImageUrl(
                            $report["image_path"] ?? ""
                        );

                    $reportData = $report;

                    if (
                        $reportData
                        instanceof MongoDB\Model\BSONDocument
                    ) {
                        $reportData =
                            $reportData->getArrayCopy();
                    }

                    ?>

                    <tr>

                        <td>

                            <div class="item-cell">

                                <?php if ($imagePath !== ""): ?>

                                    <img
                                        class="item-photo"
                                        src="<?php
                                            echo htmlspecialchars(
                                                $imagePath
                                            );
                                        ?>"
                                        alt="Item"
                                    >

                                <?php else: ?>

                                    <div class="no-photo">

                                        <i
                                            class="fa-solid fa-image"
                                        ></i>

                                    </div>

                                <?php endif; ?>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $report["item_name"]
                                        ?? "Unknown"
                                    );
                                    ?>

                                </strong>

                            </div>

                        </td>

                        <td>

                            <span
                                class="<?php
                                    echo
                                        ($report["type"] ?? "")
                                        === "lost"
                                        ? "lost"
                                        : "found";
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $report["type"] ?? ""
                                    )
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $report["student_name"]
                                ?? "Unknown"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $report["location"]
                                ?? "N/A"
                            );
                            ?>

                        </td>

                        <td>

                            <span
                                class="badge <?php
                                    echo htmlspecialchars(
                                        $report["status"]
                                        ?? "pending"
                                    );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $report["status"]
                                        ?? "pending"
                                    )
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <button
                                class="view-btn"
                                onclick='openReport(
                                    <?php
                                    echo json_encode(
                                        $reportData,
                                        JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_AMP
                                    );
                                    ?>,
                                    <?php
                                    echo json_encode(
                                        $imagePath
                                    );
                                    ?>
                                )'
                            >

                                <i class="fa-solid fa-eye"></i>

                                View

                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <?php if (!$hasReports): ?>

                    <tr>

                        <td
                            colspan="6"
                            style="
                                text-align:center;
                                padding:45px;
                                color:#8b8193;
                            "
                        >

                            <i
                                class="fa-solid fa-folder-open"
                                style="font-size:35px;"
                            ></i>

                            <br><br>

                            No reports found for this filter.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<div
    class="modal"
    id="reportModal"
>

    <div class="modal-box">

        <button
            class="close"
            onclick="closeReport()"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

        <h2 id="modalTitle">
            Report Details
        </h2>

        <img
            id="modalPhoto"
            class="modal-photo"
            style="display:none;"
            alt="Reported item"
        >

        <div class="detail">

            <strong>
                Report Type
            </strong>

            <span id="modalType"></span>

        </div>

        <div class="detail">

            <strong>
                Student
            </strong>

            <span id="modalStudent"></span>

        </div>

        <div class="detail">

            <strong>
                Description
            </strong>

            <span id="modalDescription"></span>

        </div>

        <div class="detail">

            <strong>
                Location
            </strong>

            <span id="modalLocation"></span>

        </div>

        <div class="detail">

            <strong>
                Status
            </strong>

            <span id="modalStatus"></span>

        </div>

    </div>

</div>

<script>

function openReport(report, imagePath) {

    document.getElementById("modalTitle").textContent =
        report.item_name || "Report Details";

    document.getElementById("modalType").textContent =
        report.type
            ? report.type.toUpperCase()
            : "N/A";

    document.getElementById("modalStudent").textContent =
        report.student_name || "N/A";

    document.getElementById("modalDescription").textContent =
        report.description || "N/A";

    document.getElementById("modalLocation").textContent =
        report.location || "N/A";

    document.getElementById("modalStatus").textContent =
        report.status
            ? report.status.toUpperCase()
            : "N/A";

    const photo =
        document.getElementById("modalPhoto");

    if (imagePath) {

        photo.src = imagePath;

        photo.style.display = "block";

    } else {

        photo.style.display = "none";

        photo.removeAttribute("src");

    }

    document.getElementById("reportModal").style.display =
        "flex";
}

function closeReport() {

    document.getElementById("reportModal").style.display =
        "none";
}

window.onclick = function(event) {

    const modal =
        document.getElementById("reportModal");

    if (event.target === modal) {

        closeReport();

    }

};

</script>

</body>

</html>