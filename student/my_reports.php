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

/*
|--------------------------------------------------------------------------
| Image URL Helper
|--------------------------------------------------------------------------
| Cloudinary URLs are already complete URLs.
| Old local images still need ../
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Reports</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="student.css">

</head>

<body class="student-dashboard">

<?php include __DIR__ . '/../navbar.php'; ?>


<main class="reports-page">


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


    <section class="reports-grid">

        <?php

        $hasReports = false;

        foreach ($myReports as $report):

            $hasReports = true;

            $status = strtolower($report["status"] ?? "pending");

            $type = strtolower($report["type"] ?? "lost");

            $imagePath = $report["image_path"] ?? "";

            $imageUrl = getStudentImageUrl($imagePath);

            $itemName = $report["item_name"] ?? "Unknown Item";

            $description = $report["description"] ?? "No description provided.";

            $location = $report["location"] ?? "Not provided";

        ?>

            <article class="my-report-card">


                <!-- IMAGE -->

                <div class="my-report-image">

                    <?php if ($imageUrl !== ""): ?>

                        <img
                            src="<?php echo htmlspecialchars($imageUrl); ?>"
                            alt="<?php echo htmlspecialchars($itemName); ?>"
                        >

                    <?php else: ?>

                        <div class="no-item-image">

                            <?php echo $type === "lost"
                                ? "📦"
                                : "🎒"; ?>

                            <span>
                                No photo
                            </span>

                        </div>

                    <?php endif; ?>


                    <span class="item-type-badge">

                        <?php echo $type === "lost"
                            ? "LOST"
                            : "FOUND"; ?>

                    </span>

                </div>


                <!-- CONTENT -->

                <div class="my-report-content">

                    <div class="my-report-title-row">

                        <h2>
                            <?php echo htmlspecialchars($itemName); ?>
                        </h2>

                        <span class="status <?php echo htmlspecialchars($status); ?>">
                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                        </span>

                    </div>


                    <p class="my-report-description">

                        <?php echo htmlspecialchars($description); ?>

                    </p>


                    <div class="report-info-list">

                        <div>
                            📍
                            <span>
                                <?php echo htmlspecialchars($location); ?>
                            </span>
                        </div>


                        <div>

                            📅

                            <span>

                                <?php

                                if ($type === "lost") {

                                    echo htmlspecialchars(
                                        $report["date_lost"] ?? "Not provided"
                                    );

                                } else {

                                    echo htmlspecialchars(
                                        $report["date_found"] ?? "Not provided"
                                    );

                                }

                                ?>

                            </span>

                        </div>

                    </div>


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


</main>

</body>

</html>