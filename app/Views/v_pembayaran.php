<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran ShopeePay / QRIS — <?= esc($merchant_name ?? 'ZHAR STORE - GADGET') ?></title>
    
    <!-- Google Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'sans-serif'],
                        mono: ['Geist Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Geist', sans-serif;
            background-color: #09090b;
            color: #fafafa;
        }
        [v-cloak] { display: none; }
    </style>

    <!-- Vue 3 & QRCode.js -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="min-h-screen bg-black text-zinc-100 antialiased flex items-center justify-center p-4 sm:p-6">

<div id="app" v-cloak class="w-full max-w-md mx-auto">

    <!-- Toast Notification (shadcn style) -->
    <transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0">
        <div v-if="toast.show" 
             class="fixed top-5 right-5 z-50 flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900/95 px-4 py-3 text-xs text-zinc-100 shadow-xl backdrop-blur-md font-mono">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ toast.message }}</span>
        </div>
    </transition>

    <!-- MAIN CARD -->
    <div class="relative rounded-2xl border border-zinc-800 bg-zinc-950 shadow-2xl overflow-hidden transition-all duration-300">
        
        <!-- Header Toko Merchant -->
        <div class="p-6 pb-4 border-b border-zinc-800/80">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500 font-bold text-xs font-mono">
                        SP
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight text-white flex items-center gap-1.5">
                            <?= esc($merchant_name ?? 'ZHAR STORE - GADGET') ?>
                        </h2>
                        <p class="text-[11px] text-zinc-500 font-mono">NMID: <?= esc($nmid ?? 'ID1026637793641') ?></p>
                    </div>
                </div>
                
                <span class="inline-flex items-center gap-1.5 rounded-full border border-orange-500/30 bg-orange-500/10 px-2.5 py-0.5 text-[11px] font-mono text-orange-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-400 animate-pulse"></span>
                    Shopee Partner
                </span>
            </div>

            <!-- Countdown & Order Ref -->
            <div v-if="orderStatus === 'pending'" class="mt-4 flex items-center justify-between text-xs text-zinc-400 bg-zinc-900/50 p-2.5 rounded-lg border border-zinc-800">
                <div class="font-mono">
                    REF: <span class="text-zinc-200 font-medium">{{ orderId }}</span>
                </div>
                <div class="flex items-center gap-1 font-mono font-medium" :class="remainingSeconds < 120 ? 'text-rose-400' : 'text-zinc-300'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <polyline points="12 6 12 12 16 14" stroke-width="2"/>
                    </svg>
                    <span>{{ formattedTimer }}</span>
                </div>
            </div>
        </div>

        <!-- ======================= -->
        <!-- STATE 1: PENDING        -->
        <!-- ======================= -->
        <div v-if="orderStatus === 'pending'" class="p-6 space-y-5">
            
            <!-- FORM INPUT NOMINAL SENDIRI SEBELUM BAYAR -->
            <div class="p-4 rounded-xl bg-zinc-900/70 border border-zinc-800 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-mono text-zinc-400 uppercase tracking-wider">
                        Atur Nominal Bayar:
                    </label>
                    <span v-if="isUpdatingAmount" class="text-[10px] text-orange-400 font-mono animate-pulse">
                        Memperbarui QR...
                    </span>
                </div>

                <!-- Input Nominal Field -->
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-sm font-mono text-zinc-500 font-semibold">Rp</span>
                    <input type="number" 
                           v-model.number="inputAmount" 
                           @input="onAmountChanged"
                           min="100"
                           step="1000"
                           placeholder="Masukkan nominal..." 
                           class="w-full bg-black border border-zinc-700 focus:border-orange-500 rounded-lg pl-10 pr-3 py-2 text-lg font-bold text-white font-mono tracking-tight focus:outline-none">
                </div>

                <!-- Tombol Cepat Nominal -->
                <div class="grid grid-cols-4 gap-1.5 text-[11px] font-mono pt-1">
                    <button v-for="nom in quickNominals" 
                            :key="nom"
                            @click="setQuickAmount(nom)"
                            :class="inputAmount === nom ? 'bg-orange-500 text-white border-orange-400' : 'bg-zinc-950 text-zinc-400 border-zinc-800 hover:text-white'"
                            class="py-1 px-1.5 rounded border transition-colors text-center">
                        {{ formatShort(nom) }}
                    </button>
                </div>
            </div>

            <!-- TAB PILIHAN: QRIS DINAMIS VS QRIS STATIS -->
            <div class="grid grid-cols-2 gap-1 p-1 bg-zinc-900/80 rounded-xl border border-zinc-800 text-xs font-mono">
                <button @click="switchMode('dinamis')" 
                        :class="qrisMode === 'dinamis' ? 'bg-zinc-800 text-white font-semibold shadow' : 'text-zinc-400 hover:text-zinc-200'"
                        class="py-2 rounded-lg transition-all text-center flex items-center justify-center gap-1.5">
                    <span>QRIS Dinamis</span>
                    <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-1 py-0.2 rounded">Auto Pas</span>
                </button>
                <button @click="switchMode('statis')" 
                        :class="qrisMode === 'statis' ? 'bg-zinc-800 text-white font-semibold shadow' : 'text-zinc-400 hover:text-zinc-200'"
                        class="py-2 rounded-lg transition-all text-center flex items-center justify-center gap-1.5">
                    <span>QRIS Statis Toko</span>
                    <span class="text-[9px] bg-zinc-700 text-zinc-300 px-1 py-0.2 rounded">Asli</span>
                </button>
            </div>

            <!-- TAMPILAN QRIS (DINAMIS / STATIS) -->
            <div class="space-y-4">
                <div class="flex flex-col items-center justify-center">
                    <div class="relative p-3.5 bg-white rounded-xl shadow-lg border border-zinc-200">
                        <div id="qrcode" class="flex items-center justify-center"></div>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="bg-white/95 px-2 py-0.5 rounded shadow text-[10px] font-bold text-zinc-800 border border-zinc-200 tracking-wider font-mono">
                                {{ qrisMode === 'dinamis' ? 'QRIS DYNAMIC' : 'QRIS STATIC' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-[11px] text-zinc-400 font-mono">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Scan via DANA, ShopeePay, BCA, Livin' Mandiri, GoPay
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button @click="downloadQr"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900 hover:bg-zinc-800 px-3 py-2 text-xs font-medium text-zinc-200 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Simpan QR
                    </button>
                    <button @click="copyText(activeQrisData, 'String QRIS berhasil disalin!')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900 hover:bg-zinc-800 px-3 py-2 text-xs font-medium text-zinc-200 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" stroke-width="2"/>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" stroke-width="2"/>
                        </svg>
                        Salin Kode QRIS
                    </button>
                </div>
            </div>

            <!-- Testing Simulator -->
            <div class="pt-2 border-t border-zinc-800/50 flex items-center justify-between">
                <span class="text-[10px] text-zinc-500 font-mono">Dev Testing:</span>
                <button @click="simulatePayment" 
                        :disabled="simulating"
                        class="text-[11px] text-zinc-400 hover:text-emerald-400 underline transition-colors disabled:opacity-50">
                    {{ simulating ? 'Memproses...' : 'Simulasi Pembayaran Berhasil' }}
                </button>
            </div>

        </div>

        <!-- ======================= -->
        <!-- STATE 2: SUCCESS / PAID -->
        <!-- ======================= -->
        <div v-else-if="orderStatus === 'paid'" class="p-8 text-center space-y-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <div>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400 mb-2 font-mono">
                    Pembayaran Berhasil!
                </span>
                <h3 class="text-xl font-bold tracking-tight text-white">Transaksi Selesai</h3>
                <p class="text-xs text-zinc-400 mt-1">Pembayaran ke <?= esc($merchant_name ?? 'ZHAR STORE - GADGET') ?> telah diverifikasi secara realtime.</p>
            </div>

            <!-- Receipt Detail -->
            <div class="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 text-left text-xs space-y-2.5 font-mono">
                <div class="flex justify-between text-zinc-400">
                    <span>Merchant</span>
                    <span class="text-zinc-200 font-medium"><?= esc($merchant_name ?? 'ZHAR STORE - GADGET') ?></span>
                </div>
                <div class="flex justify-between text-zinc-400">
                    <span>ID Pesanan</span>
                    <span class="text-zinc-200">{{ orderId }}</span>
                </div>
                <div class="flex justify-between text-zinc-400">
                    <span>Status</span>
                    <span class="text-emerald-400 font-semibold">LUNAS / PAID</span>
                </div>
                <div class="pt-2 border-t border-zinc-800 flex justify-between text-sm font-bold text-white">
                    <span>Total Bayar</span>
                    <span class="text-emerald-400">{{ formatRupiah(inputAmount) }}</span>
                </div>
            </div>

            <button @click="backToHome"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-zinc-100 hover:bg-white text-zinc-900 font-medium px-4 py-2.5 text-xs shadow transition-colors">
                Kembali ke Beranda / Toko
            </button>
        </div>

    </div>

    <div class="mt-4 text-center text-[11px] text-zinc-600 font-mono">
        Dicetak resmi untuk merchant ShopeePay 93600918
    </div>

</div>

<script>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            // Data Resmi ZHAR STORE - GADGET dari QRIS Asli Shopee Partner
            const orderId      = ref('<?= esc($order_id) ?>');
            const inputAmount  = ref(<?= (int)$total_bayar ?>);
            const officialStatic = '00020101021126610016ID.CO.SHOPEE.WWW01189360091800241576270208241576270303UBE51440014ID.CO.QRIS.WWW0215ID10266377936410303UBE5204481253033605802ID5910Zhar store6010PEKALONGAN61055111162070703A0163041111';
            
            const qrisStatis   = ref(officialStatic);
            const qrisDinamis  = ref('<?= addslashes($qris_dinamis) ?>');
            const produk       = ref('<?= esc($produk ?? "Pesanan") ?>');
            
            // Mode QRIS: 'dinamis' atau 'statis'
            const qrisMode     = ref('dinamis');
            const isUpdatingAmount = ref(false);
            const quickNominals = [10000, 20000, 50000, 100000];

            // Hitung CRC16 standar EMVCo
            const calculateCrc16 = (data) => {
                let crc = 0xFFFF;
                for (let i = 0; i < data.length; i++) {
                    let x = ((crc >> 8) ^ data.charCodeAt(i)) & 0xFF;
                    x ^= x >> 4;
                    crc = ((crc << 8) ^ (x << 12) ^ (x << 5) ^ x) & 0xFFFF;
                }
                return (crc & 0xFFFF).toString(16).toUpperCase().padStart(4, '0');
            };

            // Rakit string QRIS dinamis langsung dari QRIS Statis Resmi ZHAR STORE
            const buildClientDynamicQris = (amount) => {
                let base = officialStatic;
                const pos63 = base.lastIndexOf('6304');
                if (pos63 !== -1) base = base.substring(0, pos63);

                // Ubah tipe inisiasi ke dinamis (12)
                base = base.replace('010211', '010212');

                const pos58 = base.indexOf('5802ID');
                let part1 = pos58 !== -1 ? base.substring(0, pos58) : base;
                let part2 = pos58 !== -1 ? base.substring(pos58) : '';

                // Buang tag 54 lama jika ada
                const pos54 = part1.indexOf('54');
                if (pos54 !== -1) {
                    const len54 = parseInt(part1.substring(pos54 + 2, pos54 + 4), 10);
                    part1 = part1.substring(0, pos54) + part1.substring(pos54 + 4 + len54);
                }

                // Tambahkan tag 54 nominal baru
                const amountStr = String(amount);
                const tag54 = '54' + String(amountStr.length).padStart(2, '0') + amountStr;

                const fullWithoutCrc = part1 + tag54 + part2 + '6304';
                return fullWithoutCrc + calculateCrc16(fullWithoutCrc);
            };

            const activeQrisData = computed(() => {
                return qrisMode.value === 'dinamis' ? qrisDinamis.value : qrisStatis.value;
            });

            // Sinkronkan ke server saat nominal diubah
            let debounceTimer = null;
            const syncAmountWithServer = async (amount) => {
                isUpdatingAmount.value = true;
                try {
                    await fetch('<?= base_url('payment/update-amount') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            order_id: orderId.value,
                            amount: amount
                        })
                    });
                } catch (e) {
                    console.warn(e);
                } finally {
                    isUpdatingAmount.value = false;
                }
            };

            const onAmountChanged = () => {
                if (inputAmount.value > 0) {
                    qrisDinamis.value = buildClientDynamicQris(inputAmount.value);
                    renderQr();

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        syncAmountWithServer(inputAmount.value);
                    }, 500);
                }
            };

            const setQuickAmount = (val) => {
                inputAmount.value = val;
                onAmountChanged();
                showToast(`Nominal: Rp ${val.toLocaleString('id-ID')}`);
            };

            // UI State
            const orderStatus  = ref('pending');
            const remainingSeconds = ref(15 * 60);
            const simulating   = ref(false);

            // Toast State
            const toast = ref({ show: false, message: '' });
            const showToast = (msg) => {
                toast.value.message = msg;
                toast.value.show = true;
                setTimeout(() => { toast.value.show = false; }, 2500);
            };

            const copyText = async (text, msg) => {
                try {
                    await navigator.clipboard.writeText(text);
                    showToast(msg);
                } catch (e) {
                    showToast('Teks disalin');
                }
            };

            const formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');
            const formatShort = (val) => (val >= 1000) ? (val / 1000) + 'k' : val;

            const formattedTimer = computed(() => {
                const m = Math.floor(remainingSeconds.value / 60);
                const s = remainingSeconds.value % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            });

            let pollInterval = null;
            let timerInterval = null;

            const renderQr = () => {
                const qrContainer = document.getElementById("qrcode");
                if (qrContainer) {
                    qrContainer.innerHTML = "";
                    new QRCode(qrContainer, {
                        text: activeQrisData.value,
                        width: 220,
                        height: 220,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            };

            const switchMode = (mode) => {
                qrisMode.value = mode;
                renderQr();
                showToast(mode === 'dinamis' ? 'Mode: QRIS Dinamis (Auto Pas)' : 'Mode: QRIS Statis Toko Asli');
            };

            const downloadQr = () => {
                const img = document.querySelector("#qrcode img");
                if (img && img.src) {
                    const a = document.createElement("a");
                    a.href = img.src;
                    a.download = `QRIS-${qrisMode.value}-${orderId.value}.png`;
                    a.click();
                    showToast("QR Code berhasil diunduh");
                }
            };

            // Polling status background tanpa reload
            const checkStatus = async () => {
                if (orderStatus.value !== 'pending') return;
                try {
                    const res = await fetch(`<?= base_url('payment/check-status/') ?>${orderId.value}`);
                    if (!res.ok) return;
                    const data = await res.json();
                    
                    if (data.status === 'paid') {
                        orderStatus.value = 'paid';
                        clearInterval(pollInterval);
                        clearInterval(timerInterval);
                        showToast("Pembayaran Berhasil Dikonfirmasi!");
                    } else if (data.status === 'expired') {
                        orderStatus.value = 'expired';
                        clearInterval(pollInterval);
                        clearInterval(timerInterval);
                    }
                } catch (err) {
                    console.warn("Polling error:", err);
                }
            };

            // Simulasi pembayaran
            const simulatePayment = async () => {
                simulating.value = true;
                try {
                    const res = await fetch(`<?= base_url('payment/simulate-success/') ?>${orderId.value}`, {
                        method: 'POST'
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        orderStatus.value = 'paid';
                        clearInterval(pollInterval);
                        clearInterval(timerInterval);
                        showToast("Simulasi pembayaran berhasil!");
                    }
                } catch (e) {
                    showToast("Gagal simulasi");
                } finally {
                    simulating.value = false;
                }
            };

            const backToHome = () => {
                window.location.href = '<?= base_url('checkout') ?>';
            };

            onMounted(() => {
                // Generate QR awal
                qrisDinamis.value = buildClientDynamicQris(inputAmount.value);
                renderQr();

                pollInterval = setInterval(checkStatus, 3000);
                timerInterval = setInterval(() => {
                    if (remainingSeconds.value > 0) {
                        remainingSeconds.value--;
                    } else {
                        orderStatus.value = 'expired';
                        clearInterval(timerInterval);
                        clearInterval(pollInterval);
                    }
                }, 1000);
            });

            onUnmounted(() => {
                if (pollInterval) clearInterval(pollInterval);
                if (timerInterval) clearInterval(timerInterval);
            });

            return {
                orderId,
                inputAmount,
                quickNominals,
                isUpdatingAmount,
                qrisDinamis,
                qrisStatis,
                qrisMode,
                activeQrisData,
                produk,
                orderStatus,
                remainingSeconds,
                formattedTimer,
                toast,
                simulating,
                onAmountChanged,
                setQuickAmount,
                switchMode,
                copyText,
                formatRupiah,
                formatShort,
                downloadQr,
                simulatePayment,
                backToHome
            };
        }
    }).mount('#app');
</script>

</body>
</html>
