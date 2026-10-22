<?php
use Zayncaleb\Lostandfoundsystem\Database;
use MongoDB\BSON\ObjectId;

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !in_array($_SESSION["role"] ?? "", ["staff", "admin"], true)
) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

$db = new Database();
$database = $db->getDatabase();

$claims = $database->claims;
$reports = $database->reports;

$role = $_SESSION["role"];
$name = $_SESSION["name"] ?? ucfirst($role);
$roleLabel = ucfirst($role);

/* Safely display text */
function claimText($value, $fallback = "—"): string
{
    if ($value === null || $value === "") {
        $value = $fallback;
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* Safely resolve Cloudinary URLs and older local image paths */
function claimImageUrl($value): string
{
    if (!is_string($value) || trim($value) === "") {
        return "";
    }

    $value = trim($value);

    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ["https", "http"], true) ? $value : "";
    }

    if (str_starts_with($value, "//")) {
        return "";
    }

    return "../" . ltrim($value, "/\\");
}

/* Safely convert a valid MongoDB ID */
function claimObjectId($value): ?ObjectId
{
    if ($value instanceof ObjectId) {
        return $value;
    }

    if (is_string($value) && preg_match('/^[a-f0-9]{24}$/i', $value)) {
        try {
            return new ObjectId($value);
        } catch (Throwable $e) {
            return null;
        }
    }

    return null;
}

/* Format dates consistently */
function claimDate($value): string
{
    if ($value instanceof MongoDB\BSON\UTCDateTime) {
        return $value->toDateTime()->format("M d, Y");
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format("M d, Y");
    }

    return is_string($value) && $value !== "" ? $value : "—";
}

/* Update claim status */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $claimId = $_POST["claim_id"] ?? "";
    $action = $_POST["action"] ?? "";

    if (
        is_string($claimId) &&
        $claimId !== "" &&
        in_array($action, ["approve", "reject"], true)
    ) {
        $objectId = claimObjectId($claimId);

        if ($objectId !== null) {
            try {
                $claim = $claims->findOne(["_id" => $objectId]);

                if ($claim && strtolower((string)($claim["status"] ?? "pending")) === "pending") {
                    $newStatus = $action === "approve" ? "approved" : "rejected";

                    $claims->updateOne(
                        ["_id" => $objectId],
                        ['$set' => ["status" => $newStatus]]
                    );
                }
            } catch (Throwable $e) {
                error_log("Claim status update failed: " . $e->getMessage());
            }
        }
    }

    header("Location: claims.php");
    exit;
}

/* Status filters */
$filter = strtolower((string)($_GET["status"] ?? "all"));

if (!in_array($filter, ["all", "pending", "approved", "rejected"], true)) {
    $filter = "all";
}

$counts = [
    "all" => $claims->countDocuments(),
    "pending" => $claims->countDocuments(["status" => "pending"]),
    "approved" => $claims->countDocuments(["status" => "approved"]),
    "rejected" => $claims->countDocuments(["status" => "rejected"])
];

$query = $filter === "all" ? [] : ["status" => $filter];

$claimRows = iterator_to_array(
    $claims->find($query, [
        "sort" => ["created_at" => -1]
    ]),
    false
);

/* Index reports so claims can find their associated item */
$reportLookup = [];

foreach ($reports->find([]) as $report) {
    $reportLookup[(string)$report["_id"]] = $report;
}

/* Prepare safe data for the page */
$rows = [];

