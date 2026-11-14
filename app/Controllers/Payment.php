<?php namespace App\Controllers;

use App\Models\OrderModel;
use App\Libraries\ShopeePayService;

class Payment extends BaseController {

    protected $shopeeService;

    // QRIS Statis Resmi Shopee Partner Toko ZHAR STORE - GADGET (NMID: ID1026637793641)
    private string $officialStaticQris = "00020101021126610016ID.CO.SHOPEE.WWW01189360091800241576270208241576270303UBE51440014ID.CO.QRIS.WWW0215ID10266377936410303UBE5204481253033605802ID5910Zhar store6010PEKALONGAN61055111162070703A0163041111";

    public function __construct() {
        $this->shopeeService = new ShopeePayService();
    }

    /**
     * Checkout demo / standalone
     */
    public function checkout() {
        $model = new OrderModel();
        
        $customAmount = (int) $this->request->getGet('amount');
        $total = ($customAmount > 0) ? $customAmount : 10000;

        $orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(3)));

        // Generate QRIS Dinamis dari QRIS Statis Resmi Toko Zhar Store
        $dynamicQris = $this->generateDynamicQris($this->officialStaticQris, $total);

        $data = [
            'order_id'    => $orderId,
            'produk'      => 'Topup / Pesanan Toko',
            'total_bayar' => $total,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $model->save($data);

        return view('v_pembayaran', [
            'order_id'      => $orderId,
            'merchant_name' => 'ZHAR STORE - GADGET',
            'nmid'          => 'ID1026637793641',
            'kota'          => 'PEKALONGAN',
            'produk'        => $data['produk'],
            'total_bayar'   => $total,
            'qris_data'     => $dynamicQris,
            'qris_dinamis'  => $dynamicQris,
            'qris_statis'   => $this->officialStaticQris,
            'created_at'    => $data['created_at'],
            'merchant_info' => $this->shopeeService->getSettings(),
        ]);
    }

    /**
     * Endpoint pay dipanggil dari toko eksternal / toko-kelontong
     */
    public function pay() {
        $amount = (int) $this->request->getGet('amount');
        $ref    = $this->request->getGet('ref') ?? ('ORD-' . strtoupper(bin2hex(random_bytes(3))));
        $produk = $this->request->getGet('produk') ?? 'Pembayaran Toko';

        $total = ($amount > 0) ? $amount : 10000;

        $model = new OrderModel();

        // Generate QRIS Dinamis dari QRIS Statis Resmi Toko Zhar Store
        $dynamicQris = $this->generateDynamicQris($this->officialStaticQris, $total);

        $dataOrder = [
            'order_id'    => $ref,
            'produk'      => $produk,
            'total_bayar' => $total,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $model->save($dataOrder);

        return view('v_pembayaran', [
            'order_id'      => $ref,
            'merchant_name' => 'ZHAR STORE - GADGET',
            'nmid'          => 'ID1026637793641',
            'kota'          => 'PEKALONGAN',
            'produk'        => $produk,
            'total_bayar'   => $total,
            'qris_data'     => $dynamicQris,
            'qris_dinamis'  => $dynamicQris,
            'qris_statis'   => $this->officialStaticQris,
            'created_at'    => $dataOrder['created_at'],
            'merchant_info' => $this->shopeeService->getSettings(),
        ]);
    }

    /**
     * Update Nominal Pembayaran oleh Pengguna Sebelum Bayar
     */
    public function updateAmount() {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $orderId = $json['order_id'] ?? null;
        $amount  = (int) ($json['amount'] ?? 0);

        if (!$orderId || $amount <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Parameter order_id dan amount valid wajib diisi.'
            ])->setStatusCode(400);
        }

        $model = new OrderModel();
        $order = $model->where('order_id', $orderId)->first();

        if (!$order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Pesanan tidak ditemukan.'
            ])->setStatusCode(404);
        }

        $model->update($order['id'], [
            'total_bayar' => $amount,
            'updated_at'  => date('Y-m-d H:i:s')
        ]);

        $newDynamicQris = $this->generateDynamicQris($this->officialStaticQris, $amount);

        return $this->response->setJSON([
            'status'       => 'success',
            'message'      => 'Nominal berhasil diperbarui.',
            'order_id'     => $orderId,
            'total_bayar'  => $amount,
            'qris_dinamis' => $newDynamicQris,
        ]);
    }

    /**
     * Inject Nominal Dinamis ke String QRIS Statis Resmi Shopee Partner
     */
    private function generateDynamicQris(string $staticQris, int $amount): string {
        // 1. Buang tag 6304...
        $pos63 = strrpos($staticQris, '6304');
        $base = ($pos63 !== false) ? substr($staticQris, 0, $pos63) : $staticQris;

        // 2. Ganti tipe inisiasi 010211 (Statis) menjadi 010212 (Dinamis)
        $base = str_replace('010211', '010212', $base);

        // 3. Pisahkan di Tag 58 (Country Code ID)
        $pos58 = strpos($base, '5802ID');
        if ($pos58 !== false) {
            $part1 = substr($base, 0, $pos58);
            $part2 = substr($base, $pos58);
        } else {
            $part1 = $base;
            $part2 = '';
        }

        // 4. Hapus Tag 54 lama jika ada
        $pos54 = strpos($part1, '54');
        if ($pos54 !== false) {
            $len54 = (int) substr($part1, $pos54 + 2, 2);
            $part1 = substr($part1, 0, $pos54) . substr($part1, $pos54 + 4 + $len54);
        }

        // 5. Suntik Tag 54 Baru (Nominal)
        $nominalStr = (string) $amount;
        $tag54 = '54' . sprintf('%02d', strlen($nominalStr)) . $nominalStr;

        // 6. Gabungkan tanpa CRC
        $dataTanpaCrc = $part1 . $tag54 . $part2 . '6304';

        // 7. Hitung CRC16 standar EMVCo
        $crc = $this->crc16($dataTanpaCrc);

        return $dataTanpaCrc . $crc;
    }

    /**
     * Hitung CRC16 standar QRIS (CCITT-FALSE)
     */
    private function crc16(string $data): string {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
        }
        return strtoupper(sprintf('%04X', $crc));
    }

    /**
     * Endpoint API ShopeePay CPM (Scan Barcode/QR Pembeli kode 918...)
     */
    public function processCPM() {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $orderId     = $json['order_id'] ?? null;
        $paymentCode = $json['payment_code'] ?? null;

        if (!$orderId || !$paymentCode) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Parameter order_id dan payment_code wajib diisi.'
            ])->setStatusCode(400);
        }

        $model = new OrderModel();
        $order = $model->where('order_id', $orderId)->first();

        if (!$order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Pesanan tidak ditemukan.'
            ])->setStatusCode(404);
        }

        $result = $this->shopeeService->createCPMPayment($orderId, $order['total_bayar'], $paymentCode);

        if (isset($result['errcode']) && $result['errcode'] === 0) {
            $model->update($order['id'], [
                'status'     => 'paid',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Pembayaran ShopeePay CPM Berhasil!',
                'data'    => $result
            ]);
        }

        return $this->response->setJSON([
            'status'    => 'error',
            'errcode'   => $result['errcode'] ?? -1,
            'message'   => $result['debug_msg'] ?? 'Gagal memproses pembayaran ShopeePay.',
            'raw'       => $result
        ])->setStatusCode(400);
    }

    /**
     * Endpoint Simpan / Login Pengaturan Akun Shopee Partner Merchant
     */
    public function saveMerchantAccount() {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        $this->shopeeService->saveSettings([
            'shopee_merchant_ext_id' => $json['merchant_ext_id'] ?? '',
            'shopee_store_ext_id'    => $json['store_ext_id'] ?? '',
            'shopee_client_id'       => $json['client_id'] ?? '',
            'shopee_secret_key'      => $json['secret_key'] ?? '',
            'shopee_base_url'        => $json['base_url'] ?? 'https://api.airpay.co.id',
        ]);

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Kredensial Shopee Partner berhasil disimpan.',
            'settings' => $this->shopeeService->getSettings()
        ]);
    }

    /**
     * Endpoint API status transaksi (polling tanpa reload)
     * Otomatis cek mutasi real-time ShopeePay Partner tanpa MacroDroid / Ngrok!
     */
    public function checkStatus($orderId = null) {
        if (!$orderId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Order ID required']);
        }

        $model = new OrderModel();
        $order = $model->where('order_id', $orderId)->first();

        if (!$order) {
            return $this->response->setJSON(['status' => 'not_found', 'message' => 'Pesanan tidak ditemukan']);
        }

        // Jika status masih pending, otomatis cek mutasi real-time dari Shopee Partner
        if ($order['status'] !== 'paid') {
            try {
                $verified = $this->shopeeService->verifyOrderPayment($order);
                if ($verified && !empty($verified['matched'])) {
                    $model->update($order['id'], [
                        'status'     => 'paid',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $order['status'] = 'paid';
                    $order['paid_meta'] = $verified;
                }
            } catch (\Throwable $e) {
                // Abaikan error jaringan sementara agar polling tidak macet
            }
        }

        return $this->response->setJSON([
            'status'      => $order['status'],
            'order_id'    => $order['order_id'],
            'total_bayar' => (int)$order['total_bayar'],
            'produk'      => $order['produk'] ?? 'Pesanan',
            'updated_at'  => $order['updated_at'] ?? null,
            'paid_meta'   => $order['paid_meta'] ?? null,
        ]);
    }

    /**
     * Endpoint API untuk melihat riwayat mutasi ShopeePay live
     */
    public function mutasiList() {
        $result = $this->shopeeService->getTransactionList(20, 72);
        return $this->response->setJSON($result);
    }

    /**
     * Webhook Notifikasi Shopee Partner
     */
    public function receiveNotif() {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $amount  = $json['amount'] ?? $json['total_bayar'] ?? $json['nominal'] ?? null;
        $orderId = $json['order_id'] ?? $json['ref'] ?? $json['payment_reference_id'] ?? null;

        $model = new OrderModel();
        $order = null;

        if ($orderId) {
            $order = $model->where('order_id', $orderId)->first();
        } elseif ($amount) {
            $order = $model->where('total_bayar', (int)$amount)->where('status', 'pending')->first();
        }

        if (!empty($order)) {
            $model->update($order['id'], ['status' => 'paid']);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Pembayaran berhasil dikonfirmasi',
                'data'    => $order
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Pesanan pending tidak ditemukan untuk data ini'
        ])->setStatusCode(404);
    }

    /**
     * Simulasi Pembayaran Sukses (Testing Mode)
     */
    public function simulateSuccess($orderId = null) {
        if (!$orderId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Order ID required']);
        }

        $model = new OrderModel();
        $order = $model->where('order_id', $orderId)->first();

        if (!$order) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Order not found']);
        }

        $model->update($order['id'], ['status' => 'paid']);

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Status berhasil disimulasikan menjadi PAID',
            'order_id' => $orderId
        ]);
    }
}
