<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "staff") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();

$database = $db->getDatabase();

$reports = $database->reports;
$claims = $database->claims;


/* =========================================================
   REPORT STATISTICS
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


/* =========================================================
   CLAIM STATISTICS
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
        "limit" => 5
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
        "limit" => 5
    ]
);


/* =========================================================
   REPORT DATA FOR MODAL
   ========================================================= */

$reportData = [];

foreach (
    $reports->find(
        [],
        [
            "sort" => [
                "created_at" => -1
            ],
            "limit" => 10
        ]
    ) as $report
) {

    $id = (string)$report["_id"];

    $type = $report["type"] ?? "unknown";

    $date = $type === "lost"
        ? ($report["date_lost"] ?? "—")
        : ($report["date_found"] ?? "—");


    /*
     * IMPORTANT:
     * Cloudinary URLs must be used directly.
     */

    $imagePath = "";

    if (!empty($report["image_path"])) {
        $imagePath = (string)$report["image_path"];
    }


    $reportData[$id] = [

        "itemName" =>
            $report["item_name"] ?? "Unknown Item",

        "description" =>
            $report["description"] ?? "No description.",

        "student" =>
            $report["student_name"] ?? "Unknown Student",

        "location" =>
            $report["location"] ?? "Not specified",

        "type" =>
            ucfirst($type),

        "date" =>
            $date,

        "status" =>
            ucfirst($report["status"] ?? "pending"),

        "rawStatus" =>
            $report["status"] ?? "pending",

        "photo" =>
            $imagePath
    ];
}


/* =========================================================
   CLAIM DATA FOR MODAL
   ========================================================= */

$claimData = [];

foreach (
    $claims->find(
        [],
        [
            "sort" => [
                "created_at" => -1
            ],
            "limit" => 10
        ]
    ) as $claim
) {

    $id = (string)$claim["_id"];

    $item = null;


    try {

        if (!empty($claim["item_id"])) {

            $item = $reports->findOne([
                "_id" => new MongoDB\BSON\ObjectId(
                    $claim["item_id"]
                )
            ]);

        }

    } catch (Exception $e) {

        $item = null;
    }


    /*
     * IMPORTANT:
     * Cloudinary URLs must be used directly.
     */

    $claimPhoto = "";

    if (
        $item &&
        !empty($item["image_path"])
    ) {
        $claimPhoto = (string)$item["image_path"];
    }


    $claimData[$id] = [

        "itemName" =>
            $item["item_name"] ?? "Item Not Found",

        "description" =>
            $item["description"] ?? "No description.",

        "student" =>
            $claim["student_name"] ?? "Unknown Student",

        "location" =>
            $item["location"] ?? "Not specified",

        "reason" =>
            $claim["reason"] ?? "No reason provided.",

        "status" =>
            ucfirst($claim["status"] ?? "pending"),

        "rawStatus" =>
            $claim["status"] ?? "pending",

        "photo" =>
            $claimPhoto
    ];
}


/* =========================================================
   STAFF NAME
   ========================================================= */

$staffName = $_SESSION["name"] ?? "Staff";

