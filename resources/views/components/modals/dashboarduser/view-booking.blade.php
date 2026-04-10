    <div x-show="showViewModal" x-cloak class="modal p-4" :class="{ 'modal-open': showViewModal }" @keydown.escape.window="showViewModal = false">
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
                <div class="bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-4 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-calendar-check text-7xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                                <i class="fa-solid fa-calendar-days text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Booking Details</h3>
                                <p class="text-teal-100 text-sm" x-text="viewEvent?.room_name || selectedBooking?.room_name || ''"></p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-white/80 hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-amber-100 text-amber-700': (viewEvent?.status || selectedBooking?.status) === 'pending',
                                  'bg-green-100 text-green-700': (viewEvent?.status || selectedBooking?.status) === 'approved',
                                  'bg-red-100 text-red-700': (viewEvent?.status || selectedBooking?.status) === 'rejected',
                                  'bg-gray-100 text-gray-700': (viewEvent?.status || selectedBooking?.status) === 'cancelled'
                              }"
                              x-text="((viewEvent?.status || selectedBooking?.status || '').charAt(0).toUpperCase() + (viewEvent?.status || selectedBooking?.status || '').slice(1))"></span>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-building text-teal-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Room</p>
                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="viewEvent?.room_name || selectedBooking?.room_name || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-calendar-days text-blue-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="viewEvent?.formatted_date || selectedBooking?.formatted_date || viewEvent?.date || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clock text-purple-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Time</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="viewEvent?.formatted_time || selectedBooking?.formatted_time || '—'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-users text-amber-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Attendees</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="(viewEvent?.attendees || selectedBooking?.attendees || '0') + ' people'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Booked By -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-indigo-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Booked By</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="'****'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Purpose -->
                    <template x-if="(viewEvent?.title || viewEvent?.purpose || selectedBooking?.title)">
                        <div class="mb-4 p-3 bg-gray-50 rounded-xl">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium mb-1">Purpose</p>
                            <p class="text-sm text-gray-900" x-text="viewEvent?.title || viewEvent?.purpose || selectedBooking?.title"></p>
                        </div>
                    </template>

                    <!-- Description -->
                    <template x-if="(viewEvent?.description || selectedBooking?.description)">
                        <div class="mb-4 p-3 bg-gray-50 rounded-xl">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium mb-1">Description</p>
                            <p class="text-sm text-gray-900" x-text="viewEvent?.description || selectedBooking?.description"></p>
                        </div>
                    </template>

                    <!-- QR Code Section (Approved bookings only) -->
                    <template x-if="(viewEvent?.status || selectedBooking?.status) === 'approved' && (viewEvent?.qr_code_url || viewEvent?.qr_token || selectedBooking?.qr_code_url || selectedBooking?.qr_token)">
                        <div class="mb-4 relative overflow-hidden rounded-2xl border border-teal-200/60 bg-gradient-to-br from-teal-50/80 via-emerald-50/60 to-white p-5"
                             style="animation: qrSlideIn 0.4s ease-out both;">
                            <!-- Decorative glow -->
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-gradient-to-br from-teal-200/30 to-emerald-200/30 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-gradient-to-br from-emerald-200/20 to-teal-200/20 rounded-full blur-2xl pointer-events-none"></div>

                            <div class="relative z-10 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <i class="fa-solid fa-qrcode text-teal-600"></i>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Booking QR Code</h3>
                                </div>

                                <div class="inline-block p-3 bg-white rounded-xl shadow-sm border border-gray-100 mb-3">
                                    <img :src="viewEvent?.qr_code_url || selectedBooking?.qr_code_url || `/bookings/qr/${viewEvent?.qr_token || selectedBooking?.qr_token}`"
                                         alt="Booking QR Code"
                                         class="w-44 h-44 mx-auto object-contain"
                                         onerror="this.parentElement.innerHTML='<div class=\'w-44 h-44 flex items-center justify-center bg-gray-50 rounded-lg\'><div class=\'text-center\'><i class=\'fa-solid fa-qrcode text-gray-300 text-4xl mb-2\'></i><p class=\'text-xs text-gray-400\'>QR unavailable</p></div></div>'">
                                </div>

                                <p class="text-xs text-gray-500 mb-3">Present this QR Code at the room scanner or librarian on duty</p>

                                <!-- Booking Status Badge -->
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700': (viewEvent?.booking_status || selectedBooking?.booking_status) === 'valid',
                                          'bg-red-100 text-red-700': (viewEvent?.booking_status || selectedBooking?.booking_status) === 'expired',
                                          'bg-amber-100 text-amber-700': !(viewEvent?.booking_status || selectedBooking?.booking_status) || (viewEvent?.booking_status || selectedBooking?.booking_status) === 'upcoming'
                                      }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="{
                                              'bg-emerald-500': (viewEvent?.booking_status || selectedBooking?.booking_status) === 'valid',
                                              'bg-red-500': (viewEvent?.booking_status || selectedBooking?.booking_status) === 'expired',
                                              'bg-amber-500': !(viewEvent?.booking_status || selectedBooking?.booking_status) || (viewEvent?.booking_status || selectedBooking?.booking_status) === 'upcoming'
                                          }"></span>
                                    <span x-text="((viewEvent?.booking_status || selectedBooking?.booking_status || 'upcoming').charAt(0).toUpperCase() + (viewEvent?.booking_status || selectedBooking?.booking_status || 'upcoming').slice(1))"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-2xl">
                    <button @click="showViewModal = false" class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">Close</button>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showViewModal = false">close</button>
</div>

<style>
@keyframes qrSlideIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
