<?php
/*
============================================================
 ESP-SWITCH5 REMOTE
 OWNER-ONLY CONTROLLER MANAGEMENT
============================================================

OWNER TIME CONTROL

Customer schedule:
    start_time
    end_time

Owner schedule:
    owner_start_time
    owner_end_time

IMPORTANT:

    Owner time is separate from customer time.

    Owner END TIME is the maximum allowed time.

============================================================
*/


/* =========================================================
   SESSION
========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/* =========================================================
   CONFIGURATION
========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set("Asia/Kolkata");


/* =========================================================
   VARIABLES
========================================================= */

$login_error = "";

$message = "";

$message_type = "";

$edit_controller = null;


/* =========================================================
   HELPER
========================================================= */

function datetime_local_value($value)
{
    if ($value === null || $value === "") {
        return "";
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return "";
    }

    return date(
        "Y-m-d\TH:i",
        $timestamp
    );
}


/* =========================================================
   CURRENT IST TIME
========================================================= */

$current_time =
    new DateTime(
        "now",
        new DateTimeZone("Asia/Kolkata")
    );


/* =========================================================
   OWNER LOGIN
========================================================= */

if (isset($_POST["owner_login"])) {

    $password =
        $_POST["owner_password"] ?? "";

    if (
        $token_password !== "" &&
        hash_equals(
            $token_password,
            $password
        )
    ) {

        $_SESSION["esp_owner"] = true;

        header("Location: owner_token.php");

        exit;

    } else {

        $login_error =
            "Invalid owner password.";
    }
}


/* =========================================================
   OWNER LOGOUT
========================================================= */

if (isset($_GET["logout"])) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: owner_token.php");

    exit;
}


/* =========================================================
   OWNER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["esp_owner"]) ||
    $_SESSION["esp_owner"] !== true
) {

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
ESP-SWITCH5 - Owner Login
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f2f2;
}

.login-box {

    max-width: 450px;

    margin: 80px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);

    text-align: center;
}

h1 {

    margin-top: 0;

    color: #333;
}

.warning {

    background: #fff3cd;

    color: #856404;

    border: 1px solid #ffeeba;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-size: 14px;
}

input {

    width: 100%;

    padding: 12px;

    font-size: 16px;

    border: 1px solid #aaa;

    border-radius: 6px;

    margin: 15px 0;
}

button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    background: #343a40;

    color: white;

    font-size: 16px;

    cursor: pointer;
}

button:hover {

    opacity: 0.85;
}

.error {

    color: #dc3545;

    margin-bottom: 10px;

    font-weight: bold;
}

.small {

    margin-top: 15px;

    color: #777;

    font-size: 13px;
}

</style>

</head>

<body>

<div class="login-box">

<h1>
ESP-SWITCH5
</h1>

<h2>
OWNER ACCESS
</h2>

<div class="warning">

This page is for owner use only.<br>

Do not give this page or its password to customers.

</div>

<?php

if ($login_error !== "") {

    echo
        '<div class="error">' .
        htmlspecialchars(
            $login_error,
            ENT_QUOTES,
            "UTF-8"
        ) .
        '</div>';
}

?>

<form method="post">

<input
    type="password"
    name="owner_password"
    placeholder="Enter owner password"
    required
    autofocus
>

<button
    type="submit"
    name="owner_login"
>
OWNER LOGIN
</button>

</form>

<div class="small">

ESP-SWITCH5 Controller Management

</div>

</div>

</body>

</html>

<?php

exit;

}


/* =========================================================
   DELETE CONTROLLER
========================================================= */