$staffInitial = strtoupper(
    substr($staffName, 0, 1)
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

<title>Staff Dashboard</title>

<link
    rel="stylesheet"
    href="staff.css"
>

<style>

/* =========================================================
   DASHBOARD ADDITIONS
   ========================================================= */

.staff-welcome-box {
    background:
        linear-gradient(
            135deg,
            #123b2a,
            #1d5a3e
        );

    border-radius: 20px;

    padding: 26px 30px;

    color: white;

    margin-bottom: 24px;

    box-shadow:
        0 14px 35px rgba(16, 65, 44, .16);

    position: relative;

    overflow: hidden;
}

.staff-welcome-box::after {
    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    border-radius: 50%;

    background: rgba(255,255,255,.06);

    right: -45px;
    top: -60px;
}

.staff-welcome-box h2 {
    margin: 0 0 7px;

    font-size: 24px;
}

.staff-welcome-box p {
    margin: 0;

    color: rgba(255,255,255,.78);

    font-size: 13px;
}

.staff-quick-actions {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 12px;

    margin-top: 20px;
}

.staff-quick-action {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px 17px;

    border-radius: 13px;

    background: #fff;

    border: 1px solid #e5ebe7;

    color: #193b2b;

    text-decoration: none;

    transition: .2s ease;
}

.staff-quick-action:hover {
    transform: translateY(-2px);

    border-color: #2f7553;

    box-shadow:
        0 8px 20px rgba(22,61,43,.08);
}

.staff-quick-icon {
    width: 38px;
    height: 38px;

    border-radius: 10px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #edf6f0;

    font-size: 18px;
}

.staff-quick-action strong {
    display: block;

    font-size: 12px;
}

.staff-quick-action span {
    display: block;

    margin-top: 3px;

    color: #89958d;

    font-size: 10px;
}


/* =========================================================
   EXTENDED STATISTICS
   ========================================================= */

.staff-status-overview {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;

    margin-top: 18px;
}

.staff-status-box {
    background: #fff;

    border: 1px solid #e6ece8;

    border-radius: 15px;

    padding: 17px 18px;

    box-shadow:
        0 5px 16px rgba(30,65,48,.04);
}

.staff-status-box-top {
    display: flex;

    align-items: center;
    justify-content: space-between;

    margin-bottom: 8px;
}

.staff-status-box label {
    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .7px;

    color: #7d8b83;

    font-weight: 800;
}

.staff-status-number {
    font-size: 24px;

    font-weight: 850;

    color: #173b2b;
}

.staff-status-box small {
    display: block;

    margin-top: 3px;

    color: #9aa49e;

    font-size: 10px;
}

.status-dot {
    width: 9px;
    height: 9px;

    border-radius: 50%;
}

.status-dot.pending {
    background: #d79b27;
}

.status-dot.approved {
    background: #32845a;
}

.status-dot.rejected {
    background: #c94d4d;
}


/* =========================================================
   SECTION TITLE
   ========================================================= */

.staff-section-title {
    display: flex;

    align-items: flex-end;
    justify-content: space-between;

    margin: 28px 0 12px;
}

.staff-section-title h2 {
    margin: 0;

    color: #173b2b;

    font-size: 16px;
}

.staff-section-title p {
    margin: 4px 0 0;

    color: #89958d;

    font-size: 11px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 850px) {

    .staff-status-overview {
        grid-template-columns:
            1fr;
    }

}

@media (max-width: 600px) {

    .staff-quick-actions {
        grid-template-columns:
            1fr;
    }

    .staff-welcome-box {
        padding: 22px;
    }

}

</style>

</head>


<body class="staff-body">


<div class="staff-layout">


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<aside class="staff-sidebar">

    <div class="staff-brand">

        <div class="staff-brand-icon">
            🛠️
        </div>

        <div>

            <h2>
                Lost & Found
            </h2>

            <span>
                Staff Management
            </span>

        </div>

    </div>


    <nav class="staff-nav">

        <div class="staff-nav-label">
            Workspace
        </div>


        <a
            href="dashboard.php"
            class="active"
        >

            <span class="staff-nav-icon">
                ▦
            </span>

            Dashboard

        </a>


        <a href="reports.php">

            <span class="staff-nav-icon">
                📋
            </span>

            Reports

            <?php if ($pendingReports > 0): ?>

                <span style="
                    margin-left:auto;
                    background:#d9a52e;
                    color:#fff;
                    min-width:21px;
                    height:21px;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:9px;
                    font-weight:800;
                ">
                    <?php echo $pendingReports; ?>
                </span>

            <?php endif; ?>

        </a>


        <a href="claims.php">

            <span class="staff-nav-icon">
                📨
            </span>

            Claims

            <?php if ($pendingClaims > 0): ?>

                <span style="
                    margin-left:auto;
                    background:#d9a52e;
                    color:#fff;
                    min-width:21px;
                    height:21px;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:9px;
                    font-weight:800;
                ">
                    <?php echo $pendingClaims; ?>
                </span>

            <?php endif; ?>

        </a>

    </nav>


    <div class="staff-user">

        <div class="staff-user-top">

            <div class="staff-avatar">

                <?php echo htmlspecialchars($staffInitial); ?>

            </div>

            <div>

                <strong>
                    <?php
                    echo htmlspecialchars($staffName);
                    ?>
                </strong>

                <small>
                    Staff Account
                </small>

            </div>

        </div>


        <a
            href="../logout.php"
            class="staff-logout"
        >
            Sign Out
        </a>

    </div>

</aside>


<!-- =====================================================
     MAIN
     ===================================================== -->

<main class="staff-main">


<div class="staff-topbar">

    <div class="staff-breadcrumb">

        Staff /

        <strong>
            Dashboard
        </strong>

    </div>


    <div class="staff-date">
        🛠️ Management Workspace
    </div>

</div>


<section class="staff-page-heading">

    <h1>
        Staff Dashboard
    </h1>

    <p>
        Monitor reports, review claims, and manage the Lost & Found system.
    </p>

</section>


<!-- =====================================================
     WELCOME
     ===================================================== -->

<section class="staff-welcome-box">

    <h2>
        Welcome back, <?php echo htmlspecialchars($staffName); ?> 👋
    </h2>

    <p>
        You have
        <strong><?php echo $pendingReports; ?></strong>
        report<?php echo $pendingReports == 1 ? "" : "s"; ?>
        and
        <strong><?php echo $pendingClaims; ?></strong>
        claim<?php echo $pendingClaims == 1 ? "" : "s"; ?>
        waiting for review.
    </p>

</section>


<!-- =====================================================
     MAIN STATISTICS
     ===================================================== -->

<section class="staff-stats">


    <div class="staff-stat-card">

        <div class="staff-stat-top">

            <span class="staff-stat-label">
                Total Reports
            </span>

            <div class="staff-stat-icon">
                📋
            </div>

        </div>

        <div class="staff-stat-number">
            <?php echo $totalReports; ?>
        </div>

        <div class="staff-stat-note">
            All submitted reports
        </div>

    </div>


    <div class="staff-stat-card">

        <div class="staff-stat-top">

            <span class="staff-stat-label">
                Pending Reports
            </span>

            <div class="staff-stat-icon">
                ⏳
            </div>

        </div>

        <div class="staff-stat-number">
            <?php echo $pendingReports; ?>
        </div>

        <div class="staff-stat-note">
            Need staff review
        </div>

    </div>


    <div class="staff-stat-card">

        <div class="staff-stat-top">

            <span class="staff-stat-label">
                Total Claims
            </span>

            <div class="staff-stat-icon">
                📨
            </div>

        </div>

        <div class="staff-stat-number">
            <?php echo $totalClaims; ?>
        </div>

        <div class="staff-stat-note">
            All submitted claims
        </div>

    </div>


    <div class="staff-stat-card">

        <div class="staff-stat-top">

            <span class="staff-stat-label">
                Pending Claims
            </span>

            <div class="staff-stat-icon">
                🔔
            </div>

        </div>

        <div class="staff-stat-number">
            <?php echo $pendingClaims; ?>
        </div>

        <div class="staff-stat-note">
            Need staff review
        </div>

    </div>


</section>


<!-- =====================================================
     STATUS OVERVIEW
     ===================================================== -->

<section class="staff-status-overview">


    <div class="staff-status-box">

        <div class="staff-status-box-top">

            <label>
                Approved Reports
            </label>

            <span class="status-dot approved"></span>

        </div>

        <div class="staff-status-number">
            <?php echo $approvedReports; ?>
        </div>

        <small>
            Successfully reviewed
        </small>

    </div>


    <div class="staff-status-box">

        <div class="staff-status-box-top">

            <label>
                Rejected Reports
            </label>

            <span class="status-dot rejected"></span>

        </div>

        <div class="staff-status-number">
            <?php echo $rejectedReports; ?>
        </div>

        <small>
            Declined submissions
        </small>

    </div>


    <div class="staff-status-box">

        <div class="staff-status-box-top">

            <label>
                Approved Claims
            </label>

            <span class="status-dot approved"></span>

        </div>

        <div class="staff-status-number">
            <?php echo $approvedClaims; ?>
        </div>

        <small>
            Successfully reviewed
        </small>

    </div>


</section>


<!-- =====================================================
     QUICK ACTIONS
     ===================================================== -->

<section class="staff-section-title">

    <div>

        <h2>
            Quick Actions
        </h2>

        <p>
            Access common management tasks.
        </p>

    </div>

</section>


<div class="staff-quick-actions">


    <a
        href="reports.php?status=pending"
        class="staff-quick-action"
    >

        <div class="staff-quick-icon">
            ⏳
        </div>

        <div>

            <strong>
                Review Pending Reports
            </strong>

            <span>
                <?php echo $pendingReports; ?> waiting for review
            </span>

        </div>

    </a>


    <a
        href="claims.php?status=pending"
        class="staff-quick-action"
    >

        <div class="staff-quick-icon">
            📨
        </div>

        <div>

            <strong>
                Review Pending Claims
            </strong>

            <span>
                <?php echo $pendingClaims; ?> waiting for review
            </span>

        </div>

    </a>


</div>


<!-- =====================================================
     RECENT DATA
     ===================================================== -->

<section class="staff-section-title">

    <div>

        <h2>
            Recent Activity
        </h2>

        <p>
            The latest reports and claims submitted by students.
        </p>

    </div>

</section>


<div class="staff-dashboard-grid">


<!-- =====================================================
     RECENT REPORTS
     ===================================================== -->

<section class="staff-panel">

    <div class="staff-panel-header">

        <a
            href="reports.php"
            class="staff-view-all"
        >
            View All
        </a>

        <h2>
            Recent Reports
        </h2>

        <p>
            Click a report to view full details.
        </p>

    </div>


    <div class="staff-table-wrap">

        <table class="staff-table">

            <thead>

                <tr>

                    <th>
                        Item
                    </th>

                    <th>
                        Type
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            $hasRecentReports = false;

            foreach ($recentReports as $report):

                $hasRecentReports = true;

                $id = (string)$report["_id"];

                $type =
                    $report["type"] ?? "unknown";

                $status =
                    $report["status"] ?? "pending";

            ?>

                <tr
                    class="report-clickable"
                    onclick="openReport('<?php echo htmlspecialchars($id, ENT_QUOTES); ?>')"
                >

                    <td>

                        <div style="
                            display:flex;
                            align-items:center;
                            gap:9px;
                        ">

                            <div class="staff-item-photo">

                                <?php if (!empty($report["image_path"])): ?>

                                    <!-- CLOUDINARY IMAGE -->
                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            (string)$report["image_path"],
                                            ENT_QUOTES
                                        );
                                        ?>"
                                        alt="Item"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="staff-no-photo"
                                        style="display:none;"
                                    >
                                        📦
                                    </div>

                                <?php else: ?>

                                    <div class="staff-no-photo">
                                        📦
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div>

                                <div class="staff-item-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $report["item_name"]
                                        ?? "Unknown"
                                    );
                                    ?>

                                </div>

                                <div class="staff-item-sub">

                                    <?php
                                    echo htmlspecialchars(
                                        $report["student_name"]
                                        ?? "Unknown"
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>

                    </td>


                    <td>

                        <span
                            class="staff-type <?php echo htmlspecialchars($type); ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                ucfirst($type)
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <span
                            class="staff-status <?php echo htmlspecialchars($status); ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                ucfirst($status)
                            );
                            ?>

                        </span>

                    </td>

                </tr>

            <?php endforeach; ?>


            <?php if (!$hasRecentReports): ?>

                <tr>

                    <td colspan="3">

                        <div class="staff-empty">

                            <div class="staff-empty-icon">
                                📭
                            </div>

                            <h3>
                                No reports
                            </h3>

                            <p>
                                No reports have been submitted.
                            </p>

                        </div>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- =====================================================
     RECENT CLAIMS
     ===================================================== -->

