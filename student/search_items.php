<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "student"
) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();

$reports = $db->getDatabase()->reports;


/* =========================================================
   SEARCH / TYPE
   ========================================================= */

$search = trim($_GET["search"] ?? "");

$type = strtolower(
    trim($_GET["type"] ?? "found")
);

if ($type !== "found" && $type !== "lost") {
    $type = "found";
}


/* =========================================================
   BUILD SEARCH FILTER
   ========================================================= */

$filter = [
    "type" => $type
];

if ($search !== "") {

    /*
     * Escape special regex characters so searches such as:
     *
     * C++
     * phone (black)
     * bag.
     *
     * do not break MongoDB regex searching.
     */

    $safeSearch = preg_quote(
        $search,
        "/"
    );

    $filter["\$or"] = [

        [
            "item_name" => [
                "\$regex" => $safeSearch,
                "\$options" => "i"
            ]
        ],

        [
            "description" => [
                "\$regex" => $safeSearch,
                "\$options" => "i"
            ]
        ],

        [
            "location" => [
                "\$regex" => $safeSearch,
                "\$options" => "i"
            ]
        ]

    ];
}


/* =========================================================
   GET ITEMS
   ========================================================= */

$items = $reports->find(
    $filter,
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


/* =========================================================
   IMAGE URL HELPER
   ========================================================= */

function getItemImageUrl($imagePath)
{
    $imagePath = trim(
        (string) $imagePath
    );

    if ($imagePath === "") {
        return "";
    }


    /*
     * CLOUDINARY / EXTERNAL IMAGE
     *
     * If the database contains:
     *
     * https://res.cloudinary.com/...
     *
     * use it directly.
     */

    if (
        str_starts_with(
            $imagePath,
            "http://"
        ) ||
        str_starts_with(
            $imagePath,
            "https://"
        )
    ) {

        return $imagePath;
    }


    /*
     * LOCAL IMAGE
     *
     * Older reports may still contain:
     *
     * uploads/items/example.jpg
     */

    return "../" .
        ltrim(
            $imagePath,
            "/\\"
        );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        <?php

        echo $type === "found"
            ? "Search Found Items"
            : "Search Lost Items";

        ?>

    </title>


    <link
        rel="stylesheet"
        href="../style.css"
    >

    <link
        rel="stylesheet"
        href="student.css"
    >


    <style>

        /* =====================================================
           SEARCH PAGE
           ===================================================== */

        .search-page {
            max-width: 1150px;
            margin: 0 auto;
            padding: 30px 20px 50px;
        }


        /* =====================================================
           SEARCH FORM
           ===================================================== */

        .search-box form {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .big-search-input {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: 1px solid #dfe5e1;
            border-radius: 13px;
            padding: 0 14px;
            min-height: 48px;
        }

        .big-search-input input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            min-width: 0;
            font-size: 14px;
        }

        .big-search-input a {
            text-decoration: none;
            color: #7b8780;
            font-size: 17px;
            padding: 4px;
        }

        .big-search-input a:hover {
            color: #173b2b;
        }

        .search-button {
            border: 0;
            border-radius: 13px;
            padding: 0 22px;
            min-height: 48px;
            background: #173b2b;
            color: white;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
        }

        .search-button:hover {
            background: #23734a;
            transform: translateY(-1px);
        }


        /* =====================================================
           ITEM IMAGE
           ===================================================== */

        .item-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #eef2ef;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .item-image img.image-error {
            display: none;
        }

        .no-item-image {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #829087;
            font-size: 35px;
        }

        .no-item-image span {
            font-size: 12px;
            font-weight: 700;
        }


        /* =====================================================
           IMAGE ERROR FALLBACK
           ===================================================== */

        .image-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            color: #829087;
            background: #eef2ef;
            font-size: 30px;
        }

        .image-fallback span {
            font-size: 11px;
            font-weight: 700;
        }


        /* =====================================================
           MOBILE SEARCH
           ===================================================== */

        @media (max-width: 650px) {

            .search-page {
                padding: 20px 14px 40px;
            }

            .search-box form {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
                min-height: 46px;
            }

            .item-image {
                height: 200px;
            }

        }

    </style>

</head>


<body class="student-dashboard">


<?php include __DIR__ . '/../navbar.php'; ?>


