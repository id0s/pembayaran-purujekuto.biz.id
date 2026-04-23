<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bayar via QRIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="p-3 bg-white d-inline-block rounded shadow-sm mb-3">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= $qris_data ?>" alt="QRIS Dinamis">
</div>

<div class="p-3 bg-dark text-white rounded">
    <p class="mb-0 small">Scan QR di atas, harga otomatis pas:</p>
    <h2 class="font-weight-bold text-warning">Rp <?= number_format($total_bayar, 0, ',', '.') ?></h2>
</div>

<script>
    // Cek status otomatis tiap 3 detik
    const checkPayment = setInterval(function() {
        fetch('<?= base_url('payment/check-status/'.$order_id) ?>')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'paid') {
                    clearInterval(checkPayment);
                    document.getElementById('status').innerHTML = "✅ Pembayaran Berhasil! Mengalihkan...";
                    setTimeout(() => window.location.href = '/success-page', 2000);
                }
            });
    }, 3000);
</script>
</body>
</html>