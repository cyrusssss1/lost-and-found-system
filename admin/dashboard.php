<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();
$database = $db->getDatabase();

$users   = $database->users;
$reports = $database->reports;
$claims  = $database->claims;

$adminName = htmlspecialchars($_SESSION["name"] ?? "Admin", ENT_QUOTES, "UTF-8");

$totalStaff       = $users->countDocuments(["role" => "staff"]);
$totalReports     = $reports->countDocuments();
$pendingReports   = $reports->countDocuments(["status" => "pending"]);
$approvedReports  = $reports->countDocuments(["status" => "approved"]);
$rejectedReports  = $reports->countDocuments(["status" => "rejected"]);
$totalLost        = $reports->countDocuments(["type" => "lost"]);
$totalFound       = $reports->countDocuments(["type" => "found"]);

$totalClaims      = $claims->countDocuments();
$pendingClaims    = $claims->countDocuments(["status" => "pending"]);
$approvedClaims   = $claims->countDocuments(["status" => "approved"]);
$rejectedClaims   = $claims->countDocuments(["status" => "rejected"]);

$recentReports = $reports->find([], [
    "sort" => ["created_at" => -1],
    "limit" => 6
]);

$recentClaims = $claims->find([], [
    "sort" => ["created_at" => -1],
    "limit" => 6
]);

