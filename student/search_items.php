
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "student"
) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

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
| Resolve item image URLs
|--------------------------------------------------------------------------
| Supports Cloudinary HTTPS URLs and older local image paths.
|--------------------------------------------------------------------------
*/
function getStudentImageUrl($imagePath): string
{
    if (!is_string($imagePath) || trim($imagePath) === "") {
        return "";
    }

    $imagePath = trim($imagePath);

    // Cloudinary or another external HTTP/HTTPS image URL.
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        $parts = parse_url($imagePath);

        if (
            !$parts ||
            empty($parts["host"]) ||
            !in_array(
                strtolower($parts["scheme"] ?? ""),
                ["http", "https"],
                true
            )
        ) {
            return "";
        }

        return $imagePath;
    }

    // Reject protocol-relative URLs.
    if (str_starts_with($imagePath, "//")) {
        return "";
    }

    // Normalize Windows path separators.
    $imagePath = str_replace("\\", "/", $imagePath);

    // Remove old relative prefixes.
    $imagePath = preg_replace('#^(?:\./)+#', "", $imagePath);

    // Root-relative path.
    if (str_starts_with($imagePath, "/")) {
        return $imagePath;
    }

    // Project-root-relative paths.
    if (
        str_starts_with($imagePath, "student/") ||
        str_starts_with($imagePath, "uploads/") ||
        str_starts_with($imagePath, "images/")
    ) {
        return "/" . $imagePath;
    }

    // Paths relative to the student directory.
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

// Prevent excessively long search input.
$search = mb_substr($search, 0, 150);

/*
|--------------------------------------------------------------------------
| Build the shared report search
|--------------------------------------------------------------------------
| Searches reports from all users, not only the logged-in student.
|--------------------------------------------------------------------------
*/
$filter = [
    "type" => $type
];

if ($search !== "") {
    $safeSearch = preg_quote($search, "/");

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
| Retrieve reports
|--------------------------------------------------------------------------
| Students can search reports created by other accounts.
| Rejected reports are excluded from public search results.
| Pending reports remain visible with their status.
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
        .search-page {
            width: min(1200px, 94%);
            margin: 28px auto 50px;
        }

        .item-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            margin-top: 22px;
        }

        .item-card {
            min-width: 0;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .1);
        }

        .item-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f1f5f9;
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

        .no-item-image[hidden] {
            display: none !important;
        }

        .item-type-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 11px;
            border-radius: 999px;
            background: rgba(15, 23, 42, .86);
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
            color: #0f172a;
            overflow-wrap: anywhere;
        }

        .item-description {
            margin: 12px 0;
            color: #475569;
            overflow-wrap: anywhere;
            white-space: pre-line;
            line-height: 1.6;
        }

        .item-details {
            display: grid;
            gap: 10px;
            margin-top: 14px;
            color: #475569;
            font-size: 14px;
        }

        .item-details > div {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: capitalize;
        }

        .status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .claim-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            padding: 12px 15px;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            transition: background .2s ease;
        }

        .claim-button:hover {
            background: #1d4ed8;
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

        .empty-results {
            grid-column: 1 / -1;
            padding: 45px 20px;
            text-align: center;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
        }

        .empty-icon {
            margin-bottom: 12px;
            font-size: 48px;
        }

        .empty-results h2 {
            margin: 0 0 8px;
            color: #0f172a;
        }

        .empty-results p {
            margin: 0;
            color: #64748b;
        }

        @media (max-width: 900px) {
            .item-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .item-grid {
                grid-template-columns: 1fr;
            }

            .item-image {
                height: 220px;
            }

            .search-page {
                width: 92%;
                margin-top: 18px;
            }
        }
    </style>
</head>

<body class="student-dashboard">

<?php include __DIR__ . "/../navbar.php"; ?>

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

            $status = strtolower((string)($item["status"] ?? "pending"));

            // Do not display rejected reports in public search.
            if ($status === "rejected") {
                continue;
            }

            $hasItems = true;

            $itemType = strtolower((string)($item["type"] ?? $type));

            $itemName = $item["item_name"] ?? "Unknown Item";
            $description = $item["description"] ?? "No description provided.";
            $location = $item["location"] ?? "Not provided";

            // Support current and legacy image fields.
            $imagePath = $item["image_path"]
                ?? $item["image_url"]
                ?? $item["photo"]
                ?? "";

            $imageUrl = getStudentImageUrl($imagePath);

            $itemDate = $itemType === "lost"
                ? ($item["date_lost"] ?? "Not provided")
                : ($item["date_found"] ?? "Not provided");

            // Convert MongoDB BSON date to a readable date.
            if ($itemDate instanceof MongoDB\BSON\UTCDateTime) {
                $itemDate = $itemDate
                    ->toDateTime()
                    ->format("M j, Y");
            }

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
                        <h3>
                            <?php echo searchEscape($itemName); ?>
                        </h3>

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

                    <?php if (
                        $itemType === "found" &&
                        $status === "approved" &&
                        $itemId !== ""
                    ): ?>
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
                        No approved or pending reports are available in this
                        section yet.
                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
```
