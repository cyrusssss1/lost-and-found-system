<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

$message = "";
$messageType = "";

$itemName = "";
$description = "";
$location = "";
$dateFound = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $itemName = trim($_POST["item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $dateFound = $_POST["date_found"] ?? "";

    if (
        $itemName === "" ||
        $description === "" ||
        $location === "" ||
        $dateFound === ""
    ) {

        $message = "Please fill in all fields.";
        $messageType = "error";

    } else {

        $imagePath = "";

        /*
         * ==========================================
         * CLOUDINARY IMAGE UPLOAD
         * ==========================================
         */

        if (
            isset($_FILES["item_image"]) &&
            $_FILES["item_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["item_image"]["error"] !== UPLOAD_ERR_OK) {

                $message = "Image upload failed. Please choose the image again.";
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
                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "image/webp"
                    ];

                    $mimeType = $imageInfo["mime"];

                    if (!in_array($mimeType, $allowedTypes, true)) {

                        $message =
                            "Only JPG, PNG, GIF, and WEBP images are allowed.";

                        $messageType = "error";

                    } else {

                        try {

                            /*
                             * ==========================================
                             * CLOUDINARY CREDENTIALS
                             * ==========================================
                             *
                             * First try environment variables.
                             * This allows Render to continue working.
                             */

                            $cloudName = getenv("CLOUDINARY_CLOUD_NAME");
                            $apiKey = getenv("CLOUDINARY_API_KEY");
                            $apiSecret = getenv("CLOUDINARY_API_SECRET");


                            /*
                             * ==========================================
                             * LOCALHOST SETTINGS
                             * ==========================================
                             *
                             * PUT YOUR ACTUAL CLOUDINARY VALUES HERE.
                             *
                             * DO NOT SEND YOUR API SECRET TO ME.
                             */

                            if (empty($cloudName)) {
                                $cloudName = "YOUR_CLOUD_NAME";
                            }

                            if (empty($apiKey)) {
                                $apiKey = "YOUR_API_KEY";
                            }

                            if (empty($apiSecret)) {
                                $apiSecret = "YOUR_API_SECRET";
                            }


                            /*
                             * Check credentials
                             */

                            if (
                                $cloudName === "YOUR_CLOUD_NAME" ||
                                $apiKey === "YOUR_API_KEY" ||
                                $apiSecret === "YOUR_API_SECRET" ||
                                empty($cloudName) ||
                                empty($apiKey) ||
                                empty($apiSecret)
                            ) {

                                throw new Exception(
                                    "Cloudinary is not configured for localhost."
                                );
                            }


                            /*
                             * Configure Cloudinary
                             */

                            Configuration::instance([
                                "cloud" => [
                                    "cloud_name" => $cloudName,
                                    "api_key" => $apiKey,
                                    "api_secret" => $apiSecret
                                ],
                                "url" => [
                                    "secure" => true
                                ]
                            ]);


                            /*
                             * Upload image
                             */

                            $uploadApi = new UploadApi();

                            $uploadResult = $uploadApi->upload(
                                $tmpFile,
                                [
                                    "folder" => "lost_and_found/items"
                                ]
                            );


                            /*
                             * Get Cloudinary URL
                             */

                            if (
                                !isset($uploadResult["secure_url"]) ||
                                empty($uploadResult["secure_url"])
                            ) {

                                throw new Exception(
                                    "Cloudinary did not return an image URL."
                                );
                            }

                            $imagePath =
                                $uploadResult["secure_url"];


                        } catch (Throwable $e) {

                            /*
                             * Show the real error while we are
                             * testing locally.
                             */

                            $message =
                                "Image upload failed: " .
                                $e->getMessage();

                            $messageType = "error";
                        }
                    }
                }
            }
        }


        /*
         * ==========================================
         * SAVE REPORT
         * ==========================================
         */

        if ($messageType !== "error") {

            try {

                $db = new Database();

                $reports = $db->getDatabase()->reports;

                $reportData = [
                    "user_id" => $_SESSION["user_id"],
                    "student_name" => $_SESSION["name"],
                    "type" => "found",
                    "item_name" => $itemName,
                    "description" => $description,
                    "location" => $location,
                    "date_found" => $dateFound,
                    "status" => "pending",
                    "created_at" => new MongoDB\BSON\UTCDateTime()
                ];


                /*
                 * Save Cloudinary URL
                 */

                if ($imagePath !== "") {
                    $reportData["image_path"] = $imagePath;
                }


                $reports->insertOne($reportData);


                if ($imagePath !== "") {

                    $message =
                        "Found item reported successfully! ✓ Photo uploaded successfully.";

                } else {

                    $message =
                        "Found item reported successfully!";
                }

                $messageType = "success";


                /*
                 * Clear form after success
                 */

                $itemName = "";
                $description = "";
                $location = "";
                $dateFound = "";


            } catch (Throwable $e) {

                $message =
                    "Unable to save the report. Please try again.";

                $messageType = "error";
            }
        }
    }
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
        Report Found Item
    </title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>


