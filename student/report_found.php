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


    /*
     * ==============================
     * BASIC VALIDATION
     * ==============================
     */

    if (
        $itemName === "" ||
        $description === "" ||
        $location === "" ||
        $dateFound === ""
    ) {

        $message = "Please fill in all fields.";
        $messageType = "error";

    } else {

        $imageUrl = "";


        /*
         * ==============================
         * CLOUDINARY CONFIGURATION
         * ==============================
         */

        $cloudName = getenv("CLOUDINARY_CLOUD_NAME");
        $apiKey = getenv("CLOUDINARY_API_KEY");
        $apiSecret = getenv("CLOUDINARY_API_SECRET");


        /*
         * ==============================
         * IMAGE UPLOAD
         * ==============================
         */

        if (
            isset($_FILES["item_image"]) &&
            $_FILES["item_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["item_image"]["error"] !== UPLOAD_ERR_OK
            ) {

                $message =
                    "There was a problem uploading the image. Please try again.";

                $messageType = "error";

            } elseif (
                $_FILES["item_image"]["size"] > 5 * 1024 * 1024
            ) {

                $message =
                    "Image must be 5MB or smaller.";

                $messageType = "error";

            } else {

                $tmpFile =
                    $_FILES["item_image"]["tmp_name"];


                /*
                 * Verify that the file
                 * is actually an image.
                 */

                $imageInfo =
                    @getimagesize($tmpFile);


                if ($imageInfo === false) {

                    $message =
                        "Please upload a valid image file.";

                    $messageType = "error";

                } else {

                    $allowedTypes = [

                        "image/jpeg",
                        "image/png",
                        "image/gif",
                        "image/webp"

                    ];


                    $mimeType =
                        $imageInfo["mime"];


                    if (
                        !in_array(
                            $mimeType,
                            $allowedTypes,
                            true
                        )
                    ) {

                        $message =
                            "Only JPG, PNG, GIF, and WEBP images are allowed.";

                        $messageType = "error";

                    } elseif (
                        empty($cloudName) ||
                        empty($apiKey) ||
                        empty($apiSecret)
                    ) {

                        $message =
                            "Cloudinary is not configured correctly.";

                        $messageType = "error";

                    } else {

                        try {

                            /*
                             * Configure Cloudinary
                             */

                            Configuration::instance([

                                "cloud" => [

                                    "cloud_name" =>
                                        $cloudName,

                                    "api_key" =>
                                        $apiKey,

                                    "api_secret" =>
                                        $apiSecret

                                ],

                                "url" => [

                                    "secure" =>
                                        true

                                ]

                            ]);


                            /*
                             * Upload image to Cloudinary
                             */

                            $upload =
                                new UploadApi();


                            $uploadResult =
                                $upload->upload(

                                    $tmpFile,

                                    [

                                        "folder" =>
                                            "lost_and_found/items"

                                    ]

                                );


                            /*
                             * Get permanent HTTPS URL
                             */

                            if (
                                isset(
                                    $uploadResult["secure_url"]
                                ) &&
                                $uploadResult["secure_url"] !== ""
                            ) {

                                $imageUrl =
                                    $uploadResult["secure_url"];

                            } else {

                                $message =
                                    "Image uploaded but no image URL was returned.";

                                $messageType =
                                    "error";
                            }


                        } catch (Exception $e) {

                            $message =
                                "Image upload failed. Please try again.";

                            $messageType =
                                "error";


                            error_log(
                                "Cloudinary upload error: " .
                                $e->getMessage()
                            );

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

            try {

                $db =
                    new Database();


                $reports =
                    $db->getDatabase()->reports;


                $reportData = [

                    "user_id" =>
                        $_SESSION["user_id"],

                    "student_name" =>
                        $_SESSION["name"],

                    "type" =>
                        "found",

                    "item_name" =>
                        $itemName,

                    "description" =>
                        $description,

                    "location" =>
                        $location,

                    "date_found" =>
                        $dateFound,

                    "status" =>
                        "pending",

                    "created_at" =>
                        new MongoDB\BSON\UTCDateTime()

                ];


                /*
                 * Save Cloudinary URL
                 * into MongoDB.
                 */

                if ($imageUrl !== "") {

                    $reportData["image_path"] =
                        $imageUrl;

                }


                $reports->insertOne(
                    $reportData
                );


                $message =
                    "Found item reported successfully!";

                $messageType =
                    "success";


                /*
                 * Clear form
                 */

                $itemName = "";
                $description = "";
                $location = "";
                $dateFound = "";


            } catch (Exception $e) {

                $message =
                    "The report could not be saved. Please try again.";

                $messageType =
                    "error";


                error_log(
                    "Report insert error: " .
                    $e->getMessage()
                );

            }

        }

    }

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
    Report Found Item
</title>


<link
    rel="stylesheet"
    href="../style.css"
>

<link
    rel="stylesheet"
    href="student.css"
>
```

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>

<main class="report-page">

```
<!-- HEADER -->

<section class="report-header found-header">

    <div>

        <span class="report-label">
            REPORT AN ITEM
        </span>

        <h1>
            I Found Something 🎒
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

        <div
            class="form-message
            <?php echo htmlspecialchars($messageType); ?>"
        >

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
    >


        <!-- ITEM NAME -->

        <div class="form-field">

            <label for="item_name">
                Item Name
            </label>

            <div class="field-wrapper">

                <span>
                    📦
                </span>

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

                <span>
                    📝
                </span>

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

                    <span>
                        📍
                    </span>

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

                    <span>
                        📅
                    </span>

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


            <label
                class="image-upload"
                id="imageUploadBox"
            >

                <input
                    type="file"
                    name="item_image"
                    id="item_image"
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


                <small id="uploadSubtext">
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
                class="submit-button found-submit"
            >
                🎒 Submit Found Item
            </button>

        </div>


    </form>

</section>


<!-- TIP -->

<div class="form-tip">

    <span>
        💡
    </span>


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
```

</main>

<script>

const imageInput =
    document.getElementById("item_image");

const uploadText =
    document.getElementById("uploadText");

const uploadSubtext =
    document.getElementById("uploadSubtext");

const uploadIcon =
    document.getElementById("uploadIcon");


imageInput.addEventListener(
    "change",
    function () {

        const file =
            this.files[0];


        if (!file) {

            uploadIcon.textContent =
                "📷";

            uploadText.textContent =
                "Upload a photo of the item";

            uploadSubtext.textContent =
                "JPG, PNG, GIF, or WEBP — maximum 5MB";

            return;

        }


        /*
         * Check file size
         */

        if (
            file.size >
            5 * 1024 * 1024
        ) {

            alert(
                "The selected image is larger than 5MB."
            );

            this.value = "";


            uploadIcon.textContent =
                "📷";

            uploadText.textContent =
                "Upload a photo of the item";

            uploadSubtext.textContent =
                "JPG, PNG, GIF, or WEBP — maximum 5MB";

            return;

        }


        /*
         * Show selected image filename
         */

        uploadIcon.textContent =
            "✅";


        uploadText.textContent =
            "Image selected!";


        uploadSubtext.textContent =
            file.name +
            " • " +
            (
                file.size /
                1024 /
                1024
            ).toFixed(2) +
            " MB";

    }
);

</script>

</body>

</html>
