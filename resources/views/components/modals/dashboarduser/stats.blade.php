    <div x-show="showStatsModal" x-cloak class="modal p-4" :class="{ 'modal-open': showStatsModal }" @keydown.escape.window="showStatsModal = false">
            <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col transform transition-all"
                 @click.stop
                 x-show="showStatsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white" x-text="statsModalTitle"></h3>
                            <p class="text-indigo-100 text-sm" x-text="statsModalList.length + ' Bookings'"></p>
                        </div>
                        <button @click="showStatsModal = false" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>
                </div>
                <div class="p-4 flex-1 min-h-0 overflow-y-auto">
                    <div class="space-y-3">
                        <template x-for="event in statsModalList" :key="event.id">
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors border border-gray-100"
                                 @click="openViewBookingModal(event); showStatsModal = false;">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900" x-text="event.room ? event.room.name : (event.room_name || 'Room')"></h4>
                                        <p class="text-xs text-gray-500 mt-1" x-text="(new Date(event.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})) + ' • ' + (event.time || event.formatted_time || '')"></p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700" 
                                          :class="event.status === 'pending' ? '!bg-amber-100 !text-amber-700' : (event.status === 'rejected' || event.status === 'cancelled' ? '!bg-red-100 !text-red-700' : '')"
                                          x-text="event.status"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="statsModalList.length === 0">
                            <div class="text-center py-6">
                                <p class="text-sm text-gray-500">No bookings found in this category.</p>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100">
                    <button @click="showStatsModal = false" class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">Close</button>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showStatsModal = false">close</button>
</div>