<section class="staff-panel">

    <div class="staff-panel-header">

        <a
            href="claims.php"
            class="staff-view-all"
        >
            View All
        </a>

        <h2>
            Recent Claims
        </h2>

        <p>
            Click a claim to view full details.
        </p>

    </div>


    <div class="staff-table-wrap">

        <table class="staff-table">

            <thead>

                <tr>

                    <th>
                        Item
                    </th>

                    <th>
                        Student
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            $hasRecentClaims = false;

            foreach ($recentClaims as $claim):

                $hasRecentClaims = true;

                $claimId =
                    (string)$claim["_id"];

                $item = null;


                try {

                    if (!empty($claim["item_id"])) {

                        $item =
                            $reports->findOne([
                                "_id" =>
                                    new MongoDB\BSON\ObjectId(
                                        $claim["item_id"]
                                    )
                            ]);

                    }

                } catch (Exception $e) {

                    $item = null;
                }


                $claimStatus =
                    $claim["status"]
                    ?? "pending";

            ?>

                <tr
                    class="claim-clickable"
                    onclick="openClaim('<?php echo htmlspecialchars($claimId, ENT_QUOTES); ?>')"
                >

                    <td>

                        <div style="
                            display:flex;
                            align-items:center;
                            gap:9px;
                        ">

                            <div class="staff-item-photo">

                                <?php if (
                                    $item &&
                                    !empty($item["image_path"])
                                ): ?>

                                    <!-- CLOUDINARY IMAGE -->
                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            (string)$item["image_path"],
                                            ENT_QUOTES
                                        );
                                        ?>"
                                        alt="Item"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="staff-no-photo"
                                        style="display:none;"
                                    >
                                        📦
                                    </div>

                                <?php else: ?>

                                    <div class="staff-no-photo">
                                        📦
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div>

                                <div class="staff-item-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $item["item_name"]
                                        ?? "Item Not Found"
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $claim["student_name"]
                            ?? "Unknown"
                        );
                        ?>

                    </td>


                    <td>

                        <span
                            class="staff-status <?php echo htmlspecialchars($claimStatus); ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                ucfirst($claimStatus)
                            );
                            ?>

                        </span>

                    </td>

                </tr>

            <?php endforeach; ?>


            <?php if (!$hasRecentClaims): ?>

                <tr>

                    <td colspan="3">

                        <div class="staff-empty">

                            <div class="staff-empty-icon">
                                📨
                            </div>

                            <h3>
                                No claims
                            </h3>

                            <p>
                                No claims have been submitted.
                            </p>

                        </div>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


