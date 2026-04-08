    <div x-show="showViewModal" x-cloak class="modal p-4" :class="{ 'modal-open': showViewModal }" @keydown.escape.window="closeViewModal()">
            <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col
                        transform transition-all"
                 @click.stop
                 x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Reservation Details</h2>
                                <p class="text-indigo-100 text-sm" x-text="selectedBooking?.room_name || 'View booking information'"></p>
                            </div>
                        </div>
                        <button @click="closeViewModal()" class="text-white/80 hover:text-white transition-colors">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-amber-100 text-amber-700': selectedBooking?.status === 'pending',
                                  'bg-green-100 text-green-700': selectedBooking?.status === 'approved',
                                  'bg-red-100 text-red-700': selectedBooking?.status === 'rejected',
                                  'bg-gray-100 text-gray-700': selectedBooking?.status === 'cancelled'
                              }"
                              x-text="selectedBooking?.status?.charAt(0).toUpperCase() + selectedBooking?.status?.slice(1)"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                            <p class="font-semibold text-gray-900 truncate" x-text="selectedBooking?.room_name"></p>
                            <p class="text-sm text-gray-500 truncate" x-text="selectedBooking?.room_location || 'No location'"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Date & Time</p>
                            <p class="font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                            <p class="text-sm text-gray-500" x-text="selectedBooking?.formatted_time"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Booked By</p>
                            <p class="font-semibold text-gray-900 truncate" x-text="selectedBooking?.user_name"></p>
                            <p class="text-sm text-gray-500 truncate" x-text="selectedBooking?.user_email"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Attendees</p>
                            <p class="font-semibold text-gray-900" x-text="selectedBooking?.attendees + ' people'"></p>
                        </div>
                    </div>

                    <template x-if="selectedBooking?.title">
                        <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Purpose</p>
                            <p class="text-gray-900" x-text="selectedBooking?.title"></p>
                        </div>
                    </template>

                    <template x-if="selectedBooking?.description">
                        <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Description</p>
                            <p class="text-gray-900" x-text="selectedBooking?.description"></p>
                        </div>
                    </template>

                    <!-- QR Code Section (Approved bookings only) -->
                    <template x-if="selectedBooking?.status === 'approved' && (selectedBooking?.qr_code_url || selectedBooking?.qr_token)">
                        <div class="mb-4 relative overflow-hidden rounded-2xl border border-indigo-200/60 bg-gradient-to-br from-indigo-50/80 via-purple-50/60 to-white p-5"
                             style="animation: qrFadeIn 0.4s ease-out both;">
                            <!-- Decorative glow -->
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-gradient-to-br from-indigo-200/30 to-purple-200/30 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-gradient-to-br from-purple-200/20 to-indigo-200/20 rounded-full blur-2xl pointer-events-none"></div>

                            <div class="relative z-10 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <i class="fa-solid fa-qrcode text-indigo-600"></i>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Booking QR Code</h3>
                                </div>

                                <div class="inline-block p-3 bg-white rounded-xl shadow-sm border border-gray-100 mb-3">
                                    <img :src="selectedBooking.qr_code_url || `/bookings/qr/${selectedBooking.qr_token}`"
                                         alt="Booking QR Code"
                                         class="w-44 h-44 mx-auto object-contain"
                                         onerror="this.parentElement.innerHTML='<div class=\'w-44 h-44 flex items-center justify-center bg-gray-50 rounded-lg\'><div class=\'text-center\'><i class=\'fa-solid fa-qrcode text-gray-300 text-4xl mb-2\'></i><p class=\'text-xs text-gray-400\'>QR unavailable</p></div></div>'">
                                </div>

                                <p class="text-xs text-gray-500 mb-3">Present this QR Code at the room scanner or librarian on duty</p>

                                <!-- Booking Status Badge -->
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700': (selectedBooking?.booking_status || '').toLowerCase() === 'valid' || (selectedBooking?.booking_status || '').toLowerCase() === 'active',
                                          'bg-red-100 text-red-700': (selectedBooking?.booking_status) === 'expired',
                                          'bg-amber-100 text-amber-700': !selectedBooking?.booking_status || selectedBooking?.booking_status === 'upcoming'
                                      }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="{
                                              'bg-emerald-500': (selectedBooking?.booking_status || '').toLowerCase() === 'valid' || (selectedBooking?.booking_status || '').toLowerCase() === 'active',
                                              'bg-red-500': (selectedBooking?.booking_status) === 'expired',
                                              'bg-amber-500': !selectedBooking?.booking_status || selectedBooking?.booking_status === 'upcoming'
                                          }"></span>
                                    <span x-text="((selectedBooking?.booking_status || '').toLowerCase() === 'valid' || (selectedBooking?.booking_status || '').toLowerCase() === 'active') ? 'Active' : ((selectedBooking?.booking_status || 'upcoming').charAt(0).toUpperCase() + (selectedBooking?.booking_status || 'upcoming').slice(1))"></span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <div class="flex justify-end">
                        <button @click="closeViewModal()"
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeViewModal()">close</button>
</div>

<style>
@keyframes qrFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
