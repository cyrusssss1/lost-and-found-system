<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();
$users = $db->getDatabase()->users;

$message = "";
$messageType = "";


/* =========================================================
   ADD STAFF
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /* =====================================================
       CREATE STAFF
       ===================================================== */

    if ($action === "add_staff") {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($name === "" || $email === "" || $password === "") {

            $message = "Please fill in all fields.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($password) < 6) {

            $message = "Password must be at least 6 characters.";
            $messageType = "error";

        } else {

            $existingUser = $users->findOne([
                "email" => $email
            ]);

            if ($existingUser) {

                $message = "An account with this email already exists.";
                $messageType = "error";

            } else {

                $users->insertOne([
                    "name" => $name,
                    "email" => $email,
                    "password" => password_hash($password, PASSWORD_DEFAULT),
                    "role" => "staff",
                    "created_at" => new MongoDB\BSON\UTCDateTime()
                ]);

                $message = "Staff account created successfully.";
                $messageType = "success";
            }
        }
    }


    /* =====================================================
       DELETE STAFF
       ===================================================== */

    elseif ($action === "delete_staff") {

        $staffId = $_POST["staff_id"] ?? "";

        if ($staffId !== "") {

            try {

                $users->deleteOne([
                    "_id" => new MongoDB\BSON\ObjectId($staffId),
                    "role" => "staff"
                ]);

                $message = "Staff account deleted successfully.";
                $messageType = "success";

            } catch (Exception $e) {

                $message = "Unable to delete this staff account.";
                $messageType = "error";
            }
        }
    }
}


/* =========================================================
   GET STAFF
   ========================================================= */

$staffMembers = $users->find(
    [
        "role" => "staff"
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

```
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Staff</title>

<link rel="stylesheet" href="../style.css">

<style>

    .staff-page {
        max-width: 1100px;
        margin: 90px auto 40px;
        padding: 0 20px;
    }

    .staff-header {
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .staff-header h1 {
        margin: 5px 0 8px;
    }

    .staff-header p {
        margin: 0;
        opacity: .9;
    }

    .staff-header-icon {
        font-size: 55px;
    }

    .staff-card {
        background: white;
        border-radius: 18px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0,0,0,.08);
    }

    .staff-card h2 {
        margin-top: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .form-field.full {
        grid-column: 1 / -1;
    }

    .form-field label {
        font-weight: 600;
    }

    .form-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 13px 15px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 15px;
    }

    .form-field input:focus {
        outline: none;
        border-color: #2563eb;
    }

    .add-staff-button {
        margin-top: 18px;
        border: none;
        background: #2563eb;
        color: white;
        padding: 13px 22px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
    }

    .add-staff-button:hover {
        background: #1d4ed8;
    }

    .message {
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .message.success {
        background: #dcfce7;
        color: #166534;
    }

    .message.error {
        background: #fee2e2;
        color: #991b1b;
    }

    .staff-table-wrapper {
        overflow-x: auto;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
    }

    .staff-table th,
    .staff-table td {
        padding: 15px 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .staff-table th {
        background: #f8fafc;
        font-size: 14px;
    }

    .staff-role {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
    }

    .delete-button {
        border: none;
        background: #fee2e2;
        color: #b91c1c;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .delete-button:hover {
        background: #fecaca;
    }

    .empty-staff {
        text-align: center;
        padding: 35px;
        color: #6b7280;
    }

    @media (max-width: 700px) {

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-field.full {
            grid-column: auto;
        }

        .staff-header {
            padding: 25px;
        }

        .staff-header-icon {
            display: none;
        }

    }

</style>
```

</head>

<body>

<?php include __DIR__ . "/../navbar.php"; ?>

<main class="staff-page">

```
<!-- HEADER -->

<section class="staff-header">

    <div>

        <small>
            ADMINISTRATION
        </small>

        <h1>
            Manage Staff 👥
        </h1>

        <p>
            Create and manage staff accounts for the Lost and Found System.
        </p>

    </div>

    <div class="staff-header-icon">
        👥
    </div>

</section>


<!-- MESSAGE -->

<?php if ($message !== ""): ?>

    <div class="message <?php echo htmlspecialchars($messageType); ?>">

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php endif; ?>


<!-- ADD STAFF -->

<section class="staff-card">

    <h2>
        Add New Staff
    </h2>

    <p>
        Create an account that can be used by staff members.
    </p>


    <form method="POST">

        <input
            type="hidden"
            name="action"
            value="add_staff"
        >

        <div class="form-grid">


            <div class="form-field">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter staff name"
                    required
                >

            </div>


            <div class="form-field">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="staff@example.com"
                    required
                >

            </div>


            <div class="form-field">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 6 characters"
                    minlength="6"
                    required
                >

            </div>


        </div>


        <button
            type="submit"
            class="add-staff-button"
        >
            + Create Staff Account
        </button>

    </form>

</section>


<!-- STAFF LIST -->

<section class="staff-card">

    <h2>
        Staff Accounts
    </h2>

    <p>
        Staff members currently registered in the system.
    </p>


    <div class="staff-table-wrapper">

        <table class="staff-table">

            <thead>

                <tr>

                    <th>
                        Name
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Role
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            $hasStaff = false;

            foreach ($staffMembers as $staff):

                $hasStaff = true;

            ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars(
                            $staff["name"] ?? "Unnamed Staff"
                        ); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars(
                            $staff["email"] ?? "No email"
                        ); ?>
                    </td>

                    <td>

                        <span class="staff-role">
                            STAFF
                        </span>

                    </td>

                    <td>

                        <form
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this staff account?');"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="delete_staff"
                            >

                            <input
                                type="hidden"
                                name="staff_id"
                                value="<?php echo htmlspecialchars(
                                    (string) $staff["_id"]
                                ); ?>"
                            >

                            <button
                                type="submit"
                                class="delete-button"
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endforeach; ?>


            <?php if (!$hasStaff): ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty-staff"
                    >

                        👥 No staff accounts have been created yet.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>
```

</main>

</body>

</html>