if (isset($_POST["delete_controller"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );


    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";

    } else {

        $stmt =
            $conn->prepare("
                DELETE FROM controllers
                WHERE controller_id = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $message =
                "Delete preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "s",
                $controller_id
            );


            try {

                if ($stmt->execute()) {

                    if (
                        $stmt->affected_rows > 0
                    ) {

                        $message =
                            "Controller " .
                            $controller_id .
                            " deleted successfully.";

                        $message_type =
                            "success";

                    } else {

                        $message =
                            "Controller not found.";

                        $message_type =
                            "error";
                    }

                } else {

                    $message =
                        "Controller deletion failed.";

                    $message_type =
                        "error";
                }

            }
            catch (mysqli_sql_exception $e) {

                $message =
                    "Controller could not be deleted. " .
                    "It may be referenced by another table.";

                $message_type =
                    "error";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   ACTIVATE CONTROLLER
========================================================= */

if (isset($_POST["activate_controller"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );


    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";

    } else {

        $stmt =
            $conn->prepare("
                UPDATE controllers
                SET active = 1
                WHERE controller_id = ?
            ");


        if (!$stmt) {

            $message =
                "Activation preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "s",
                $controller_id
            );


            if ($stmt->execute()) {

                $message =
                    "Controller " .
                    $controller_id .
                    " activated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Controller activation failed.";

                $message_type =
                    "error";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   DEACTIVATE CONTROLLER
========================================================= */

if (isset($_POST["deactivate_controller"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );


    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";

    } else {

        $stmt =
            $conn->prepare("
                UPDATE controllers
                SET active = 0
                WHERE controller_id = ?
            ");


        if (!$stmt) {

            $message =
                "Deactivation preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "s",
                $controller_id
            );


            if ($stmt->execute()) {

                $message =
                    "Controller " .
                    $controller_id .
                    " deactivated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Controller deactivation failed.";

                $message_type =
                    "error";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   ADD CONTROLLER
========================================================= */

if (isset($_POST["add_controller"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );

    $customer_token =
        trim(
            $_POST["customer_token"] ?? ""
        );

    $device_token =
        trim(
            $_POST["device_token"] ?? ""
        );

    $customer_name =
        trim(
            $_POST["customer_name"] ?? ""
        );

    $active =
        isset($_POST["active"])
            ? (int)$_POST["active"]
            : 1;

    $owner_start_time =
        trim(
            $_POST["owner_start_time"] ?? ""
        );

    $owner_end_time =
        trim(
            $_POST["owner_end_time"] ?? ""
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";

    }
    elseif (
        !preg_match(
            '/^[A-Za-z0-9_-]{1,50}$/',
            $controller_id
        )
    ) {

        $message =
            "Invalid Controller ID.";

        $message_type =
            "error";

    }
    elseif (
        $customer_token !== "" &&
        !preg_match(
            '/^[A-Za-z0-9_-]{1,100}$/',
            $customer_token
        )
    ) {

        $message =
            "Invalid Customer Token.";

        $message_type =
            "error";

    }
    elseif ($device_token === "") {

        $message =
            "Device Token is required.";

        $message_type =
            "error";

    }
    elseif (
        !preg_match(
            '/^[A-Za-z0-9_-]{8,100}$/',
            $device_token
        )
    ) {

        $message =
            "Invalid Device Token.";

        $message_type =
            "error";

    }
    elseif (
        strlen($customer_name) > 100
    ) {

        $message =
            "Customer name is too long.";

        $message_type =
            "error";

    }
    elseif (
        ($owner_start_time === "" &&
         $owner_end_time !== "") ||
        ($owner_start_time !== "" &&
         $owner_end_time === "")
    ) {

        $message =
            "Please enter both OWNER START TIME and OWNER END TIME.";

        $message_type =
            "error";

    }
    elseif (
        $owner_start_time !== "" &&
        $owner_end_time !== "" &&
        strtotime($owner_end_time) <=
        strtotime($owner_start_time)
    ) {

        $message =
            "OWNER END TIME must be later than OWNER START TIME.";

        $message_type =
            "error";

    }
    else {

        $owner_start_db = null;
        $owner_end_db = null;


        if ($owner_start_time !== "") {

            $owner_start_db =
                str_replace(
                    "T",
                    " ",
                    $owner_start_time
                ) . ":00";
        }


        if ($owner_end_time !== "") {

            $owner_end_db =
                str_replace(
                    "T",
                    " ",
                    $owner_end_time
                ) . ":00";
        }


        $stmt =
            $conn->prepare("
                INSERT INTO controllers
                (
                    controller_id,
                    customer_token,
                    device_token,
                    customer_name,
                    active,
                    owner_start_time,
                    owner_end_time
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?)
            ");


        if (!$stmt) {

            $message =
                "Controller insertion preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "ssssiss",
                $controller_id,
                $customer_token,
                $device_token,
                $customer_name,
                $active,
                $owner_start_db,
                $owner_end_db
            );


            try {

                if ($stmt->execute()) {

                    $message =
                        "Controller " .
                        $controller_id .
                        " added successfully.";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "Could not add controller.";

                    $message_type =
                        "error";
                }

            }
            catch (mysqli_sql_exception $e) {

                if (
                    $e->getCode() == 1062
                ) {

                    $message =
                        "Controller ID or Device Token already exists.";

                } else {

                    $message =
                        "Could not add controller.";
                }

                $message_type =
                    "error";
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   UPDATE CONTROLLER
========================================================= */

if (isset($_POST["update_controller"])) {

    $original_controller_id =
        trim(
            $_POST["original_controller_id"] ?? ""
        );

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );

    $customer_token =
        trim(
            $_POST["customer_token"] ?? ""
        );

    $device_token =
        trim(
            $_POST["device_token"] ?? ""
        );

    $customer_name =
        trim(
            $_POST["customer_name"] ?? ""
        );

    $active =
        isset($_POST["active"])
            ? (int)$_POST["active"]
            : 1;

    $owner_start_time =
        trim(
            $_POST["owner_start_time"] ?? ""
        );

    $owner_end_time =
        trim(
            $_POST["owner_end_time"] ?? ""
        );


    /* -----------------------------------------------------
       VALIDATE
    ----------------------------------------------------- */

    if ($original_controller_id === "") {

        $message =
            "Original Controller ID is missing.";

        $message_type =
            "error";

    }
    elseif ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";

    }
    elseif (
        !preg_match(
            '/^[A-Za-z0-9_-]{1,50}$/',
            $controller_id
        )
    ) {

        $message =
            "Invalid Controller ID.";

        $message_type =
            "error";

    }
    elseif (
        $customer_token !== "" &&
        !preg_match(
            '/^[A-Za-z0-9_-]{1,100}$/',
            $customer_token
        )
    ) {

        $message =
            "Invalid Customer Token.";

        $message_type =
            "error";

    }
    elseif ($device_token === "") {

        $message =
            "Device Token is required.";

        $message_type =
            "error";

    }
    elseif (
        !preg_match(
            '/^[A-Za-z0-9_-]{8,100}$/',
            $device_token
        )
    ) {

        $message =
            "Invalid Device Token.";

        $message_type =
            "error";

    }
    elseif (
        strlen($customer_name) > 100
    ) {

        $message =
            "Customer name is too long.";

        $message_type =
            "error";

    }
    elseif (
        ($owner_start_time === "" &&
         $owner_end_time !== "") ||
        ($owner_start_time !== "" &&
         $owner_end_time === "")
    ) {

        $message =
            "Please enter both OWNER START TIME and OWNER END TIME.";

        $message_type =
            "error";

    }
    elseif (
        $owner_start_time !== "" &&
        $owner_end_time !== "" &&
        strtotime($owner_end_time) <=
        strtotime($owner_start_time)
    ) {

        $message =
            "OWNER END TIME must be later than OWNER START TIME.";

        $message_type =
            "error";

    }
    else {

        $owner_start_db = null;
        $owner_end_db = null;


        if ($owner_start_time !== "") {

            $owner_start_db =
                str_replace(
                    "T",
                    " ",
                    $owner_start_time
                ) . ":00";
        }


        if ($owner_end_time !== "") {

            $owner_end_db =
                str_replace(
                    "T",
                    " ",
                    $owner_end_time
                ) . ":00";
        }


        $stmt =
            $conn->prepare("
                UPDATE controllers
                SET
                    controller_id = ?,
                    customer_token = ?,
                    device_token = ?,
                    customer_name = ?,
                    active = ?,
                    owner_start_time = ?,
                    owner_end_time = ?
                WHERE controller_id = ?
            ");


        if (!$stmt) {

            $message =
                "Controller update preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "ssssisss",
                $controller_id,
                $customer_token,
                $device_token,
                $customer_name,
                $active,
                $owner_start_db,
                $owner_end_db,
                $original_controller_id
            );


            try {

                if ($stmt->execute()) {

                    $message =
                        "Controller " .
                        $controller_id .
                        " updated successfully.";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "Controller update failed.";

                    $message_type =
                        "error";
                }

            }
            catch (mysqli_sql_exception $e) {

                if (
                    $e->getCode() == 1062
                ) {

                    $message =
                        "Controller ID or Device Token already exists.";

                } else {

                    $message =
                        "Could not update controller.";
                }

                $message_type =
                    "error";
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   LOAD EDIT CONTROLLER
========================================================= */

if (
    isset($_GET["edit"]) &&
    trim($_GET["edit"]) !== ""
) {

    $edit_id =
        trim(
            $_GET["edit"]
        );


    $stmt =
        $conn->prepare("
            SELECT
                id,
                controller_id,
                customer_token,
                device_token,
                customer_name,
                active,
                last_seen,
                start_time,
                end_time,
                owner_start_time,
                owner_end_time
            FROM controllers
            WHERE controller_id = ?
            LIMIT 1
        ");


    if ($stmt) {

        $stmt->bind_param(
            "s",
            $edit_id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        if (
            $result->num_rows > 0
        ) {

            $edit_controller =
                $result->fetch_assoc();

        } else {

            $message =
                "Controller not found.";

            $message_type =
                "error";
        }


        $stmt->close();
    }
}


/* =========================================================
   AUTOMATICALLY TURN OFF CONTROLLERS
   AFTER OWNER END TIME
========================================================= */

$expire_result =
    $conn->query("
        SELECT
            controller_id,
            owner_end_time,
            active
        FROM controllers
        WHERE
            owner_end_time IS NOT NULL
            AND owner_end_time <> ''
            AND active = 1
    ");


if ($expire_result) {

    while (
        $row =
        $expire_result->fetch_assoc()
    ) {

        try {

            $owner_end =
                new DateTime(
                    $row["owner_end_time"],
                    new DateTimeZone("Asia/Kolkata")
                );


            if (
                $current_time >= $owner_end
            ) {

                $controller_id_expired =
                    $row["controller_id"];


                $reset_stmt =
                    $conn->prepare("
                        UPDATE esp_control
                        SET
                            D1 = 0,
                            D2 = 0,
                            D3 = 0,
                            D4 = 0,
                            D5 = 0,
                            D6 = 0,
                            D7 = 0,
                            D8 = 0
                        WHERE controller_id = ?
                    ");


                if ($reset_stmt) {

                    $reset_stmt->bind_param(
                        "s",
                        $controller_id_expired
                    );

                    $reset_stmt->execute();

                    $reset_stmt->close();
                }
            }

        }
        catch (Exception $e) {

            /* Ignore invalid date */
        }
    }
}


/* =========================================================
   READ ALL CONTROLLERS
========================================================= */

$controllers = [];


$result =
    $conn->query("
        SELECT
            id,
            controller_id,
            customer_token,
            device_token,
            customer_name,
            active,
            last_seen,
            start_time,
            end_time,
            owner_start_time,
            owner_end_time
        FROM controllers
        ORDER BY controller_id
    ");


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $controllers[] =
            $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
ESP-SWITCH5 - Owner Controller Management
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f2f2;

    color: #222;
}

.container {

    max-width: 1250px;

    margin: 30px auto;

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);
}

.header {

    text-align: center;

    margin-bottom: 25px;
}

.header h1 {

    margin: 0;

    color: #333;
}

.subtitle {

    color: #666;

    margin-top: 5px;
}

.current-time {

    margin-top: 12px;

    padding: 10px;

    background: #e7f3ff;

    border-radius: 6px;

    color: #0056b3;

    font-weight: bold;

    text-align: center;
}

.top-buttons {

    text-align: center;

    margin: 20px 0;
}

.top-buttons a {

    display: inline-block;

    text-decoration: none;

    color: white;

    padding: 10px 18px;

    border-radius: 6px;

    margin: 4px;

    font-weight: bold;
}

.add-link {

    background: #007bff;
}

.logout-link {

    background: #6c757d;
}

.form-box {

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 25px;
}

.form-box h2 {

    margin-top: 0;

    text-align: center;
}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 7px;

    margin-top: 14px;
}

input[type="text"],
input[type="datetime-local"],
select {

    width: 100%;

    padding: 11px;

    font-size: 16px;

    border: 1px solid #aaa;

    border-radius: 6px;
}

.owner-time-section {

    margin-top: 20px;

    padding: 18px;

    background: #fff3cd;

    border: 2px solid #ffc107;

    border-radius: 10px;
}

.owner-time-section h3 {

    margin-top: 0;

    color: #856404;

    text-align: center;
}

.owner-time-note {

    margin-top: 12px;

    font-size: 13px;

    color: #665c3b;

    text-align: center;

    line-height: 1.5;
}

.form-button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    margin-top: 20px;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.add-button {

    background: #007bff;
}

.update-button {

    background: #28a745;
}

.cancel-button {

    display: block;

    text-align: center;

    margin-top: 10px;

    padding: 11px;

    background: #6c757d;

    color: white;

    text-decoration: none;

    border-radius: 6px;
}

.message {

    padding: 13px;

    border-radius: 7px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}

.success {

    background: #d4edda;

    color: #155724;
}

.error {

    background: #f8d7da;

    color: #721c24;
}

.table-wrapper {

    overflow-x: auto;

    margin-top: 20px;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1450px;
}

th {

    background: #343a40;

    color: white;

    padding: 11px;

    text-align: center;
}

td {

    border: 1px solid #ddd;

    padding: 10px;

    text-align: center;

    vertical-align: middle;
}

tr:nth-child(even) {

    background: #f8f8f8;
}

.active {

    color: #198754;

    font-weight: bold;
}

.inactive {

    color: #dc3545;

    font-weight: bold;
}

.expired {

    color: #dc3545;

    font-weight: bold;
}

.valid {

    color: #198754;

    font-weight: bold;
}

.schedule {

    white-space: nowrap;

    font-size: 13px;
}

.action-button {

    display: inline-block;

    border: none;

    border-radius: 5px;

    padding: 7px 11px;

    margin: 2px;

    color: white;

    text-decoration: none;

    cursor: pointer;

    font-size: 13px;

    font-weight: bold;
}

.edit-button {

    background: #007bff;
}

.delete-button {

    background: #dc3545;
}

.activate-button {

    background: #28a745;
}

.deactivate-button {

    background: #6c757d;
}

.action-form {

    display: inline;
}

@media (max-width: 600px) {

    body {
        padding: 10px;
    }

    .container {
        padding: 15px;
    }

    table {
        min-width: 1450px;
    }
}

</style>

</head>

<body>

<div class="container">


<!-- ======================================================
     HEADER
====================================================== -->

<div class="header">

<h1>
ESP-SWITCH5 REMOTE
</h1>

<div class="subtitle">
OWNER CONTROLLER MANAGEMENT
</div>

<div class="current-time">

CURRENT IST TIME:

<?php

echo $current_time->format(
    "Y-m-d H:i:s"
);

?>

</div>

</div>


<!-- ======================================================
     TOP BUTTONS
====================================================== -->

<div class="top-buttons">

<a
href="owner_token.php"
class="add-link"
>
ADD NEW CONTROLLER
</a>

<a
href="owner_token.php?logout=1"
class="logout-link"
>
OWNER LOGOUT
</a>

</div>


<!-- ======================================================
     MESSAGE
====================================================== -->

<?php

if ($message !== "") {

?>

<div
class="message
<?php
echo
    $message_type === "success"
        ? "success"
        : "error";
?>"
>

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php

}

?>


<!-- ======================================================
     EDIT FORM
====================================================== -->

<?php

if ($edit_controller !== null) {

?>

<div class="form-box">

<h2>
EDIT CONTROLLER
</h2>

<form method="post">

<input
type="hidden"
name="original_controller_id"
value="<?php
echo htmlspecialchars(
    $edit_controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
>


<label>
Controller ID
</label>

<input
type="text"
name="controller_id"
maxlength="50"
value="<?php
echo htmlspecialchars(
    $edit_controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
required
>


<label>
Customer Token
</label>

<input
type="text"
name="customer_token"
maxlength="100"
value="<?php
echo htmlspecialchars(
    $edit_controller["customer_token"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);
?>"
autocomplete="off"
>


<label>
Device Token
</label>

<input
type="text"
name="device_token"
maxlength="100"
value="<?php
echo htmlspecialchars(
    $edit_controller["device_token"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
autocomplete="off"
required
>


<label>
Customer Name
</label>

<input
type="text"
name="customer_name"
maxlength="100"
value="<?php
echo htmlspecialchars(
    $edit_controller["customer_name"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);
?>"
>


<label>
Status
</label>

<select name="active">

<option
value="1"
<?php
echo
    ((int)$edit_controller["active"] === 1)
        ? "selected"
        : "";
?>
>
ACTIVE
</option>

<option
value="0"
<?php
echo
    ((int)$edit_controller["active"] === 0)
        ? "selected"
        : "";
?>
>
INACTIVE
</option>

</select>


<!-- ====================================================
     OWNER TIME
===================================================== -->

<div class="owner-time-section">

<h3>
OWNER TIME CONTROL
</h3>


<label>
OWNER START TIME
</label>

<input
type="datetime-local"
name="owner_start_time"
value="<?php
echo htmlspecialchars(
    datetime_local_value(
        $edit_controller["owner_start_time"] ?? ""
    ),
    ENT_QUOTES,
    "UTF-8"
);
?>"
>


<label>
OWNER END TIME
</label>

<input
type="datetime-local"
name="owner_end_time"
value="<?php
echo htmlspecialchars(
    datetime_local_value(
        $edit_controller["owner_end_time"] ?? ""
    ),
    ENT_QUOTES,
    "UTF-8"
);
?>"
>


<div class="owner-time-note">

<strong>OWNER CONTROL:</strong>

The owner END TIME is the maximum time
the customer is allowed to operate.

<br>

For example, if customer END TIME is
14:48 and owner END TIME is 14:43,
the controller will stop at 14:43.

<br>

Timezone: Asia/Kolkata (IST)

</div>

</div>


<button
type="submit"
name="update_controller"
class="form-button update-button"
>
UPDATE CONTROLLER
</button>


<a
href="owner_token.php"
class="cancel-button"
>
CANCEL EDIT
</a>

</form>

</div>

<?php

} else {

?>


<!-- ======================================================
     ADD FORM
====================================================== -->

<div class="form-box">

<h2>
ADD NEW CONTROLLER
</h2>

<form method="post">


<label>
Controller ID
</label>

<input
type="text"
name="controller_id"
maxlength="50"
placeholder="Example: ESP0001"
required
autocomplete="off"
>


<label>
Customer Token
</label>

<input
type="text"
name="customer_token"
maxlength="100"
placeholder="Example: ESP0001-CUST-ABC123"
autocomplete="off"
>


<label>
Device Token
</label>

<input
type="text"
name="device_token"
maxlength="100"
placeholder="Example: ESP0001-TOKEN-2026-RAVI1"
required
autocomplete="off"
>


<label>
Customer Name
</label>

<input
type="text"
name="customer_name"
maxlength="100"
placeholder="Example: Test Customer"
>


<label>
Status
</label>

<select name="active">

<option value="1">
ACTIVE
</option>

<option value="0">
INACTIVE
</option>

</select>


<div class="owner-time-section">

<h3>
OWNER TIME CONTROL
</h3>


<label>
OWNER START TIME
</label>

<input
type="datetime-local"
name="owner_start_time"
>


<label>
OWNER END TIME
</label>

<input
type="datetime-local"
name="owner_end_time"
>


<div class="owner-time-note">

Leave both blank if no owner time restriction
is required.

<br>

Timezone: Asia/Kolkata (IST)

</div>

</div>


<button
type="submit"
name="add_controller"
class="form-button add-button"
>
ADD CONTROLLER
</button>

</form>

</div>

<?php

}

?>


<!-- ======================================================
     ALL CONTROLLERS
====================================================== -->

<div class="form-box">

<h2>
ALL CONTROLLERS
</h2>

<div class="table-wrapper">

<table>

<thead>

<tr>

<th>ID</th>

<th>CONTROLLER ID</th>

<th>CUSTOMER TOKEN</th>

<th>DEVICE TOKEN</th>

<th>CUSTOMER NAME</th>

<th>STATUS</th>

<th>CUSTOMER START</th>

<th>CUSTOMER END</th>

<th>OWNER START</th>

<th>OWNER END</th>

<th>LAST SEEN</th>

<th>ACTIONS</th>

</tr>

</thead>

<tbody>

<?php

if (
    count($controllers) === 0
) {

?>

<tr>

<td colspan="12">
No controllers found.
</td>

</tr>

<?php

} else {

    foreach (
        $controllers
        as $controller
    ) {

?>

<tr>


<td>

<?php

echo htmlspecialchars(
    $controller["id"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<strong>

<?php

echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</strong>

</td>


<td>

<?php

echo htmlspecialchars(
    $controller["customer_token"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $controller["device_token"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $controller["customer_name"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

if (
    (int)$controller["active"] === 1
) {

?>

<span class="active">
ACTIVE
</span>

<?php

} else {

?>

<span class="inactive">
INACTIVE
</span>

<?php

}

?>

</td>


<td class="schedule">

<?php

echo
    !empty($controller["start_time"])
        ? htmlspecialchars(
            $controller["start_time"],
            ENT_QUOTES,
            "UTF-8"
        )
        : "Not set";

?>

</td>


<td class="schedule">

<?php

echo
    !empty($controller["end_time"])
        ? htmlspecialchars(
            $controller["end_time"],
            ENT_QUOTES,
            "UTF-8"
        )
        : "Not set";

?>

</td>


<td class="schedule">

<?php

echo
    !empty($controller["owner_start_time"])
        ? htmlspecialchars(
            $controller["owner_start_time"],
            ENT_QUOTES,
            "UTF-8"
        )
        : "Not set";

?>

</td>


<td class="schedule">

<?php

if (
    !empty($controller["owner_end_time"])
) {

    try {

        $owner_end_display =
            new DateTime(
                $controller["owner_end_time"],
                new DateTimeZone("Asia/Kolkata")
            );

        if (
            $current_time >=
            $owner_end_display
        ) {

?>

<span class="expired">

<?php

echo htmlspecialchars(
    $controller["owner_end_time"],
    ENT_QUOTES,
    "UTF-8"
);

?>

<br>

EXPIRED

</span>

<?php

        } else {

?>

<span class="valid">

<?php

echo htmlspecialchars(
    $controller["owner_end_time"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</span>

<?php

        }

    }
    catch (Exception $e) {

        echo htmlspecialchars(
            $controller["owner_end_time"],
            ENT_QUOTES,
            "UTF-8"
        );
    }

} else {

    echo "Not set";
}

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $controller["last_seen"] ??
    "Not yet seen",
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>


<!-- EDIT -->

<a
href="owner_token.php?edit=<?php
echo rawurlencode(
    $controller["controller_id"]
);
?>"
class="action-button edit-button"
>
EDIT
</a>


<!-- ACTIVATE / DEACTIVATE -->

<?php

if (
    (int)$controller["active"] === 1
) {

?>

<form
method="post"
class="action-form"
onsubmit="return confirm(
    'Deactivate controller <?php
    echo htmlspecialchars(
        $controller["controller_id"],
        ENT_QUOTES,
        "UTF-8"
    );
    ?>?'
);"
>

<input
type="hidden"
name="controller_id"
value="<?php
echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
>

<button
type="submit"
name="deactivate_controller"
class="action-button deactivate-button"
>
DEACTIVATE
</button>

</form>

<?php

} else {

?>

<form
method="post"
class="action-form"
onsubmit="return confirm(
    'Activate controller <?php
    echo htmlspecialchars(
        $controller["controller_id"],
        ENT_QUOTES,
        "UTF-8"
    );
    ?>?'
);"
>

<input
type="hidden"
name="controller_id"
value="<?php
echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
>

<button
type="submit"
name="activate_controller"
class="action-button activate-button"
>
ACTIVATE
</button>

</form>

<?php

}

?>


<!-- DELETE -->

<form
method="post"
class="action-form"
onsubmit="return confirm(
    'WARNING!\\n\\n' +
    'Permanently DELETE controller <?php
    echo htmlspecialchars(
        $controller["controller_id"],
        ENT_QUOTES,
        "UTF-8"
    );
    ?>?\\n\\n' +
    'This action cannot be undone.'
);"
>

<input
type="hidden"
name="controller_id"
value="<?php
echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);
?>"
>

<button
type="submit"
name="delete_controller"
class="action-button delete-button"
>
DELETE
</button>

</form>


</td>

</tr>

<?php

    }

}

?>

</tbody>

</table>

</div>

</div>


<!-- ======================================================
     IMPORTANT INFORMATION
====================================================== -->

<div class="form-box">

<strong>
OWNER CONTROL:
</strong>

<br><br>

<strong>
Customer START/END
</strong>
are stored in:

<strong>
start_time / end_time
</strong>

<br><br>

<strong>
Owner START/END
</strong>
are stored in:

<strong>
owner_start_time / owner_end_time
</strong>

<br><br>

The owner END TIME is the maximum permitted
operating time for the customer.

<br><br>

Example:

<br><br>

Customer END = <strong>14:48</strong>

<br>

Owner END = <strong>14:43</strong>

<br><br>

Actual permitted END = <strong>14:43</strong>.

<br><br>

When the owner END TIME has passed,
this page resets D1-D8 in the
<strong>esp_control</strong> table to zero.

<br><br>

The <strong>api.php</strong> must also enforce the same
owner END TIME because the ESP8266 communicates directly
with the API.

</div>


</div>

</body>

</html>
