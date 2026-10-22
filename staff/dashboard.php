
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !in_array($_SESSION["role"] ?? "", ["staff", "admin"], true)
) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();
$database = $db->getDatabase();

$users = $database->users;
$reports = $database->reports;
$claims = $database->claims;

$role = $_SESSION["role"];
$name = $_SESSION["name"] ?? ucfirst($role);
$roleLabel = ucfirst($role);

function dashboardText($value, $fallback = "—"): string
{
    if ($value === null || $value === "") {
        $value = $fallback;
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function dashboardStatus($value): string
{
    $status = strtolower((string)($value ?? "pending"));

    return in_array($status, ["pending", "approved", "rejected"], true)
        ? $status
        : "pending";
}

function dashboardDate($value): string
{
    if ($value instanceof MongoDB\BSON\UTCDateTime) {
        return $value->toDateTime()->format("M d, Y");
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format("M d, Y");
    }

    return is_string($value) && $value !== "" ? $value : "—";
}

/* Report statistics */
$totalReports = $reports->countDocuments();
$pendingReports = $reports->countDocuments(["status" => "pending"]);
$approvedReports = $reports->countDocuments(["status" => "approved"]);
$rejectedReports = $reports->countDocuments(["status" => "rejected"]);
$totalLost = $reports->countDocuments(["type" => "lost"]);
$totalFound = $reports->countDocuments(["type" => "found"]);

/* Claim statistics */
$totalClaims = $claims->countDocuments();
$pendingClaims = $claims->countDocuments(["status" => "pending"]);
$approvedClaims = $claims->countDocuments(["status" => "approved"]);
$rejectedClaims = $claims->countDocuments(["status" => "rejected"]);

/* Account statistics */
$totalStudents = $users->countDocuments(["role" => "student"]);
$totalStaff = $users->countDocuments(["role" => "staff"]);
$totalUsers = $users->countDocuments();

/* Recent activity */
$recentReports = iterator_to_array(
    $reports->find([], [
        "sort" => ["created_at" => -1],
        "limit" => 6
    ]),
    false
);

$recentClaims = iterator_to_array(
    $claims->find([], [
        "sort" => ["created_at" => -1],
        "limit" => 6
    ]),
    false
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= dashboardText($roleLabel) ?> Dashboard | Lost &amp; Found</title>

    <link rel="stylesheet" href="staff.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .dash-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 28px;
            margin-bottom: 24px;
            border-radius: 18px;
            color: white;
            background: linear-gradient(125deg, #172554, #1d4ed8, #3b82f6);
            box-shadow: 0 14px 34px rgba(37, 99, 235, .15);
        }

        .dash-welcome h1 {
            margin: 0;
            color: white;
            font-size: clamp(24px, 3vw, 32px);
        }

        .dash-welcome p {
            margin: 9px 0 0;
            color: #dbeafe;
            font-size: 13px;
            line-height: 1.6;
        }

        .dash-eyebrow {
            display: block;
            margin-bottom: 9px;
            color: #dbeafe;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .dash-welcome-icon {
            width: 65px;
            height: 65px;
            flex: 0 0 65px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 18px;
            background: rgba(255,255,255,.12);
            font-size: 26px;
        }

        .dash-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 24px;
        }

        .dash-stat {
            min-width: 0;
            padding: 19px;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: white;
            box-shadow: 0 7px 25px rgba(15,23,42,.035);
        }

        .dash-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .dash-stat-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .dash-stat-icon {
            width: 37px;
            height: 37px;
            flex: 0 0 37px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
        }

        .dash-stat-number {
            margin-top: 12px;
            color: #0f172a;
            font-size: 29px;
            font-weight: 800;
        }

        .dash-stat-note {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
        }

        .dash-stat.amber .dash-stat-icon {
            background: #fffbeb;
            color: #b45309;
        }

        .dash-stat.green .dash-stat-icon {
            background: #ecfdf5;
            color: #047857;
        }

        .dash-stat.red .dash-stat-icon {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dash-shortcuts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .dash-shortcut {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: white;
            color: #1e293b;
            text-decoration: none;
            transition: .18s ease;
        }

        .dash-shortcut:hover {
            transform: translateY(-2px);
            border-color: #93c5fd;
            box-shadow: 0 10px 22px rgba(37,99,235,.08);
        }

        .dash-shortcut > i {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
        }

        .dash-shortcut strong {
            display: block;
            font-size: 12px;
        }

        .dash-shortcut small {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .dash-activity-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .dash-activity-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: white;
            box-shadow: 0 7px 25px rgba(15,23,42,.035);
        }

        .dash-panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf2f7;
        }

        .dash-panel-heading h2 {
            margin: 0;
            color: #172033;
            font-size: 15px;
        }

        .dash-panel-heading p {
            margin: 5px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        .dash-panel-heading a {
            color: #2563eb;
            font-size: 10px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .dash-activity-list {
            padding: 0 20px;
        }

        .dash-activity-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .dash-activity-row:last-child {
            border-bottom: 0;
        }

        .dash-activity-copy {
            min-width: 0;
        }

        .dash-activity-copy strong {
            display: block;
            color: #1e293b;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .dash-activity-copy small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .dash-status {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .dash-status.pending {
            background: #fffbeb;
            color: #a16207;
        }

        .dash-status.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .dash-status.rejected {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dash-empty {
            padding: 28px 10px;
            color: #94a3b8;
            text-align: center;
            font-size: 12px;
        }

        .dash-overview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 24px;
        }

        .dash-overview span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #64748b;
            font-size: 11px;
        }

        .dash-overview strong {
            color: #0f172a;
        }

        @media (max-width: 1100px) {
            .dash-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .dash-activity-grid {
                grid-template-columns: 1fr;
            }

            .dash-shortcuts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .dash-welcome {
                padding: 21px;
                align-items: flex-start;
            }

            .dash-welcome-icon {
                width: 45px;
                height: 45px;
                flex-basis: 45px;
                font-size: 19px;
            }

            .dash-stats,
            .dash-shortcuts {
                grid-template-columns: 1fr;
            }

            .dash-panel-heading,
            .dash-activity-list {
                padding-left: 15px;
                padding-right: 15px;
            }

            .dash-overview {
                display: grid;
                grid-template-columns: 1fr;
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
                <span><?= dashboardText($roleLabel) ?> Management</span>
            </div>
        </div>

        <nav class="staff-nav">
            <div class="staff-nav-label">Workspace</div>

            <a href="dashboard.php" class="active">
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

            <a href="claims.php">
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
                    <?= dashboardText(strtoupper(substr($name, 0, 1))) ?>
                </div>

                <div>
                    <strong><?= dashboardText($name) ?></strong>
                    <small><?= dashboardText($roleLabel) ?> Account</small>
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
                Lost &amp; Found / <strong><?= dashboardText($roleLabel) ?> Dashboard</strong>
            </div>

            <div class="staff-date">
                <i class="fa-regular fa-circle-user"></i>
                &nbsp; Signed in as <?= dashboardText($roleLabel) ?>
            </div>
        </header>

        <section class="dash-welcome">
            <div>
                <span class="dash-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i>
                    <?= dashboardText($roleLabel) ?> workspace
                </span>

                <h1>Welcome back, <?= dashboardText($name) ?>!</h1>

                <p>
                    Review reports, manage claims, and track Lost &amp; Found activity
                    from one organized dashboard.
                </p>
            </div>

            <div class="dash-welcome-icon">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
        </section>

        <section class="dash-stats">

            <article class="dash-stat">
                <div class="dash-stat-top">
                    <span class="dash-stat-label">Total Reports</span>
                    <span class="dash-stat-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </span>
                </div>
                <div class="dash-stat-number"><?= number_format($totalReports) ?></div>
                <div class="dash-stat-note">All lost and found reports</div>
            </article>

            <article class="dash-stat amber">
                <div class="dash-stat-top">
                    <span class="dash-stat-label">Pending Reports</span>
                    <span class="dash-stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </span>
                </div>
                <div class="dash-stat-number"><?= number_format($pendingReports) ?></div>
                <div class="dash-stat-note">Waiting for review</div>
            </article>

            <article class="dash-stat green">
                <div class="dash-stat-top">
                    <span class="dash-stat-label">Approved Reports</span>
                    <span class="dash-stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <div class="dash-stat-number"><?= number_format($approvedReports) ?></div>
                <div class="dash-stat-note">Approved reports</div>
            </article>

            <article class="dash-stat">
                <div class="dash-stat-top">
                    <span class="dash-stat-label">Total Claims</span>
                    <span class="dash-stat-icon">
                        <i class="fa-solid fa-hand-holding"></i>
                    </span>
                </div>
                <div class="dash-stat-number"><?= number_format($totalClaims) ?></div>
                <div class="dash-stat-note">
                    <?= number_format($pendingClaims) ?> pending claims
                </div>
            </article>

        </section>

        <section class="dash-shortcuts">
            <a class="dash-shortcut" href="reports.php">
                <i class="fa-solid fa-file-lines"></i>
                <span>
                    <strong>Review Reports</strong>
                    <small>Approve or reject item reports</small>
                </span>
            </a>

            <a class="dash-shortcut" href="claims.php">
                <i class="fa-solid fa-hand-holding"></i>
                <span>
                    <strong>Review Claims</strong>
                    <small>Check item claim requests</small>
                </span>
            </a>

            <?php if ($role === "admin"): ?>
                <a class="dash-shortcut" href="../admin/dashboard.php">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>
                        <strong>Admin Dashboard</strong>
                        <small>Open the administration portal</small>
                    </span>
                </a>
            <?php else: ?>
                <a class="dash-shortcut" href="../logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>
                        <strong>Sign Out</strong>
                        <small>Safely end your session</small>
                    </span>
                </a>
            <?php endif; ?>
        </section>

        <div class="dash-overview">
            <span>
                <i class="fa-solid fa-magnifying-glass"></i>
                Lost items: <strong><?= number_format($totalLost) ?></strong>
            </span>

            <span>
                <i class="fa-solid fa-box-open"></i>
                Found items: <strong><?= number_format($totalFound) ?></strong>
            </span>

            <span>
                <i class="fa-solid fa-circle-xmark"></i>
                Rejected reports: <strong><?= number_format($rejectedReports) ?></strong>
            </span>

            <span>
                <i class="fa-solid fa-circle-check"></i>
                Approved claims: <strong><?= number_format($approvedClaims) ?></strong>
            </span>

            <?php if ($role === "admin"): ?>
                <span>
                    <i class="fa-solid fa-users"></i>
                    Total accounts: <strong><?= number_format($totalUsers) ?></strong>
                </span>
                <span>
                    <i class="fa-solid fa-user-gear"></i>
                    Staff accounts: <strong><?= number_format($totalStaff) ?></strong>
                </span>
                <span>
                    <i class="fa-solid fa-user-graduate"></i>
                    Student accounts: <strong><?= number_format($totalStudents) ?></strong>
                </span>
            <?php endif; ?>
        </div>

        <section class="dash-activity-grid">

            <article class="dash-activity-panel">
                <div class="dash-panel-heading">
                    <div>
                        <h2>Recent Reports</h2>
                        <p>Latest items submitted to the system</p>
                    </div>
                    <a href="reports.php">View all &rarr;</a>
                </div>

                <div class="dash-activity-list">
                    <?php if (!$recentReports): ?>
                        <div class="dash-empty">No reports submitted yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentReports as $report): ?>
                            <?php $status = dashboardStatus($report["status"] ?? "pending"); ?>

                            <div class="dash-activity-row">
                                <div class="dash-activity-copy">
                                    <strong>
                                        <?= dashboardText($report["item_name"] ?? "Unknown Item") ?>
                                    </strong>
                                    <small>
                                        <?= dashboardText(ucfirst((string)($report["type"] ?? "unknown"))) ?>
                                        ·
                                        <?= dashboardText($report["student_name"] ?? "Unknown Student") ?>
                                        <br>
                                        <?= dashboardText(dashboardDate($report["created_at"] ?? null)) ?>
                                    </small>
                                </div>

                                <span class="dash-status <?= dashboardText($status) ?>">
                                    <?= dashboardText($status) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <article class="dash-activity-panel">
                <div class="dash-panel-heading">
                    <div>
                        <h2>Recent Claims</h2>
                        <p>Latest requests from students</p>
                    </div>
                    <a href="claims.php">View all &rarr;</a>
                </div>

                <div class="dash-activity-list">
                    <?php if (!$recentClaims): ?>
                        <div class="dash-empty">No claims submitted yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentClaims as $claim): ?>
                            <?php $status = dashboardStatus($claim["status"] ?? "pending"); ?>

                            <div class="dash-activity-row">
                                <div class="dash-activity-copy">
                                    <strong>
                                        <?= dashboardText($claim["student_name"] ?? "Unknown Student") ?>
                                    </strong>
                                    <small>
                                        <?= dashboardText($claim["reason"] ?? "No reason provided") ?>
                                        <br>
                                        <?= dashboardText(dashboardDate($claim["created_at"] ?? null)) ?>
                                    </small>
                                </div>

                                <span class="dash-status <?= dashboardText($status) ?>">
                                    <?= dashboardText($status) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

        </section>
    </main>
</div>
</body>
</html>
