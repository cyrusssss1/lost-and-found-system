<?php 

$role = $_SESSION["role"] ?? ""; 

?>

<!-- ==============================
     NORMAL NAVIGATION
     ============================== -->

<div class="navbar">

    <?php if ($role === "student"): ?>

        <a href="/student/my_reports.php">
            My Reports
        </a>

        <a href="/student/my_claims.php">
            My Claims
        </a>

        <a href="/logout.php">
            Logout
        </a>


    <?php elseif ($role === "staff"): ?>

        <a href="/staff/reports.php">
            Reports
        </a>

        <a href="/staff/claims.php">
            Claims
        </a>

        <a href="/logout.php">
            Logout
        </a>


    <?php elseif ($role === "admin"): ?>

        <a href="/admin/manage_staff.php">
            Staff
        </a>

        <a href="/admin/reports.php">
            Reports
        </a>

        <a href="/admin/claims.php">
            Claims
        </a>

        <a href="/logout.php">
            Logout
        </a>

    <?php endif; ?>

</div>


<!-- ==============================
     DASHBOARD BUTTON
     ============================== -->

<div class="top-nav">

    <?php if ($role === "student"): ?>

        <a href="/student/dashboard.php">
            Dashboard
        </a>


    <?php elseif ($role === "staff"): ?>

        <a href="/staff/dashboard.php">
            Dashboard
        </a>


    <?php elseif ($role === "admin"): ?>

        <a href="/admin/dashboard.php">
            Dashboard
        </a>

    <?php endif; ?>

</div>
