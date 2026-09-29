<?php

$role = $_SESSION["role"] ?? "";

?>

<!-- ==============================
     NORMAL NAVIGATION
     ============================== -->

<div class="navbar">

    <?php if ($role === "student"): ?>

        <a href="/Lost%20and%20Found%20System/student/my_reports.php">
            My Reports
        </a>

        <a href="/Lost%20and%20Found%20System/student/my_claims.php">
            My Claims
        </a>

        <a href="/Lost%20and%20Found%20System/logout.php">
            Logout
        </a>


    <?php elseif ($role === "staff"): ?>

        <a href="/Lost%20and%20Found%20System/staff/reports.php">
            Reports
        </a>

        <a href="/Lost%20and%20Found%20System/staff/claims.php">
            Claims
        </a>

        <a href="/Lost%20and%20Found%20System/logout.php">
            Logout
        </a>


    <?php elseif ($role === "admin"): ?>

        <a href="/Lost%20and%20Found%20System/admin/manage_staff.php">
            Staff
        </a>

        <a href="/Lost%20and%20Found%20System/admin/reports.php">
            Reports
        </a>

        <a href="/Lost%20and%20Found%20System/admin/claims.php">
            Claims
        </a>

        <a href="/Lost%20and%20Found%20System/logout.php">
            Logout
        </a>

    <?php endif; ?>

</div>


<!-- ==============================
     FIXED DASHBOARD BUTTON
     ============================== -->

<div class="top-nav">

    <?php if ($role === "student"): ?>

        <a href="/Lost%20and%20Found%20System/student/dashboard.php">
            Dashboard
        </a>


    <?php elseif ($role === "staff"): ?>

        <a href="/Lost%20and%20Found%20System/staff/dashboard.php">
            Dashboard
        </a>


    <?php elseif ($role === "admin"): ?>

        <a href="/Lost%20and%20Found%20System/admin/dashboard.php">
            Dashboard
        </a>

    <?php endif; ?>

</div>