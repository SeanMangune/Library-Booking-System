    <div x-show="showEventModal" x-cloak class="modal p-4" :class="{ 'modal-open': showEventModal }" @keydown.escape.window="closeEventModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">Booking Details</h2>
                        <button @click="closeEventModal()" class="text-white/80 hover:text-white">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Purpose</p>
                            <p class="font-semibold text-gray-900" x-text="selectedEvent?.purpose || selectedEvent?.title || 'No purpose provided'"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.room_name"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Date</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.formatted_date || selectedEvent?.date"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Time</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.formatted_time || 'N/A'"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Attendees</p>
                                <p class="font-semibold text-gray-900" x-text="(selectedEvent?.attendees || 0) + ' people'"></p>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Booked By</p>
                            <p class="font-semibold text-gray-900" x-text="selectedEvent?.user_name"></p>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Status</p>
                            </div>
                            <span class="px-3 py-1.5 rounded-full text-sm font-semibold"
                                  :class="{
                                      'bg-green-100 text-green-700': selectedEvent?.status === 'approved',
                                      'bg-amber-100 text-amber-700': selectedEvent?.status === 'pending',
                                      'bg-red-100 text-red-700': selectedEvent?.status === 'rejected',
                                      'bg-gray-100 text-gray-700': selectedEvent?.status === 'cancelled'
                                  }"
                                  x-text="selectedEvent?.status?.charAt(0).toUpperCase() + selectedEvent?.status?.slice(1)"></span>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="closeEventModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeEventModal()">close</button>
</div>

