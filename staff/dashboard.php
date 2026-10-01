<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "staff") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;
use MongoDB\BSON\ObjectId;

$db = new Database();

$reports = $db->getDatabase()->reports;


/* =========================================================
   IMAGE URL HELPER
   =========================================================
   Cloudinary URLs are already complete URLs.
   Old local images still need ../
   ========================================================= */

function getStaffImageUrl($imagePath): string
{
    if (empty($imagePath)) {
        return "";
    }

    $imagePath = trim((string)$imagePath);

    // Cloudinary / external image
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    // Old local image
    return "../" . ltrim($imagePath, "/\\");
}


/* =========================================================
   APPROVE / REJECT
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $reportId = $_POST["report_id"] ?? "";
    $action = $_POST["action"] ?? "";

    if (
        $reportId !== "" &&
        ($action === "approve" || $action === "reject")
    ) {

        try {

            $report = $reports->findOne([
                "_id" => new ObjectId($reportId)
            ]);

            if ($report) {

                $newStatus =
                    $action === "approve"
                    ? "approved"
                    : "rejected";


                $reports->updateOne(
                    [
                        "_id" => $report["_id"]
                    ],
                    [
                        '$set' => [
                            "status" => $newStatus
                        ]
                    ]
                );

            }

        } catch (Exception $e) {

            // Invalid report ID

        }

    }

    header("Location: reports.php");
    exit;
}


/* =========================================================
   GET REPORTS
   ========================================================= */

$filter = $_GET["status"] ?? "all";

$allowedFilters = [
    "all",
    "pending",
    "approved",
    "rejected"
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = "all";
}

$reportQuery = [];

if ($filter !== "all") {
    $reportQuery["status"] = $filter;
}

$allReports = $reports->find(
    $reportQuery,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);

$allCount =
    $reports->countDocuments([]);

$pendingCount =
    $reports->countDocuments([
        "status" => "pending"
    ]);

$approvedCount =
    $reports->countDocuments([
        "status" => "approved"
    ]);

$rejectedCount =
    $reports->countDocuments([
        "status" => "rejected"
    ]);


/* =========================================================
   DATA FOR MODALS
   ========================================================= */

$reportData = [];

foreach ($reports->find(
    [],
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
) as $report) {

    $id = (string)$report["_id"];

    $type =
        $report["type"]
        ?? "unknown";

    $date =
        $type === "lost"
        ? ($report["date_lost"] ?? "—")
        : ($report["date_found"] ?? "—");


    /*
    |--------------------------------------------------------------------------
    | CLOUDINARY / LOCAL IMAGE
    |--------------------------------------------------------------------------
    */

    $photoPath = "";

    if (!empty($report["image_path"])) {
        $photoPath =
            getStaffImageUrl(
                $report["image_path"]
            );
    }


    $reportData[$id] = [

        "itemName" =>
            $report["item_name"]
            ?? "Unknown Item",

        "description" =>
            $report["description"]
            ?? "No description.",

        "student" =>
            $report["student_name"]
            ?? "Unknown Student",

        "location" =>
            $report["location"]
            ?? "Not specified",

        "type" =>
            ucfirst($type),

        "date" =>
            $date,

        "status" =>
            ucfirst(
                $report["status"]
                ?? "pending"
            ),

        "rawStatus" =>
            $report["status"]
            ?? "pending",

        "photo" =>
            $photoPath

    ];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <style>

        .report-filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .report-filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border: 1px solid #dce5df;
            border-radius: 10px;
            background: #f7faf8;
            color: #536158;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: .2s ease;
        }

        .report-filter-tab span {
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #e8eeea;
            font-size: 11px;
        }

        .report-filter-tab:hover,
        .report-filter-tab.active {
            background: #1f5c45;
            color: white;
            border-color: #1f5c45;
        }

        .report-filter-tab.active span {
            background: rgba(255,255,255,.18);
            color: white;
        }

        .report-filter-tab.approved.active {
            background: #237a4b;
            border-color: #237a4b;
        }

        .report-filter-tab.rejected.active {
            background: #a33a3a;
            border-color: #a33a3a;
        }

        @media (max-width: 600px) {

            .report-filter-tab {
                flex: 1 1 calc(50% - 10px);
                justify-content: center;
            }

        }

    </style>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Reports
    </title>

    <link
        rel="stylesheet"
        href="staff.css"
    >

</head>

<body class="staff-body">


