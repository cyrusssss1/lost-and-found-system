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


/* =========================================================
   UPDATE CLAIM STATUS
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claimId = $_POST["claim_id"] ?? "";
    $action = $_POST["action"] ?? "";

    if ($claimId !== "" && in_array($action, ["approved", "rejected"])) {

        try {

            $claimObjectId = new ObjectId($claimId);

            $result = $claims->updateOne(
                [
                    "_id" => $claimObjectId
                ],
                [
                    '$set' => [
                        "status" => $action,
                        "reviewed_at" => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );

            if ($result->getModifiedCount() > 0) {

                $message = "Claim " . $action . " successfully.";
                $messageType = "success";

            } else {

                $message = "Claim was not updated.";
                $messageType = "error";
            }

        } catch (Exception $e) {

            $message = "Unable to update claim.";
            $messageType = "error";
        }
    }
}


/* =========================================================
   FILTER
   ========================================================= */

$filter = $_GET["status"] ?? "all";

$query = [];

if (in_array($filter, ["pending", "approved", "rejected"])) {
    $query["status"] = $filter;
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

$pendingClaims = $claims->countDocuments([
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Claims</title>

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
    background: linear-gradient(135deg,#24103d,#5b2181,#7c3aed);
    color: white;
    padding: 20px 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 25px rgba(40,20,60,.18);
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

.message {
    padding: 14px 18px;
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

.claim-actions {
    display: flex;
    gap: 8px;
    margin-top: 20px;
}

.claim-actions button {
    flex: 1;
    border: 0;
    padding: 12px;
    border-radius: 11px;
    font-weight: 800;
    cursor: pointer;
}

.approve-btn {
    background: #dcfce7;
    color: #166534;
}

.reject-btn {
    background: #fee2e2;
    color: #991b1b;
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
            <h1>Admin Control Center</h1>
            <small>CLAIM MANAGEMENT</small>
        </div>

    </div>

    <div class="user">

        <span>
            <?php echo htmlspecialchars($_SESSION["name"] ?? "Admin"); ?>
        </span>

        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

    </div>

</header>

<nav class="nav">

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>

    <a href="reports.php">
        <i class="fa-solid fa-file-lines"></i> Reports
    </a>

    <a class="active" href="claim.php">
        <i class="fa-solid fa-hand-holding"></i> Claims
    </a>

    <a href="manage_staff.php">
        <i class="fa-solid fa-users-gear"></i> Staff
    </a>

    <a href="create_staff.php">
        <i class="fa-solid fa-user-plus"></i> Create Staff
    </a>

</nav>

<main class="container">

    <div class="page-head">

        <h2>Claim Management</h2>

        <p>
            Review and manage item claims submitted by students.
        </p>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?php echo $messageType; ?>">

            <i class="fa-solid
                <?php echo $messageType === "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation"; ?>">
            </i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <section class="stats">

        <div class="stat">
            <small>All Claims</small>
            <strong><?php echo $totalClaims; ?></strong>
        </div>

        <div class="stat">
            <small>Pending</small>
            <strong><?php echo $pendingClaims; ?></strong>
        </div>

        <div class="stat">
            <small>Approved</small>
            <strong><?php echo $approvedClaims; ?></strong>
        </div>

        <div class="stat">
            <small>Rejected</small>
            <strong><?php echo $rejectedClaims; ?></strong>
        </div>

    </section>

    <div class="filters">

        <a
            class="<?php echo $filter === 'all' ? 'active' : ''; ?>"
            href="claim.php?status=all"
        >
            All
        </a>

        <a
            class="<?php echo $filter === 'pending' ? 'active' : ''; ?>"
            href="claim.php?status=pending"
        >
            Pending
        </a>

        <a
            class="<?php echo $filter === 'approved' ? 'active' : ''; ?>"
            href="claim.php?status=approved"
        >
            Approved
        </a>

        <a
            class="<?php echo $filter === 'rejected' ? 'active' : ''; ?>"
            href="claim.php?status=rejected"
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
                    <th>Student</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>

                </thead>

                <tbody>

                <?php $hasClaims = false; ?>

                <?php foreach ($allClaims as $claim): ?>

                    <?php

                    $hasClaims = true;

                    $item = null;

                    try {

                        if (isset($claim["item_id"])) {

                            $itemId = $claim["item_id"];

                            if ($itemId instanceof ObjectId) {

                                $item = $reports->findOne([
                                    "_id" => $itemId
                                ]);

                            } elseif (is_string($itemId) && $itemId !== "") {

                                $item = $reports->findOne([
                                    "_id" => new ObjectId($itemId)
                                ]);
                            }
                        }

                    } catch (Exception $e) {

                        $item = null;
                    }

                    $imagePath = "";

                    if ($item) {

                        $imagePath = getAdminImageUrl(
                            $item["image_path"] ?? ""
                        );
                    }

                    $claimData = $claim;

                    if ($claimData instanceof MongoDB\Model\BSONDocument) {
                        $claimData = $claimData->getArrayCopy();
                    }

                    $itemData = $item;

                    if ($itemData instanceof MongoDB\Model\BSONDocument) {
                        $itemData = $itemData->getArrayCopy();
                    }

                    ?>

                    <tr>

                        <td>

                            <div class="item-cell">

                                <?php if ($imagePath !== ""): ?>

                                    <img
                                        class="item-photo"
                                        src="<?php echo htmlspecialchars($imagePath); ?>"
                                        alt="Item"
                                    >

                                <?php else: ?>

                                    <div class="no-photo">
                                        <i class="fa-solid fa-image"></i>
                                    </div>

                                <?php endif; ?>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $item["item_name"] ?? "Unknown Item"
                                    );
                                    ?>
                                </strong>

                            </div>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $claim["student_name"]
                                ?? $claim["name"]
                                ?? "Unknown"
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $claim["reason"]
                                ?? $claim["message"]
                                ?? "N/A"
                            );
                            ?>

                        </td>

                        <td>

                            <span class="badge <?php
                                echo htmlspecialchars(
                                    $claim["status"] ?? "pending"
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $claim["status"] ?? "pending"
                                    )
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <button
                                class="view-btn"
                                onclick='openClaim(
                                    <?php
                                    echo json_encode(
                                        $claimData,
                                        JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_AMP
                                    );
                                    ?>,
                                    <?php
                                    echo json_encode(
                                        $itemData,
                                        JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_AMP
                                    );
                                    ?>,
                                    <?php
                                    echo json_encode($imagePath);
                                    ?>
                                )'
                            >

                                <i class="fa-solid fa-eye"></i>
                                View

                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$hasClaims): ?>

                    <tr>

                        <td
                            colspan="5"
                            style="text-align:center;padding:45px;color:#8b8193;"
                        >

                            <i
                                class="fa-solid fa-folder-open"
                                style="font-size:35px;"
                            ></i>

                            <br><br>

                            No claims found for this filter.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<div class="modal" id="claimModal">

    <div class="modal-box">

        <button
            class="close"
            onclick="closeClaim()"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 id="modalTitle">
            Claim Details
        </h2>

        <img
            id="modalPhoto"
            class="modal-photo"
            style="display:none;"
            alt="Claimed item"
        >

        <div class="detail">

            <strong>Item</strong>

            <span id="modalItem"></span>

        </div>

        <div class="detail">

            <strong>Student</strong>

            <span id="modalStudent"></span>

        </div>

        <div class="detail">

            <strong>Reason</strong>

            <span id="modalReason"></span>

        </div>

        <div class="detail">

            <strong>Status</strong>

            <span id="modalStatus"></span>

        </div>

        <div
            class="claim-actions"
            id="claimActions"
        >

            <form method="POST">

                <input
                    type="hidden"
                    name="claim_id"
                    id="approveClaimId"
                >

                <input
                    type="hidden"
                    name="action"
                    value="approved"
                >

                <button
                    type="submit"
                    class="approve-btn"
                >
                    <i class="fa-solid fa-check"></i>
                    Approve
                </button>

            </form>

            <form method="POST">

                <input
                    type="hidden"
                    name="claim_id"
                    id="rejectClaimId"
                >

                <input
                    type="hidden"
                    name="action"
                    value="rejected"
                >

                <button
                    type="submit"
                    class="reject-btn"
                >
                    <i class="fa-solid fa-xmark"></i>
                    Reject
                </button>

            </form>

        </div>

    </div>

</div>

<script>

function openClaim(claim, item, imagePath) {

    document.getElementById("modalTitle").textContent =
        "Claim Details";

    document.getElementById("modalItem").textContent =
        item && item.item_name
            ? item.item_name
            : "Unknown Item";

    document.getElementById("modalStudent").textContent =
        claim.student_name ||
        claim.name ||
        "N/A";

    document.getElementById("modalReason").textContent =
        claim.reason ||
        claim.message ||
        "N/A";

    document.getElementById("modalStatus").textContent =
        claim.status
            ? claim.status.toUpperCase()
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

    const claimId =
        claim._id && claim._id.$oid
            ? claim._id.$oid
            : claim._id || "";

    document.getElementById("approveClaimId").value =
        claimId;

    document.getElementById("rejectClaimId").value =
        claimId;

    const actions =
        document.getElementById("claimActions");

    actions.style.display =
        claim.status === "pending"
            ? "flex"
            : "none";

    document.getElementById("claimModal").style.display =
        "flex";
}

function closeClaim() {

    document.getElementById("claimModal").style.display =
        "none";
}

window.onclick = function(event) {

    const modal =
        document.getElementById("claimModal");

    if (event.target === modal) {
        closeClaim();
    }

};

</script>

</body>
</html>