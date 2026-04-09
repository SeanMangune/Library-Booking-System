<div x-show="showModal" x-cloak class="modal p-4" :class="{ 'modal-open': showModal }" @keydown.escape.window="closeModal()">
    <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Booking Request Details</h2>
                    <p class="text-purple-200 text-sm">Review and take action</p>
                </div>
                <button @click="closeModal()" class="text-white/80 hover:text-white">
                    <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                </button>
            </div>
        </div>

        <div class="p-6 flex-1 min-h-0 overflow-y-auto">
            <template x-if="selectedBooking?.has_conflict">
                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4">
                    <i class="w-5 h-5 text-red-500 shrink-0 mt-0.5 fa-icon fa-solid fa-triangle-exclamation text-xl leading-none"></i>
                    <div>
                        <p class="text-sm font-medium text-red-800">Scheduling Conflict</p>
                        <p class="text-xs text-red-600 mt-0.5">This booking conflicts with an existing reservation.</p>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-building text-xl leading-none"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Room</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_time || selectedBooking?.time"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Duration</p>
                        <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.duration || 'N/A'"></p>
                    </div>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Requestor Information</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <i class="w-4 h-4 text-purple-500 fa-icon fa-solid fa-user text-base leading-none"></i>
                        <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i class="w-4 h-4 text-purple-500 fa-icon fa-solid fa-envelope text-base leading-none"></i>
                        <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                <div class="flex items-center gap-2">
                    <i class="w-5 h-5 text-purple-500 fa-icon fa-solid fa-users text-xl leading-none"></i>
                    <div>
                        <p class="text-xs text-gray-500">Attendees</p>
                        <p class="text-sm font-semibold" :class="selectedBooking?.exceeds_capacity ? 'text-purple-600' : 'text-gray-900'" x-text="selectedBooking?.attendees + ' people'"></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Room Capacity</p>
                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_capacity + ' people'"></p>
                </div>
            </div>

            <template x-if="selectedBooking?.purpose">
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Purpose</h3>
                    <p class="text-sm text-gray-600" x-text="selectedBooking?.purpose"></p>
                </div>
            </template>

            <template x-if="selectedBooking?.description">
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Description</h3>
                    <p class="text-sm text-gray-600" x-text="selectedBooking?.description"></p>
                </div>
            </template>

            <template x-if="selectedBooking?.status === 'pending'">
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <label class="block text-sm font-semibold text-red-800 mb-2">Rejection reason (required for reject)</label>
                    <textarea x-model="rejectionReason"
                              placeholder="Explain why this booking is being rejected..."
                              class="w-full p-3 border border-red-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-300 resize-none"
                              rows="3"></textarea>
                    <p class="mt-2 text-xs text-red-700">This reason will be included in the user's rejection email and notification.</p>
                </div>
            </template>

            <!-- Show Approve/Reject only if pending -->
            <template x-if="selectedBooking?.status === 'pending'">
                <div class="flex gap-3">
                    <button @click="approveBooking()"
                            :disabled="isLoading"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <i x-show="!isLoading || actionType !== 'approve'" class="w-5 h-5 fa-icon fa-solid fa-circle-check text-xl leading-none"></i>
                        <i x-show="isLoading && actionType === 'approve'" class="animate-spin w-5 h-5 fa-icon fa-solid fa-spinner text-xl leading-none"></i>
                        <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : 'Approve'"></span>
                    </button>
                    <button @click="rejectBooking()"
                            :disabled="isLoading"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50">
                        <i x-show="!isLoading || actionType !== 'reject'" class="w-5 h-5 fa-icon fa-solid fa-circle-xmark text-xl leading-none"></i>
                        <i x-show="isLoading && actionType === 'reject'" class="animate-spin w-5 h-5 fa-icon fa-solid fa-spinner text-xl leading-none"></i>
                        <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                    </button>
                </div>
            </template>

            <!-- If already approved, show details and QR code only -->
            <template x-if="selectedBooking?.status === 'approved'">
                <div class="w-full relative overflow-hidden rounded-2xl border border-purple-200/60 bg-gradient-to-br from-purple-50/80 via-indigo-50/60 to-white p-5"
                     style="animation: approvalQrFade 0.4s ease-out both;">
                    <!-- Decorative glow -->
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-gradient-to-br from-purple-200/30 to-indigo-200/30 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-gradient-to-br from-indigo-200/20 to-purple-200/20 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 text-center">
                        <div class="flex items-center justify-center gap-2 mb-3">
                            <i class="fa-solid fa-qrcode text-purple-600"></i>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Booking QR Code</h3>
                        </div>

                        <div class="inline-block p-3 bg-white rounded-xl shadow-sm border border-gray-100 mb-3">
                            <template x-if="selectedBooking?.qr_code_url || selectedBooking?.qr_token">
                                <img :src="selectedBooking.qr_code_url || `/bookings/qr/${selectedBooking.qr_token}`" alt="Booking QR Code" class="w-44 h-44 mx-auto object-contain">
                            </template>
                            <template x-if="!selectedBooking?.qr_code_url && !selectedBooking?.qr_token">
                                <div class="w-44 h-44 flex items-center justify-center bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <i class="fa-solid fa-qrcode text-gray-300 text-4xl mb-2"></i>
                                        <p class="text-xs text-gray-400">QR unavailable</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p class="text-xs text-gray-500 mb-3">Present this QR Code at the room scanner or librarian on duty</p>

                        <!-- Booking Status Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                            :class="{
                                'bg-emerald-100 text-emerald-700': (selectedBooking?.booking_status || selectedBooking?.qr_status) === 'valid',
                                'bg-red-100 text-red-700': (selectedBooking?.booking_status || selectedBooking?.qr_status) === 'expired',
                                'bg-amber-100 text-amber-700': (selectedBooking?.booking_status || selectedBooking?.qr_status || 'upcoming') === 'upcoming'
                            }">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="{
                                      'bg-emerald-500': (selectedBooking?.booking_status || selectedBooking?.qr_status) === 'valid',
                                      'bg-red-500': (selectedBooking?.booking_status || selectedBooking?.qr_status) === 'expired',
                                      'bg-amber-500': (selectedBooking?.booking_status || selectedBooking?.qr_status || 'upcoming') === 'upcoming'
                                  }"></span>
                            <span x-text="((selectedBooking?.booking_status || selectedBooking?.qr_status || 'upcoming').toString().charAt(0).toUpperCase() + (selectedBooking?.booking_status || selectedBooking?.qr_status || 'upcoming').toString().slice(1))"></span>
                        </span>
                    </div>
                </div>
            </template>
            <template x-if="selectedBooking?.status === 'rejected'">
                <div class="w-full text-center">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">This booking was rejected.</h3>
                </div>
            </template>
            </div>
        </div>
    </div>
    <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="closeModal()">close</button>
</div>