</div>


</main>

</div>


<!-- =====================================================
     REPORT MODAL
     ===================================================== -->

<div
    id="reportModal"
    class="staff-modal"
    onclick="closeModal('reportModal', event)"
>

    <div
        class="staff-modal-box"
        onclick="event.stopPropagation()"
    >

        <div class="staff-modal-header">

            <h2>
                Report Details
            </h2>

            <button
                class="staff-modal-close"
                onclick="closeModal('reportModal')"
            >
                ×
            </button>

        </div>


        <div class="staff-modal-content">

            <div class="staff-detail-layout">


                <div
                    id="reportPhoto"
                    class="staff-large-photo"
                ></div>


                <div class="staff-detail-info">

                    <h3 id="reportItem">
                        Item
                    </h3>

                    <div class="staff-detail-subtitle">
                        Complete report information
                    </div>


                    <div class="staff-detail-grid">


                        <div class="staff-detail-field">

                            <label>
                                Type
                            </label>

                            <div id="reportType">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Status
                            </label>

                            <div id="reportStatus">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Student
                            </label>

                            <div id="reportStudent">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Location
                            </label>

                            <div id="reportLocation">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Date
                            </label>

                            <div id="reportDate">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field full">

                            <label>
                                Description
                            </label>

                            <div id="reportDescription">
                                —
                            </div>

                        </div>


                    </div>


                    <div
                        id="reportActions"
                        class="staff-modal-actions"
                    ></div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     CLAIM MODAL
     ===================================================== -->

