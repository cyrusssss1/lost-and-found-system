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

$database = $db->getDatabase();

$claims = $database->claims;
$reports = $database->reports;

$message = "";
$messageType = "";


/* =========================================================
   APPROVE / REJECT CLAIM
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claimId = $_POST["claim_id"] ?? "";
    $newStatus = $_POST["status"] ?? "";

    if (
        $claimId !== "" &&
        in_array($newStatus, ["approved", "rejected"])
    ) {

        try {

            $result = $claims->updateOne(
                [
                    "_id" => new ObjectId($claimId)
                ],
                [
                    '$set' => [
                        "status" => $newStatus
                    ]
                ]
            );

            if ($result->getModifiedCount() > 0) {

                $message =
                    "Claim has been " .
                    $newStatus .
                    " successfully.";

                $messageType = "success";

            } else {

                $message = "Claim was not updated.";
                $messageType = "error";

            }

        } catch (Exception $e) {

            $message = "Unable to update the claim.";
            $messageType = "error";

        }

    }

}


/* =========================================================
   FILTER
   ========================================================= */

$filter = $_GET["status"] ?? "review";

if ($filter === "review") {

    $query = [
        "status" => "pending"
    ];

} elseif (
    in_array(
        $filter,
        ["approved", "rejected"]
    )
) {

    $query = [
        "status" => $filter
    ];

} else {

    $query = [];

}


/* =========================================================
   GET CLAIMS
   ========================================================= */

$allClaims = $claims->find(
    $query,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


/* =========================================================
   COUNTS
   ========================================================= */

$totalClaims = $claims->countDocuments();

$reviewClaims = $claims->countDocuments([
    "status" => "pending"
]);

$approvedClaims = $claims->countDocuments([
    "status" => "approved"
]);

$rejectedClaims = $claims->countDocuments([
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

<title>Admin Claims</title>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =========================================================
   RESET
   ========================================================= */

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
            rgba(124,58,237,.12),
            transparent 30%
        ),
        #f5f3f8;

    color: #261b35;
}


/* =========================================================
   TOP BAR
   ========================================================= */

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
        rgba(40,20,60,.20);
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

    font-size: 17px;
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

    padding: 17px;

    color: #6d6475;

    text-decoration: none;

    font-weight: 600;

    white-space: nowrap;

    transition: .2s;
}

.nav a:hover,
.nav a.active {

    color: #7c3aed;

    border-bottom:
        3px solid #7c3aed;
}


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    max-width: 1450px;

    margin: auto;

    padding: 35px;
}


/* =========================================================
   HEADER
   ========================================================= */

.page-head {

    margin-bottom: 25px;
}

.page-head h2 {

    margin: 0 0 7px;

    font-size: 30px;
}

.page-head p {

    margin: 0;

    color: #81758c;
}


/* =========================================================
   STAT CARDS
   ========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

    margin-bottom: 25px;
}

.stat {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 20px;

    padding: 22px;

    box-shadow:
        0 8px 25px
        rgba(40,20,60,.06);
}

.stat-top {

    display: flex;

    justify-content:
        space-between;

    align-items: center;
}

.stat-icon {

    width: 44px;

    height: 44px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;
}

.stat-icon.purple {

    background: #f1e8ff;

    color: #7c3aed;
}

.stat-icon.orange {

    background: #fff4d6;

    color: #a16207;
}

.stat-icon.green {

    background: #dcfce7;

    color: #166534;
}

.stat-icon.red {

    background: #fee2e2;

    color: #991b1b;
}

.stat small {

    display: block;

    margin-top: 15px;

    color: #81758c;
}

.stat strong {

    display: block;

    margin-top: 4px;

    font-size: 30px;
}


/* =========================================================
   FILTER TABS
   ========================================================= */

.filters {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 18px;

    padding: 8px;

    display: flex;

    gap: 7px;

    margin-bottom: 20px;

    box-shadow:
        0 6px 20px
        rgba(40,20,60,.05);

    overflow-x: auto;
}

.filter {

    text-decoration: none;

    color: #71667c;

    padding:
        11px 17px;

    border-radius: 11px;

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;
}

.filter:hover {

    background: #f1e8ff;

    color: #6d28a8;
}

.filter.active {

    background:
        linear-gradient(
            135deg,
            #6d28a8,
            #7c3aed
        );

    color: white;

    box-shadow:
        0 6px 15px
        rgba(124,58,237,.20);
}


/* =========================================================
   TABLE
   ========================================================= */

.table-card {

    background: white;

    border:
        1px solid #eee8f5;

    border-radius: 22px;

    overflow: hidden;

    box-shadow:
        0 8px 25px
        rgba(40,20,60,.06);
}

