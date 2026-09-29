<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$message = "";
$messageType = "";

$itemName = "";
$description = "";
$location = "";
$dateLost = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $itemName = trim($_POST["item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $dateLost = $_POST["date_lost"] ?? "";

    if (
        $itemName === "" ||
        $description === "" ||
        $location === "" ||
        $dateLost === ""
    ) {

        $message = "Please fill in all fields.";
        $messageType = "error";

    } else {

        $imagePath = "";

        /*
         * ==============================
         * IMAGE UPLOAD
         * ==============================
         */

        if (
            isset($_FILES["item_image"]) &&
            $_FILES["item_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["item_image"]["error"] !== UPLOAD_ERR_OK) {

                $message = "There was a problem uploading the image.";
                $messageType = "error";

            } elseif ($_FILES["item_image"]["size"] > 5 * 1024 * 1024) {

                $message = "Image must be 5MB or smaller.";
                $messageType = "error";

            } else {

                $tmpFile = $_FILES["item_image"]["tmp_name"];

                $imageInfo = @getimagesize($tmpFile);

                if ($imageInfo === false) {

                    $message = "Please upload a valid image.";
                    $messageType = "error";

                } else {

                    $allowedTypes = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/gif" => "gif",
                        "image/webp" => "webp"
                    ];

                    $mimeType = $imageInfo["mime"];

                    if (!isset($allowedTypes[$mimeType])) {

                        $message = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                        $messageType = "error";

                    } else {

                        $uploadDirectory = __DIR__ . '/../uploads/items';

                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0777, true);
                        }

                        $fileName =
                            uniqid("item_", true) .
                            "." .
                            $allowedTypes[$mimeType];

                        $destination =
                            $uploadDirectory .
                            DIRECTORY_SEPARATOR .
                            $fileName;

                        if (move_uploaded_file($tmpFile, $destination)) {

                            $imagePath = "uploads/items/" . $fileName;

                        } else {

                            $message = "Unable to save the uploaded image.";
                            $messageType = "error";
                        }
                    }
                }
            }
        }


        /*
         * ==============================
         * SAVE REPORT
         * ==============================
         */

        if ($messageType !== "error") {

            $db = new Database();

            $reports = $db->getDatabase()->reports;

            $reportData = [
                "user_id" => $_SESSION["user_id"],
                "student_name" => $_SESSION["name"],
                "type" => "lost",
                "item_name" => $itemName,
                "description" => $description,
                "location" => $location,
                "date_lost" => $dateLost,
                "status" => "pending",
                "created_at" => new MongoDB\BSON\UTCDateTime()
            ];

            if ($imagePath !== "") {
                $reportData["image_path"] = $imagePath;
            }

            $reports->insertOne($reportData);

            $message = "Lost item reported successfully!";
            $messageType = "success";

            $itemName = "";
            $description = "";
            $location = "";
            $dateLost = "";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Lost Item</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="report-page">


    <!-- HEADER -->

    <section class="report-header lost-header">

        <div>

            <span class="report-label">
                REPORT AN ITEM
            </span>

            <h1>
                I Lost Something
                📦
            </h1>

            <p>
                Tell us about your lost item so other students and staff
                can help you find it.
            </p>

        </div>

        <div class="report-header-icon">
            🔎
        </div>

    </section>


    <!-- FORM -->

    <section class="report-card">


        <?php if ($message !== ""): ?>

            <div class="form-message <?php echo $messageType; ?>">

                <span>
                    <?php echo $messageType === "success" ? "✓" : "!"; ?>
                </span>

                <div>
                    <?php echo htmlspecialchars($message); ?>
                </div>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- ITEM NAME -->

            <div class="form-field">

                <label for="item_name">
                    Item Name
                </label>

                <div class="field-wrapper">

                    <span>📦</span>

                    <input
                        type="text"
                        id="item_name"
                        name="item_name"
                        placeholder="Example: Black Wallet"
                        value="<?php echo htmlspecialchars($itemName); ?>"
                        required
                    >

                </div>

            </div>


            <!-- DESCRIPTION -->

            <div class="form-field">

                <label for="description">
                    Description
                </label>

                <div class="textarea-wrapper">

                    <span>📝</span>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the item, including color, brand, marks, or other identifying details..."
                        required
                    ><?php echo htmlspecialchars($description); ?></textarea>

                </div>

            </div>


            <!-- LOCATION -->

            <div class="form-row">

                <div class="form-field">

                    <label for="location">
                        Location Lost
                    </label>

                    <div class="field-wrapper">

                        <span>📍</span>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            placeholder="Example: School Cafeteria"
                            value="<?php echo htmlspecialchars($location); ?>"
                            required
                        >

                    </div>

                </div>


                <!-- DATE -->

                <div class="form-field">

                    <label for="date_lost">
                        Date Lost
                    </label>

                    <div class="field-wrapper">

                        <span>📅</span>

                        <input
                            type="date"
                            id="date_lost"
                            name="date_lost"
                            value="<?php echo htmlspecialchars($dateLost); ?>"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- IMAGE -->

            <div class="form-field">

                <label>
                    Item Photo
                    <span class="optional">
                        Optional
                    </span>
                </label>

                <label class="image-upload">

                    <input
                        type="file"
                        name="item_image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                    >

                    <span class="upload-icon">
                        📷
                    </span>

                    <strong>
                        Upload a photo of the item
                    </strong>

                    <small>
                        JPG, PNG, GIF, or WEBP — maximum 5MB
                    </small>

                </label>

            </div>


            <!-- BUTTONS -->

            <div class="form-actions">

                <a
                    href="dashboard.php"
                    class="secondary-button"
                >
                    ← Back
                </a>

                <button
                    type="submit"
                    class="submit-button lost-submit"
                >
                    📦 Submit Lost Item
                </button>

            </div>


        </form>

    </section>


    <!-- TIP -->

    <div class="form-tip">

        <span>💡</span>

        <div>

            <strong>
                Helpful Tip
            </strong>

            <p>
                A clear photo and detailed description can make your item
                much easier to identify.
            </p>

        </div>

    </div>


</main>

</body>

</html>