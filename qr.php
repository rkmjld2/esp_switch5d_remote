```php
<?php

/*
============================================================
 ESP-SWITCH5B REMOTE
 QR CODE PAGE
============================================================
*/

$controller_id = trim($_GET["controller_id"] ?? "");


/* =========================================================
   CHECK CONTROLLER ID
========================================================= */

if ($controller_id === "") {
    die("Controller ID missing.");
}


/* =========================================================
   VALIDATE CONTROLLER ID
========================================================= */

if (!preg_match('/^[A-Za-z0-9_-]+$/', $controller_id)) {
    die("Invalid Controller ID.");
}


/* =========================================================
   CREATE CUSTOMER CONTROLLER URL
========================================================= */

$controller_url =
    "https://esp-switch5b-remote.onrender.com/c/" .
    rawurlencode($controller_id);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Controller QR Code</title>


<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 30px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f2f2;

    text-align: center;
}


.box {

    max-width: 500px;

    margin: auto;

    padding: 30px;

    background: white;

    border-radius: 12px;

    border: 1px solid #ccc;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);
}


h1 {

    margin-top: 0;

    color: #333;

}


.controller {

    font-size: 22px;

    font-weight: bold;

    margin: 15px 0 25px 0;

}


#qrcode {

    width: 300px;

    height: 300px;

    margin: 0 auto;

}


#qrcode img,
#qrcode canvas {

    display: block;

    margin: auto;

}


.url {

    margin-top: 20px;

    margin-bottom: 20px;

    font-size: 15px;

    word-break: break-all;

}


.url a {

    color: #007bff;

    text-decoration: none;

}


.url a:hover {

    text-decoration: underline;

}


.buttons {

    margin-top: 20px;

}


button,
.open-button {

    display: inline-block;

    border: none;

    border-radius: 6px;

    padding: 12px 20px;

    margin: 5px;

    font-size: 15px;

    cursor: pointer;

    text-decoration: none;

}


.download-button {

    background: #28a745;

    color: white;

}


.print-button {

    background: #007bff;

    color: white;

}


.open-button {

    background: #6c757d;

    color: white;

}


button:hover,
.open-button:hover {

    opacity: 0.85;

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {

        background: white;

        padding: 0;

    }

    .box {

        border: none;

        box-shadow: none;

    }

    .buttons,
    .url {

        display: none;

    }

}

</style>

</head>


<body>


<div class="box">


<h1>
ESP-SWITCH5B REMOTE
</h1>


<div class="controller">

Controller:

<?= htmlspecialchars(
    $controller_id,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


<!-- ======================================================
     QR CODE
======================================================= -->

<div id="qrcode"></div>


<!-- ======================================================
     CONTROLLER URL
======================================================= -->

<div class="url">

<a
    href="<?= htmlspecialchars(
        $controller_url,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    target="_blank"
>
<?= htmlspecialchars(
    $controller_url,
    ENT_QUOTES,
    "UTF-8"
) ?>
</a>

</div>


<!-- ======================================================
     BUTTONS
======================================================= -->

<div class="buttons">


<!-- DOWNLOAD -->

<button
    type="button"
    class="download-button"
    onclick="downloadQR()"
>
DOWNLOAD QR CODE
</button>


<!-- PRINT -->

<button
    type="button"
    class="print-button"
    onclick="window.print()"
>
PRINT QR CODE
</button>


<!-- OPEN CONTROLLER -->

<a
    class="open-button"
    href="<?= htmlspecialchars(
        $controller_url,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    target="_blank"
>
OPEN CONTROLLER
</a>


</div>


</div>


<!-- ======================================================
     QR CODE LIBRARY
======================================================= -->

<script
src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
</script>


<script>

/* =========================================================
   CONTROLLER URL
========================================================= */

const controllerURL =
    <?= json_encode($controller_url) ?>;


const controllerID =
    <?= json_encode($controller_id) ?>;


/* =========================================================
   GENERATE QR CODE
========================================================= */

const qrContainer =
    document.getElementById("qrcode");


const qr =
    new QRCode(

        qrContainer,

        {

            text: controllerURL,

            width: 300,

            height: 300,

            correctLevel:
                QRCode.CorrectLevel.H

        }

    );


/* =========================================================
   DOWNLOAD QR CODE
========================================================= */

function downloadQR()
{

    const canvas =
        qrContainer.querySelector("canvas");


    if (canvas)
    {

        const link =
            document.createElement("a");


        link.download =
            controllerID + "_QR.png";


        link.href =
            canvas.toDataURL("image/png");


        document.body.appendChild(link);


        link.click();


        document.body.removeChild(link);


        return;

    }


    const image =
        qrContainer.querySelector("img");


    if (image)
    {

        const canvas =
            document.createElement("canvas");


        canvas.width = 300;

        canvas.height = 300;


        const context =
            canvas.getContext("2d");


        context.drawImage(

            image,

            0,

            0,

            300,

            300

        );


        const link =
            document.createElement("a");


        link.download =
            controllerID + "_QR.png";


        link.href =
            canvas.toDataURL("image/png");


        document.body.appendChild(link);


        link.click();


        document.body.removeChild(link);


        return;

    }


    alert(
        "QR code is not ready. Please wait and try again."
    );

}

</script>


</body>

</html>
```
