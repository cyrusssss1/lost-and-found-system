<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard</title>

    <!-- Main system CSS -->
    <link rel="stylesheet" href="../style.css">

    <!-- Student dashboard CSS -->
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="student-container">


    <!-- =========================
         WELCOME BANNER
         ========================= -->

    <section class="student-welcome">

        <div class="welcome-content">

            <span class="dashboard-label">
                STUDENT PORTAL
            </span>

            <h1>
                Welcome back,
                <span>
                    <?php echo htmlspecialchars($_SESSION["name"]); ?>
                </span>
                👋
            </h1>

            <p>
                Manage your lost and found items easily from your dashboard.
            </p>

        </div>


        <div class="student-welcome-icon">
            🔎
        </div>

    </section>


    <!-- =========================
         ACTIONS
         ========================= -->

    <h2 class="dashboard-section-title">
        What would you like to do?
    </h2>


    <section class="student-actions">


        <!-- REPORT LOST -->

        <div class="student-action-card lost-card">

            <div class="action-icon">
                📦
            </div>

            <div class="action-content">

                <h3>
                    Lost Something?
                </h3>

                <p>
                    Report an item you've lost so others can help you find it.
                </p>

                <a
                    href="report_lost.php"
                    class="student-action-button blue"
                >
                    Report Lost Item
                    <span>→</span>
                </a>

            </div>

        </div>


        <!-- REPORT FOUND -->

        <div class="student-action-card found-card">

            <div class="action-icon">
                🎒
            </div>

            <div class="action-content">

                <h3>
                    Found Something?
                </h3>

                <p>
                    Report an item you've found and help return it to its owner.
                </p>

                <a
                    href="report_found.php"
                    class="student-action-button green"
                >
                    Report Found Item
                    <span>→</span>
                </a>

            </div>

        </div>


    </section>


    <!-- =========================
         SEARCH ITEMS
         ========================= -->

    <h2 class="dashboard-section-title">
        Search Items
    </h2>


    <section class="search-actions">


        <!-- FOUND ITEMS -->

        <a
            href="search_items.php?type=found"
            class="search-card"
        >

            <div class="search-card-icon green-icon">
                🔎
            </div>

            <div class="search-card-content">

                <h3>
                    Search Found Items
                </h3>

                <p>
                    Browse items that have been reported as found.
                </p>

            </div>

            <span class="search-arrow">
                →
            </span>

        </a>


        <!-- LOST ITEMS -->

        <a
            href="search_items.php?type=lost"
            class="search-card"
        >

            <div class="search-card-icon blue-icon">
                🔍
            </div>

            <div class="search-card-content">

                <h3>
                    Search Lost Items
                </h3>

                <p>
                    Browse items that have been reported as lost.
                </p>

            </div>

            <span class="search-arrow">
                →
            </span>

        </a>


    </section>


    <!-- =========================
         TIP
         ========================= -->

    <section class="student-tip">

        <div class="tip-icon">
            💡
        </div>

        <div>

            <strong>
                Quick Tip
            </strong>

            <p>
                When reporting an item, provide as much detail as possible
                to make it easier to identify.
            </p>

        </div>

    </section>


</main>


</body>

</html>