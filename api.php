<?php
/*
============================================================
 ESP-SWITCH5 REMOTE - api.php
============================================================

CUSTOMER SCHEDULE:
    start_time
    end_time

OWNER SCHEDULE:
    owner_start_time
    owner_end_time

OWNER HAS PRIORITY.

EFFECTIVE START:
    Later of customer START and owner START

EFFECTIVE END:
    Earlier of customer END and owner END

Example:

    Customer END = 14:48
    Owner END    = 14:43

    Effective END = 14:43

After 14:43:
    D1-D8 = 0
    ESP8266 outputs = OFF

============================================================
*/

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

date_default_timezone_set("Asia/Kolkata");

header(
    "Content-Type: application/json; charset=UTF-8"
);


/* =========================================================
   GET PARAMETERS
========================================================= */

$action =
    trim($_GET["action"] ?? "");

$controller_id =
    trim($_GET["controller_id"] ?? "");

$device_token =
    trim($_GET["device_token"] ?? "");


/* =========================================================
   VALIDATE CONTROLLER ID
========================================================= */

if ($controller_id === "") {

    echo json_encode([
        "status" => "error",
        "message" => "controller_id missing"
    ]);

    exit;
}


/* =========================================================
   VALIDATE DEVICE TOKEN
========================================================= */

if ($device_token === "") {

    echo json_encode([
        "status" => "error",
        "message" => "device_token missing"
    ]);

    exit;
}


/* =========================================================
   FIND CONTROLLER
========================================================= */

