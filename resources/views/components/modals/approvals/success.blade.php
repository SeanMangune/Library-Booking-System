<div x-show="showSuccessModal" x-cloak class="modal p-4 z-[60]" :class="{ 'modal-open': showSuccessModal }" @keydown.escape.window="closeSuccessModal()">
    <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col transform transition-all"
         x-show="showSuccessModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.stop>

        <div class="success-header">
            <div class="icon-circle">
                <i class="check-icon fa-icon fa-solid fa-circle-check text-[2.5rem] leading-none"></i>
            </div>

            <h2 class="success-title">Booking Approved!</h2>
            <p class="success-text">The booking has been successfully approved</p>
        </div>

        <style>
        .success-header {
            background: linear-gradient(to right, #22c55e, #059669);
            padding: 2rem 1.5rem;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            text-align: center;
        }

        .icon-circle {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem auto;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .check-icon {
            width: 40px;
            height: 40px;
            color: #ffffff;
        }

        .success-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .success-text {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            color: #d1fae5;
        }
        </style>

        <div class="p-6 flex-1 min-h-0 overflow-y-auto">
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Room</p>
                        <p class="font-semibold text-gray-900" x-text="approvedBooking?.room?.name || approvedBooking?.room_name"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date</p>
                        <p class="font-semibold text-gray-900" x-text="approvedBooking?.formatted_date || approvedBooking?.date"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Time</p>
                        <p class="font-semibold text-gray-900" x-text="approvedBooking?.formatted_time || approvedBooking?.time || 'N/A'"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Booking Code</p>
                        <p class="font-semibold text-purple-600" x-text="approvedBooking?.booking_code || 'Generating...'"></p>
                    </div>
                </div>
            </div>

            <div class="text-center mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Booking QR Code</h3>
                <div class="inline-block p-4 bg-white border-2 border-gray-200 rounded-xl shadow-sm">
                    <template x-if="approvedBooking?.qr_code_url">
                        <img :src="approvedBooking.qr_code_url" alt="Booking QR Code" class="w-48 h-48 mx-auto object-contain" x-on:error="qrImageFailed = true" x-on:load="qrImageFailed = false">
                    </template>
                    <template x-if="!approvedBooking?.qr_code_url || qrImageFailed">
                        <div class="w-48 h-48 flex items-center justify-center bg-gray-100 rounded-lg">
                            <div class="text-center">
                                <i class="w-12 h-12 text-gray-400 mx-auto mb-2 fa-icon fa-solid fa-qrcode text-5xl leading-none"></i>
                                <p class="text-sm text-gray-500">QR Code</p>
                                <p class="text-xs text-gray-400">Not available</p>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-gray-500 mt-3">Scan this QR code to verify the booking</p>
            </div>

            <div class="flex gap-3">
                <button @click="closeSuccessModal()"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all">
                    Done
                </button>
                <template x-if="approvedBooking?.qr_code_url && !qrImageFailed">
                    <button @click="downloadQr(approvedBooking.qr_code_url, `booking-${approvedBooking.qr_token}.png`)"
                            :disabled="isDownloading"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-all flex items-center gap-2 disabled:opacity-50">
                        <i x-show="!isDownloading" class="w-5 h-5 fa-icon fa-solid fa-arrow-up-from-bracket text-xl leading-none"></i>
                        <i x-show="isDownloading" class="animate-spin w-5 h-5 fa-icon fa-solid fa-spinner text-xl leading-none"></i>
                        <span x-text="isDownloading ? 'Saving...' : 'Download'"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity"></div>
</div>
