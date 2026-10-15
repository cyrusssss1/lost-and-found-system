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
    <!-- Assumes staff.css is stored in the project root, beside the staff/ and admin/ folders. -->
    <link rel="stylesheet" href="../staff.css">
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
