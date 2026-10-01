<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;
use MongoDB\BSON\ObjectId;

$db = new Database();

$claims = $db->getDatabase()->claims;
$reports = $db->getDatabase()->reports;

$studentId = $_SESSION["user_id"];


/* =========================================================
   GET MY CLAIMS
   ========================================================= */

$myClaims = $claims->find(
    [
        "student_id" => $studentId
    ],
    [
        "sort" => [
            "created_at" => -1
        ]
    ]
);


/* =========================================================
   IMAGE URL HELPER
   Supports:
   - Cloudinary URLs
   - Other HTTPS URLs
   - Local uploads
   ========================================================= */

function getClaimImageUrl($imagePath)
{
    if (empty($imagePath)) {
        return "";
    }

    $imagePath = trim((string)$imagePath);

    /*
     * Cloudinary / external image
     */
    if (
        str_starts_with($imagePath, "http://") ||
        str_starts_with($imagePath, "https://")
    ) {
        return $imagePath;
    }

    /*
     * Old/local uploaded image
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
    My Claims
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
       CLAIM IMAGE
       ===================================================== */

    .my-claim-image {
        position: relative;
        width: 100%;
        height: 230px;
        overflow: hidden;
        border-radius: 18px 18px 0 0;
        background: #f1f5f3;
    }

    .my-claim-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
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

<main class="claims-page">

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
            My Claims 🙋
        </h1>

        <p>
            Track the claims you have submitted and their current status.
        </p>

    </div>

    <div class="page-title-icon">
        🔔
    </div>

</section>


<!-- =====================================================
     CLAIMS
     ===================================================== -->

<?php

$hasClaims = false;

foreach ($myClaims as $claim):

    $hasClaims = true;


    /* =================================================
       STATUS
       ================================================= */

    $status = strtolower(
        $claim["status"] ?? "pending"
    );


    /* =================================================
       FIND ITEM
       ================================================= */

    $item = null;

    try {

        if (!empty($claim["item_id"])) {

            $item = $reports->findOne([
                "_id" => new ObjectId(
                    (string)$claim["item_id"]
                )
            ]);

        }

    } catch (Exception $e) {

        $item = null;

    }


    /* =================================================
       IMAGE
       ================================================= */

    $imagePath = "";

    if ($item) {

        $imagePath =
            $item["image_path"]
            ?? $item["image_url"]
            ?? $item["photo"]
            ?? "";

    }

    $imageUrl =
        getClaimImageUrl($imagePath);


    /* =================================================
       ITEM INFORMATION
       ================================================= */

    $itemName =
        $item["item_name"]
        ?? "Item No Longer Available";

    $description =
        $item["description"]
        ?? "No description provided.";

    $location =
        $item["location"]
        ?? "Location not provided.";

    $dateFound =
        $item["date_found"]
        ?? "Not provided";

?>


    <!-- =================================================
         APPROVED NOTIFICATION
         ================================================= -->

    <?php if ($status === "approved"): ?>

        <div class="claim-notification approved">

            <div class="notification-icon">
                ✓
            </div>

            <div>

                <strong>
                    Claim Approved! 🎉
                </strong>

                <p>
                    Your claim has been approved. Please follow any
                    instructions from staff regarding the item.
                </p>

            </div>

        </div>

    <?php elseif ($status === "rejected"): ?>


        <!-- =================================================
             REJECTED NOTIFICATION
             ================================================= -->

        <div class="claim-notification rejected">

            <div class="notification-icon">
                !
            </div>

            <div>

                <strong>
                    Claim Update
                </strong>

                <p>
                    Your claim for this item was rejected.
                    You may review the item details and contact staff
                    if you believe this was a mistake.
                </p>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         CLAIM CARD
         ================================================= -->

    <article class="my-claim-card">


        <!-- =================================================
             IMAGE
             ================================================= -->

        <div class="my-claim-image">


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

                    🎒

                    <span>
                        Photo unavailable
                    </span>

                </div>


            <?php else: ?>


                <div class="no-item-image">

                    🎒

                    <span>
                        No photo
                    </span>

                </div>


            <?php endif; ?>


        </div>


        <!-- =================================================
             CONTENT
             ================================================= -->

        <div class="my-claim-content">


            <?php if ($item): ?>


                <div class="my-claim-title-row">


                    <div>

                        <span class="claim-type-label">
                            FOUND ITEM
                        </span>

                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $itemName
                            );

                            ?>

                        </h2>

                    </div>


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


                <!-- DESCRIPTION -->

                <p class="my-claim-description">

                    <?php

                    echo htmlspecialchars(
                        $description
                    );

                    ?>

                </p>


                <!-- ITEM DETAILS -->

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

                            echo htmlspecialchars(
                                $dateFound
                            );

                            ?>

                        </span>

                    </div>


                </div>


            <?php else: ?>


                <h2>
                    Item No Longer Available
                </h2>


                <p>
                    The original item report could not be found.
                </p>


            <?php endif; ?>


            <!-- =================================================
                 CLAIM REASON
                 ================================================= -->

            <div class="claim-reason">

                <strong>
                    Your Claim Reason
                </strong>

                <p>

                    <?php

                    echo htmlspecialchars(
                        $claim["reason"]
                        ?? "No reason provided."
                    );

                    ?>

                </p>

            </div>


            <!-- =================================================
                 STATUS MESSAGE
                 ================================================= -->

            <?php if ($status === "pending"): ?>


                <div class="activity-notice pending">

                    ⏳ Your claim is currently being reviewed.

                </div>


            <?php elseif ($status === "approved"): ?>


                <div class="activity-notice approved">

                    ✓ Your claim has been approved.

                </div>


            <?php elseif ($status === "rejected"): ?>


                <div class="activity-notice rejected">

                    ! Your claim was rejected.

                </div>


            <?php endif; ?>


        </div>


    </article>


<?php endforeach; ?>


<!-- =====================================================
     NO CLAIMS
     ===================================================== -->

<?php if (!$hasClaims): ?>


    <div class="empty-results">


        <div class="empty-icon">
            🙋
        </div>


        <h2>
            No Claims Yet
        </h2>


        <p>
            You haven't submitted any claims yet.
            Search found items to see if something belongs to you.
        </p>


        <a
            href="search_items.php?type=found"
            class="student-action-button blue"
        >

            🔎 Search Found Items

        </a>


    </div>


<?php endif; ?>
```

</main>

</body>

</html>