<div
    id="claimModal"
    class="staff-modal"
    onclick="closeModal('claimModal', event)"
>

    <div
        class="staff-modal-box"
        onclick="event.stopPropagation()"
    >

        <div class="staff-modal-header">

            <h2>
                Claim Details
            </h2>

            <button
                class="staff-modal-close"
                onclick="closeModal('claimModal')"
            >
                ×
            </button>

        </div>


        <div class="staff-modal-content">

            <div class="staff-detail-layout">


                <div
                    id="claimPhoto"
                    class="staff-large-photo"
                ></div>


                <div class="staff-detail-info">

                    <h3 id="claimItem">
                        Item
                    </h3>

                    <div class="staff-detail-subtitle">
                        Complete claim information
                    </div>


                    <div class="staff-detail-grid">


                        <div class="staff-detail-field">

                            <label>
                                Student
                            </label>

                            <div id="claimStudent">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Status
                            </label>

                            <div id="claimStatus">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field">

                            <label>
                                Location
                            </label>

                            <div id="claimLocation">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field full">

                            <label>
                                Item Description
                            </label>

                            <div id="claimDescription">
                                —
                            </div>

                        </div>


                        <div class="staff-detail-field full">

                            <label>
                                Student's Reason
                            </label>

                            <div id="claimReason">
                                —
                            </div>

                        </div>


                    </div>


                    <div
                        id="claimActions"
                        class="staff-modal-actions"
                    ></div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

const reportData =
    <?php
    echo json_encode(
        $reportData,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    );
    ?>;

const claimData =
    <?php
    echo json_encode(
        $claimData,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    );
    ?>;


/* =====================================================
   REPORT MODAL
   ===================================================== */

