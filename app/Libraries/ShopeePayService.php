<?php

namespace App\Libraries;

use Config\Database;

class ShopeePayService {

    protected $baseUrl;
    protected $clientId;
    protected $secretKey;
    protected $merchantExtId;
    protected $storeExtId;
    protected $isConnected = false;
    protected $settings = [];

    public function __construct() {
        $this->loadSettings();
    }

    /**
     * Muat pengaturan akun merchant dari database atau .env
     */
    public function loadSettings() {
        $db = Database::connect();
        $builder = $db->table('merchant_settings');
        $rows = $builder->get()->getResultArray();

        $this->settings = [];
        foreach ($rows as $row) {
            $this->settings[$row['key_name']] = $row['key_value'];
        }

        $this->baseUrl       = $this->settings['shopee_base_url'] ?? env('SHOPEEPAY_BASE_URL', 'https://api.airpay.co.id');
        $this->clientId      = $this->settings['shopee_client_id'] ?? env('SHOPEEPAY_CLIENT_ID', '');
        $this->secretKey     = $this->settings['shopee_secret_key'] ?? env('SHOPEEPAY_SECRET_KEY', '');
        $this->merchantExtId = $this->settings['shopee_merchant_ext_id'] ?? env('SHOPEEPAY_MERCHANT_EXT_ID', '');
        $this->storeExtId    = $this->settings['shopee_store_ext_id'] ?? env('SHOPEEPAY_STORE_EXT_ID', '');
        $this->isConnected   = !empty($this->clientId) && !empty($this->secretKey);
    }

    public function getSetting(string $key, $default = null) {
        return $this->settings[$key] ?? env($key, $default);
    }

