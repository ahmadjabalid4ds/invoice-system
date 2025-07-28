<script>
    'use strict';
    const config = {
        countryCode: "{{$country_code}}",
        sessionId: "{{$session_id}}",
        cardViewId: "card-element",
        style: {
            direction: "rtl",
            cardHeight: 180,
            input: {
                color: "black",
                fontSize: "16px",
                fontFamily: "IBM Plex Sans Arabic",
                inputHeight: "32px",
                inputMargin: "0px",
                borderColor: "c7c7c7",
                borderWidth: "1px",
                borderRadius: "8px",
                boxShadow: "",
                placeHolder: {
                    holderName: "اسم حامل البطاقة",
                    cardNumber: "الرقم",
                    expiryDate: "شهر / سنة",
                    securityCode: "CVV",
                }
            },
            label: {
                display: false,
                color: "black",
                fontSize: "13px",
                fontWeight: "normal",
                fontFamily: "IBM Plex Sans Arabic",
                text: {
                    holderName: "اسم حامل البطاقة",
                    cardNumber: "رقم البطاقة",
                    expiryDate: "تاريخ الانتهاء",
                    securityCode: "رقم الحماية",
                },
            },
            error: {
                borderColor: "red",
                borderRadius: "8px",
                boxShadow: "0px",
            },
        },
    };
    myFatoorah.init(config);

    let btn = document.getElementById("btn")
    btn.addEventListener("click", submit)

    function submit() {
        myFatoorah.submit()
            // On success
            .then(function (response) {
                var sessionId = response.SessionId;
                var cardBrand = response.CardBrand;

                var request = new XMLHttpRequest();
                request.open("POST", "{{route('payment-page', ['id'=>'dsadas'] )}}");
                request.onreadystatechange = function () {
                    if (this.readyState === 4) {
                        if (this.status === 200) {
                            console.log(JSON.parse(this.responseText));
                            location.href = JSON.parse(this.responseText);
                        } else {
                            console.log(this.response);
                            var error_field = document.getElementById("error_message");
                            var error_message = this.responseText;
                            let finalString = error_message.split('"').join('')
                            error_field.innerText = finalString;
                            error_field.parentElement.style.display = 'block';
                        }

                    }
                };
                var data = new FormData();
                data.append('_token', '{{csrf_token()}}')
                data.append('sessionId', sessionId);
                data.append('cardBrand', cardBrand);
                request.send(data);
            })
            // In case of errors
            .catch(function (error) {
                var error_field = document.getElementById("error_message");
                error_field.innerText = error;
                error_field.parentElement.style.display = 'block';
                console.log(error);
            });
    }
</script>
