<div x-show="showRejectModal" x-cloak class="modal p-4 z-[60]" :class="{ 'modal-open': showRejectModal }" @keydown.escape.window="closeRejectModal()">
    <div class="modal-box w-11/12 max-w-sm p-0 bg-white rounded-2xl shadow-2xl transform transition-all"
         x-show="showRejectModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @click.stop>

        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                <i class="w-10 h-10 text-red-500 fa-icon fa-solid fa-xmark text-4xl leading-none"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Booking Rejected</h2>
            <p class="text-gray-500 text-sm mb-6">The booking request has been rejected.</p>
            <button @click="closeRejectModal()"
                    class="w-full px-4 py-3 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-medium transition-all">
                Done
            </button>
        </div>
    </div>
    <div class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity"></div>
</div>
