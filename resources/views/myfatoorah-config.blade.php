<script>
    var sessionId = "{{$session_id}}";
    var countryCode = "{{$country_code}}";
    var currencyCode = "KWD";
    var amount = "{{ $invoice->amount ?? '99' }}"; // Use actual invoice amount

    var config = {
        sessionId: sessionId,
        countryCode: countryCode,
        currencyCode: currencyCode,
        amount: amount,
        callback: payment,
        containerId: "unified-session",
        paymentOptions: ["ApplePay", "GooglePay", "Card"],
        supportedNetworks: ["visa", "masterCard", "mada", "amex"],
        language: "ar",
        settings: {
            applePay: {
                style: {
                    frameHeight: "50px",
                    frameWidth: "100%",
                    button: {
                        height: "40px",
                        type: "pay",
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
                style: {
                    frameHeight: "50px",
                    frameWidth: "100%",
                    button: {
                        height: "40px",
                        type: "pay",
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
                        borderRadius: "8px"
                    },
                    button: {
                        useCustomButton: false,
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

    // Initialize MyFatoorah
    myfatoorah.init(config);

    function payment(response) {
        console.log('Payment response received:', response);

        // Show loading state
        showLoadingState();

        $.ajax({
            url: '/payment',
            type: 'POST',
            data: {
                invoice_id: '{{ $invoice->id }}',
                session_id: response.sessionId,
                payment_type: response.paymentType,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                hideLoadingState();

                if (res.success) {
                    // Show success message
                    showSuccessMessage(res.message);

                    // Redirect if provided
                    if (res.redirect_url) {
                        setTimeout(() => {
                            window.location.href = res.redirect_url;
                        }, 2000);
                    }
                } else {
                    showErrorMessage(res.message || 'حدث خطأ أثناء معالجة الدفع');
                }
            },
            error: function(xhr) {
                hideLoadingState();
                console.error('Payment processing error:', xhr);

                let errorMessage = 'حدث خطأ أثناء معالجة الدفع. الرجاء المحاولة مرة أخرى.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showErrorMessage(errorMessage);
            }
        });

        // Log payment type specific information
        switch (response.paymentType) {
            case "ApplePay":
                console.log("Apple Pay payment completed:", response);
                break;
            case "GooglePay":
                console.log("Google Pay payment completed:", response);
                break;
            case "Card":
                console.log("Card payment completed:", response);
                break;
            default:
                console.log("Unknown payment type:", response.paymentType);
                break;
        }
    }

    function sessionCanceled() {
        console.log("Payment session canceled");
        showErrorMessage('تم إلغاء عملية الدفع');
    }

    function sessionStarted() {
        console.log("Payment session started");
    }

    function handleCardBinChanged(response) {
        console.log('Card BIN changed:', response);
    }

    function submit() {
        console.log("Manual submit triggered");
        myfatoorah.submitCardPayment();
    }

    function startApplePay() {
        console.log("Custom Apple Pay button clicked");
        myfatoorah.initApplePayPayment();
    }

    // Helper functions for UI feedback
    function showLoadingState() {
        // Add loading spinner or disable form
        $('#unified-session').append('<div id="payment-loading" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div>جاري معالجة الدفع...</div></div>');
    }

    function hideLoadingState() {
        $('#payment-loading').remove();
    }

    function showSuccessMessage(message) {
        // You can customize this based on your UI framework
        alert('✅ ' + message);
    }

    function showErrorMessage(message) {
        // You can customize this based on your UI framework
        alert('❌ ' + message);
    }
</script>