function openReport(id) {

    const data = reportData[id];

    if (!data) return;


    document.getElementById("reportItem").textContent =
        data.itemName;

    document.getElementById("reportType").textContent =
        data.type;

    document.getElementById("reportStatus").textContent =
        data.status;

    document.getElementById("reportStudent").textContent =
        data.student;

    document.getElementById("reportLocation").textContent =
        data.location;

    document.getElementById("reportDate").textContent =
        data.date;

    document.getElementById("reportDescription").textContent =
        data.description;


    const photo =
        document.getElementById("reportPhoto");


    if (data.photo) {

        photo.innerHTML = `

            <img
                src="${escapeHtml(data.photo)}"
                alt="Item photo"
                onerror="
                    this.style.display='none';
                    document.getElementById('reportNoPhoto').style.display='flex';
                "
            >

            <div
                id="reportNoPhoto"
                class="staff-no-large-photo"
                style="display:none;"
            >
                📦
                <span>Image could not be loaded</span>
            </div>

        `;

    } else {

        photo.innerHTML = `

            <div class="staff-no-large-photo">
                📦
                <span>No photo uploaded</span>
            </div>

        `;

    }


    const actions =
        document.getElementById("reportActions");


    if (data.rawStatus === "pending") {

        actions.innerHTML = `

            <form method="POST" action="reports.php">

                <input
                    type="hidden"
                    name="report_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    class="staff-modal-action approve"
                    type="submit"
                    name="action"
                    value="approve"
                >
                    ✓ Approve Report
                </button>

            </form>


            <form method="POST" action="reports.php">

                <input
                    type="hidden"
                    name="report_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    class="staff-modal-action reject"
                    type="submit"
                    name="action"
                    value="reject"
                >
                    ✕ Reject Report
                </button>

            </form>

        `;

    } else {

        actions.innerHTML = `

            <span style="
                color:#89958d;
                font-size:10px;
                font-weight:700;
            ">
                This report has already been reviewed.
            </span>

        `;

    }


    document
        .getElementById("reportModal")
        .classList.add("show");

    document.body.style.overflow = "hidden";

}


/* =====================================================
   CLAIM MODAL
   ===================================================== */

function openClaim(id) {

    const data = claimData[id];

    if (!data) return;


    document.getElementById("claimItem").textContent =
        data.itemName;

    document.getElementById("claimStudent").textContent =
        data.student;

    document.getElementById("claimStatus").textContent =
        data.status;

    document.getElementById("claimLocation").textContent =
        data.location;

    document.getElementById("claimDescription").textContent =
        data.description;

    document.getElementById("claimReason").textContent =
        data.reason;


    const photo =
        document.getElementById("claimPhoto");


    if (data.photo) {

        photo.innerHTML = `

            <img
                src="${escapeHtml(data.photo)}"
                alt="Item photo"
                onerror="
                    this.style.display='none';
                    document.getElementById('claimNoPhoto').style.display='flex';
                "
            >

            <div
                id="claimNoPhoto"
                class="staff-no-large-photo"
                style="display:none;"
            >
                📦
                <span>Image could not be loaded</span>
            </div>

        `;

    } else {

        photo.innerHTML = `

            <div class="staff-no-large-photo">
                📦
                <span>No photo uploaded</span>
            </div>

        `;

    }


    const actions =
        document.getElementById("claimActions");


    if (data.rawStatus === "pending") {

        actions.innerHTML = `

            <form method="POST" action="claims.php">

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    class="staff-modal-action approve"
                    type="submit"
                    name="action"
                    value="approve"
                >
                    ✓ Approve Claim
                </button>

            </form>


            <form method="POST" action="claims.php">

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    class="staff-modal-action reject"
                    type="submit"
                    name="action"
                    value="reject"
                >
                    ✕ Reject Claim
                </button>

            </form>

        `;

    } else {

        actions.innerHTML = `

            <span style="
                color:#89958d;
                font-size:10px;
                font-weight:700;
            ">
                This claim has already been reviewed.
            </span>

        `;

    }


    document
        .getElementById("claimModal")
        .classList.add("show");

    document.body.style.overflow = "hidden";

}


/* =====================================================
   CLOSE MODAL
   ===================================================== */

function closeModal(id, event) {

    if (
        event &&
        event.target !==
        document.getElementById(id)
    ) {
        return;
    }


    document
        .getElementById(id)
        .classList.remove("show");

    document.body.style.overflow = "";

}


/* =====================================================
   ESC KEY
   ===================================================== */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            document
                .querySelectorAll(".staff-modal")
                .forEach(function(modal) {

                    modal.classList.remove("show");

                });

            document.body.style.overflow = "";

        }

    }
);


/* =====================================================
   ESCAPE HTML
   ===================================================== */

function escapeHtml(value) {

    return String(value)

        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}

</script>


</body>

</html>
