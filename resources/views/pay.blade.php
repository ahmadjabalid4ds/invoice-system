<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embedded Payment</title>
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous"> --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

   <style>
    html, body {
    max-width: 100vw;
    overflow-x: hidden;
}
body{
    font-family: "IBM Plex Sans Arabic", sans-serif !important;
}
.text-right{
    text-align: right;
}
.alert {
    padding: 5px;
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
    border-radius: 10px;
    margin-bottom: 10px;
    display: none;
  }

  .closebtn {
    margin-left: 15px;
    color: #721c24;
    font-weight: bold;
    float: right;
    font-size: 22px;
    line-height: 20px;
    cursor: pointer;
    transition: 0.3s;
  }

  .closebtn:hover {
    color: black;
  }
.class-1 {
    margin-bottom: 8px;display: flex;flex-direction: column;align-items: center;
}
.class-2 {
    width: 100%;display: inline-block;
}
.w-400px {
    width:100%
}

        </style>
</head>

<body class="class-1">
    <div class="container">


    <div class="row d-flex justify-content-center">
        <div class="col-12 col-md-4 align-items-center">

            {{-- <a href="whatsapp://send?text=Hello World!&phone=+966554727003">Ping me on WhatsApp</a> --}}

<div class="class-2 text-right">
    <h1 class="text-right">ادفع الان</h1>
    <div class="alert">
        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
        <span id="error_message"></span>
    </div>
    <script src="https://demo.myfatoorah.com/payment/v1/session.js"></script>

    {{-- <script src="https://demo.myfatoorah.com/cardview/v1/session.js"></script> --}}
    {{-- <div class="w-400px">
        <div id="card-element"></div>
    </div>
    <div class="d-grid gap-2">
    <button id="btn" class="btn btn-primary " style="background: #a223fd; border:unset">ادفع</button>
    </div> --}}

    <div style=" margin: auto;">
        <div id="unified-session"></div>
    </div>

    <!-- This is a div element can be used as a container for Apple Pay -->
    <div style=" margin: auto;">
        <div id="apple-pay"></div>
        <!-- This is the custom button for Apple Pay. -->
        <!-- <button onclick="startApplePay()" style="display: block; margin: 0 auto; width: 400px; height: 30px; cursor: pointer; background-color: #000000; border: none; color: white; font-size: 16px; border-radius: 8px">Apple Pay</button> -->
    </div>

    <!-- This is a div element can be used as a container for Google Pay -->
    <div style=" margin: 0; position: absolute; top: 0; left: 0;">
        <div id="google-pay"></div>
    </div>

    <!-- This is your custom payment button -->
    <!-- <button onclick="customSubmit()" -->
    <!-- style="display: block; margin: 0 auto; width: 400px; height: 30px; cursor: pointer; background-color: #008CBA; border: none; color: white; font-size: 16px; border-radius: 8px">Pay -->
    <!-- Now</button> -->
</div>
</div>
</div>
</div>
@include('myfatoorah-config')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>

</html>