foreach ($claimRows as $claim) {
    $itemId = $claim["item_id"] ?? null;
    $report = null;

    if ($itemId instanceof ObjectId) {
        $report = $reportLookup[(string)$itemId] ?? null;
    } elseif (is_string($itemId)) {
        $report = $reportLookup[$itemId] ?? null;

        if (!$report) {
            $id = claimObjectId($itemId);

            if ($id !== null) {
                $report = $reportLookup[(string)$id] ?? null;
            }
        }
    }

    $imagePath = $report["image_path"] ?? ($report["photo"] ?? "");
    $status = strtolower((string)($claim["status"] ?? "pending"));

    if (!in_array($status, ["pending", "approved", "rejected"], true)) {
        $status = "pending";
    }

    $rows[] = [
        "id" => (string)$claim["_id"],
        "student" => $claim["student_name"] ?? "Unknown Student",
        "item" => $report["item_name"] ?? "Item not found",
        "description" => $report["description"] ?? "",
        "location" => $report["location"] ?? "Not specified",
        "type" => $report["type"] ?? "unknown",
        "reason" => $claim["reason"] ?? "No reason provided",
        "status" => $status,
        "date" => claimDate($claim["created_at"] ?? null),
        "image" => claimImageUrl($imagePath)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= claimText($roleLabel) ?> Claims | Lost &amp; Found</title>

    <link rel="stylesheet" href="staff.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .claims-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .claims-stat {
            min-width: 0;
            padding: 19px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .035);
        }

        .claims-stat-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .claims-stat-number {
            margin-top: 10px;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
        }

        .claims-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 17px;
        }

        .claims-filters a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .claims-filters a:hover,
        .claims-filters a.active {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .claims-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .claims-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .claims-table th,
        .claims-table td {
            padding: 14px 13px;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
            vertical-align: middle;
            font-size: 12px;
        }

        .claims-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .claims-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .claims-table tbody tr:hover {
            background: #f8fbff;
        }

        .claim-item {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 180px;
        }

        .claim-thumb {
            display: grid;
            place-items: center;
            flex: 0 0 52px;
            width: 52px;
            height: 52px;
            overflow: hidden;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
        }

        .claim-thumb img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .claim-item strong {
            display: block;
            color: #0f172a;
            overflow-wrap: anywhere;
        }

        .claim-item small {
            display: block;
            max-width: 220px;
            margin-top: 4px;
            color: #64748b;
            overflow-wrap: anywhere;
        }

        .claim-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .claim-status.pending {
            background: #fffbeb;
            color: #a16207;
        }

        .claim-status.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .claim-status.rejected {
            background: #fef2f2;
            color: #b91c1c;
        }

        .claim-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .claim-actions form {
            margin: 0;
        }

        .claim-action,
        .claim-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 32px;
            padding: 7px 10px;
            border: 0;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 750;
            cursor: pointer;
        }

        .claim-action.approve {
            background: #16a34a;
            color: #fff;
        }

        .claim-action.approve:hover {
            background: #15803d;
        }

        .claim-action.reject {
            background: #dc2626;
            color: #fff;
        }

        .claim-action.reject:hover {
            background: #b91c1c;
        }

        .claim-view {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .claim-empty {
            padding: 45px 20px;
            color: #64748b;
            text-align: center;
        }

        .claim-empty i {
            display: block;
            margin-bottom: 12px;
            color: #93c5fd;
            font-size: 30px;
        }

        .claim-empty p {
            margin-bottom: 0;
        }

        .claim-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
            background: rgba(15, 23, 42, .65);
        }

        .claim-modal.open {
            display: flex;
        }

        .claim-modal-box {
            width: min(680px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 25px 70px rgba(0, 0, 0, .25);
        }

        .claim-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 20px 23px;
            border-bottom: 1px solid #e2e8f0;
        }

        .claim-modal-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
        }

        .claim-modal-close {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 26px;
            cursor: pointer;
        }

        .claim-modal-body {
            padding: 23px;
        }

        .claim-modal-photo {
            display: grid;
            place-items: center;
            width: 100%;
            height: 260px;
            margin-bottom: 20px;
            overflow: hidden;
            border-radius: 13px;
            background: #f1f5f9;
            color: #64748b;
        }

        .claim-modal-photo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .claim-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .claim-detail {
            min-width: 0;
            padding: 13px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
        }

        .claim-detail.full {
            grid-column: 1 / -1;
        }

        .claim-detail label {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .claim-detail div {
            color: #0f172a;
            font-size: 13px;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        @media (max-width: 1000px) {
            .claims-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .claims-stats {
                grid-template-columns: 1fr;
            }

            .claims-filters {
                gap: 6px;
            }

            .claims-filters a {
                padding: 8px 10px;
            }

            .claim-modal {
                align-items: flex-start;
                padding: 10px;
            }

            .claim-modal-body {
                padding: 15px;
            }

            .claim-modal-head {
                padding: 16px;
            }

            .claim-modal-photo {
                height: 210px;
            }

            .claim-details {
                grid-template-columns: 1fr;
            }

            .claim-detail.full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body class="staff-body">
<div class="staff-layout">

    <aside class="staff-sidebar">
        <div class="staff-brand">
            <div class="staff-brand-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div>
                <h2>Lost &amp; Found</h2>
                <span><?= claimText($roleLabel) ?> Management</span>
            </div>
        </div>

        <nav class="staff-nav">
            <div class="staff-nav-label">Workspace</div>

            <a href="dashboard.php">
                <span class="staff-nav-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
                Dashboard
            </a>

            <a href="reports.php">
                <span class="staff-nav-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </span>
                Reports
            </a>

            <a href="claims.php" class="active">
                <span class="staff-nav-icon">
                    <i class="fa-solid fa-hand-holding"></i>
                </span>
                Claims
            </a>

            <?php if ($role === "admin"): ?>
                <div class="staff-nav-label" style="margin-top:20px">
                    Administration
                </div>

                <a href="../admin/dashboard.php">
                    <span class="staff-nav-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </span>
                    Admin Portal
                </a>

                <a href="../admin/manage_staff.php">
                    <span class="staff-nav-icon">
                        <i class="fa-solid fa-users"></i>
                    </span>
                    Manage Staff
                </a>
            <?php endif; ?>
        </nav>

        <div class="staff-user">
            <div class="staff-user-top">
                <div class="staff-avatar">
                    <?= claimText(strtoupper(substr($name, 0, 1))) ?>
                </div>
                <div>
                    <strong><?= claimText($name) ?></strong>
                    <small><?= claimText($roleLabel) ?> Account</small>
                </div>
            </div>

            <a href="../logout.php" class="staff-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                Sign Out
            </a>
        </div>
    </aside>

    <main class="staff-main">
        <header class="staff-topbar">
            <div class="staff-breadcrumb">
                Lost &amp; Found / <strong><?= claimText($roleLabel) ?> Claims</strong>
            </div>
            <div class="staff-date">
                <i class="fa-solid fa-hand-holding"></i>
                Claim Management
            </div>
        </header>

        <section class="staff-page-heading">
            <div>
                <h1>Manage Claims</h1>
                <p>Review claim requests and check reported item details.</p>
            </div>
        </section>

        <section class="claims-stats">
            <article class="claims-stat">
                <div class="claims-stat-label">All Claims</div>
                <div class="claims-stat-number"><?= number_format($counts["all"]) ?></div>
            </article>

            <article class="claims-stat">
                <div class="claims-stat-label">Pending</div>
                <div class="claims-stat-number"><?= number_format($counts["pending"]) ?></div>
            </article>

            <article class="claims-stat">
                <div class="claims-stat-label">Approved</div>
                <div class="claims-stat-number"><?= number_format($counts["approved"]) ?></div>
            </article>

            <article class="claims-stat">
                <div class="claims-stat-label">Rejected</div>
                <div class="claims-stat-number"><?= number_format($counts["rejected"]) ?></div>
            </article>
        </section>

        <section class="staff-panel">
            <div class="staff-panel-heading">
                <div>
                    <h2>Submitted Claims</h2>
                    <p>Filter and review student claim requests.</p>
                </div>

                <div class="claims-filters">
                    <?php foreach (["all", "pending", "approved", "rejected"] as $statusFilter): ?>
                        <a
                            class="<?= $filter === $statusFilter ? "active" : "" ?>"
                            href="claims.php?status=<?= urlencode($statusFilter) ?>"
                        >
                            <?= claimText(ucfirst($statusFilter)) ?>
                            <span><?= number_format($counts[$statusFilter]) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="claims-table-wrap">
                <table class="claims-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Student</th>
                        <th>Reason</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="6">
                                <div class="claim-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <strong>No claims found</strong>
                                    <p>There are no claims in this category.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $json = json_encode(
                                $row,
                                JSON_HEX_APOS | JSON_HEX_QUOT |
                                JSON_HEX_TAG | JSON_HEX_AMP
                            );

                            if ($json === false) {
                                $json = "{}";
                            }

                            $json = htmlspecialchars(
                                $json,
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                            <tr>
                                <td>
                                    <div class="claim-item">
                                        <div class="claim-thumb">
                                            <?php if ($row["image"] !== ""): ?>
                                                <img
                                                    src="<?= claimText($row["image"]) ?>"
                                                    alt="Item photo"
                                                    loading="lazy"
                                                    onerror="this.remove(); this.parentElement.innerHTML='<i class=&quot;fa-solid fa-box-open&quot;></i>';"
                                                >
                                            <?php else: ?>
                                                <i class="fa-solid fa-box-open"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <strong><?= claimText($row["item"]) ?></strong>
                                            <small><?= claimText($row["location"]) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td><?= claimText($row["student"]) ?></td>

                                <td>
                                    <?= claimText(
                                        function_exists("mb_strimwidth")
                                            ? mb_strimwidth((string)$row["reason"], 0, 65, "…")
                                            : substr((string)$row["reason"], 0, 65)
                                    ) ?>
                                </td>

                                <td><?= claimText($row["date"]) ?></td>

                                <td>
                                    <span class="claim-status <?= claimText($row["status"]) ?>">
                                        <?= claimText($row["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="claim-actions">
                                        <button
                                            type="button"
                                            class="claim-view"
                                            data-claim="<?= $json ?>"
                                            onclick="openClaim(this)"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            View
                                        </button>

                                        <?php if ($row["status"] === "pending"): ?>
                                            <form method="POST" onsubmit="return confirm('Approve this claim?');">
                                                <input type="hidden" name="claim_id" value="<?= claimText($row["id"]) ?>">
                                                <button
                                                    type="submit"
                                                    class="claim-action approve"
                                                    name="action"
                                                    value="approve"
                                                >
                                                    Approve
                                                </button>
                                            </form>

                                            <form method="POST" onsubmit="return confirm('Reject this claim?');">
                                                <input type="hidden" name="claim_id" value="<?= claimText($row["id"]) ?>">
                                                <button
                                                    type="submit"
                                                    class="claim-action reject"
                                                    name="action"
                                                    value="reject"
                                                >
                                                    Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div
    class="claim-modal"
    id="claimModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="claimModalTitle"
    onclick="closeClaim(event)"
>
    <div class="claim-modal-box" onclick="event.stopPropagation()">
        <div class="claim-modal-head">
            <h2 id="claimModalTitle">Claim Details</h2>
            <button
                class="claim-modal-close"
                type="button"
                aria-label="Close claim details"
                onclick="closeClaim()"
            >&times;</button>
        </div>

        <div class="claim-modal-body">
            <div class="claim-modal-photo" id="modalPhoto">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <div class="claim-details">
                <div class="claim-detail">
                    <label>Item</label>
                    <div id="modalItem">—</div>
                </div>

                <div class="claim-detail">
                    <label>Student</label>
                    <div id="modalStudent">—</div>
                </div>

                <div class="claim-detail">
                    <label>Location</label>
                    <div id="modalLocation">—</div>
                </div>

                <div class="claim-detail">
                    <label>Report Type</label>
                    <div id="modalType">—</div>
                </div>

                <div class="claim-detail">
                    <label>Date Submitted</label>
                    <div id="modalDate">—</div>
                </div>

                <div class="claim-detail">
                    <label>Status</label>
                    <div id="modalStatus">—</div>
                </div>

                <div class="claim-detail full">
                    <label>Claim Reason</label>
                    <div id="modalReason">—</div>
                </div>

                <div class="claim-detail full">
                    <label>Item Description</label>
                    <div id="modalDescription">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setClaimText(id, value) {
    document.getElementById(id).textContent = value || "—";
}

function openClaim(button) {
    let claim;

    try {
        claim = JSON.parse(button.dataset.claim || "{}");
    } catch (error) {
        alert("Unable to open this claim. Please refresh the page and try again.");
        return;
    }

    setClaimText("modalItem", claim.item);
    setClaimText("modalStudent", claim.student);
    setClaimText("modalLocation", claim.location);
    setClaimText("modalType", claim.type);
    setClaimText("modalDate", claim.date);
    setClaimText("modalStatus", claim.status);
    setClaimText("modalReason", claim.reason);
    setClaimText("modalDescription", claim.description);

    const photo = document.getElementById("modalPhoto");
    photo.replaceChildren();

    if (claim.image) {
        const img = document.createElement("img");
        img.src = claim.image;
        img.alt = "Reported item photo";

        img.onerror = function () {
            photo.replaceChildren();

            const icon = document.createElement("i");
            icon.className = "fa-solid fa-box-open";
            photo.appendChild(icon);
        };

        photo.appendChild(img);
    } else {
        const icon = document.createElement("i");
        icon.className = "fa-solid fa-box-open";
        photo.appendChild(icon);
    }

    document.getElementById("claimModal").classList.add("open");
    document.body.style.overflow = "hidden";
}

function closeClaim(event) {
    if (
        event &&
        event.target !== document.getElementById("claimModal")
    ) {
        return;
    }

    document.getElementById("claimModal").classList.remove("open");
    document.body.style.overflow = "";
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        document.getElementById("claimModal").classList.remove("open");
        document.body.style.overflow = "";
    }
});
</script>
</body>
</html>