.table-wrap {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse:
        collapse;
}

th,
td {

    padding:
        16px 18px;

    border-bottom:
        1px solid #f0ecf4;

    text-align: left;

    font-size: 13px;
}

th {

    background: #faf8fc;

    color: #766b80;

    font-size: 11px;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;
}

.item-cell {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 220px;
}

.item-photo {

    width: 58px;

    height: 58px;

    object-fit: cover;

    border-radius: 13px;

    background: #eee;
}

.no-photo {

    width: 58px;

    height: 58px;

    border-radius: 13px;

    background: #f0ebf5;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #9b90a5;
}


/* =========================================================
   STATUS
   ========================================================= */

.badge {

    display: inline-flex;

    padding:
        6px 10px;

    border-radius:
        999px;

    font-size: 11px;

    font-weight: 800;
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


/* =========================================================
   BUTTONS
   ========================================================= */

.action-group {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

.btn {

    border: 0;

    padding:
        8px 11px;

    border-radius: 9px;

    cursor: pointer;

    font-size: 12px;

    font-weight: 700;

    transition: .2s;
}

.btn:hover {

    transform:
        translateY(-1px);
}

.view-btn {

    background: #f1e8ff;

    color: #6d28a8;
}

.approve-btn {

    background: #dcfce7;

    color: #166534;
}

.reject-btn {

    background: #fee2e2;

    color: #991b1b;
}


/* =========================================================
   MODAL
   ========================================================= */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    z-index: 2000;

    background:
        rgba(25,12,37,.75);

    align-items: center;

    justify-content: center;

    padding: 20px;
}

.modal-box {

    background: white;

    width: 100%;

    max-width: 650px;

    max-height: 90vh;

    overflow-y: auto;

    border-radius: 24px;

    padding: 28px;

    position: relative;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.25);
}

.close {

    position: absolute;

    right: 20px;

    top: 18px;

    width: 36px;

    height: 36px;

    border: 0;

    border-radius: 50%;

    background: #f3edf8;

    cursor: pointer;
}

.modal-photo {

    width: 100%;

    max-height: 330px;

    object-fit: contain;

    border-radius: 16px;

    background: #f4f1f7;

    margin:
        15px 0;
}

.detail {

    background: #faf8fc;

    border-radius: 13px;

    padding: 14px;

    margin-top: 10px;
}

.detail strong {

    display: block;

    font-size: 10px;

    color: #81758c;

    text-transform:
        uppercase;

    margin-bottom: 5px;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {

    text-align: center;

    padding: 60px;

    color: #81758c;
}

.empty i {

    font-size: 40px;

    color: #c4b5fd;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width:1000px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);
    }

}

@media(max-width:600px) {

    .stats {

        grid-template-columns:
            1fr;
    }

    .container {

        padding: 20px;
    }

    .topbar {

        padding: 18px;
    }

    .user span {

        display: none;
    }

}

</style>

</head>


<body>


<!-- =======================================================
     TOP BAR
     ======================================================= -->

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
                CLAIM MANAGEMENT
            </small>

        </div>

    </div>


    <div class="user">

        <span>
            <?php
            echo htmlspecialchars(
                $_SESSION["name"]
            );
            ?>
        </span>

        <a href="../logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</header>


<!-- =======================================================
     NAV
     ======================================================= -->

<nav class="nav">

    <a href="dashboard.php">

        <i class="fa-solid fa-chart-line"></i>

        Dashboard

    </a>


    <a href="reports.php">

        <i class="fa-solid fa-file-lines"></i>

        Reports

    </a>


    <a
        class="active"
        href="claim.php"
    >

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


<!-- =======================================================
     MAIN
     ======================================================= -->

