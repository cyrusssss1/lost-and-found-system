
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

/*
|--------------------------------------------------------------------------
| Escape output safely
|--------------------------------------------------------------------------
*/
function searchEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Resolve image URL
|--------------------------------------------------------------------------
| Cloudinary URLs are used directly.
| Relative local paths are resolved from the project root.
|--------------------------------------------------------------------------
*/
function getStudentImageUrl($imagePath): string
{
    if (!is_string($imagePath) || trim($imagePath) === "") {
        return "";
    }

    $imagePath = trim($imagePath);

    // Cloudinary or another external image URL.
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)parse_url($imagePath, PHP_URL_SCHEME));

        return in_array($scheme, ["http", "https"], true)
            ? $imagePath
            : "";
    }

    // Do not allow unexpected URL schemes or protocol-relative URLs.
    if (str_starts_with($imagePath, "//")) {
        return "";
    }

    // Normalize Windows separators.
    $imagePath = str_replace("\\", "/", $imagePath);

    // Remove old relative prefixes.
    while (str_starts_with($imagePath, "./")) {
        $imagePath = substr($imagePath, 2);
    }

    // A root-relative path is already relative to the website root.
    if (str_starts_with($imagePath, "/")) {
        return $imagePath;
    }

    // A path stored as "student/uploads/..." is project-root relative.
    if (
        str_starts_with($imagePath, "student/") ||
        str_starts_with($imagePath, "uploads/") ||
        str_starts_with($imagePath, "images/")
    ) {
        return "/" . $imagePath;
    }

    // Existing local paths such as "uploads/item.jpg".
    return "../" . ltrim($imagePath, "/");
}

/*
|--------------------------------------------------------------------------
| Read search parameters
|--------------------------------------------------------------------------
*/
$search = trim((string)($_GET["search"] ?? ""));
$type = (string)($_GET["type"] ?? "found");

if (!in_array($type, ["found", "lost"], true)) {
    $type = "found";
}

/*
|--------------------------------------------------------------------------
| Build shared report search
|--------------------------------------------------------------------------
| IMPORTANT:
| Do not filter by user_id. This allows students to see reports
| submitted by other accounts in the same MongoDB collection.
|--------------------------------------------------------------------------
*/
$filter = [
    "type" => $type
];

if ($search !== "") {
    $safeSearch = preg_quote($search, "/");

    // Single quotes are important: "$or" would be treated as a PHP variable.
    $filter['$or'] = [
        [
            "item_name" => [
                '$regex' => $safeSearch,
                '$options' => 'i'
            ]
        ],
        [
            "description" => [
                '$regex' => $safeSearch,
                '$options' => 'i'
            ]
        ],
        [
            "location" => [
                '$regex' => $safeSearch,
                '$options' => 'i'
            ]
        ]
    ];
}

