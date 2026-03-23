function payNow(scrim_id) {

    fetch('/api/create_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ scrim_id: scrim_id })
    })
    .then(res => res.json())
    .then(order => {

        if (order.error) {
            alert(order.error);
            return;
        }

        var options = {
            key: "rzp_test_xxxxxxxx",
            amount: order.amount,
            currency: "INR",
            name: "BGMI Scrims",
            description: "Scrim Entry",
            order_id: order.id,

            handler: function (response) {
                verifyPayment(response, scrim_id);
            },

            theme: { color: "#facc15" }
        };

        var rzp = new Razorpay(options);
        rzp.open();
    })
    .catch(() => {
        alert("Server error");
    });
}

function verifyPayment(response, scrim_id) {
    fetch('/api/verify_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            payment_id: response.razorpay_payment_id,
            order_id: response.razorpay_order_id,
            signature: response.razorpay_signature,
            scrim_id: scrim_id
        })
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        location.reload();
    });
}