<?php namespace App\Controllers;

use App\Models\OrderModel;

class Payment extends BaseController {

    public function checkout() {
        $model = new OrderModel();
        $hargaAsli = 10000; 

        do {
            $kodeUnik = rand(1, 999);
            $total = $hargaAsli + $kodeUnik;
            $cek = $model->where(['total_bayar' => $total, 'status' => 'pending'])->first();
        } while ($cek);

        // 1. Ambil string statis kamu (Hapus 4 digit terakhir/CRC)
        // String asli: 0002010102122657...63048054
        // Kita potong 4 karakter terakhir (8054)
        $qrisStatis = "00020101021226570011ID.DANA.WWW011893600915067000001002096700000100303UMI51440014ID.CO.QRIS.WWW0215ID10243259765780303UMI5204481453033605802ID5925WARUNG FITRI LOPET CELLUL6015Kab. Pekalongan610551151621960150011ID.DANA.WWW";

        // 2. Siapkan Tag 54 (Nominal)
        $nominal = (string)$total;
        $tag54 = "54" . sprintf("%02d", strlen($nominal)) . $nominal;

        // 3. Gabungkan: String + Tag 54 + Tag 63 (Tanpa CRC)
        $qrisData = $qrisStatis . $tag54 . "6304";

        // 4. Hitung CRC16 secara manual
        $crc = $this->crc16($qrisData);
        $fullQris = $qrisData . $crc;

        $data = [
            'order_id'    => 'ORD-' . strtoupper(bin2hex(random_bytes(3))),
            'produk'      => 'Topup Game',
            'total_bayar' => $total,
            'qris_data'   => $fullQris,
        ];

        $model->save($data);
        return view('v_pembayaran', $data);
    }

    // Fungsi sakti penghitung CRC16 standar QRIS
    private function crc16($data) {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
        }
        return strtoupper(sprintf('%04X', $crc));
    }
    // File: app/Controllers/Payment.php di domain pembayaran

public function pay() {
    // 1. Ambil data dari URL (lemparan dari toko-kelontong)
    $amount = $this->request->getGet('amount');
    $ref    = $this->request->getGet('ref');

    if (!$amount || !$ref) {
        return "Data pembayaran tidak lengkap.";
    }

    $model = new \App\Models\OrderModel();

    // 2. Generate kode unik (agar total_bayar unik di DB pembayaran)
    do {
        $kodeUnik = rand(1, 999);
        $total = $amount + $kodeUnik;
        $cek = $model->where(['total_bayar' => $total, 'status' => 'pending'])->first();
    } while ($cek);

    // 3. Rakit QRIS Dinamis (String statis kamu + Suntik Nominal)
    $qrisRaw = "00020101021226570011ID.DANA.WWW011893600915067000001002096700000100303UMI51440014ID.CO.QRIS.WWW0215ID10243259765780303UMI5204481453033605802ID5925WARUNG FITRI LOPET CELLUL6015Kab. Pekalongan610551151621960150011ID.DANA.WWW";
    
    $nominalTag = (string)$total;
    $tag54 = "54" . sprintf("%02d", strlen($nominalTag)) . $nominalTag;
    $dataTanpaCRC = $qrisRaw . $tag54 . "6304";
    
    // Hitung CRC16 pakai fungsi private yang sudah kamu buat sebelumnya
    $fullQris = $dataTanpaCRC . $this->crc16($dataTanpaCRC);

    // 4. Simpan ke database transaksi pembayaran
    $model->save([
        'order_id'    => $ref, // Ini ID pesanan dari Toko-Kelontong
        'total_bayar' => $total,
        'status'      => 'pending'
    ]);

    // 5. Tampilkan ke view
    return view('v_pembayaran', [
        'order_id'    => $ref,
        'total_bayar' => $total,
        'qris_data'   => $fullQris
    ]);
}
}