/*
|--------------------------------------------------------------------------
| Retrieve reports from the shared database
|--------------------------------------------------------------------------
| This intentionally does not restrict results to the current student.
| Statuses are displayed as stored; staff/admin approval rules are
| not changed by this search-page fix.
|--------------------------------------------------------------------------
*/
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
        | Lost &amp; Found
    </title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

    <style>
        .item-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f1f5f9;
            border-radius: 14px 14px 0 0;
        }

        .item-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-item-image {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #64748b;
            font-size: 42px;
        }

        .no-item-image span {
            font-size: 13px;
        }

        .item-type-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, .85);
            color: white;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        .item-card-content {
            padding: 18px;
        }

        .item-card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .item-card-title-row h3 {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .item-description {
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .item-details {
            display: grid;
            gap: 9px;
            margin-top: 14px;
        }

        .item-details > div {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            overflow-wrap: anywhere;
        }

        .claim-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
        }

        .search-error {
            padding: 16px;
            margin: 16px 0;
            border-radius: 12px;
            background: #fff7ed;
            color: #9a3412;
        }

        .item-image img.image-failed {
            display: none;
        }

        @media (max-width: 600px) {
            .item-image {
                height: 200px;
            }
        }
    </style>
</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>

<main class="search-page">

    <!-- PAGE HEADER -->
    <section class="search-header <?php
        echo $type === "found"
            ? "found-search-header"
            : "lost-search-header";
    ?>">
        <div>
            <span class="report-label">ITEM SEARCH</span>

            <h1>
                <?php echo $type === "found"
                    ? "Find a Lost Item 🔎"
                    : "Search Lost Reports 📦"; ?>
            </h1>

            <p>
                <?php echo $type === "found"
                    ? "Browse found items reported by students."
                    : "Search items reported lost by students."; ?>
            </p>
        </div>

        <div class="search-header-icon">
            <?php echo $type === "found" ? "🎒" : "🔍"; ?>
        </div>
    </section>

    <!-- LOST / FOUND SWITCH -->
    <div class="search-tabs">
        <a
            href="search_items.php?type=found"
            class="<?php echo $type === "found" ? "active" : ""; ?>"
        >
            🎒 Found Items
        </a>

        <a
            href="search_items.php?type=lost"
            class="<?php echo $type === "lost" ? "active" : ""; ?>"
        >
            📦 Lost Items
        </a>
    </div>

    <!-- SEARCH FORM -->
    <section class="search-box">
        <form method="GET" action="search_items.php">
            <input
                type="hidden"
                name="type"
                value="<?php echo searchEscape($type); ?>"
            >

            <div class="big-search-input">
                <span>🔎</span>

                <input
                    type="search"
                    name="search"
                    placeholder="Search item name, description, or location..."
                    value="<?php echo searchEscape($search); ?>"
                    maxlength="150"
                >

                <?php if ($search !== ""): ?>
                    <a
                        href="search_items.php?type=<?php echo searchEscape($type); ?>"
                        aria-label="Clear search"
                    >✕</a>
                <?php endif; ?>
            </div>

            <button type="submit" class="search-button">
                Search
            </button>
        </form>
    </section>

    <!-- RESULTS HEADING -->
    <div class="results-heading">
        <div>
            <h2>
                <?php echo $type === "found" ? "Found Items" : "Lost Items"; ?>
            </h2>

            <p>
                <?php if ($search !== ""): ?>
                    Results for "<?php echo searchEscape($search); ?>"
                <?php else: ?>
                    Recently reported items
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- RESULTS GRID -->
    <section class="item-grid">

        <?php
        $hasItems = false;

        foreach ($items as $item):
            $hasItems = true;

            $itemType = strtolower((string)($item["type"] ?? $type));
            $status = strtolower((string)($item["status"] ?? "pending"));

            $itemName = $item["item_name"] ?? "Unknown Item";
            $description = $item["description"] ?? "No description provided.";
            $location = $item["location"] ?? "Not provided";

            // Support the current field and common legacy image field names.
            $imagePath = $item["image_path"]
                ?? $item["image_url"]
                ?? $item["photo"]
                ?? "";

            $imageUrl = getStudentImageUrl($imagePath);

            $itemDate = $itemType === "lost"
                ? ($item["date_lost"] ?? "Not provided")
                : ($item["date_found"] ?? "Not provided");

            $itemId = isset($item["_id"])
                ? (string)$item["_id"]
                : "";
        ?>

            <article class="item-card">

                <!-- ITEM PHOTO -->
                <div class="item-image">
                    <?php if ($imageUrl !== ""): ?>
                        <img
                            src="<?php echo searchEscape($imageUrl); ?>"
                            alt="<?php echo searchEscape($itemName); ?>"
                            loading="lazy"
                            onerror="this.classList.add('image-failed'); this.nextElementSibling.hidden=false;"
                        >

                        <div class="no-item-image" hidden>
                            📷
                            <span>Photo unavailable</span>
                        </div>
                    <?php else: ?>
                        <div class="no-item-image">
                            <?php echo $itemType === "found" ? "🎒" : "📦"; ?>
                            <span>No photo uploaded</span>
                        </div>
                    <?php endif; ?>

                    <span class="item-type-badge">
                        <?php echo $itemType === "found" ? "FOUND" : "LOST"; ?>
                    </span>
                </div>

                <!-- ITEM DETAILS -->
                <div class="item-card-content">

                    <div class="item-card-title-row">
                        <h3><?php echo searchEscape($itemName); ?></h3>

                        <span class="status <?php echo searchEscape($status); ?>">
                            <?php echo searchEscape(ucfirst($status)); ?>
                        </span>
                    </div>

                    <p class="item-description">
                        <?php echo searchEscape($description); ?>
                    </p>

                    <div class="item-details">
                        <div>
                            <span>📍</span>
                            <span><?php echo searchEscape($location); ?></span>
                        </div>

                        <div>
                            <span>📅</span>
                            <span><?php echo searchEscape($itemDate); ?></span>
                        </div>
                    </div>

                    <?php if ($itemType === "found" && $status !== "rejected" && $itemId !== ""): ?>
                        <a
                            class="claim-button"
                            href="claim_item.php?id=<?php echo urlencode($itemId); ?>"
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
                    <?php echo $type === "found" ? "🎒" : "📦"; ?>
                </div>

                <h2>No Items Found</h2>

                <p>
                    <?php if ($search !== ""): ?>
                        No matching items were found. Try another item name,
                        description, or location.
                    <?php else: ?>
                        No reports are currently available in this section.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

    </section>

</main>

</body>
</html>
