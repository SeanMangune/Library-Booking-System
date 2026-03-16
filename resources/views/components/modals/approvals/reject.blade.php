<div x-show="showRejectModal" x-cloak class="modal p-4 z-[60]" :class="{ 'modal-open': showRejectModal }" @keydown.escape.window="closeRejectModal()">
    <div class="modal-box w-11/12 max-w-sm p-0 bg-white rounded-2xl shadow-2xl transform transition-all"
         x-show="showRejectModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @click.stop>

        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Booking Rejected</h2>
            <p class="text-gray-500 text-sm mb-6">The booking request has been rejected.</p>
            <button @click="closeRejectModal()"
                    class="w-full px-4 py-3 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-medium transition-all">
                Done
            </button>
        </div>
    </div>
    <div class="modal-backdrop bg-black/30 backdrop-blur-sm transition-opacity"></div>
</div>
