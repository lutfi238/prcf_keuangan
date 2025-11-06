<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        #lottie-animation {
            max-width: 300px;
            width: 100%;
            margin: 0 auto;
            max-height: 250px;
        }
        
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2 py-4">
    <div class="max-w-3xl w-full">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden fade-in">
            <!-- Header (Compact) -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 py-4 px-6 text-center">
                <div class="flex items-center justify-center mb-2">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">PRCF INDONESIA Financial</h1>
                <p class="text-purple-100 text-sm">Financial Management System</p>
            </div>

            <!-- Animation (Compact) -->
            <div class="py-4 px-6">
                <div id="lottie-animation"></div>
            </div>

            <!-- Content (Compact) -->
            <div class="px-6 pb-6 text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    🔧 Sedang Dalam Perbaikan
                </h2>
                <p class="text-gray-600 text-sm mb-4">
                    Kami sedang melakukan <strong>maintenance</strong> dan <strong>update</strong> sistem untuk meningkatkan layanan Anda.
                </p>
                
                <!-- Status Badges
                <div class="flex flex-wrap justify-center gap-2 mb-4">
                    <span class="px-3 py-1.5 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold flex items-center">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2 pulse"></span>
                        Sistem Offline
                    </span>
                    <span class="px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                        ⏱️ Estimasi: 1-2 Jam
                    </span>
                </div> -->

                <!-- Info Cards (Compact)
                <div class="grid md:grid-cols-3 gap-3 mt-4">
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <div class="text-2xl mb-1">🔒</div>
                        <h3 class="font-semibold text-sm text-gray-800 mb-1">Data Aman</h3>
                        <p class="text-xs text-gray-600">Data tetap aman</p>
                    </div>
                    <div class="bg-indigo-50 p-3 rounded-lg">
                        <div class="text-2xl mb-1">⚡</div>
                        <h3 class="font-semibold text-sm text-gray-800 mb-1">Lebih Cepat</h3>
                        <p class="text-xs text-gray-600">Performa optimal</p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <div class="text-2xl mb-1">✨</div>
                        <h3 class="font-semibold text-sm text-gray-800 mb-1">Fitur Baru</h3>
                        <p class="text-xs text-gray-600">Update terbaru</p>
                    </div>
                </div> -->

                <!-- Footer (Compact) -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-gray-500 text-xs">
                        © <?php echo date('Y'); ?> PRCF INDONESIA Financial • 
                        <a href="mailto:pblprcf@gmail.com" class="text-purple-600 hover:text-purple-800 font-semibold">pblprcf@gmail.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load Lottie animation
        const animation = lottie.loadAnimation({
            container: document.getElementById('lottie-animation'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: '../assets/Maintenance web.json'
        });

        // Auto refresh setiap 5 menit untuk cek apakah maintenance selesai
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>