$stmt =
    $conn->prepare("
        SELECT
            id,
            controller_id,
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
          AND device_token = ?
        LIMIT 1
    ");


if (!$stmt) {

    echo json_encode([
        "status" => "error",
        "message" => "Controller prepare failed"
    ]);

    exit;
}


$stmt->bind_param(
    "ss",
    $controller_id,
    $device_token
);


if (!$stmt->execute()) {

    echo json_encode([
        "status" => "error",
        "message" => "Controller query failed"
    ]);

    $stmt->close();

    exit;
}


$result =
    $stmt->get_result();


/* =========================================================
   CONTROLLER NOT FOUND
========================================================= */

if ($result->num_rows === 0) {

    echo json_encode([
        "status" => "error",
        "message" =>
            "Invalid controller_id or device_token"
    ]);

    $stmt->close();

    exit;
}


$controller =
    $result->fetch_assoc();

$stmt->close();


/* =========================================================
   CURRENT IST TIME
========================================================= */

$current_time =
    new DateTime(
        "now",
        new DateTimeZone("Asia/Kolkata")
    );


$current_time_string =
    $current_time->format(
        "Y-m-d H:i:s"
    );


/* =========================================================
   READ CUSTOMER AND OWNER TIMES
========================================================= */

$customer_start =
    $controller["start_time"] ?? null;

$customer_end =
    $controller["end_time"] ?? null;

$owner_start =
    $controller["owner_start_time"] ?? null;

$owner_end =
    $controller["owner_end_time"] ?? null;


/* =========================================================
   CREATE DATETIME OBJECTS
========================================================= */

$customer_start_dt = null;
$customer_end_dt   = null;
$owner_start_dt    = null;
$owner_end_dt      = null;


try {

    if (!empty($customer_start)) {

        $customer_start_dt =
            new DateTime(
                $customer_start,
                new DateTimeZone("Asia/Kolkata")
            );
    }

    if (!empty($customer_end)) {

        $customer_end_dt =
            new DateTime(
                $customer_end,
                new DateTimeZone("Asia/Kolkata")
            );
    }

    if (!empty($owner_start)) {

        $owner_start_dt =
            new DateTime(
                $owner_start,
                new DateTimeZone("Asia/Kolkata")
            );
    }

    if (!empty($owner_end)) {

        $owner_end_dt =
            new DateTime(
                $owner_end,
                new DateTimeZone("Asia/Kolkata")
            );
    }

}
catch (Exception $e) {

    echo json_encode([

        "status" => "error",

        "message" =>
            "Invalid calendar date/time",

        "current_time" =>
            $current_time_string

    ]);

    exit;
}


/* =========================================================
   EFFECTIVE START
=========================================================

   Owner START can restrict the customer.

   Later START wins.

========================================================= */

$effective_start = null;


if (
    $customer_start_dt !== null &&
    $owner_start_dt !== null
) {

    if (
        $owner_start_dt >
        $customer_start_dt
    ) {

        $effective_start =
            $owner_start_dt;

    } else {

        $effective_start =
            $customer_start_dt;
    }

}
elseif (
    $owner_start_dt !== null
) {

    $effective_start =
        $owner_start_dt;

}
elseif (
    $customer_start_dt !== null
) {

    $effective_start =
        $customer_start_dt;
}


/* =========================================================
   EFFECTIVE END
=========================================================

   OWNER END HAS PRIORITY.

   Earlier END wins.

========================================================= */

$effective_end = null;


if (
    $customer_end_dt !== null &&
    $owner_end_dt !== null
) {

    if (
        $owner_end_dt <
        $customer_end_dt
    ) {

        $effective_end =
            $owner_end_dt;

    } else {

        $effective_end =
            $customer_end_dt;
    }

}
elseif (
    $owner_end_dt !== null
) {

    $effective_end =
        $owner_end_dt;

}
elseif (
    $customer_end_dt !== null
) {

    $effective_end =
        $customer_end_dt;
}


/* =========================================================
   CALENDAR STATUS
========================================================= */

$calendar_allowed = true;

$calendar_status =
    "NO_SCHEDULE";


/* ---------------------------------------------------------
   END TIME CHECK FIRST
--------------------------------------------------------- */

if (
    $effective_end !== null &&
    $current_time >= $effective_end
) {

    $calendar_allowed = false;

    $calendar_status =
        "EXPIRED";

}


/* ---------------------------------------------------------
   START TIME CHECK
--------------------------------------------------------- */

elseif (
    $effective_start !== null &&
    $current_time < $effective_start
) {

    $calendar_allowed = false;

    $calendar_status =
        "NOT_STARTED";

}


/* ---------------------------------------------------------
   ACTIVE
--------------------------------------------------------- */

else {

    $calendar_allowed = true;

    if (
        $effective_start !== null ||
        $effective_end !== null
    ) {

        $calendar_status =
            "ACTIVE";
    }
}


/* =========================================================
   CONTROLLER ACTIVE CHECK
========================================================= */

if (
    (int)$controller["active"] !== 1
) {

    echo json_encode([

        "status" => "error",

        "message" =>
            "Controller is inactive",

        "controller_id" =>
            $controller_id,

        "calendar_allowed" =>
            false,

        "calendar_status" =>
            "INACTIVE",

        "current_time" =>
            $current_time_string,

        "customer_start_time" =>
            $customer_start,

        "customer_end_time" =>
            $customer_end,

        "owner_start_time" =>
            $owner_start,

        "owner_end_time" =>
            $owner_end,

        "effective_start_time" =>
            $effective_start !== null
                ? $effective_start->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "effective_end_time" =>
            $effective_end !== null
                ? $effective_end->format(
                    "Y-m-d H:i:s"
                )
                : null

    ]);

    exit;
}


/* =========================================================
   UPDATE LAST SEEN
========================================================= */

$stmt =
    $conn->prepare("
        UPDATE controllers
        SET last_seen = ?
        WHERE controller_id = ?
          AND device_token = ?
    ");


if (!$stmt) {

    echo json_encode([
        "status" => "error",
        "message" => "last_seen prepare failed"
    ]);

    exit;
}


$stmt->bind_param(
    "sss",
    $current_time_string,
    $controller_id,
    $device_token
);


if (!$stmt->execute()) {

    echo json_encode([
        "status" => "error",
        "message" =>
            "Could not update last_seen"
    ]);

    $stmt->close();

    exit;
}


$stmt->close();


/* =========================================================
   CALENDAR NOT ALLOWED
========================================================= */

if (!$calendar_allowed) {


    /* -----------------------------------------------------
       EXPIRED

       RESET ALL OUTPUTS IN DATABASE
    ----------------------------------------------------- */

    if (
        $calendar_status === "EXPIRED"
    ) {

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
                $controller_id
            );

            $reset_stmt->execute();

            $reset_stmt->close();
        }
    }


    /* -----------------------------------------------------
       RETURN OFF TO ESP8266
    ----------------------------------------------------- */

    echo json_encode([

        "status" => "ok",

        "controller_id" =>
            $controller_id,

        "calendar_allowed" =>
            false,

        "calendar_status" =>
            $calendar_status,

        "current_time" =>
            $current_time_string,

        "customer_start_time" =>
            $customer_start,

        "customer_end_time" =>
            $customer_end,

        "owner_start_time" =>
            $owner_start,

        "owner_end_time" =>
            $owner_end,

        "effective_start_time" =>
            $effective_start !== null
                ? $effective_start->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "effective_end_time" =>
            $effective_end !== null
                ? $effective_end->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "D1" => 0,
        "D2" => 0,
        "D3" => 0,
        "D4" => 0,
        "D5" => 0,
        "D6" => 0,
        "D7" => 0,
        "D8" => 0,

        "last_seen" =>
            $current_time_string

    ]);

    exit;
}


