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

$studentId = $_SESSION["user_id"];

$myReports = $reports->find(
    [
        "user_id" => $studentId
    ],
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


/* =========================================================
   IMAGE URL
   Supports Cloudinary URLs and local uploads
   ========================================================= */

function getReportImageUrl($imagePath)
{
    if (empty($imagePath)) {
        return "";
    }

    $imagePath = trim((string)$imagePath);

    /*
     * Cloudinary / external image URL
     */
    if (
        str_starts_with($imagePath, "http://") ||
        str_starts_with($imagePath, "https://")
    ) {
        return $imagePath;
    }

    /*
     * Local image
     */
    return "../" . ltrim($imagePath, "/\\");
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    My Reports
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
       REPORT IMAGE
       ===================================================== */

    .my-report-image {
        position: relative;
        width: 100%;
        height: 230px;
        overflow: hidden;
        border-radius: 18px 18px 0 0;
        background: #f1f5f3;
    }

    .my-report-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .my-report-image .item-type-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 2;
    }


    /* =====================================================
       NO PHOTO
       ===================================================== */

    .no-item-image {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #f1f5f3;
        color: #7b8a82;
        font-size: 38px;
    }

    .no-item-image span {
        font-size: 12px;
        font-weight: 700;
    }


    /* =====================================================
       BROKEN PHOTO
       ===================================================== */

    .image-error-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #f1f5f3;
        color: #7b8a82;
        font-size: 38px;
    }

    .image-error-placeholder span {
        font-size: 12px;
        font-weight: 700;
    }

</style>
```

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>

<main class="reports-page">

```
<!-- =====================================================
     HEADER
     ===================================================== -->

<section class="page-title-card">

    <div>

        <span class="report-label">
            MY ACTIVITY
        </span>

        <h1>
            My Reports 📋
        </h1>

        <p>
            View and track the lost and found items you have reported.
        </p>

    </div>

    <div class="page-title-icon">
        📋
    </div>

</section>


<!-- =====================================================
     REPORTS
     ===================================================== -->

<section class="reports-grid">

    <?php

    $hasReports = false;

    foreach ($myReports as $report):

        $hasReports = true;


        /* STATUS */

        $status = strtolower(
            $report["status"] ?? "pending"
        );


        /* TYPE */

        $type = strtolower(
            $report["type"] ?? "lost"
        );


        /* =================================================
           IMAGE
           ================================================= */

        $imagePath =
            $report["image_path"]
            ?? $report["image_url"]
            ?? $report["photo"]
            ?? "";


        $imageUrl =
            getReportImageUrl($imagePath);


        /* ITEM INFORMATION */

        $itemName =
            $report["item_name"]
            ?? "Unnamed Item";


        $description =
            $report["description"]
            ?? "No description provided.";


        $location =
            $report["location"]
            ?? "Location not provided.";

    ?>


        <article class="my-report-card">


            <!-- =================================================
                 IMAGE
                 ================================================= -->

            <div class="my-report-image">


                <?php if ($imageUrl !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES); ?>"
                        alt="<?php echo htmlspecialchars($itemName, ENT_QUOTES); ?>"
                        loading="lazy"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <div
                        class="image-error-placeholder"
                        style="display:none;"
                    >

                        📦

                        <span>
                            Photo unavailable
                        </span>

                    </div>


                <?php else: ?>


                    <div class="no-item-image">

                        <?php

                        echo $type === "lost"
                            ? "📦"
                            : "🎒";

                        ?>

                        <span>
                            No photo
                        </span>

                    </div>


                <?php endif; ?>


                <span class="item-type-badge">

                    <?php

                    echo $type === "lost"
                        ? "LOST"
                        : "FOUND";

                    ?>

                </span>


            </div>


            <!-- =================================================
                 CONTENT
                 ================================================= -->

            <div class="my-report-content">


                <div class="my-report-title-row">

                    <h2>

                        <?php

                        echo htmlspecialchars(
                            $itemName
                        );

                        ?>

                    </h2>


                    <span
                        class="status <?php echo htmlspecialchars($status); ?>"
                    >

                        <?php

                        echo htmlspecialchars(
                            ucfirst($status)
                        );

                        ?>

                    </span>

                </div>


                <p class="my-report-description">

                    <?php

                    echo htmlspecialchars(
                        $description
                    );

                    ?>

                </p>


                <!-- =================================================
                     ITEM DETAILS
                     ================================================= -->

                <div class="report-info-list">


                    <div>

                        📍

                        <span>

                            <?php

                            echo htmlspecialchars(
                                $location
                            );

                            ?>

                        </span>

                    </div>


                    <div>

                        📅

                        <span>

                            <?php

                            if ($type === "lost") {

                                echo htmlspecialchars(
                                    $report["date_lost"]
                                    ?? "Not provided"
                                );

                            } else {

                                echo htmlspecialchars(
                                    $report["date_found"]
                                    ?? "Not provided"
                                );

                            }

                            ?>

                        </span>

                    </div>


                </div>


                <!-- =================================================
                     STATUS
                     ================================================= -->

                <?php if ($status === "approved"): ?>


                    <div class="activity-notice approved">

                        ✓ Your report has been approved.

                    </div>


                <?php elseif ($status === "rejected"): ?>


                    <div class="activity-notice rejected">

                        ! Your report has been rejected.

                    </div>


                <?php else: ?>


                    <div class="activity-notice pending">

                        ⏳ Your report is waiting for review.

                    </div>


                <?php endif; ?>


            </div>


        </article>


    <?php endforeach; ?>


    <!-- =====================================================
         NO REPORTS
         ===================================================== -->

    <?php if (!$hasReports): ?>


        <div class="empty-results">


            <div class="empty-icon">
                📋
            </div>


            <h2>
                No Reports Yet
            </h2>


            <p>
                You haven't reported any lost or found items yet.
            </p>


            <div class="empty-actions">


                <a
                    href="report_lost.php"
                    class="student-action-button blue"
                >

                    📦 Report Lost Item

                </a>


                <a
                    href="report_found.php"
                    class="student-action-button green"
                >

                    🎒 Report Found Item

                </a>


            </div>


        </div>


    <?php endif; ?>


</section>
```

</main>

</body>

</html>
