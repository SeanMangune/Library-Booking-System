    <div x-show="showSuccessModal" x-cloak class="modal p-4" :class="{ 'modal-open': showSuccessModal }" @keydown.escape.window="closeSuccessModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- <div class="bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-7 rounded-t-2xl text-center">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-3">
                        <i class="w-8 h-8 text-white fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
                    </div>
                    <h2 class="text-lg font-bold text-white"
                        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>
                    <p class="text-emerald-100 text-sm mt-1" x-text="successMessage"></p>
                </div> -->

                <div class="success-header">
    <div class="success-icon-wrap">
        <i class="success-icon fa-icon fa-solid fa-circle-check text-[2rem] leading-none"></i>
    </div>

    <h2 class="success-title"
        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>

    <p class="success-text" x-text="successMessage"></p>
</div>

<style>
/* Header container */
.success-header{
    background: linear-gradient(to right, #0d9488, #059669); /* teal-600 → emerald-600 */
    padding: 1.75rem 1.5rem; /* px-6 py-7 */
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem; /* rounded-t-2xl */
    text-align: center;
}

/* Icon circle */
.success-icon-wrap{
    width: 56px;   /* w-14 */
    height: 56px;  /* h-14 */
    margin: 0 auto 0.75rem auto; /* mx-auto mb-3 */
    background: rgba(255,255,255,0.2); /* bg-white/20 */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Check icon */
.success-icon{
    width: 32px;  /* w-8 */
    height: 32px; /* h-8 */
    color: #ffffff;
}

/* Title */
.success-title{
    font-size: 1.125rem; /* text-lg */
    font-weight: 700;    /* font-bold */
    color: #ffffff;
    margin: 0;
}

/* Subtitle */
.success-text{
    font-size: 0.875rem; /* text-sm */
    margin-top: 0.25rem;
    color: #d1fae5; /* emerald-100 */
}
</style>


                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="bg-gray-50 rounded-xl p-4 mb-5">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Room</p>
                                <p class="font-semibold text-gray-900" x-text="successBooking?.room?.name || selectedRoom?.name || '—'"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Date</p>
                                <p class="font-semibold text-gray-900" x-text="formatDate(successBooking?.date) || bookingForm.date"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Time</p>
                                <p class="font-semibold text-gray-900" x-text="formatTimeRange(successBooking?.start_time, successBooking?.end_time) || (bookingForm.start_time + ' - ' + bookingForm.end_time)"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Status</p>
                                <p class="font-semibold" :class="successBooking?.status === 'approved' ? 'text-emerald-600' : 'text-amber-600'"
                                   x-text="(successBooking?.status || 'pending').toString().charAt(0).toUpperCase() + (successBooking?.status || 'pending').toString().slice(1)"></p>
                            </div>
                        </div>
                    </div>

                    <template x-if="successBooking?.qr_code_url">
                        <div class="text-center mb-5">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">QR Code</h3>
                            <div class="inline-block p-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                                <img :src="successBooking.qr_code_url" alt="Booking QR Code" class="w-40 h-40 mx-auto">
                            </div>
                            <div class="mt-3">
                                <a :href="successBooking.qr_code_url" download
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-arrow-up-from-bracket text-base leading-none"></i>
                                    Download
                                </a>
                            </div>
                        </div>
                    </template>

                    <button @click="closeSuccessModal()"
                            class="w-full px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors">
                        Done
                    </button>
                </div>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="closeSuccessModal()">close</button>
</div>

