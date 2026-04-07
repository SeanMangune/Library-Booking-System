<div x-data="{
        open: false,
        saving: false,
        async saveAsPng() {
            if (this.saving) {
                return;
            }

            this.saving = true;
            try {
                const source = '{{ url('/bookings/qr/smartspace-master-token?format=png') }}';
                const response = await fetch(source, { headers: { 'Accept': 'image/png,image/svg+xml' } });
                if (!response.ok) {
                    throw new Error('Download failed');
                }

                const contentType = (response.headers.get('content-type') || '').toLowerCase();
                let pngBlob;

                if (contentType.includes('image/png')) {
                    pngBlob = await response.blob();
                } else {
                    const svgText = await response.text();
                    const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
                    const svgUrl = URL.createObjectURL(svgBlob);

                    try {
                        const image = await new Promise((resolve, reject) => {
                            const img = new Image();
                            img.onload = () => resolve(img);
                            img.onerror = reject;
                            img.src = svgUrl;
                        });

                        const width = image.naturalWidth || 960;
                        const height = image.naturalHeight || 960;
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        if (!ctx) {
                            throw new Error('Canvas unavailable');
                        }

                        // Keep a white background for better scanner compatibility.
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, width, height);
                        ctx.drawImage(image, 0, 0, width, height);

                        pngBlob = await new Promise((resolve, reject) => {
                            canvas.toBlob((blob) => {
                                if (blob) {
                                    resolve(blob);
                                } else {
                                    reject(new Error('PNG conversion failed'));
                                }
                            }, 'image/png', 1);
                        });
                    } finally {
                        URL.revokeObjectURL(svgUrl);
                    }
                }

                const objectUrl = URL.createObjectURL(pngBlob);
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = 'SmartSpace_Master_QR.png';
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                URL.revokeObjectURL(objectUrl);
            } catch (_) {
                window.open('{{ url('/bookings/qr/smartspace-master-token') }}', '_blank');
            } finally {
                this.saving = false;
            }
        },
    }" 
     @open-master-qr.window="open = true"
     x-show="open" 
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
     @keydown.escape.window="open = false">

    <!-- Backdrop (rendered FIRST so it sits behind the modal box) -->
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-md transition-opacity"
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"></div>

    <!-- Modal Box -->
    <div class="relative z-10 w-full max-w-[380px] bg-white rounded-3xl overflow-hidden shadow-[0_40px_120px_-20px_rgba(30,41,59,0.85)] border border-indigo-100/60"
         @click.stop
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-6"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-6">

        <!-- Header with gradient -->
        <div class="relative bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 px-6 pt-8 pb-6 text-center overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/5 rounded-full blur-sm"></div>
                <div class="absolute -bottom-4 -left-6 w-24 h-24 bg-white/5 rounded-full blur-sm"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-indigo-400/10 rounded-full blur-2xl"></div>
            </div>

            <!-- Close button -->
            <button @click="open = false" 
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center bg-white/15 hover:bg-white/25 rounded-full text-white/80 hover:text-white backdrop-blur-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/30">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Icon -->
            <div class="relative">
                <div class="w-18 h-18 mx-auto bg-white/15 rounded-2xl flex items-center justify-center mb-4 shadow-inner backdrop-blur-sm border border-white/20" style="width: 4.5rem; height: 4.5rem;">
                    <i class="fa-solid fa-qrcode text-3xl text-white drop-shadow-sm"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Master QR</h3>
                <p class="text-indigo-200 text-xs mt-1.5 font-medium tracking-wide uppercase">Admin-Only Access</p>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="px-6 pt-8 pb-3 text-center bg-gradient-to-b from-slate-50 to-white flex flex-col items-center">
            <div class="bg-white p-5 rounded-2xl shadow-md border border-gray-100/80 inline-block ring-1 ring-indigo-50">
                <img src="{{ url('/bookings/qr/smartspace-master-token') }}" 
                     alt="Master QR Code" 
                     class="w-52 h-52 mx-auto" 
                     style="image-rendering: crisp-edges;"
                     onerror="this.parentElement.innerHTML='<div class=\'flex flex-col items-center justify-center w-52 h-52 text-gray-400\'><i class=\'fa-solid fa-triangle-exclamation text-3xl mb-2\'></i><p class=\'text-sm\'>QR unavailable</p></div>'" />
            </div>
        </div>

        <!-- Info & Actions -->
        <div class="px-6 pb-8 text-center bg-white flex flex-col items-center">
            <!-- Security notice -->
            <div class="mt-5 flex items-start gap-2.5 text-left bg-amber-50/80 border border-amber-200/60 rounded-xl px-4 py-3 w-full max-w-[300px]">
                <i class="fa-solid fa-shield-halved text-amber-500 mt-0.5 text-sm shrink-0"></i>
                <p class="text-xs text-amber-700 leading-relaxed font-medium">
                    This QR code <span class="font-bold">never expires</span>. Keep it secure and do not share with unauthorized personnel.
                </p>
            </div>

            <!-- Download button -->
            <button type="button"
               @click="saveAsPng()"
               :disabled="saving"
               class="mt-5 inline-flex items-center justify-center w-full max-w-[300px] px-6 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold transition-all duration-200 shadow-lg shadow-indigo-600/25 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 text-sm disabled:opacity-70 disabled:cursor-not-allowed">
                <i class="fa-solid fa-download mr-2.5"></i>
                <span x-text="saving ? 'Saving PNG...' : 'Save to Device (PNG)'">Save to Device (PNG)</span>
            </button>

            <!-- Print button -->
            <button onclick="window.open('{{ url('/bookings/qr/smartspace-master-token') }}', '_blank')"
                    class="mt-2.5 inline-flex items-center justify-center w-full max-w-[300px] px-6 py-3 rounded-xl bg-white border-2 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 text-gray-700 hover:text-indigo-700 font-semibold transition-all duration-200 active:scale-[0.97] focus:outline-none text-sm">
                <i class="fa-solid fa-up-right-from-square mr-2.5 text-xs"></i>
                Open in New Tab
            </button>
        </div>
    </div>
</div>
