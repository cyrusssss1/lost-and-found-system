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
$claims = $db->getDatabase()->claims;
$reports = $db->getDatabase()->reports;


/*
|--------------------------------------------------------------------------
| IMAGE URL HELPER
|--------------------------------------------------------------------------
| Cloudinary URLs are already complete URLs.
| Old local images still need ../
*/
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


/*
|--------------------------------------------------------------------------
| APPROVE / REJECT CLAIM
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $claimId = $_POST["claim_id"] ?? "";

    if (
        in_array($action, ["approve", "reject"], true) &&
        !empty($claimId) &&
        preg_match('/^[a-f0-9]{24}$/i', $claimId)
    ) {

        try {
            $objectId = new ObjectId($claimId);

            $newStatus = ($action === "approve")
                ? "approved"
                : "rejected";

            $claims->updateOne(
                ["_id" => $objectId],
                [
                    '$set' => [
                        "status" => $newStatus,
                        "updated_at" => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );

        } catch (Exception $e) {
            // Ignore invalid IDs
        }
    }

    header("Location: claims.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/
$filter = $_GET["filter"] ?? "all";

$allowedFilters = [
    "all",
    "pending",
    "approved",
    "rejected"
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = "all";
}


/*
|--------------------------------------------------------------------------
| GET CLAIMS
|--------------------------------------------------------------------------
*/
$query = [];

if ($filter !== "all") {
    $query["status"] = $filter;
}

$allClaims = $claims
    ->find(
        $query,
        [
            "sort" => [
                "created_at" => -1
            ]
        ]
    )
    ->toArray();


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/
$totalCount = $claims->countDocuments([]);

$pendingCount = $claims->countDocuments([
    "status" => "pending"
]);

$approvedCount = $claims->countDocuments([
    "status" => "approved"
]);

$rejectedCount = $claims->countDocuments([
    "status" => "rejected"
]);


/*
|--------------------------------------------------------------------------
| CLAIM DATA FOR MODALS
|--------------------------------------------------------------------------
*/
$claimData = [];