<main class="container">


    <div class="page-head">

        <h2>
            Claim Management
        </h2>

        <p>
            Review student claims and manage their status.
        </p>

    </div>


    <!-- ===================================================
         STATISTICS
         =================================================== -->

    <section class="stats">


        <div class="stat">

            <div class="stat-top">

                <span>
                    Total Claims
                </span>

                <div class="stat-icon purple">

                    <i class="fa-solid fa-layer-group"></i>

                </div>

            </div>

            <small>
                All submitted claims
            </small>

            <strong>
                <?php echo $totalClaims; ?>
            </strong>

        </div>


        <div class="stat">

            <div class="stat-top">

                <span>
                    Review
                </span>

                <div class="stat-icon orange">

                    <i class="fa-solid fa-clock"></i>

                </div>

            </div>

            <small>
                Waiting for review
            </small>

            <strong>
                <?php echo $reviewClaims; ?>
            </strong>

        </div>


        <div class="stat">

            <div class="stat-top">

                <span>
                    Approved
                </span>

                <div class="stat-icon green">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

            </div>

            <small>
                Approved claims
            </small>

            <strong>
                <?php echo $approvedClaims; ?>
            </strong>

        </div>


        <div class="stat">

            <div class="stat-top">

                <span>
                    Rejected
                </span>

                <div class="stat-icon red">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

            </div>

            <small>
                Rejected claims
            </small>

            <strong>
                <?php echo $rejectedClaims; ?>
            </strong>

        </div>


    </section>


    <!-- ===================================================
         MESSAGE
         =================================================== -->

    <?php if ($message !== ""): ?>

        <div
            style="
                padding:15px 18px;
                border-radius:13px;
                margin-bottom:20px;
                font-weight:700;
                background:
                <?php
                echo $messageType === "success"
                    ? "#dcfce7"
                    : "#fee2e2";
                ?>;
                color:
                <?php
                echo $messageType === "success"
                    ? "#166534"
                    : "#991b1b";
                ?>;
            "
        >

            <i class="fa-solid
                <?php
                echo $messageType === "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation";
                ?>">
            </i>

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <!-- ===================================================
         FILTERS
         =================================================== -->

    <div class="filters">


        <a
            class="filter
            <?php echo $filter === "all" ? "active" : ""; ?>"
            href="claim.php?status=all"
        >

            <i class="fa-solid fa-layer-group"></i>

            All

        </a>


        <a
            class="filter
            <?php echo $filter === "review" ? "active" : ""; ?>"
            href="claim.php?status=review"
        >

            <i class="fa-solid fa-clock"></i>

            Review

            (<?php echo $reviewClaims; ?>)

        </a>


        <a
            class="filter
            <?php echo $filter === "approved" ? "active" : ""; ?>"
            href="claim.php?status=approved"
        >

            <i class="fa-solid fa-circle-check"></i>

            Approved

            (<?php echo $approvedClaims; ?>)

        </a>


        <a
            class="filter
            <?php echo $filter === "rejected" ? "active" : ""; ?>"
            href="claim.php?status=rejected"
        >

            <i class="fa-solid fa-circle-xmark"></i>

            Rejected

            (<?php echo $rejectedClaims; ?>)

        </a>


    </div>


    <!-- ===================================================
         TABLE
         =================================================== -->

    <section class="table-card">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>
                            Item
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Reason
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $hasClaims = false;

                ?>


                <?php foreach ($allClaims as $claim): ?>

                    <?php

                    $hasClaims = true;

                    $item = null;

                    try {

                        $item = $reports->findOne(
                            [
                                "_id" =>
                                    new ObjectId(
                                        $claim["item_id"]
                                    )
                            ]
                        );

                    } catch (Exception $e) {

                        $item = null;

                    }


                    $imagePath = "";

                    if ($item) {

                        $imagePath =
                            $item["image_path"]
                            ?? "";

                    }


                    if (
                        $imagePath !== "" &&
                        !str_starts_with(
                            $imagePath,
                            "../"
                        )
                    ) {

                        $imagePath =
                            "../" .
                            ltrim(
                                $imagePath,
                                "/"
                            );

                    }

                    ?>


                    <tr>


                        <!-- ITEM -->

                        <td>

                            <?php if ($item): ?>

                                <div class="item-cell">


                                    <?php if ($imagePath !== ""): ?>

                                        <img
                                            class="item-photo"
                                            src="<?php
                                                echo htmlspecialchars(
                                                    $imagePath
                                                );
                                            ?>"
                                            alt="Item photo"
                                        >

                                    <?php else: ?>

                                        <div class="no-photo">

                                            <i class="fa-solid fa-image"></i>

                                        </div>

                                    <?php endif; ?>


                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $item["item_name"]
                                            ?? "Unknown item"
                                        );

                                        ?>

                                    </strong>

                                </div>

                            <?php else: ?>

                                <strong>
                                    Item unavailable
                                </strong>

                            <?php endif; ?>

                        </td>


                        <!-- STUDENT -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $claim["student_name"]
                                ?? "Unknown"
                            );

                            ?>

                        </td>


                        <!-- REASON -->

                        <td>

                            <?php

                            $reason =
                                $claim["reason"]
                                ?? "No reason provided";

                            echo htmlspecialchars(
                                strlen($reason) > 45
                                    ? substr(
                                        $reason,
                                        0,
                                        45
                                    ) . "..."
                                    : $reason
                            );

                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span
                                class="badge
                                <?php
                                echo htmlspecialchars(
                                    $claim["status"]
                                    ?? "pending"
                                );
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    ucfirst(
                                        $claim["status"]
                                        ?? "pending"
                                    )
                                );

                                ?>

                            </span>

                        </td>


                        <!-- ACTIONS -->

                        <td>

                            <div class="action-group">


                                <!-- VIEW -->

                                <button
                                    class="btn view-btn"
                                    onclick='openClaim(
                                        <?php
                                        echo json_encode(
                                            $claim,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        );
                                        ?>,
                                        <?php
                                        echo json_encode(
                                            $item,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        );
                                        ?>
                                    )'
                                >

                                    <i class="fa-solid fa-eye"></i>

                                    View

                                </button>


                                <!-- APPROVE -->

                                <?php if (
                                    ($claim["status"]
                                    ?? "pending")
                                    === "pending"
                                ): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="claim_id"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    (string)
                                                    $claim["_id"]
                                                );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="approved"
                                        >

                                        <button
                                            type="submit"
                                            class="btn approve-btn"
                                            onclick="return confirm('Approve this claim?');"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Approve

                                        </button>

                                    </form>


                                    <!-- REJECT -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="claim_id"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    (string)
                                                    $claim["_id"]
                                                );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="rejected"
                                        >

                                        <button
                                            type="submit"
                                            class="btn reject-btn"
                                            onclick="return confirm('Reject this claim?');"
                                        >

                                            <i class="fa-solid fa-xmark"></i>

                                            Reject

                                        </button>

                                    </form>

                                <?php endif; ?>


                            </div>

                        </td>


                    </tr>


                <?php endforeach; ?>


                <?php if (!$hasClaims): ?>

                    <tr>

                        <td
                            colspan="5"
                            class="empty"
                        >

                            <i class="fa-solid fa-folder-open"></i>

                            <br><br>

                            No claims found in this section.

                        </td>

                    </tr>

                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>