<main class="search-page">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <section
        class="search-header
        <?php

        echo $type === "found"
            ? "found-search-header"
            : "lost-search-header";

        ?>"
    >

        <div>

            <span class="report-label">
                ITEM SEARCH
            </span>


            <h1>

                <?php

                echo $type === "found"
                    ? "Find a Lost Item 🔎"
                    : "Search Lost Reports 📦";

                ?>

            </h1>


            <p>

                <?php

                echo $type === "found"
                    ? "Browse items that have been found and reported by students."
                    : "Search items that students have reported as lost.";

                ?>

            </p>

        </div>


        <div class="search-header-icon">

            <?php

            echo $type === "found"
                ? "🎒"
                : "🔍";

            ?>

        </div>

    </section>


    <!-- =====================================================
         TYPE SWITCH
         ===================================================== -->

    <div class="search-tabs">


        <a
            href="search_items.php?type=found"
            class="<?php

            echo $type === "found"
                ? "active"
                : "";

            ?>"
        >

            🎒 Found Items

        </a>


        <a
            href="search_items.php?type=lost"
            class="<?php

            echo $type === "lost"
                ? "active"
                : "";

            ?>"
        >

            📦 Lost Items

        </a>


    </div>


    <!-- =====================================================
         SEARCH
         ===================================================== -->

    <section class="search-box">


        <form
            method="GET"
            action="search_items.php"
        >


            <input
                type="hidden"
                name="type"
                value="<?php

                echo htmlspecialchars(
                    $type,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
            >


            <div class="big-search-input">


                <span>
                    🔎
                </span>


                <input
                    type="search"
                    name="search"
                    placeholder="Search by item name, description, or location..."
                    value="<?php

                    echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?>"
                    autocomplete="off"
                >


                <?php if ($search !== ""): ?>

                    <a
                        href="search_items.php?type=<?php

                        echo urlencode($type);

                        ?>"
                        title="Clear search"
                    >
                        ✕
                    </a>

                <?php endif; ?>


            </div>


            <button
                type="submit"
                class="search-button"
            >

                🔎 Search

            </button>


        </form>


    </section>


    <!-- =====================================================
         RESULTS HEADING
         ===================================================== -->

    <div class="results-heading">


        <div>

            <h2>

                <?php

                echo $type === "found"
                    ? "Found Items"
                    : "Lost Items";

                ?>

            </h2>


            <p>

                <?php

                if ($search !== "") {

                    echo "Showing results for \"";

                    echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    echo "\"";

                } else {

                    echo "Recently reported items";

                }

                ?>

            </p>

        </div>


    </div>


    <!-- =====================================================
         RESULTS
         ===================================================== -->

    <section class="item-grid">


        <?php

        $hasItems = false;


        foreach ($items as $item):


            $hasItems = true;


            $status = strtolower(
                (string) (
                    $item["status"]
                    ?? "pending"
                )
            );


            $imagePath =
                $item["image_path"]
                ?? "";


            $imageUrl =
                getItemImageUrl(
                    $imagePath
                );


            $itemName =
                $item["item_name"]
                ?? "Unnamed Item";


            $description =
                $item["description"]
                ?? "No description provided.";


            $location =
                $item["location"]
                ?? "Location not provided.";


            $itemType =
                $item["type"]
                ?? $type;

        ?>


            <article class="item-card">


                <!-- =================================================
                     IMAGE
                     ================================================= -->

                <div class="item-image">


                    <?php if ($imageUrl !== ""): ?>


                        <img
                            src="<?php

                            echo htmlspecialchars(
                                $imageUrl,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>"
                            alt="<?php

                            echo htmlspecialchars(
                                $itemName,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>"
                            loading="lazy"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >


                        <div
                            class="image-fallback"
                            style="display:none;"
                        >

                            <?php

                            echo $itemType === "found"
                                ? "🎒"
                                : "📦";

                            ?>

                            <span>
                                Photo unavailable
                            </span>

                        </div>


                    <?php else: ?>


                        <div class="no-item-image">

                            <?php

                            echo $itemType === "found"
                                ? "🎒"
                                : "📦";

                            ?>

                            <span>
                                No photo
                            </span>

                        </div>


                    <?php endif; ?>


                    <span class="item-type-badge">

                        <?php

                        echo $itemType === "found"
                            ? "FOUND"
                            : "LOST";

                        ?>

                    </span>


                </div>


                <!-- =================================================
                     CONTENT
                     ================================================= -->

                <div class="item-card-content">


                    <div class="item-card-title-row">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $itemName,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>

                        </h3>


                        <span
                            class="status
                            <?php

                            echo htmlspecialchars(
                                $status,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                ucfirst($status),
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>

                        </span>


                    </div>


                    <p class="item-description">

                        <?php

                        echo htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>

                    </p>


                    <div class="item-details">


                        <div>

                            📍

                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $location,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>

                            </span>

                        </div>


                        <div>

                            📅

                            <span>


                                <?php

                                if ($itemType === "lost") {

                                    echo htmlspecialchars(
                                        $item["date_lost"]
                                        ?? "Not provided",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                } else {

                                    echo htmlspecialchars(
                                        $item["date_found"]
                                        ?? "Not provided",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                }

                                ?>


                            </span>

                        </div>


                    </div>


                    <!-- =================================================
                         CLAIM BUTTON
                         ================================================= -->

                    <?php if (
                        $itemType === "found" &&
                        $status === "pending"
                    ): ?>


                        <a
                            class="claim-button"
                            href="claim_item.php?id=<?php

                            echo urlencode(
                                (string) $item["_id"]
                            );

                            ?>"
                        >

                            🙋 Claim This Item

                            <span>
                                →
                            </span>

                        </a>


                    <?php endif; ?>


                </div>


            </article>


        <?php endforeach; ?>


        <!-- =====================================================
             EMPTY
             ===================================================== -->

        <?php if (!$hasItems): ?>


            <div class="empty-results">


                <div class="empty-icon">

                    <?php

                    echo $type === "found"
                        ? "🎒"
                        : "📦";

                    ?>

                </div>


                <h2>
                    No Items Found
                </h2>


                <p>

                    <?php

                    echo $search !== ""
                        ? "We couldn't find any matching items. Try a different search."
                        : "There are currently no items in this section.";

                    ?>

                </p>


            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>