foreach ($allClaims as $claim) {

    $id = (string)$claim["_id"];

    $item = null;

    if (!empty($claim["item_id"])) {

        try {

            $itemId = $claim["item_id"];

            if ($itemId instanceof ObjectId) {

                $item = $reports->findOne([
                    "_id" => $itemId
                ]);

            } elseif (
                is_string($itemId) &&
                preg_match('/^[a-f0-9]{24}$/i', $itemId)
            ) {

                $item = $reports->findOne([
                    "_id" => new ObjectId($itemId)
                ]);
            }

        } catch (Exception $e) {
            $item = null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CLOUDINARY / LOCAL IMAGE
    |--------------------------------------------------------------------------
    */
    $photoPath = "";

    if ($item && !empty($item["image_path"])) {
        $photoPath = getStaffImageUrl($item["image_path"]);
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
            $photoPath
    ];
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

    <title>Staff Claims</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #f4f7fb;
            color: #1f2937;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;

            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #0f172a 0%,
                    #172554 100%
                );

            color: white;

            padding: 25px 18px;

            box-shadow:
                4px 0 20px rgba(15, 23, 42, 0.12);

            z-index: 100;
        }


        .brand {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 5px 10px 30px;
        }


        .brand-icon {
            width: 42px;
            height: 42px;

            border-radius: 12px;

            background: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 21px;
        }


        .brand-text h2 {
            font-size: 17px;
            font-weight: 700;
        }


        .brand-text p {
            font-size: 11px;
            color: #94a3b8;

            margin-top: 2px;
        }


        .nav-section {
            margin-top: 10px;
        }


        .nav-label {
            font-size: 10px;

            color: #64748b;

            text-transform: uppercase;

            letter-spacing: 1px;

            padding:
                0 12px
                10px;
        }


        .nav-link {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px;

            border-radius: 10px;

            color: #cbd5e1;

            text-decoration: none;

            font-size: 14px;

            margin-bottom: 5px;

            transition: 0.2s;
        }


        .nav-link:hover {
            background: rgba(255,255,255,0.07);
            color: white;
        }


        .nav-link.active {
            background: #2563eb;
            color: white;

            box-shadow:
                0 5px 15px rgba(37,99,235,0.25);
        }


        .nav-icon {
            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-bottom {
            position: absolute;

            bottom: 25px;

            left: 18px;
            right: 18px;
        }


        .account-link {
            display: flex;

            align-items: center;

            gap: 10px;

            color: #cbd5e1;

            text-decoration: none;

            padding: 12px;

            border-radius: 10px;

            font-size: 13px;
        }


        .account-link:hover {
            background: rgba(255,255,255,0.07);
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            margin-left: 250px;

            min-height: 100vh;

            padding: 35px 40px;
        }


        .page-header {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            margin-bottom: 30px;
        }


        .page-title h1 {
            font-size: 27px;

            color: #0f172a;

            margin-bottom: 6px;
        }


        .page-title p {
            color: #64748b;

            font-size: 14px;
        }


        /* =========================================================
           STATS
        ========================================================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 28px;
        }


        .stat-card {
            background: white;

            border-radius: 15px;

            padding: 20px;

            border:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            gap: 15px;

            box-shadow:
                0 3px 12px
                rgba(15,23,42,0.04);
        }


        .stat-icon {
            width: 46px;
            height: 46px;

            border-radius: 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;

            background: #eff6ff;
        }


        .stat-number {
            font-size: 23px;

            font-weight: 700;

            color: #0f172a;
        }


        .stat-label {
            color: #64748b;

            font-size: 12px;

            margin-top: 2px;
        }


        /* =========================================================
           FILTERS
        ========================================================= */

        .filter-card {
            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 15px;

            padding: 8px;

            display: flex;

            gap: 5px;

            margin-bottom: 18px;
        }


        .filter-link {
            padding:
                10px
                18px;

            border-radius: 9px;

            text-decoration: none;

            color: #64748b;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s;
        }


        .filter-link:hover {
            background: #f1f5f9;
        }


        .filter-link.active {
            background: #2563eb;

            color: white;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-card {
            background: white;

            border:
                1px solid #e5e7eb;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 4px 15px
                rgba(15,23,42,0.04);
        }


        .table-header {
            padding: 20px 22px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .table-header h2 {
            font-size: 16px;

            color: #0f172a;
        }


        .table-header span {
            color: #94a3b8;

            font-size: 12px;
        }


        table {
            width: 100%;

            border-collapse: collapse;
        }


        th {
            text-align: left;

            padding:
                13px
                18px;

            background: #f8fafc;

            color: #64748b;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }


        td {
            padding:
                15px
                18px;

            border-top:
                1px solid #eef2f7;

            font-size: 13px;

            vertical-align: middle;
        }


        tr:hover td {
            background: #fafcff;
        }


        .item-cell {
            display: flex;

            align-items: center;

            gap: 12px;
        }


        .item-image {
            width: 48px;
            height: 48px;

            border-radius: 10px;

            overflow: hidden;

            background: #f1f5f9;

            display: flex;

            align-items: center;
            justify-content: center;

            flex-shrink: 0;
        }


        .item-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .no-photo {
            font-size: 20px;
        }


        .item-name {
            font-weight: 600;

            color: #0f172a;
        }


        .item-sub {
            font-size: 11px;

            color: #94a3b8;

            margin-top: 3px;
        }


        .student-name {
            font-weight: 600;

            color: #334155;
        }


        .location {
            color: #64748b;
        }


        .status {
            display: inline-flex;

            align-items: center;

            padding:
                5px
                10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;
        }


        .status.pending {
            background: #fff7ed;

            color: #c2410c;
        }


        .status.approved {
            background: #ecfdf5;

            color: #047857;
        }


        .status.rejected {
            background: #fef2f2;

            color: #b91c1c;
        }


        .actions {
            display: flex;

            gap: 6px;
        }


        .btn {
            border: none;

            cursor: pointer;

            border-radius: 8px;

            padding:
                8px
                11px;

            font-size: 11px;

            font-weight: 600;

            transition: 0.2s;
        }


        .btn-view {
            background: #eff6ff;

            color: #2563eb;
        }


        .btn-view:hover {
            background: #dbeafe;
        }


        .btn-approve {
            background: #ecfdf5;

            color: #047857;
        }


        .btn-approve:hover {
            background: #d1fae5;
        }


        .btn-reject {
            background: #fef2f2;

            color: #dc2626;
        }


        .btn-reject:hover {
            background: #fee2e2;
        }


        .empty {
            padding: 70px 20px;

            text-align: center;

            color: #94a3b8;
        }


        .empty-icon {
            font-size: 42px;

            margin-bottom: 12px;
        }


        .empty h3 {
            color: #475569;

            font-size: 16px;

            margin-bottom: 5px;
        }


        .empty p {
            font-size: 13px;
        }


        /* =========================================================
           MODAL
        ========================================================= */

        .modal-overlay {
            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(15,23,42,0.60);

            z-index: 1000;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        .modal-overlay.show {
            display: flex;
        }


        .modal {
            width: 100%;

            max-width: 720px;

            max-height: 90vh;

            overflow-y: auto;

            background: white;

            border-radius: 18px;

            box-shadow:
                0 25px 70px
                rgba(0,0,0,0.25);

            animation:
                modalIn 0.2s ease;
        }


        @keyframes modalIn {

            from {
                opacity: 0;

                transform:
                    translateY(10px)
                    scale(0.98);
            }

            to {
                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }

        }


        .modal-header {
            padding:
                20px 24px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .modal-header h2 {
            font-size: 18px;

            color: #0f172a;
        }


        .close-btn {
            border: none;

            background: #f1f5f9;

            width: 34px;
            height: 34px;

            border-radius: 50%;

            cursor: pointer;

            font-size: 18px;

            color: #64748b;
        }


        .close-btn:hover {
            background: #e2e8f0;
        }


        .modal-body {
            padding: 24px;
        }


        .photo-box {
            width: 100%;

            height: 260px;

            background: #f8fafc;

            border-radius: 14px;

            overflow: hidden;

            margin-bottom: 22px;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .photo-box img {
            width: 100%;
            height: 100%;

            object-fit: contain;
        }


        .staff-no-large-photo {
            color: #94a3b8;

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 8px;

            font-size: 13px;
        }


        .staff-no-large-photo:first-letter {
            font-size: 35px;
        }


        .details {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 16px;
        }


        .detail {
            background: #f8fafc;

            border-radius: 10px;

            padding: 13px;
        }


        .detail.full {
            grid-column: 1 / -1;
        }


        .detail-label {
            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            color: #94a3b8;

            margin-bottom: 5px;
        }


        .detail-value {
            font-size: 13px;

            color: #334155;

            line-height: 1.5;
        }


        .modal-footer {
            padding:
                18px 24px;

            border-top:
                1px solid #e5e7eb;

            display: flex;

            justify-content: flex-end;

            gap: 8px;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .main {
                padding:
                    25px;
            }

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
            }

        }


        @media (max-width: 800px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;
            }

            .sidebar-bottom {
                position: static;

                margin-top: 20px;
            }

            .main {
                margin-left: 0;
            }

            table {
                min-width: 850px;
            }

            .table-card {
                overflow-x: auto;
            }

        }


        @media (max-width: 600px) {

            .stats {
                grid-template-columns:
                    1fr;
            }

            .details {
                grid-template-columns:
                    1fr;
            }

            .detail.full {
                grid-column: auto;
            }

        }

    </style>

</head>


<body>


<!-- =============================================================
     SIDEBAR
============================================================= -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            🔎
        </div>

        <div class="brand-text">
            <h2>Lost & Found</h2>
            <p>Staff Management</p>
        </div>

    </div>


    <div class="nav-section">

        <div class="nav-label">
            Management
        </div>


        <a
            href="dashboard.php"
            class="nav-link"
        >
            <span class="nav-icon">📊</span>
            Dashboard
        </a>


        <a
            href="reports.php"
            class="nav-link"
        >
            <span class="nav-icon">📦</span>
            Reports
        </a>


        <a
            href="claims.php"
            class="nav-link active"
        >
            <span class="nav-icon">📋</span>
            Claims
        </a>

    </div>


    <div class="sidebar-bottom">

        <a
            href="account.php"
            class="account-link"
        >
            <span>👤</span>
            Staff Account
        </a>


        <a
            href="../logout.php"
            class="account-link"
        >
            <span>↪</span>
            Sign Out
        </a>

    </div>

</aside>


<!-- =============================================================
     MAIN
============================================================= -->

<main class="main">


    <div class="page-header">

        <div class="page-title">

            <h1>Claim Management</h1>

            <p>
                Review and manage student claims for reported items.
            </p>

        </div>

    </div>


    <!-- =========================================================
         STATS
    ========================================================= -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div>

                <div class="stat-number">
                    <?php echo $totalCount; ?>
                </div>

                <div class="stat-label">
                    Total Claims
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <div class="stat-number">
                    <?php echo $pendingCount; ?>
                </div>

                <div class="stat-label">
                    Pending
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✅
            </div>

            <div>

                <div class="stat-number">
                    <?php echo $approvedCount; ?>
                </div>

                <div class="stat-label">
                    Approved
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ❌
            </div>

            <div>

                <div class="stat-number">
                    <?php echo $rejectedCount; ?>
                </div>

                <div class="stat-label">
                    Rejected
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         FILTERS
    ========================================================= -->

    <div class="filter-card">

        <a
            href="claims.php?filter=all"
            class="filter-link <?php echo $filter === 'all' ? 'active' : ''; ?>"
        >
            All
        </a>


        <a
            href="claims.php?filter=pending"
            class="filter-link <?php echo $filter === 'pending' ? 'active' : ''; ?>"
        >
            Pending
        </a>


        <a
            href="claims.php?filter=approved"
            class="filter-link <?php echo $filter === 'approved' ? 'active' : ''; ?>"
        >
            Approved
        </a>


        <a
            href="claims.php?filter=rejected"
            class="filter-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>"
        >
            Rejected
        </a>

    </div>


    <!-- =========================================================
         CLAIM TABLE
    ========================================================= -->

    <div class="table-card">

        <div class="table-header">

            <h2>Student Claims</h2>

            <span>
                <?php echo count($allClaims); ?> result(s)
            </span>

        </div>


        <?php if (empty($allClaims)): ?>

            <div class="empty">

                <div class="empty-icon">
                    📭
                </div>

                <h3>No claims found</h3>

                <p>
                    There are no claims matching this filter.
                </p>

            </div>

        <?php else: ?>


            <table>

                <thead>

                    <tr>

                        <th>Item</th>

                        <th>Student</th>

                        <th>Location</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($allClaims as $claim): ?>


                    <?php

                    $claimId = (string)$claim["_id"];

                    $item = null;


                    if (!empty($claim["item_id"])) {

                        try {

                            $itemId = $claim["item_id"];

                            if ($itemId instanceof ObjectId) {

                                $item = $reports->findOne([
                                    "_id" => $itemId
                                ]);

                            } elseif (
                                is_string($itemId) &&
                                preg_match('/^[a-f0-9]{24}$/i', $itemId)
                            ) {

                                $item = $reports->findOne([
                                    "_id" => new ObjectId($itemId)
                                ]);

                            }

                        } catch (Exception $e) {

                            $item = null;

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CLOUDINARY / LOCAL IMAGE
                    |--------------------------------------------------------------------------
                    */

                    $photoPath = "";

                    if (
                        $item &&
                        !empty($item["image_path"])
                    ) {

                        $photoPath =
                            getStaffImageUrl(
                                $item["image_path"]
                            );

                    }


                    $itemName =
                        $item["item_name"]
                        ?? "Item Not Found";


                    $studentName =
                        $claim["student_name"]
                        ?? "Unknown Student";


                    $location =
                        $item["location"]
                        ?? "Not specified";


                    $status =
                        $claim["status"]
                        ?? "pending";

                    ?>


                    <tr>


                        <!-- ITEM -->

                        <td>

                            <div class="item-cell">


                                <div class="item-image">

                                    <?php if (!empty($photoPath)): ?>

                                        <img
                                            src="<?php echo htmlspecialchars($photoPath); ?>"
                                            alt="Item photo"
                                            onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=&quot;no-photo&quot;>📦</div>';"
                                        >

                                    <?php else: ?>

                                        <div class="no-photo">
                                            📦
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div>

                                    <div class="item-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $itemName
                                        );
                                        ?>

                                    </div>


                                    <div class="item-sub">
                                        Claim #<?php echo htmlspecialchars(substr($claimId, 0, 8)); ?>
                                    </div>

                                </div>


                            </div>

                        </td>


                        <!-- STUDENT -->

                        <td>

                            <div class="student-name">

                                <?php
                                echo htmlspecialchars(
                                    $studentName
                                );
                                ?>

                            </div>

                        </td>


                        <!-- LOCATION -->

                        <td>

                            <div class="location">

                                📍
                                <?php
                                echo htmlspecialchars(
                                    $location
                                );
                                ?>

                            </div>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span
                                class="status <?php echo htmlspecialchars($status); ?>"
                            >

                                <?php
                                echo ucfirst(
                                    htmlspecialchars($status)
                                );
                                ?>

                            </span>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <div class="actions">


                                <button
                                    type="button"
                                    class="btn btn-view"
                                    onclick="openClaimModal('<?php echo htmlspecialchars($claimId, ENT_QUOTES); ?>')"
                                >
                                    View
                                </button>


                                <?php if ($status === "pending"): ?>


                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Approve this claim?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="claim_id"
                                            value="<?php echo htmlspecialchars($claimId); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-approve"
                                        >
                                            Approve
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Reject this claim?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="claim_id"
                                            value="<?php echo htmlspecialchars($claimId); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-reject"
                                        >
                                            Reject
                                        </button>

                                    </form>


                                <?php endif; ?>


                            </div>

                        </td>


                    </tr>


                <?php endforeach; ?>

                </tbody>

            </table>


        <?php endif; ?>

    </div>


</main>


<!-- =============================================================
     CLAIM MODAL
============================================================= -->

<div
    class="modal-overlay"
    id="claimModal"
    onclick="closeClaimModal(event)"
>


    <div
        class="modal"
        onclick="event.stopPropagation();"
    >


        <div class="modal-header">

            <h2>
                Claim Details
            </h2>


            <button
                class="close-btn"
                onclick="closeClaimModal()"
            >
                ×
            </button>

        </div>


        <div class="modal-body">


            <div
                class="photo-box"
                id="claimPhoto"
            >
                <div class="staff-no-large-photo">
                    📦
                    <span>Loading...</span>
                </div>
            </div>


            <div class="details">


                <div class="detail">

                    <div class="detail-label">
                        Item
                    </div>

                    <div
                        class="detail-value"
                        id="claimItem"
                    >
                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Student
                    </div>

                    <div
                        class="detail-value"
                        id="claimStudent"
                    >
                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Location
                    </div>

                    <div
                        class="detail-value"
                        id="claimLocation"
                    >
                    </div>

                </div>


                <div class="detail">

                    <div class="detail-label">
                        Status
                    </div>

                    <div
                        class="detail-value"
                        id="claimStatus"
                    >
                    </div>

                </div>


                <div class="detail full">

                    <div class="detail-label">
                        Item Description
                    </div>

                    <div
                        class="detail-value"
                        id="claimDescription"
                    >
                    </div>

                </div>


                <div class="detail full">

                    <div class="detail-label">
                        Claim Reason
                    </div>

                    <div
                        class="detail-value"
                        id="claimReason"
                    >
                    </div>

                </div>


            </div>


        </div>


        <div
            class="modal-footer"
            id="modalActions"
        >
        </div>


    </div>

</div>


<script>

const claimData =
    <?php
    echo json_encode(
        $claimData,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    );
    ?>;


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(value) {

    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   OPEN MODAL
========================================================= */

function openClaimModal(id) {

    const data = claimData[id];

    if (!data) {
        return;
    }


    document.getElementById("claimItem").textContent =
        data.itemName || "Item Not Found";


    document.getElementById("claimStudent").textContent =
        data.student || "Unknown Student";


    document.getElementById("claimLocation").textContent =
        data.location || "Not specified";


    document.getElementById("claimStatus").textContent =
        data.status || "Pending";


    document.getElementById("claimDescription").textContent =
        data.description || "No description.";


    document.getElementById("claimReason").textContent =
        data.reason || "No reason provided.";


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    const photo =
        document.getElementById("claimPhoto");


    if (data.photo) {

        photo.innerHTML = `
            <img
                src="${escapeHtml(data.photo)}"
                alt="Item photo"
                onerror="this.parentElement.innerHTML='<div class=&quot;staff-no-large-photo&quot;>📦<span>Photo could not be loaded</span></div>';"
            >
        `;

    } else {

        photo.innerHTML = `
            <div class="staff-no-large-photo">
                📦
                <span>No photo uploaded</span>
            </div>
        `;

    }


    /*
    |--------------------------------------------------------------------------
    | ACTION BUTTONS
    |--------------------------------------------------------------------------
    */

    const actions =
        document.getElementById("modalActions");


    if (data.rawStatus === "pending") {

        actions.innerHTML = `

            <form
                method="POST"
                style="display:inline;"
                onsubmit="return confirm('Approve this claim?');"
            >

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <input
                    type="hidden"
                    name="action"
                    value="approve"
                >

                <button
                    type="submit"
                    class="btn btn-approve"
                >
                    ✓ Approve Claim
                </button>

            </form>


            <form
                method="POST"
                style="display:inline;"
                onsubmit="return confirm('Reject this claim?');"
            >

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <input
                    type="hidden"
                    name="action"
                    value="reject"
                >

                <button
                    type="submit"
                    class="btn btn-reject"
                >
                    ✕ Reject Claim
                </button>

            </form>

        `;

    } else {

        actions.innerHTML = `
            <button
                type="button"
                class="btn btn-view"
                onclick="closeClaimModal()"
            >
                Close
            </button>
        `;

    }


    document
        .getElementById("claimModal")
        .classList.add("show");

}


/* =========================================================
   CLOSE MODAL
========================================================= */

function closeClaimModal(event) {

    if (
        event &&
        event.target &&
        event.target.id !== "claimModal"
    ) {
        return;
    }


    document
        .getElementById("claimModal")
        .classList.remove("show");

}


/* =========================================================
   ESC KEY
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            document
                .getElementById("claimModal")
                .classList.remove("show");

        }

    }
);

</script>


</body>

</html>