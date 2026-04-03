    <div x-show="showViewModal" x-cloak class="modal p-4" :class="{ 'modal-open': showViewModal }" @keydown.escape.window="closeViewModal()">
            <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Reservation Details</h2>
                                <p class="text-indigo-100 text-sm">View booking information</p>
                            </div>
                        </div>
                        <button @click="closeViewModal()" class="text-white/80 hover:text-white">
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

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                            <p class="font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                            <p class="text-sm text-gray-500" x-text="selectedBooking?.room_location || 'No location'"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Date & Time</p>
                            <p class="font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                            <p class="text-sm text-gray-500" x-text="selectedBooking?.formatted_time"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Booked By</p>
                            <p class="font-semibold text-gray-900" x-text="selectedBooking?.user_name"></p>
                            <p class="text-sm text-gray-500" x-text="selectedBooking?.user_email"></p>
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

