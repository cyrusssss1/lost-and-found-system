<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();

$reports = $db->getDatabase()->reports;

$search = trim($_GET["search"] ?? "");
$type = $_GET["type"] ?? "found";

if ($type !== "found" && $type !== "lost") {
    $type = "found";
}


/* ==============================
   SEARCH
   ============================== */

$filter = [
    "type" => $type
];

if ($search !== "") {

    $filter["$or"] = [

        [
            "item_name" => [
                '$regex' => $search,
                '$options' => 'i'
            ]
        ],

        [
            "description" => [
                '$regex' => $search,
                '$options' => 'i'
            ]
        ],

        [
            "location" => [
                '$regex' => $search,
                '$options' => 'i'
            ]
        ]

    ];
}


$items = $reports->find(
    $filter,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $type === "found"
            ? "Search Found Items"
            : "Search Lost Items"; ?>
    </title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="search-page">


    <!-- HEADER -->

    <section class="search-header
        <?php echo $type === "found"
            ? "found-search-header"
            : "lost-search-header"; ?>">

        <div>

            <span class="report-label">
                ITEM SEARCH
            </span>

            <h1>

                <?php echo $type === "found"
                    ? "Find a Lost Item 🔎"
                    : "Search Lost Reports 📦"; ?>

            </h1>

            <p>

                <?php echo $type === "found"
                    ? "Browse items that have been found and reported by students."
                    : "Search items that students have reported as lost."; ?>

            </p>

        </div>

        <div class="search-header-icon">

            <?php echo $type === "found"
                ? "🎒"
                : "🔍"; ?>

        </div>

    </section>


    <!-- TYPE SWITCH -->

    <div class="search-tabs">

        <a
            href="search_items.php?type=found"
            class="<?php echo $type === "found"
                ? "active"
                : ""; ?>"
        >
            🎒 Found Items
        </a>

        <a
            href="search_items.php?type=lost"
            class="<?php echo $type === "lost"
                ? "active"
                : ""; ?>"
        >
            📦 Lost Items
        </a>

    </div>


    <!-- SEARCH FORM -->

    <section class="search-box">

        <form method="GET">

            <input
                type="hidden"
                name="type"
                value="<?php echo htmlspecialchars($type); ?>"
            >

            <div class="big-search-input">

                <span>
                    🔎
                </span>

                <input
                    type="text"
                    name="search"
                    placeholder="Search by item name, description, or location..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <?php if ($search !== ""): ?>

                    <a href="search_items.php?type=<?php echo htmlspecialchars($type); ?>">
                        ✕
                    </a>

                <?php endif; ?>

            </div>

            <button
                type="submit"
                class="search-button"
            >
                Search
            </button>

        </form>

    </section>


    <!-- RESULTS TITLE -->

    <div class="results-heading">

        <div>

            <h2>
                <?php echo $type === "found"
                    ? "Found Items"
                    : "Lost Items"; ?>
            </h2>

            <p>
                <?php
                echo $search !== ""
                    ? "Showing results for \"" . htmlspecialchars($search) . "\""
                    : "Recently reported items";
                ?>
            </p>

        </div>

    </div>


    <!-- RESULTS -->

    <section class="item-grid">

        <?php

        $hasItems = false;

        foreach ($items as $item):

            $hasItems = true;

            $status = strtolower($item["status"] ?? "pending");

            $imagePath = $item["image_path"] ?? "";

        ?>

            <article class="item-card">


                <!-- IMAGE -->

                <div class="item-image">

                    <?php if ($imagePath !== ""): ?>

                        <img
                            src="../<?php echo htmlspecialchars($imagePath); ?>"
                            alt="<?php echo htmlspecialchars($item["item_name"]); ?>"
                        >

                    <?php else: ?>

                        <div class="no-item-image">

                            <?php echo $type === "found"
                                ? "🎒"
                                : "📦"; ?>

                            <span>
                                No photo
                            </span>

                        </div>

                    <?php endif; ?>


                    <span class="item-type-badge">

                        <?php echo $item["type"] === "found"
                            ? "FOUND"
                            : "LOST"; ?>

                    </span>

                </div>


                <!-- CONTENT -->

                <div class="item-card-content">

                    <div class="item-card-title-row">

                        <h3>
                            <?php echo htmlspecialchars(
                                $item["item_name"]
                            ); ?>
                        </h3>

                        <span class="status
                            <?php echo htmlspecialchars($status); ?>">
                            <?php echo htmlspecialchars(
                                ucfirst($status)
                            ); ?>
                        </span>

                    </div>


                    <p class="item-description">

                        <?php echo htmlspecialchars(
                            $item["description"]
                        ); ?>

                    </p>


                    <div class="item-details">

                        <div>
                            📍
                            <span>
                                <?php echo htmlspecialchars(
                                    $item["location"]
                                ); ?>
                            </span>
                        </div>


                        <div>

                            📅

                            <span>

                                <?php

                                if ($item["type"] === "lost") {

                                    echo htmlspecialchars(
                                        $item["date_lost"] ?? "Not provided"
                                    );

                                } else {

                                    echo htmlspecialchars(
                                        $item["date_found"] ?? "Not provided"
                                    );
                                }

                                ?>

                            </span>

                        </div>

                    </div>


                    <?php if (
                        $item["type"] === "found" &&
                        $status === "pending"
                    ): ?>

                        <a
                            class="claim-button"
                            href="claim_item.php?id=<?php echo urlencode(
                                (string) $item["_id"]
                            ); ?>"
                        >
                            🙋 Claim This Item
                            <span>→</span>
                        </a>

                    <?php endif; ?>


                </div>

            </article>

        <?php endforeach; ?>


        <?php if (!$hasItems): ?>

            <div class="empty-results">

                <div class="empty-icon">
                    <?php echo $type === "found"
                        ? "🎒"
                        : "📦"; ?>
                </div>

                <h2>
                    No Items Found
                </h2>

                <p>

                    <?php echo $search !== ""
                        ? "We couldn't find any matching items. Try a different search."
                        : "There are currently no items in this section."; ?>

                </p>

            </div>

        <?php endif; ?>

    </section>


</main>

</body>

</html>