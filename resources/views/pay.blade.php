<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embedded Payment</title>
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
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .class-2 {
            width: 100%;
            display: inline-block;
        }
        .w-400px {
            width:100%
        }

        .invoice-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .invoice-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .invoice-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
            color: #a223fd;
        }

        .invoice-label {
            font-weight: 500;
        }

        .invoice-value {
            font-weight: 600;
        }
    </style>
</head>

<body class="class-1">
<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-12 col-md-4 align-items-center">
            <div class="class-2 text-right">
                <h1 class="text-right">ادفع الان</h1>

                <!-- Invoice Summary -->
                <div class="invoice-summary text-right">
                    <h5 class="mb-3">ملخص الفاتورة</h5>

                    @php
                        // Calculate proper totals
                        $vatRate = 0.15; // 15% VAT rate in Saudi Arabia
                        $totalWithVat = $invoice->total;
                        $shipping = $invoice->shipping;
                        $discount = $invoice->discount;

                        // Calculate subtotal without VAT
                        $subtotalWithShipping = $totalWithVat / (1 + $vatRate);
                        $subtotal = $subtotalWithShipping - $shipping;
                        $vatAmount = $totalWithVat - $subtotalWithShipping;
                    @endphp

                    <div class="invoice-row">
                        <span class="invoice-label">المبلغ الفرعي:</span>
                        <span class="invoice-value">{{ number_format($subtotal, 2) }} ريال</span>
                    </div>

                    @if($shipping > 0)
                        <div class="invoice-row">
                            <span class="invoice-label">الشحن:</span>
                            <span class="invoice-value">{{ number_format($shipping, 2) }} ريال</span>
                        </div>
                    @endif

                    @if($discount > 0)
                        <div class="invoice-row">
                            <span class="invoice-label">الخصم:</span>
                            <span class="invoice-value">-{{ number_format($discount, 2) }} ريال</span>
                        </div>
                    @endif

                    <div class="invoice-row">
                        <span class="invoice-label">ضريبة القيمة المضافة (15%):</span>
                        <span class="invoice-value">{{ number_format($vatAmount, 2) }} ريال</span>
                    </div>

                    <div class="invoice-row">
                        <span class="invoice-label">المجموع الكلي:</span>
                        <span class="invoice-value">{{ number_format($totalWithVat, 2) }} ريال</span>
                    </div>
                </div>

                <div class="alert">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <span id="error_message"></span>
                </div>

                <script src="https://demo.myfatoorah.com/payment/v1/session.js"></script>

                <div style="margin: auto;">
                    <div id="unified-session"></div>
                </div>

                <!-- Apple Pay container -->
                <div style="margin: auto;">
                    <div id="apple-pay"></div>
                </div>

                <!-- Google Pay container -->
                <div style="margin: 0; position: absolute; top: 0; left: 0;">
                    <div id="google-pay"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('myfatoorah-config')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>
</html>