/* =========================================================
   ACTION = GET
========================================================= */

if (
    $action === "get"
) {

    $stmt =
        $conn->prepare("
            SELECT
                D1,
                D2,
                D3,
                D4,
                D5,
                D6,
                D7,
                D8
            FROM esp_control
            WHERE controller_id = ?
            LIMIT 1
        ");


    if (!$stmt) {

        echo json_encode([
            "status" => "error",
            "message" =>
                "esp_control prepare failed"
        ]);

        exit;
    }


    $stmt->bind_param(
        "s",
        $controller_id
    );


    if (!$stmt->execute()) {

        echo json_encode([
            "status" => "error",
            "message" =>
                "esp_control query failed"
        ]);

        $stmt->close();

        exit;
    }


    $result =
        $stmt->get_result();


    if (
        $result->num_rows === 0
    ) {

        echo json_encode([
            "status" => "error",
            "message" =>
                "No esp_control record found"
        ]);

        $stmt->close();

        exit;
    }


    $row =
        $result->fetch_assoc();

    $stmt->close();


    echo json_encode([

        "status" => "ok",

        "controller_id" =>
            $controller_id,

        "calendar_allowed" =>
            true,

        "calendar_status" =>
            $calendar_status,

        "current_time" =>
            $current_time_string,

        "customer_start_time" =>
            $customer_start,

        "customer_end_time" =>
            $customer_end,

        "owner_start_time" =>
            $owner_start,

        "owner_end_time" =>
            $owner_end,

        "effective_start_time" =>
            $effective_start !== null
                ? $effective_start->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "effective_end_time" =>
            $effective_end !== null
                ? $effective_end->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "D1" => (int)$row["D1"],
        "D2" => (int)$row["D2"],
        "D3" => (int)$row["D3"],
        "D4" => (int)$row["D4"],
        "D5" => (int)$row["D5"],
        "D6" => (int)$row["D6"],
        "D7" => (int)$row["D7"],
        "D8" => (int)$row["D8"],

        "last_seen" =>
            $current_time_string

    ]);

    exit;
}


/* =========================================================
   ACTION = SET
========================================================= */

if (
    $action === "set"
) {

    $pin =
        strtoupper(
            trim(
                $_GET["pin"] ?? ""
            )
        );


    $value =
        isset($_GET["value"])
            ? (int)$_GET["value"]
            : -1;


    /* -----------------------------------------------------
       VALIDATE PIN
    ----------------------------------------------------- */

    if (
        !preg_match(
            '/^D[1-8]$/',
            $pin
        )
    ) {

        echo json_encode([
            "status" => "error",
            "message" => "Invalid pin"
        ]);

        exit;
    }


    /* -----------------------------------------------------
       VALIDATE VALUE
    ----------------------------------------------------- */

    if (
        $value !== 0 &&
        $value !== 1
    ) {

        echo json_encode([
            "status" => "error",
            "message" => "Invalid value"
        ]);

        exit;
    }


    /* -----------------------------------------------------
       UPDATE OUTPUT
    ----------------------------------------------------- */

    $sql = "
        UPDATE esp_control
        SET `$pin` = ?
        WHERE controller_id = ?
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        echo json_encode([
            "status" => "error",
            "message" =>
                "Pin update prepare failed"
        ]);

        exit;
    }


    $stmt->bind_param(
        "is",
        $value,
        $controller_id
    );


    if (!$stmt->execute()) {

        echo json_encode([
            "status" => "error",
            "message" =>
                "Pin update failed"
        ]);

        $stmt->close();

        exit;
    }


    $stmt->close();


    echo json_encode([

        "status" => "ok",

        "controller_id" =>
            $controller_id,

        "pin" =>
            $pin,

        "value" =>
            $value,

        "calendar_allowed" =>
            true,

        "calendar_status" =>
            $calendar_status,

        "current_time" =>
            $current_time_string,

        "customer_start_time" =>
            $customer_start,

        "customer_end_time" =>
            $customer_end,

        "owner_start_time" =>
            $owner_start,

        "owner_end_time" =>
            $owner_end,

        "effective_end_time" =>
            $effective_end !== null
                ? $effective_end->format(
                    "Y-m-d H:i:s"
                )
                : null,

        "last_seen" =>
            $current_time_string

    ]);

    exit;
}


/* =========================================================
   UNKNOWN ACTION
========================================================= */

echo json_encode([

    "status" => "error",

    "message" =>
        "Unknown action. Use action=get or action=set"

]);

exit;

?>