</main>


<!-- =======================================================
     DETAILS MODAL
     ======================================================= -->

<div
    class="modal"
    id="claimModal"
>

    <div class="modal-box">


        <button
            class="close"
            onclick="closeClaim()"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <h2 id="claimTitle">
            Claim Details
        </h2>


        <img
            id="claimPhoto"
            class="modal-photo"
            style="display:none;"
            alt="Item photo"
        >


        <div class="detail">

            <strong>
                Student
            </strong>

            <span id="claimStudent">
            </span>

        </div>


        <div class="detail">

            <strong>
                Item
            </strong>

            <span id="claimItem">
            </span>

        </div>


        <div class="detail">

            <strong>
                Location Found
            </strong>

            <span id="claimLocation">
            </span>

        </div>


        <div class="detail">

            <strong>
                Student's Reason
            </strong>

            <span id="claimReason">
            </span>

        </div>


        <div class="detail">

            <strong>
                Claim Status
            </strong>

            <span id="claimStatus">
            </span>

        </div>


    </div>

</div>


<script>

/* =========================================================
   OPEN CLAIM
   ========================================================= */

function openClaim(claim, item) {


    document.getElementById(
        "claimTitle"
    ).textContent =

        item && item.item_name

            ? item.item_name

            : "Claim Details";


    document.getElementById(
        "claimStudent"
    ).textContent =

        claim.student_name
        || "N/A";


    document.getElementById(
        "claimItem"
    ).textContent =

        item && item.item_name

            ? item.item_name

            : "Item unavailable";


    document.getElementById(
        "claimLocation"
    ).textContent =

        item && item.location

            ? item.location

            : "N/A";


    document.getElementById(
        "claimReason"
    ).textContent =

        claim.reason
        || "N/A";


    document.getElementById(
        "claimStatus"
    ).textContent =

        claim.status

            ? claim.status.toUpperCase()

            : "N/A";


    const photo =
        document.getElementById(
            "claimPhoto"
        );


    if (
        item &&
        item.image_path
    ) {


        let path =
            item.image_path;


        if (
            !path.startsWith("../")
        ) {

            path =
                "../" +
                path.replace(
                    /^\/+/,
                    ""
                );

        }


        photo.src = path;

        photo.style.display =
            "block";


    } else {

        photo.style.display =
            "none";

    }


    document.getElementById(
        "claimModal"
    ).style.display =
        "flex";

}


/* =========================================================
   CLOSE CLAIM
   ========================================================= */

function closeClaim() {

    document.getElementById(
        "claimModal"
    ).style.display =
        "none";

}


/* =========================================================
   CLOSE WHEN CLICKING OUTSIDE
   ========================================================= */

window.onclick =
    function(event) {

        const modal =
            document.getElementById(
                "claimModal"
            );

        if (
            event.target === modal
        ) {

            closeClaim();

        }

    };

</script>


</body>

</html>