<div class="staff-layout">


    <!-- SIDEBAR -->

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


            <a href="dashboard.php">

                <span class="staff-nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="reports.php"
                class="active"
            >

                <span class="staff-nav-icon">
                    📋
                </span>

                Reports

            </a>


            <a href="claims.php">

                <span class="staff-nav-icon">
                    📨
                </span>

                Claims

            </a>

        </nav>


        <div class="staff-user">

            <div class="staff-user-top">

                <div class="staff-avatar">

                    <?php
                    echo strtoupper(
                        substr(
                            $_SESSION["name"],
                            0,
                            1
                        )
                    );
                    ?>

                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["name"]
                        );
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


    <!-- MAIN -->

    <main class="staff-main">


        <div class="staff-topbar">

            <div class="staff-breadcrumb">

                Staff /
                <strong>
                    Reports
                </strong>

            </div>

            <div class="staff-date">
                📋 Report Management
            </div>

        </div>


        <section class="staff-page-heading">

            <h1>
                Manage Reports
            </h1>

            <p>
                Click any report to view its photo and complete details.
            </p>

        </section>


        <section class="staff-panel">


            <div class="staff-panel-header">

                <h2>
                    Submitted Reports
                </h2>

                <p>
                    Review student reports before approving them.
                </p>

                <div class="report-filter-tabs">

                    <a
                        href="reports.php?status=all"
                        class="report-filter-tab <?php echo $filter === "all" ? "active" : ""; ?>"
                    >
                        All
                        <span>
                            <?php echo $allCount; ?>
                        </span>
                    </a>


                    <a
                        href="reports.php?status=pending"
                        class="report-filter-tab <?php echo $filter === "pending" ? "active" : ""; ?>"
                    >
                        Pending
                        <span>
                            <?php echo $pendingCount; ?>
                        </span>
                    </a>


                    <a
                        href="reports.php?status=approved"
                        class="report-filter-tab approved <?php echo $filter === "approved" ? "active" : ""; ?>"
                    >
                        Approved
                        <span>
                            <?php echo $approvedCount; ?>
                        </span>
                    </a>


                    <a
                        href="reports.php?status=rejected"
                        class="report-filter-tab rejected <?php echo $filter === "rejected" ? "active" : ""; ?>"
                    >
                        Rejected
                        <span>
                            <?php echo $rejectedCount; ?>
                        </span>
                    </a>

                </div>

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
                                Student
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $hasReports = false;

                    foreach ($allReports as $report):

                        $hasReports = true;

                        $id =
                            (string)$report["_id"];

                        $type =
                            $report["type"]
                            ?? "unknown";

                        $status =
                            $report["status"]
                            ?? "pending";

                        $date =
                            $type === "lost"
                            ? ($report["date_lost"] ?? "—")
                            : ($report["date_found"] ?? "—");


                        /*
                        |--------------------------------------------------------------------------
                        | CLOUDINARY / LOCAL IMAGE
                        |--------------------------------------------------------------------------
                        */

                        $photoPath = "";

                        if (!empty($report["image_path"])) {

                            $photoPath =
                                getStaffImageUrl(
                                    $report["image_path"]
                                );

                        }

                    ?>

                        <tr
                            class="report-clickable"
                            onclick="openReport('<?php echo htmlspecialchars($id, ENT_QUOTES); ?>')"
                        >


                            <td>

                                <div
                                    style="
                                    display:flex;
                                    align-items:center;
                                    gap:10px;
                                    "
                                >

                                    <div class="staff-item-photo">

                                        <?php if (!empty($photoPath)): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($photoPath); ?>"
                                                alt="Item photo"
                                            >

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
                                                $report["description"]
                                                ?? ""
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
                                    ?? "Not specified"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $date
                                );
                                ?>

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


                            <td
                                onclick="event.stopPropagation();"
                            >

                                <?php if ($status === "pending"): ?>

                                    <div class="staff-button-row">

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="report_id"
                                                value="<?php echo htmlspecialchars($id); ?>"
                                            >

                                            <button
                                                class="staff-button staff-approve"
                                                type="submit"
                                                name="action"
                                                value="approve"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="report_id"
                                                value="<?php echo htmlspecialchars($id); ?>"
                                            >

                                            <button
                                                class="staff-button staff-reject"
                                                type="submit"
                                                name="action"
                                                value="reject"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <span
                                        style="
                                        color:#9aa59e;
                                        font-size:10px;
                                        font-weight:700;
                                        "
                                    >
                                        Reviewed
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (!$hasReports): ?>

                        <tr>

                            <td colspan="7">

                                <div class="staff-empty">

                                    <div class="staff-empty-icon">
                                        📭
                                    </div>

                                    <h3>
                                        No reports submitted
                                    </h3>

                                    <p>
                                        Student reports will appear here.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


    </main>

</div>


<!-- =====================================================
     REPORT MODAL
     ===================================================== -->

<div
    id="reportModal"
    class="staff-modal"
    onclick="closeReport(event)"
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
                onclick="closeReport()"
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
                                Report Type
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


<script>

const reportData =
    <?php echo json_encode($reportData); ?>;


function openReport(id) {

    const data = reportData[id];

    if (!data) {
        return;
    }


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

        photo.innerHTML =
            `<img src="${escapeHtml(data.photo)}" alt="Item photo">`;

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

            <form method="POST">

                <input
                    type="hidden"
                    name="report_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    type="submit"
                    name="action"
                    value="approve"
                    class="staff-modal-action approve"
                >
                    ✓ Approve Report
                </button>

            </form>


            <form method="POST">

                <input
                    type="hidden"
                    name="report_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    type="submit"
                    name="action"
                    value="reject"
                    class="staff-modal-action reject"
                >
                    ✕ Reject Report
                </button>

            </form>

        `;

    } else {

        actions.innerHTML =
            `<span style="color:#89958d;font-size:10px;font-weight:700;">
                This report has already been reviewed.
            </span>`;

    }


    document
        .getElementById("reportModal")
        .classList.add("show");

    document.body.style.overflow = "hidden";
}


function closeReport(event) {

    if (
        event &&
        event.target !==
        document.getElementById("reportModal")
    ) {
        return;
    }


    document
        .getElementById("reportModal")
        .classList.remove("show");

    document.body.style.overflow = "";
}


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {
            closeReport();
        }

    }
);


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