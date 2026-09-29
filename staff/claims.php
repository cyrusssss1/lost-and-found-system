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


/* =========================================================
   APPROVE / REJECT CLAIM
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claimId = $_POST["claim_id"] ?? "";
    $action = $_POST["action"] ?? "";

    if (
        $claimId !== "" &&
        ($action === "approve" || $action === "reject")
    ) {

        try {

            $claim = $claims->findOne([
                "_id" => new ObjectId($claimId)
            ]);

            if ($claim) {

                $newStatus =
                    $action === "approve"
                    ? "approved"
                    : "rejected";

                $claims->updateOne(
                    [
                        "_id" => $claim["_id"]
                    ],
                    [
                        '$set' => [
                            "status" => $newStatus
                        ]
                    ]
                );
            }

        } catch (Exception $e) {

            // Invalid claim ID

        }
    }

    header("Location: claims.php");
    exit;
}


/* =========================================================
   FILTER
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


/* =========================================================
   CLAIM QUERY
   ========================================================= */

$claimQuery = [];

if ($filter !== "all") {
    $claimQuery["status"] = $filter;
}

$allClaims = $claims->find(
    $claimQuery,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


/* =========================================================
   COUNTS
   ========================================================= */

$allCount = $claims->countDocuments([]);

$pendingCount = $claims->countDocuments([
    "status" => "pending"
]);

$approvedCount = $claims->countDocuments([
    "status" => "approved"
]);

$rejectedCount = $claims->countDocuments([
    "status" => "rejected"
]);


/* =========================================================
   PREPARE CLAIM DATA FOR MODAL
   ========================================================= */

$claimData = [];

foreach ($claims->find(
    [],
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
) as $claim) {

    $id = (string)$claim["_id"];

    $item = null;

    try {

        if (!empty($claim["item_id"])) {

            $item = $reports->findOne([
                "_id" => new ObjectId(
                    (string)$claim["item_id"]
                )
            ]);

        }

    } catch (Exception $e) {

        $item = null;

    }


    /*
     * IMPORTANT:
     * Student reports save photos as image_path.
     */

    $photoPath = "";

    if (
        $item &&
        !empty($item["image_path"])
    ) {

        $photoPath =
            "../" .
            ltrim(
                (string)$item["image_path"],
                "/\\"
            );
    }


    $claimData[$id] = [

        "itemName" =>
            $item["item_name"]
            ?? "Item Not Found",

        "description" =>
            $item["description"]
            ?? "No description.",

        "student" =>
            $claim["student_name"]
            ?? "Unknown Student",

        "location" =>
            $item["location"]
            ?? "Not specified",

        "reason" =>
            $claim["reason"]
            ?? "No reason provided.",

        "status" =>
            ucfirst(
                $claim["status"]
                ?? "pending"
            ),

        "rawStatus" =>
            $claim["status"]
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Claims
    </title>

    <link
        rel="stylesheet"
        href="staff.css"
    >

    <style>

        .claim-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .claim-filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 10px;
            text-decoration: none;
            background: #f1f4f2;
            color: #56635c;
            font-size: 11px;
            font-weight: 800;
            border: 1px solid #e1e7e3;
            transition: .2s;
        }

        .claim-filter-tab:hover {
            background: #e6ece8;
            transform: translateY(-1px);
        }

        .claim-filter-tab.active {
            background: #173b2b;
            color: white;
            border-color: #173b2b;
        }

        .claim-filter-tab.approved.active {
            background: #23734a;
            border-color: #23734a;
        }

        .claim-filter-tab.rejected.active {
            background: #a33b3b;
            border-color: #a33b3b;
        }

        .claim-filter-count {
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            font-size: 9px;
        }

        .claim-filter-tab:not(.active) .claim-filter-count {
            background: #dfe7e2;
            color: #56635c;
        }

        .staff-large-photo img {
            width: 100%;
            max-height: 360px;
            object-fit: contain;
            border-radius: 14px;
            display: block;
            background: #f2f5f3;
        }

        .staff-item-photo img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
        }

        .staff-no-photo {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ef;
        }

    </style>

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


            <a href="reports.php">

                <span class="staff-nav-icon">
                    📋
                </span>

                Reports

            </a>


            <a
                href="claims.php"
                class="active"
            >

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
                    Claims
                </strong>

            </div>

            <div class="staff-date">
                📨 Claim Management
            </div>

        </div>


        <section class="staff-page-heading">

            <h1>
                Manage Claims
            </h1>

            <p>
                Review student claims and verify the item information before making a decision.
            </p>

        </section>


        <section class="staff-panel">


            <div class="staff-panel-header">

                <h2>
                    Submitted Claims
                </h2>

                <p>
                    Click a claim to view the item photo and complete claim details.
                </p>

            </div>


            <!-- FILTERS -->

            <div class="claim-filter-bar">

                <a
                    href="claims.php?status=all"
                    class="claim-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>"
                >

                    All

                    <span class="claim-filter-count">
                        <?php echo $allCount; ?>
                    </span>

                </a>


                <a
                    href="claims.php?status=pending"
                    class="claim-filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>"
                >

                    Pending

                    <span class="claim-filter-count">
                        <?php echo $pendingCount; ?>
                    </span>

                </a>


                <a
                    href="claims.php?status=approved"
                    class="claim-filter-tab approved <?php echo $filter === 'approved' ? 'active' : ''; ?>"
                >

                    Approved

                    <span class="claim-filter-count">
                        <?php echo $approvedCount; ?>
                    </span>

                </a>


                <a
                    href="claims.php?status=rejected"
                    class="claim-filter-tab rejected <?php echo $filter === 'rejected' ? 'active' : ''; ?>"
                >

                    Rejected

                    <span class="claim-filter-count">
                        <?php echo $rejectedCount; ?>
                    </span>

                </a>

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
                                Location
                            </th>

                            <th>
                                Reason
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

                    $hasClaims = false;

                    foreach ($allClaims as $claim):

                        $hasClaims = true;

                        $id = (string)$claim["_id"];

                        $item = null;


                        try {

                            if (!empty($claim["item_id"])) {

                                $item = $reports->findOne([
                                    "_id" =>
                                        new ObjectId(
                                            (string)$claim["item_id"]
                                        )
                                ]);

                            }

                        } catch (Exception $e) {

                            $item = null;

                        }


                        $status =
                            $claim["status"]
                            ?? "pending";


                        /*
                         * IMPORTANT:
                         * Use image_path, not photo.
                         */

                        $photoPath = "";

                        if (
                            $item &&
                            !empty($item["image_path"])
                        ) {

                            $photoPath =
                                "../" .
                                ltrim(
                                    (string)$item["image_path"],
                                    "/\\"
                                );
                        }

                    ?>

                        <tr
                            class="claim-clickable"
                            onclick="openClaim('<?php echo htmlspecialchars($id, ENT_QUOTES); ?>')"
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

                                        <?php if ($photoPath !== ""): ?>

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
                                                $item["item_name"]
                                                ?? "Item Not Found"
                                            );
                                            ?>

                                        </div>

                                        <div class="staff-item-sub">

                                            Click to view details

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

                                <?php
                                echo htmlspecialchars(
                                    $item["location"]
                                    ?? "Not specified"
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $reason =
                                    $claim["reason"]
                                    ?? "No reason";

                                echo htmlspecialchars(
                                    mb_strimwidth(
                                        $reason,
                                        0,
                                        45,
                                        "..."
                                    )
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
                                                name="claim_id"
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
                                                name="claim_id"
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


                    <?php if (!$hasClaims): ?>

                        <tr>

                            <td colspan="6">

                                <div class="staff-empty">

                                    <div class="staff-empty-icon">
                                        📨
                                    </div>

                                    <h3>
                                        No <?php echo htmlspecialchars($filter); ?> claims
                                    </h3>

                                    <p>
                                        There are currently no claims in this section.
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
     CLAIM DETAILS MODAL
     ===================================================== -->

<div
    id="claimModal"
    class="staff-modal"
    onclick="closeClaim(event)"
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
                onclick="closeClaim()"
            >
                ×
            </button>

        </div>


        <div class="staff-modal-content">

            <div class="staff-detail-layout">


                <!-- ITEM PHOTO -->

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

const claimData =
    <?php echo json_encode($claimData); ?>;


function openClaim(id) {

    const data = claimData[id];

    if (!data) {
        return;
    }


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


    /* =====================================================
       SHOW PHOTO
       ===================================================== */

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


    /* =====================================================
       ACTIONS
       ===================================================== */

    const actions =
        document.getElementById("claimActions");


    if (data.rawStatus === "pending") {

        actions.innerHTML = `

            <form method="POST">

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    type="submit"
                    name="action"
                    value="approve"
                    class="staff-modal-action approve"
                >
                    ✓ Approve Claim
                </button>

            </form>


            <form method="POST">

                <input
                    type="hidden"
                    name="claim_id"
                    value="${escapeHtml(id)}"
                >

                <button
                    type="submit"
                    name="action"
                    value="reject"
                    class="staff-modal-action reject"
                >
                    ✕ Reject Claim
                </button>

            </form>

        `;

    } else {

        actions.innerHTML =
            `<span style="color:#89958d;font-size:10px;font-weight:700;">
                This claim has already been reviewed.
            </span>`;

    }


    document
        .getElementById("claimModal")
        .classList.add("show");

    document.body.style.overflow = "hidden";
}


function closeClaim(event) {

    if (
        event &&
        event.target !==
        document.getElementById("claimModal")
    ) {
        return;
    }


    document
        .getElementById("claimModal")
        .classList.remove("show");

    document.body.style.overflow = "";
}


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {
            closeClaim();
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