<body class="student-dashboard">


<?php include __DIR__ . '/../navbar.php'; ?>


<main class="report-page">


    <!-- HEADER -->

    <section class="report-header found-header">

        <div>

            <span class="report-label">
                REPORT AN ITEM
            </span>

            <h1>
                I Found Something
                🎒
            </h1>

            <p>
                Help return someone's belongings by reporting the item
                you found.
            </p>

        </div>

        <div class="report-header-icon">
            🤝
        </div>

    </section>


    <!-- FORM -->

    <section class="report-card">


        <?php if ($message !== ""): ?>

            <div class="form-message <?php echo $messageType; ?>">

                <span>
                    <?php
                    echo $messageType === "success"
                        ? "✓"
                        : "!";
                    ?>
                </span>

                <div>
                    <?php
                    echo htmlspecialchars($message);
                    ?>
                </div>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="reportForm"
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


            <!-- LOCATION + DATE -->

            <div class="form-row">

                <div class="form-field">

                    <label for="location">
                        Location Found
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


                <div class="form-field">

                    <label for="date_found">
                        Date Found
                    </label>

                    <div class="field-wrapper">

                        <span>📅</span>

                        <input
                            type="date"
                            id="date_found"
                            name="date_found"
                            value="<?php echo htmlspecialchars($dateFound); ?>"
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
                        id="item_image"
                        name="item_image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                    >


                    <span
                        class="upload-icon"
                        id="uploadIcon"
                    >
                        📷
                    </span>


                    <strong id="uploadText">
                        Upload a photo of the item
                    </strong>


                    <small id="uploadStatus">
                        JPG, PNG, GIF, or WEBP — maximum 5MB
                    </small>


                    <img
                        id="imagePreview"
                        src=""
                        alt="Selected image preview"
                        style="
                            display:none;
                            max-width:180px;
                            max-height:180px;
                            margin:15px auto 5px;
                            border-radius:12px;
                            object-fit:cover;
                        "
                    >

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
                    class="submit-button found-submit"
                    id="submitButton"
                >
                    🎒 Submit Found Item
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
                If possible, upload a clear photo so the owner can recognize
                their belongings.
            </p>

        </div>

    </div>


</main>


<script>

const imageInput =
    document.getElementById("item_image");

const uploadText =
    document.getElementById("uploadText");

const uploadStatus =
    document.getElementById("uploadStatus");

const uploadIcon =
    document.getElementById("uploadIcon");

const imagePreview =
    document.getElementById("imagePreview");

const reportForm =
    document.getElementById("reportForm");

const submitButton =
    document.getElementById("submitButton");


/*
 * ==========================================
 * IMAGE SELECTION
 * ==========================================
 */

imageInput.addEventListener("change", function () {

    const file = this.files[0];


    if (!file) {

        uploadIcon.textContent = "📷";

        uploadText.textContent =
            "Upload a photo of the item";

        uploadStatus.textContent =
            "JPG, PNG, GIF, or WEBP — maximum 5MB";

        imagePreview.style.display =
            "none";

        return;
    }


    /*
     * File size check
     */

    if (file.size > 5 * 1024 * 1024) {

        uploadIcon.textContent = "⚠️";

        uploadText.textContent =
            "Image is too large";

        uploadStatus.textContent =
            "Please choose an image smaller than 5MB";

        imagePreview.style.display =
            "none";

        this.value = "";

        return;
    }


    /*
     * SUCCESSFUL IMAGE SELECTION
     */

    uploadIcon.textContent = "✓";

    uploadText.textContent =
        "Image selected!";

    uploadStatus.textContent =
        file.name +
        " • " +
        (file.size / 1024 / 1024).toFixed(2) +
        " MB";


    /*
     * Preview
     */

    const reader =
        new FileReader();

    reader.onload = function (event) {

        imagePreview.src =
            event.target.result;

        imagePreview.style.display =
            "block";

    };

    reader.readAsDataURL(file);

});


/*
 * ==========================================
 * SUBMIT INDICATOR
 * ==========================================
 */

reportForm.addEventListener("submit", function () {

    submitButton.disabled = true;

    submitButton.textContent =
        "⏳ Uploading & Submitting...";

});

</script>


</body>

</html>