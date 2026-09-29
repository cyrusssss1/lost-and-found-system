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

$reports = $db->getDatabase()->reports;
$claims = $db->getDatabase()->claims;

$itemId = $_GET["id"] ?? "";

try {

    $item = $reports->findOne([
        "_id" => new ObjectId($itemId),
        "type" => "found",
        "status" => "pending"
    ]);

} catch (Exception $e) {

    $item = null;
}


if (!$item) {

    ?>

    <!DOCTYPE html>
    <html>

    <head>

        <title>Item Not Found</title>

        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="student.css">

    </head>

    <body class="student-dashboard">

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="claim-page">

        <div class="empty-results">

            <div class="empty-icon">
                🔎
            </div>

            <h2>
                Item Not Found
            </h2>

            <p>
                This item may have already been claimed or is no longer available.
            </p>

            <a
                href="search_items.php?type=found"
                class="secondary-button"
            >
                ← Back to Found Items
            </a>

        </div>

    </main>

    </body>

    </html>

    <?php

    exit;
}


$message = "";
$messageType = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $reason = trim($_POST["reason"] ?? "");

    if ($reason === "") {

        $message = "Please explain why this item belongs to you.";
        $messageType = "error";

    } else {

        $existingClaim = $claims->findOne([
            "item_id" => (string) $item["_id"],
            "student_id" => $_SESSION["user_id"]
        ]);

        if ($existingClaim) {

            $message = "You have already submitted a claim for this item.";
            $messageType = "error";

        } else {

            $claims->insertOne([
                "item_id" => (string) $item["_id"],
                "student_id" => $_SESSION["user_id"],
                "student_name" => $_SESSION["name"],
                "reason" => $reason,
                "status" => "pending",
                "created_at" => new MongoDB\BSON\UTCDateTime()
            ]);

            $message = "Claim submitted successfully!";
            $messageType = "success";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Claim Item
    </title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="claim-page">


    <section class="claim-card">


        <!-- ITEM IMAGE -->

        <div class="claim-item-image">

            <?php if (!empty($item["image_path"])): ?>

                <img
                    src="../<?php echo htmlspecialchars(
                        $item["image_path"]
                    ); ?>"
                    alt="<?php echo htmlspecialchars(
                        $item["item_name"]
                    ); ?>"
                >

            <?php else: ?>

                <div class="claim-no-image">
                    🎒
                </div>

            <?php endif; ?>

        </div>


        <!-- ITEM INFO -->

        <div class="claim-item-info">

            <span class="report-label">
                FOUND ITEM
            </span>

            <h1>
                <?php echo htmlspecialchars(
                    $item["item_name"]
                ); ?>
            </h1>

            <p class="claim-description">
                <?php echo htmlspecialchars(
                    $item["description"]
                ); ?>
            </p>


            <div class="claim-details">

                <div>
                    <span>📍</span>
                    <strong>Location Found</strong>
                    <p>
                        <?php echo htmlspecialchars(
                            $item["location"]
                        ); ?>
                    </p>
                </div>


                <div>
                    <span>📅</span>
                    <strong>Date Found</strong>
                    <p>
                        <?php echo htmlspecialchars(
                            $item["date_found"] ?? "Not provided"
                        ); ?>
                    </p>
                </div>

            </div>

        </div>


    </section>


    <!-- CLAIM FORM -->

    <section class="claim-form-card">

        <div class="claim-form-header">

            <div class="claim-form-icon">
                🙋
            </div>

            <div>

                <h2>
                    Claim This Item
                </h2>

                <p>
                    Tell us why you believe this item belongs to you.
                </p>

            </div>

        </div>


        <?php if ($message !== ""): ?>

            <div class="form-message <?php echo $messageType; ?>">

                <span>
                    <?php echo $messageType === "success"
                        ? "✓"
                        : "!"; ?>
                </span>

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-field">

                <label for="reason">
                    Why does this item belong to you?
                </label>

                <div class="textarea-wrapper">

                    <span>📝</span>

                    <textarea
                        id="reason"
                        name="reason"
                        rows="7"
                        placeholder="Describe identifying details, where you lost it, what was inside it, or anything else that can help verify your claim..."
                        required
                    ></textarea>

                </div>

            </div>


            <div class="claim-warning">

                <span>⚠️</span>

                <p>
                    Only submit a claim if you genuinely believe this item
                    belongs to you. Staff may review your explanation before
                    approving the claim.
                </p>

            </div>


            <div class="form-actions">

                <a
                    href="search_items.php?type=found"
                    class="secondary-button"
                >
                    ← Back
                </a>

                <button
                    type="submit"
                    class="submit-button claim-submit"
                >
                    🙋 Submit Claim
                </button>

            </div>

        </form>

    </section>


</main>

</body>

</html>