function safeText($value, $fallback = "—") {
    if ($value === null || $value === "") {
        $value = $fallback;
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function safeStatus($value) {
    $allowed = ["pending", "approved", "rejected"];
    $status = strtolower((string)($value ?? "pending"));
    return in_array($status, $allowed, true) ? $status : "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Lost &amp; Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Load the shared Staff stylesheet from the staff folder. -->
    <link rel="stylesheet" href="../staff/staff.css">
    <style>
        /* Admin-only additions. The main layout and components reuse staff.css. */
        .admin-role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            margin-top: 6px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .admin-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            padding: 27px 30px;
            margin-bottom: 24px;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(125deg, #172554 0%, #1d4ed8 58%, #3b82f6 100%);
            box-shadow: 0 14px 34px rgba(37, 99, 235, .17);
            overflow: hidden;
            position: relative;
        }
        .admin-welcome::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border: 35px solid rgba(255,255,255,.08);
            border-radius: 50%;
            right: -60px;
            top: -110px;
            pointer-events: none;
        }
        .admin-welcome-content { position: relative; z-index: 1; }
        .admin-eyebrow {
            display: inline-block;
            margin-bottom: 8px;
            color: #dbeafe;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .admin-welcome h1 {
            margin: 0;
            color: #fff;
            font-size: clamp(24px, 3vw, 32px);
            letter-spacing: -.7px;
        }
        .admin-welcome p {
            margin: 8px 0 0;
            max-width: 650px;
            color: #dbeafe;
            font-size: 13px;
            line-height: 1.6;
        }
        .admin-welcome-icon {
            position: relative;
            z-index: 1;
            width: 68px;
            height: 68px;
            flex: 0 0 68px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 20px;
            background: rgba(255,255,255,.13);
            font-size: 28px;
        }
        .admin-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 24px;
        }
        .admin-stat-card {
            padding: 19px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            box-shadow: 0 7px 25px rgba(15,23,42,.035);
            min-width: 0;
        }
        .admin-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .admin-stat-label {
            color: #7b8794;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .55px;
            text-transform: uppercase;
        }
        .admin-stat-icon {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
            flex-shrink: 0;
        }
        .admin-stat-number {
            margin-top: 12px;
            color: #0f172a;
            font-size: 29px;
            line-height: 1.15;
            font-weight: 800;
        }
        .admin-stat-note {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 10px;
        }
        .admin-stat-card.amber .admin-stat-icon { background: #fffbeb; color: #b45309; }
        .admin-stat-card.green .admin-stat-icon { background: #ecfdf5; color: #047857; }
        .admin-stat-card.red .admin-stat-icon { background: #fef2f2; color: #b91c1c; }
        .admin-shortcuts {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .admin-shortcut {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #fff;
            color: #1e293b;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            box-shadow: 0 5px 18px rgba(15,23,42,.025);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .admin-shortcut:hover {
            transform: translateY(-2px);
            border-color: #93c5fd;
            box-shadow: 0 10px 22px rgba(37,99,235,.08);
        }
        .admin-shortcut i {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            flex: 0 0 37px;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
        }
        .admin-shortcut span { font-size: 12px; font-weight: 800; }
        .admin-shortcut small {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 500;
        }
        .admin-activity-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .admin-panel {
            min-width: 0;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            box-shadow: 0 7px 25px rgba(15,23,42,.035);
        }
        .admin-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf2f7;
        }
        .admin-panel-head h2 { margin: 0; color: #172033; font-size: 15px; }
        .admin-panel-head p { margin: 5px 0 0; color: #94a3b8; font-size: 10px; }
        .admin-panel-head a {
            color: #2563eb;
            font-size: 10px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }
        .admin-panel-head a:hover { text-decoration: underline; }
        .admin-activity-list { padding: 0 20px; }
        .admin-activity-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .admin-activity-item:last-child { border-bottom: 0; }
        .admin-activity-main { min-width: 0; }
        .admin-activity-main strong {
            display: block;
            overflow-wrap: anywhere;
            color: #1e293b;
            font-size: 12px;
        }
        .admin-activity-main small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }
        .admin-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .admin-status.pending { background: #fffbeb; color: #a16207; }
        .admin-status.approved { background: #ecfdf5; color: #047857; }
        .admin-status.rejected { background: #fef2f2; color: #b91c1c; }
        .admin-empty {
            padding: 28px 10px;
            color: #94a3b8;
            text-align: center;
            font-size: 12px;
        }
        .admin-overview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .admin-overview span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
            font-size: 11px;
        }
        .admin-overview strong { color: #0f172a; }
        @media (max-width: 1100px) {
            .admin-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .admin-shortcuts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 800px) {
            .admin-activity-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .admin-welcome { align-items: flex-start; padding: 22px; }
            .admin-welcome-icon { width: 48px; height: 48px; flex-basis: 48px; border-radius: 14px; font-size: 20px; }
            .admin-stat-grid, .admin-shortcuts { grid-template-columns: 1fr; }
            .admin-panel-head { padding: 16px; }
            .admin-activity-list { padding: 0 16px; }
            .admin-overview { display: grid; grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="staff-body">
<div class="staff-layout">
    <aside class="staff-sidebar">
        <div class="staff-brand">
            <div class="staff-brand-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div>
                <h2>Lost &amp; Found</h2>
                <span>Management Portal</span>
            </div>
        </div>

        <nav class="staff-nav" aria-label="Admin navigation">
            <p class="staff-nav-label">Workspace</p>
            <a href="dashboard.php" class="active">
                <span class="staff-nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                Dashboard
            </a>
            <a href="reports.php">
                <span class="staff-nav-icon"><i class="fa-solid fa-file-lines"></i></span>
                Reports
            </a>
            <a href="claim.php">
                <span class="staff-nav-icon"><i class="fa-solid fa-hand-holding"></i></span>
                Claims
            </a>

            <p class="staff-nav-label" style="margin-top:25px;">Administration</p>
            <a href="manage_staff.php">
                <span class="staff-nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                Manage Staff
            </a>
            <a href="create_staff.php">
                <span class="staff-nav-icon"><i class="fa-solid fa-user-plus"></i></span>
                Create Staff
            </a>
        </nav>

        <div class="staff-user">
            <div class="staff-user-top">
                <div class="staff-avatar"><i class="fa-solid fa-user-shield"></i></div>
                <div>
                    <strong><?= $adminName ?></strong>
                    <small>ADMINISTRATOR</small>
                    <span class="admin-role-pill"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                </div>
            </div>
            <a class="staff-logout" href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> &nbsp; Log out
            </a>
        </div>
    </aside>

    <main class="staff-main">
        <header class="staff-topbar">
            <div class="staff-breadcrumb">
                Lost &amp; Found / <strong>Admin Dashboard</strong>
            </div>
            <div class="staff-date">
                <i class="fa-regular fa-circle-user"></i>
                &nbsp; Signed in as <strong>Admin</strong>
            </div>
        </header>

        <section class="admin-welcome">
            <div class="admin-welcome-content">
                <span class="admin-eyebrow"><i class="fa-solid fa-shield-halved"></i> Administration overview</span>
                <h1>Welcome back, <?= $adminName ?>!</h1>
                <p>Manage reports, claims, and staff activity from one organized workspace.</p>
            </div>
            <div class="admin-welcome-icon"><i class="fa-solid fa-chart-pie"></i></div>
        </section>

        <section class="admin-stat-grid" aria-label="System statistics">
            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <span class="admin-stat-label">Total Staff</span>
                    <span class="admin-stat-icon"><i class="fa-solid fa-users"></i></span>
                </div>
                <div class="admin-stat-number"><?= number_format($totalStaff) ?></div>
                <div class="admin-stat-note">Staff accounts registered</div>
            </article>
            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <span class="admin-stat-label">Total Reports</span>
                    <span class="admin-stat-icon"><i class="fa-solid fa-file-lines"></i></span>
                </div>
                <div class="admin-stat-number"><?= number_format($totalReports) ?></div>
                <div class="admin-stat-note">Lost and found reports</div>
            </article>
            <article class="admin-stat-card amber">
                <div class="admin-stat-top">
                    <span class="admin-stat-label">Pending Reports</span>
                    <span class="admin-stat-icon"><i class="fa-solid fa-clock"></i></span>
                </div>
                <div class="admin-stat-number"><?= number_format($pendingReports) ?></div>
                <div class="admin-stat-note">Waiting for review</div>
            </article>
            <article class="admin-stat-card green">
                <div class="admin-stat-top">
                    <span class="admin-stat-label">Approved Reports</span>
                    <span class="admin-stat-icon"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <div class="admin-stat-number"><?= number_format($approvedReports) ?></div>
                <div class="admin-stat-note">Approved and published</div>
            </article>
        </section>

        <section class="admin-shortcuts" aria-label="Quick actions">
            <a class="admin-shortcut" href="reports.php">
                <i class="fa-solid fa-file-lines"></i>
                <span>Review Reports<small>Manage lost and found posts</small></span>
            </a>
            <a class="admin-shortcut" href="claim.php">
                <i class="fa-solid fa-hand-holding"></i>
                <span>Review Claims<small>Check item claim requests</small></span>
            </a>
            <a class="admin-shortcut" href="manage_staff.php">
                <i class="fa-solid fa-users-gear"></i>
                <span>Manage Staff<small>View staff accounts</small></span>
            </a>
            <a class="admin-shortcut" href="create_staff.php">
                <i class="fa-solid fa-user-plus"></i>
                <span>Create Staff<small>Add a staff account</small></span>
            </a>
        </section>

        <div class="admin-overview">
            <span><i class="fa-solid fa-magnifying-glass"></i> Lost items: <strong><?= number_format($totalLost) ?></strong></span>
            <span><i class="fa-solid fa-box-open"></i> Found items: <strong><?= number_format($totalFound) ?></strong></span>
            <span><i class="fa-solid fa-hand-holding"></i> Total claims: <strong><?= number_format($totalClaims) ?></strong></span>
            <span><i class="fa-solid fa-clock"></i> Pending claims: <strong><?= number_format($pendingClaims) ?></strong></span>
        </div>

        <section class="admin-activity-grid" style="margin-top:24px;">
            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2><i class="fa-solid fa-file-lines" style="color:#2563eb;"></i> &nbsp; Recent Reports</h2>
                        <p>The latest lost and found submissions</p>
                    </div>
                    <a href="reports.php">View all <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="admin-activity-list">
                    <?php $hasRecentReports = false; ?>
                    <?php foreach ($recentReports as $report): $hasRecentReports = true; $status = safeStatus($report["status"] ?? "pending"); ?>
                        <div class="admin-activity-item">
                            <div class="admin-activity-main">
                                <strong><?= safeText($report["item_name"] ?? null, "Unknown item") ?></strong>
                                <small>
                                    <?= safeText($report["student_name"] ?? null, "Unknown student") ?>
                                    &bull; <?= safeText(ucfirst((string)($report["type"] ?? "report"))) ?>
                                </small>
                            </div>
                            <span class="admin-status <?= $status ?>"><?= safeText($status) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$hasRecentReports): ?>
                        <div class="admin-empty"><i class="fa-regular fa-folder-open"></i><br><br>No reports have been submitted yet.</div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2><i class="fa-solid fa-hand-holding" style="color:#2563eb;"></i> &nbsp; Recent Claims</h2>
                        <p>The latest item claim requests</p>
                    </div>
                    <a href="claim.php">View all <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="admin-activity-list">
                    <?php $hasRecentClaims = false; ?>
                    <?php foreach ($recentClaims as $claim): $hasRecentClaims = true; $status = safeStatus($claim["status"] ?? "pending"); ?>
                        <div class="admin-activity-item">
                            <div class="admin-activity-main">
                                <strong><?= safeText($claim["student_name"] ?? $claim["name"] ?? null, "Unknown student") ?></strong>
                                <small>Claim request submitted</small>
                            </div>
                            <span class="admin-status <?= $status ?>"><?= safeText($status) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$hasRecentClaims): ?>
                        <div class="admin-empty"><i class="fa-regular fa-folder-open"></i><br><br>No claims have been submitted yet.</div>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>
</div>
</body>
</html>
