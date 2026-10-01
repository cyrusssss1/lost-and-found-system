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


/*
|--------------------------------------------------------------------------
| Image URL Helper
|--------------------------------------------------------------------------
*/
function getStudentImageUrl($imagePath): string
{
    if (empty($imagePath)) {
        return "";
    }

    $imagePath = trim((string)$imagePath);

    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    return "../" . ltrim($imagePath, "/\\");
}


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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Claims</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="claims-page">


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


    <?php

    $hasClaims = false;

    foreach ($myClaims as $claim):

        $hasClaims = true;

        $status = strtolower($claim["status"] ?? "pending");

        $item = null;

        /*
        |--------------------------------------------------------------------------
        | Get Original Report
        |--------------------------------------------------------------------------
        */
        try {

            $itemId = (string)($claim["item_id"] ?? "");

            if ($itemId !== "" && preg_match('/^[a-f0-9]{24}$/i', $itemId)) {

                $item = $reports->findOne([
                    "_id" => new ObjectId($itemId)
                ]);

            }

        } catch (Exception $e) {

            $item = null;

        }

        $imageUrl = "";

        if ($item && !empty($item["image_path"])) {
            $imageUrl = getStudentImageUrl($item["image_path"]);
        }

    ?>


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


        <article class="my-claim-card">


            <!-- IMAGE -->

            <div class="my-claim-image">

                <?php if ($imageUrl !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($imageUrl); ?>"
                        alt="<?php echo htmlspecialchars(
                            $item["item_name"] ?? "Claimed Item"
                        ); ?>"
                    >

                <?php else: ?>

                    <div class="no-item-image">

                        🎒

                        <span>
                            No photo
                        </span>

                    </div>

                <?php endif; ?>

            </div>


            <!-- CONTENT -->

            <div class="my-claim-content">

                <?php if ($item): ?>

                    <div class="my-claim-title-row">

                        <div>

                            <span class="claim-type-label">
                                FOUND ITEM
                            </span>

                            <h2>
                                <?php echo htmlspecialchars(
                                    $item["item_name"] ?? "Unknown Item"
                                ); ?>
                            </h2>

                        </div>

                        <span class="status <?php echo htmlspecialchars($status); ?>">
                            <?php echo htmlspecialchars(
                                ucfirst($status)
                            ); ?>
                        </span>

                    </div>


                    <p class="my-claim-description">

                        <?php echo htmlspecialchars(
                            $item["description"] ?? "No description provided."
                        ); ?>

                    </p>


                    <div class="report-info-list">

                        <div>

                            📍

                            <span>

                                <?php echo htmlspecialchars(
                                    $item["location"] ?? "Not provided"
                                ); ?>

                            </span>

                        </div>


                        <div>

                            📅

                            <span>

                                <?php echo htmlspecialchars(
                                    $item["date_found"] ?? "Not provided"
                                ); ?>

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


                <div class="claim-reason">

                    <strong>
                        Your Claim Reason
                    </strong>

                    <p>

                        <?php echo htmlspecialchars(
                            $claim["reason"] ?? "No reason provided."
                        ); ?>

                    </p>

                </div>


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


</main>

</body>

</html>