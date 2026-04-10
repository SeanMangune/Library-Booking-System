<div x-show="showCancelModal" x-cloak class="modal modal-bottom sm:modal-middle p-4" :class="{ 'modal-open': showCancelModal }" @keydown.escape.window="closeCancelModal()">
    <div class="modal-box w-full max-w-lg p-0 bg-white rounded-2xl shadow-2xl overflow-hidden"
         @click.stop
         x-show="showCancelModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Cancel booking?
            </h3>
            <p class="text-red-100 text-sm mt-1">Please provide a reason before cancelling this booking.</p>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label for="cancel-reason" class="block text-sm font-semibold text-gray-700 mb-1">Cancellation reason <span class="text-red-600">*</span></label>
                <textarea id="cancel-reason"
                          x-model="cancelReason"
                          rows="4"
                          placeholder="Enter your reason for cancellation"
                          class="textarea textarea-bordered w-full rounded-lg"></textarea>
            </div>

            <p x-show="cancelError" x-text="cancelError" class="text-sm text-red-600"></p>

            <div class="modal-action mt-1">
                <button type="button"
                        @click="closeCancelModal()"
                        class="btn btn-ghost">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                    Keep Booking
                </button>
                <button type="button"
                        @click="confirmCancelBooking()"
                        :disabled="isCancelling"
                        class="btn btn-error text-white disabled:opacity-60 disabled:cursor-not-allowed">
                    <template x-if="!isCancelling">
                        <span class="inline-flex items-center gap-2">
                            <i class="fa-solid fa-xmark"></i>
                            Cancel Booking
                        </span>
                    </template>
                    <template x-if="isCancelling">
                        <span class="inline-flex items-center gap-2">
                            <span class="loading loading-spinner loading-xs"></span>
                            Cancelling...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="modal-backdrop" @click="closeCancelModal()">close</button>
</div>