    /**
     * Simpan / Perbarui identitas akun merchant Shopee Partner
     */
    public function saveSettings(array $data) {
        $db = Database::connect();
        $builder = $db->table('merchant_settings');

        $allowedKeys = [
            'shopee_base_url',
            'shopee_client_id',
            'shopee_secret_key',
            'shopee_merchant_ext_id',
            'shopee_store_ext_id',
            'shopee_token',
            'shopee_store_name',
            'shopee_store_id',
            'shopee_merchant_id'
        ];

        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $exists = $builder->where('key_name', $key)->countAllResults();
                if ($exists > 0) {
                    $builder->where('key_name', $key)->update([
                        'key_value'  => trim($data[$key]),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $builder->insert([
                        'key_name'   => $key,
                        'key_value'  => trim($data[$key]),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $this->loadSettings();
        return true;
    }

    /**
     * Ambil info status akun merchant
     */
    public function getSettings() {
        return [
            'base_url'        => $this->baseUrl,
            'client_id'       => $this->clientId,
            'merchant_ext_id' => $this->merchantExtId,
            'store_ext_id'    => $this->storeExtId,
            'has_secret'      => !empty($this->secretKey),
            'is_connected'    => $this->isConnected,
        ];
    }

    /**
     * Create CPM Payment (Customer-Presented Mode)
     * Mengirim request pemotongan saldo setelah kasir/merchant scan kode bayar pembeli (kode 918...)
     * 
     * Endpoint: /v3/merchant-host/transaction/payment/create
     */
    public function createCPMPayment($orderId, $amount, $paymentCode) {
        // ShopeePay IDR diinflasi faktor 100 (contoh Rp 10.000 -> 1000000)
        $amountInflated = (int) ($amount * 100);

        $payload = [
            'request_id'           => 'REQ-' . strtoupper(bin2hex(random_bytes(8))),
            'amount'               => $amountInflated,
            'merchant_ext_id'      => $this->merchantExtId ?: 'MERCHANT-DEFAULT',
            'store_ext_id'         => $this->storeExtId ?: 'STORE-DEFAULT',
            'payment_code'         => trim($paymentCode),
            'payment_reference_id' => $orderId,
            'currency'             => 'IDR',
            'validity_period'      => 120,
        ];

        // Jika kredensial belum diisi atau masih mode sandbox testing
        if (!$this->isConnected) {
            // Mode Simulasi Development jika belum memasukkan Client ID & Secret resmi
            if (str_starts_with($paymentCode, '918') || strlen($paymentCode) >= 16) {
                return [
                    'errcode'   => 0,
                    'debug_msg' => 'Simulasi CPM Sukses: Akun Shopee Partner belum terisi kredensial live, tetapi format kode bayar pembeli (918) valid.',
                    'transaction_list' => [
                        [
                            'transaction_id'       => 'SPP-' . time(),
                            'payment_reference_id' => $orderId,
                            'amount'               => $amountInflated,
                            'status'               => 'SUCCESS'
                        ]
                    ]
                ];
            } else {
                return [
                    'errcode'   => 301,
                    'debug_msg' => 'Invalid payment code: Kode bayar ShopeePay harus 20 digit diawali 918.'
                ];
            }
        }

        // Live API Call ke ShopeePay Host
        $url = rtrim($this->baseUrl, '/') . '/v3/merchant-host/transaction/payment/create';
        $jsonPayload = json_encode($payload);

        // Generate HMAC-SHA256 Signature
        $signature = hash_hmac('sha256', $jsonPayload, $this->secretKey);

        $headers = [
            'Content-Type: application/json',
            'Client-Id: ' . $this->clientId,
            'X-Airpay-ClientId: ' . $this->clientId,
            'X-Signature: ' . $signature,
            'Authorization: Bearer ' . $signature,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'errcode'   => -2,
                'debug_msg' => 'Koneksi ke server ShopeePay gagal: ' . $curlError
            ];
        }

        $resData = json_decode($response, true);
        if (!$resData) {
            return [
                'errcode'   => -1,
                'debug_msg' => 'Respon dari ShopeePay tidak valid (HTTP ' . $httpCode . '): ' . $response
            ];
        }

        return $resData;
    }

    /**
     * Ambil riwayat mutasi transaksi real-time dari ShopeePay Partner Web Portal
     */
    public function getTransactionList(int $limit = 15, int $hoursBack = 48): array {
        $token = $this->getSetting('shopee_token') ?: env('SHOPEEPAY_TOKEN');
        if (empty($token)) {
            return [
                'status'  => 'error',
                'message' => 'Token Shopee Partner belum dikonfigurasi.'
            ];
        }

        $now = time();
        $startTime = $now - ($hoursBack * 3600);
        $endTime = $now + 3600;

        $payload = [
            'data' => [
                'metadata' => [
                    'token'    => $token,
                    'language' => 'id',
                    'timezone' => 'Asia/Jakarta'
                ],
                'pageSize' => $limit,
                'filter' => [
                    'startTime'   => $startTime,
                    'endTime'     => $endTime,
                    'serviceList' => [1, 3]
                ],
                'sorter' => [
                    'field' => 'create_time',
                    'order' => 'descend'
                ],
                'next_position' => ''
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Origin: https://partner.shopee.co.id',
            'Referer: https://partner.shopee.co.id/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'X-Timestamp-Ms: ' . round(microtime(true) * 1000)
        ];

        $url = 'https://shopeepay.shopee.co.id/merchant/v1/partner-web/get-transaction-list';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status'  => 'error',
                'message' => 'Gagal koneksi ke ShopeePay API: ' . $curlError
            ];
        }

        $res = json_decode($response, true);
        if (!$res || !isset($res['code'])) {
            return [
                'status'  => 'error',
                'message' => 'Respon ShopeePay tidak valid: ' . $response
            ];
        }

        if ($res['code'] !== 0) {
            return [
                'status'  => 'error',
                'code'    => $res['code'],
                'message' => $res['msg'] ?? 'Gagal membaca riwayat transaksi.'
            ];
        }

        return [
            'status' => 'success',
            'data'   => $res['data']['list'] ?? []
        ];
    }

    /**
     * Verifikasi otomatis mutasi QRIS Shopee Partner untuk pesanan tertentu
     */
    public function verifyOrderPayment(array $order) {
        $nominalTarget = (string)(int)($order['total_bayar'] ?? 0);
        if ((int)$nominalTarget <= 0) {
            return false;
        }

        try {
            $dt = new \DateTime($order['created_at'] ?? 'now', new \DateTimeZone('Asia/Jakarta'));
            $orderCreatedAt = $dt->getTimestamp();
        } catch (\Throwable $e) {
            $orderCreatedAt = time();
        }

        $res = $this->getTransactionList(15, 24);

        if ($res['status'] !== 'success' || empty($res['data'])) {
            return false;
        }

        foreach ($res['data'] as $trx) {
            $trxStatus = (int)($trx['status'] ?? 0);
            $trxAmount = (string)($trx['amount'] ?? '0');
            $trxTime   = (int)($trx['createTime'] ?? 0);

            // Status 3 = SUCCESS
            if ($trxStatus === 3 && $trxAmount === $nominalTarget) {
                // Toleransi waktu: transaksi terjadi setelah pesanan dibuat (toleransi -300s untuk clock skew)
                if ($trxTime >= ($orderCreatedAt - 300)) {
                    return [
                        'matched'                 => true,
                        'transaction_id'          => $trx['transactionId'] ?? null,
                        'external_transaction_id' => $trx['externalTransactionId'] ?? null,
                        'amount'                  => $trxAmount,
                        'create_time'             => $trxTime,
                        'payment_method'          => $trx['paymentMethodName'] ?? 'QRIS'
                    ];
                }
            }
        }

        return false;
    }
}
