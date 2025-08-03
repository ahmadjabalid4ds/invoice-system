<script>
    var sessionId = "{{$session_id}}";
    var countryCode = "{{$country_code}}";
    var currencyCode = "KWD";
    var amount = "99";

    var config = {
        sessionId: sessionId,
        countryCode: countryCode,
        currencyCode: currencyCode,
        amount: amount,
        callback: payment,
        containerId: "unified-session",
        paymentOptions: ["ApplePay", "GooglePay", "Card"], //"GooglePay", "ApplePay", "Card"
        supportedNetworks: ["visa", "masterCard", "mada", "amex"], //"visa", "masterCard", "mada", "amex"
        language: "ar", //ar en
        settings: {
            applePay: {
                //supportedNetworks: "["visa", "masterCard", "mada"]",
                //containerId: "apple-pay",
                //callback: paymentAP,
                style: {
                    frameHeight: "50px",
                    frameWidth: "100%",
                    button: {
                        height: "40px",
                        type: "pay", //["plain", "buy", "pay", "checkout", "continue", "book", "donate", "subscribe", "reload", "add", "topup", "order", "rent", "support", "contribute", "setup", "tip"]
                        borderRadius: "0px"
                    }
                },
                useCustomButton: false,
                sessionStarted: sessionStarted,
                sessionCanceled: sessionCanceled,
                requiredShippingContactFields: ["postalAddress", "name", "phone", "email"],
                requiredBillingContactFields: ["postalAddress", "name", "phone"]
            },
            googlePay: {
                //supportedNetworks: ["visa", "masterCard"],
                //containerId: "google-pay",
                //callback: paymentGP,
                style: {
                    frameHeight: "50px",
                    frameWidth: "100%",
                    button: {
                        height: "40px",
                        type: "pay", //Accepted texts ["book", "buy", "checkout", "donate", "order", "pay", "plain", "subscribe"]
                        borderRadius: "0px",
                        color: "black",
                        language: "en"
                    }
                }
            },
            card: {
                onCardBinChanged: handleCardBinChanged,
                style: {
                    hideNetworkIcons: false,
                    cardHeight: "180px",
                    tokenHeight: "180px",
                    input: {
                        color: "black",
                        fontSize: "15px",
                        fontFamily: "Times",
                        inputHeight: "32px",
                        inputMargin: "-1px",
                        borderColor: "#000",
                        borderWidth: "1px",
                        borderRadius: "30px",
                        outerRadius: "10px",
                        //boxShadow: "0 0 10px 5px purple, 0 0 15px 10px lightblue"
                        placeHolder: {
                            holderName: "اسم حامل البطاقة",
                            cardNumber: "رقم البطاقة",
                            expiryDate: "تاريخ الانتهاء",
                            securityCode: "رمز الأمان"
                        }
                    },
                    text: {
                        saveCard: "احفظ بيانات البطاقة للمدفوعات المستقبلية",
                        addCard: "استخدم بطاقة أخرى!",
                        deleteAlert: {
                            title: "حذف",
                            message: "هل أنت متأكد؟",
                            confirm: "نعم",
                            cancel: "لا"
                        }
                    },
                    label: {
                        display: false,
                        color: "black",
                        fontSize: "13px",
                        fontWeight: "bold",
                        fontFamily: "Times",
                        text: {
                            holderName: "اسم حامل البطاقة",
                            cardNumber: "رقم البطاقة",
                            expiryDate: "تاريخ الانتهاء",
                            securityCode: "رمز الأمان"
                        }
                    },
                    error: {
                        borderColor: "red",
                        //boxShadow: "0 0 10px 5px purple, 0 0 15px 10px lightblue",
                        borderRadius: "8px"
                    },
                    button: {
                        useCustomButton: false,
                        //onButtonClicked: submit,//You will have to implement this function and call myfatoorah.submitCardPayment()
                        textContent: "ادفع",
                        fontSize: "16px",
                        fontFamily: "Times",
                        color: "white",
                        backgroundColor: "#a147fd",
                        height: "30px",
                        borderRadius: "8px",
                        width: "70%",
                        margin: "0 auto",
                        cursor: "pointer"
                    },
                    separator: {
                        useCustomSeparator: false,
                        textContent: "أدخل بطاقتك",
                        fontSize: "20px",
                        color: "#a147fd",
                        fontFamily: "IBM Plex Sans Arabic",
                        textSpacing: "2px",
                        lineStyle: "dashed",
                        lineColor: "black",
                        lineThickness: "3px"
                    }
                }
            }
        }
    };


    myfatoorah.init(config);

    function payment(response) {

        // Use jQuery to send the invoice token to the backend after payment
        // Make sure jQuery is loaded in your main page (pay.blade.php)

        $.ajax({
            url: '/payment',
            type: 'POST',
            data: {
                invoice_id: '{{ $invoice->id }}',
                session_id: response.sessionId,
                InvoiceValue: '{{ $invoice->total }}',
                CustomerName: 'test test',
                payment_type: response.paymentType,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
              console.log('res',res);
              if (res.redirect_url) {
                window.location.href = res.redirect_url; // Redirects the browser
                }
            },
            error: function(xhr) {
                console.error('❌ AJAX Error:', xhr.status, xhr.responseText);
                alert('حدث خطأ أثناء معالجة الدفع. الرجاء المحاولة مرة أخرى.');
            }
        });


        switch (response.paymentType) {
            case "ApplePay":
                console.log("response >> " + JSON.stringify(response));
                break;
            case "GooglePay":
                console.log("response >> " + JSON.stringify(response));
                break;
            case "Card":
                console.log("response >> " + JSON.stringify(response));
                break;
            default:
                console.log("Unknown payment type");
                break;
        }
        // window.location = 'embedded-payment-sample-code-call-ExecutePayment.php?sessionId=' + sessionId;
    }

    function sessionCanceled() {
        console.log("Failed");
    }

    function sessionStarted() {
        console.log("Start");
    }



    //You need to implement here the handling of the callback for Apple Pay
    // function paymentAP(response) {
    //     //Here you need to pass session id to you backend here
    //     var sessionId = response.sessionId;
    //     var cardBrandAP = response.card.brand;

    //     console.log("SessionID via AP >> ", sessionId);
    //     console.log("cardBrand via AP >> ", cardBrandAP);
    //     console.log("response via AP >> ", response);
    // }

    //You need to implement here the handling of the callback for Google Pay
    // function paymentGP(response) {
    //     //Here you need to pass session id to you backend here
    //     var sessionId = response.sessionId;
    //     var cardBrandGP = response.card.brand;

    //     console.log("SessionID via GP >> ", sessionId);
    //     console.log("cardBrand via GP >> ", cardBrandGP);
    //     console.log("response via GP >> ", response);
    // }

    //Here you implement the function of clicking on the payment button using your own function
    function submit() {
        console.log("Submit");
        myfatoorah.submitCardPayment(); //It is mandatory to call this function
    }

    // //Here you implement the function of clicking on your custom payment button
    // function customSubmit() {
    //     console.log("Custom Submit");
    //     myfatoorah.submitCardPayment(); //It is mandatory to call this function
    // }

    function handleCardBinChanged(response) {
        console.log(response);
    }

    //Here you specify the actions you need to do when customer clicks on your custom Apple Pay button
    function startApplePay() {
        console.log("using custom button");
        myfatoorah.initApplePayPayment(); //It is mandatory to call this function
    }
</script>
