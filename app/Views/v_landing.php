<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purujekuto Pay — Shopee Partner & QRIS Gateway</title>
    
    <!-- Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
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
            background-color: #050507;
            color: #ededed;
            overflow-x: hidden;
        }
        [v-cloak] { display: none; }
        
        .electric-glow {
            filter: drop-shadow(0 0 16px #38bdf8) drop-shadow(0 0 32px #0284c7);
        }
    </style>

    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-[#050507] text-zinc-100 antialiased selection:bg-zinc-800">

<div id="app" v-cloak class="relative min-h-screen">

    <!-- Navbar Minimalis -->
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-zinc-900/80 bg-[#050507]/85 backdrop-blur-md">
        <div class="max-w-6xl mx-auto flex h-14 items-center justify-between px-6">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded bg-white text-black flex items-center justify-center font-bold text-xs font-mono">
                    P
                </div>
                <span class="text-sm font-semibold tracking-tight text-white">PurujekutoPay</span>
                <span class="text-[11px] font-mono text-zinc-500 border border-zinc-800 px-1.5 py-0.5 rounded">Shopee Partner</span>
            </div>

            <div class="flex items-center gap-4">
                <a href="<?= base_url('checkout') ?>" 
                   class="h-8 px-3.5 rounded-md bg-white hover:bg-zinc-200 text-black text-xs font-medium flex items-center gap-1.5 transition-colors">
                    <span>Demo Checkout</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="pt-28 pb-10 px-6 text-center max-w-4xl mx-auto">
        <div class="inline-flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900/60 px-3 py-1 text-xs text-zinc-400 mb-6 font-mono">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            Merchant Aktif: ZHAR STORE - GADGET (NMID: ID1026637793641)
        </div>

        <h1 class="text-4xl sm:text-6xl font-semibold tracking-tight text-white leading-tight">
            Hubungkan Toko ke Shopee Partner
        </h1>

        <p class="mt-4 text-sm sm:text-base text-zinc-400 max-w-xl mx-auto leading-relaxed">
            API gateway langsung ke akun Shopee Partner tokomu. Generate QRIS dinamis otomatis dan verifikasi mutasi realtime tanpa reload halaman.
        </p>
    </section>

    <!-- ======================================================== -->
    <!-- STICKY SCROLL CONNECTOR (RINGKAS ~2X SCROLL PAS)         -->
    <!-- ======================================================== -->
    <!-- Tinggi diatur 125vh agar cukup sekitar 2x putaran roda scroll -->
    <div ref="trackSection" class="relative h-[125vh] w-full">
        
        <!-- Layar Sticky: Diam di tengah selama 2x scroll -->
        <div class="sticky top-0 h-screen w-full flex flex-col items-center justify-center overflow-hidden pointer-events-none">
            
            <!-- AREA VISUAL KONEKTOR BESAR -->
            <div class="relative w-full flex items-center justify-center overflow-hidden">
                
                <!-- Ambient Glow saat Bertemu -->
                <div class="absolute w-[450px] h-[450px] rounded-full blur-3xl pointer-events-none transition-all duration-300"
                     :class="isConnected ? 'bg-sky-500/25 scale-125' : (isSparking ? 'bg-sky-400/20 scale-100' : 'bg-transparent scale-50')">
                </div>

                <!-- ============================================== -->
                <!-- KONEKTOR KIRI: DARI PINGGIR KIRI MENUJU TENGAH -->
                <!-- ============================================== -->
                <div class="flex items-center transition-transform duration-100 ease-out will-change-transform z-10"
                     :style="{ transform: `translateX(${-leftDistance}px)` }">
                    
                    <!-- Kabel Kiri -->
                    <div class="w-48 sm:w-80 md:w-[500px] h-7 sm:h-8 bg-gradient-to-r from-transparent via-zinc-800 to-zinc-750 shadow-inner relative border-y border-zinc-800">
                        <div class="absolute -top-7 left-10 text-[11px] font-mono text-zinc-500 tracking-wider flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                            <span>SERVER TOKO ANDA</span>
                        </div>
                    </div>

                    <!-- Bodi Steker Kiri -->
                    <div class="relative w-32 sm:w-44 h-22 sm:h-28 bg-gradient-to-b from-zinc-700 via-zinc-800 to-zinc-950 rounded-l-2xl border-l border-y border-zinc-600 shadow-2xl flex items-center justify-end px-3">
                        <div class="flex gap-2 mr-3 opacity-40">
                            <div class="w-1.5 h-12 bg-zinc-500 rounded"></div>
                            <div class="w-1.5 h-12 bg-zinc-500 rounded"></div>
                        </div>

                        <!-- Pin Logam (Colokan) -->
                        <div class="absolute -right-9 sm:-right-12 flex flex-col gap-4 z-0">
                            <div class="w-9 sm:w-12 h-3.5 sm:h-4 bg-gradient-to-r from-zinc-300 via-zinc-100 to-white rounded-r border-r border-zinc-400 shadow-md"
                                 :class="isSparking ? 'electric-glow' : ''"></div>
                            <div class="w-9 sm:w-12 h-3.5 sm:h-4 bg-gradient-to-r from-zinc-300 via-zinc-100 to-white rounded-r border-r border-zinc-400 shadow-md"
                                 :class="isSparking ? 'electric-glow' : ''"></div>
                        </div>
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- EFEK SPARK & CHECKMARK SUKSES DI TITIK TEMU    -->
                <!-- ============================================== -->
                <div class="absolute z-30 flex items-center justify-center pointer-events-none"
                     v-if="isSparking || isConnected">
                    
                    <!-- Shockwave Radial -->
                    <div v-if="isConnected" class="w-40 h-40 rounded-full border-2 border-sky-400/50 animate-ping"></div>

                    <!-- Sinyal Listrik Spark saat Hampir Bersentuhan -->
                    <svg v-if="isSparking && !isConnected" class="w-16 h-16 text-sky-400 electric-glow animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>

                    <!-- Checkmark Bulat Biru Neon saat Terkunci Sempurna -->
                    <div v-if="isConnected" 
                         class="absolute -top-16 sm:-top-20 w-12 h-12 rounded-full bg-sky-500/20 border border-sky-400 flex items-center justify-center text-sky-300 shadow-xl shadow-sky-500/30 animate-bounce">
                        <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- KONEKTOR KANAN: DARI PINGGIR KANAN MENUJU TENGAH -->
                <!-- ============================================== -->
                <div class="flex items-center transition-transform duration-100 ease-out will-change-transform z-10"
                     :style="{ transform: `translateX(${rightDistance}px)` }">
                    
                    <!-- Bodi Soket Kanan -->
                    <div class="relative w-32 sm:w-44 h-22 sm:h-28 bg-gradient-to-b from-zinc-700 via-zinc-800 to-zinc-950 rounded-r-2xl border-r border-y border-zinc-600 shadow-2xl flex items-center justify-start px-3">
                        <div class="w-4 h-14 bg-black rounded-l border-l border-zinc-900 shadow-inner mr-3"></div>
                        <div class="flex gap-2 ml-1 opacity-40">
                            <div class="w-1.5 h-12 bg-zinc-500 rounded"></div>
                            <div class="w-1.5 h-12 bg-zinc-500 rounded"></div>
                        </div>
                    </div>

                    <!-- Kabel Kanan -->
                    <div class="w-48 sm:w-80 md:w-[500px] h-7 sm:h-8 bg-gradient-to-l from-transparent via-orange-600/80 to-zinc-750 shadow-inner relative border-y border-zinc-800">
                        <div class="absolute -top-7 right-10 text-[11px] font-mono text-orange-400 tracking-wider flex items-center gap-1.5">
                            <span>SHOPEE PARTNER API</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Status Terhubung Ringkas Bawah -->
            <div class="mt-10 text-center text-xs font-mono text-zinc-500">
                <span v-if="isConnected" class="text-emerald-400 font-semibold tracking-wider">
                    ● TERHUBUNG SEMPURNA
                </span>
                <span v-else class="text-zinc-500 flex items-center justify-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-500 animate-pulse"></span>
                    Scroll untuk menyambungkan
                </span>
            </div>

        </div>

    </div>

    <!-- ============================================== -->
    <!-- INFO OUTLET & DEMO CHECKOUT                    -->
    <!-- ============================================== -->
    <section class="py-20 px-6 border-t border-zinc-900 bg-black relative z-20">
        <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
            
            <div class="space-y-4">
                <div class="text-xs font-mono text-zinc-500 uppercase tracking-wider">Shopee Partner Merchant</div>
                <h2 class="text-3xl font-semibold tracking-tight text-white">
                    Siap Digunakan untuk ZHAR STORE - GADGET
                </h2>
                <p class="text-sm text-zinc-400 leading-relaxed">
                    Sistem telah terhubung dengan string QRIS resmi toko Anda. Setiap ada checkout dari pembeli, kode QR dinamis dengan nominal pas otomatis digenerate dan diverifikasi tanpa refresh halaman.
                </p>

                <div class="pt-2 flex items-center gap-3">
                    <a href="<?= base_url('checkout') ?>" 
                       class="inline-flex items-center gap-2 h-10 px-5 rounded-md bg-white hover:bg-zinc-200 text-black text-xs font-semibold transition-all">
                        <span>Buka Halaman Checkout</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Terminal Info Data Toko -->
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-6 font-mono text-xs text-zinc-300 space-y-3 shadow-2xl">
                <div class="text-zinc-500 text-[11px] pb-2 border-b border-zinc-900 flex justify-between">
                    <span>OUTLET DATA</span>
                    <span class="text-emerald-400">ACTIVE</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-900/60">
                    <span class="text-zinc-500">Merchant:</span>
                    <span class="text-white font-medium">ZHAR STORE - GADGET</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-900/60">
                    <span class="text-zinc-500">NMID:</span>
                    <span class="text-zinc-300">ID1026637793641</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-900/60">
                    <span class="text-zinc-500">ShopeePay ID:</span>
                    <span class="text-orange-400">24157627</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-900/60">
                    <span class="text-zinc-500">Kota:</span>
                    <span class="text-zinc-300">PEKALONGAN</span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-zinc-500">Mode QRIS:</span>
                    <span class="text-emerald-400">Dinamis (Auto) & Statis (Manual)</span>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 border-t border-zinc-900 text-center text-xs text-zinc-600 font-mono">
        PurujekutoPay &copy; <?= date('Y') ?> — ZHAR STORE - GADGET Shopee Partner Gateway
    </footer>

</div>

<script>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const trackSection = ref(null);
            const progress = ref(0);

            // Jarak maksimal steker di pinggir layar
            const maxDistance = 350;

            const leftDistance = computed(() => {
                const remaining = (100 - progress.value) / 100;
                return Math.max(remaining * maxDistance, 0);
            });

            const rightDistance = computed(() => {
                const remaining = (100 - progress.value) / 100;
                return Math.max(remaining * maxDistance, 0);
            });

            const isSparking = computed(() => progress.value >= 70 && progress.value < 95);
            const isConnected = computed(() => progress.value >= 95);

            // Scroll pas dan responsif (~2x scroll langsung selesai dan lanjut)
            const handleScroll = () => {
                if (!trackSection.value) return;

                const rect = trackSection.value.getBoundingClientRect();
                const scrollableDistance = rect.height - window.innerHeight;

                if (scrollableDistance <= 0) return;

                const currentScroll = -rect.top;
                const ratio = Math.min(Math.max(currentScroll / scrollableDistance, 0), 1);
                
                progress.value = Math.round(ratio * 100);
            };

            onMounted(() => {
                window.addEventListener('scroll', handleScroll, { passive: true });
                handleScroll();
            });

            onUnmounted(() => {
                window.removeEventListener('scroll', handleScroll);
            });

            return {
                trackSection,
                progress,
                leftDistance,
                rightDistance,
                isSparking,
                isConnected
            };
        }
    }).mount('#app');
</script>

</body>
</html>
