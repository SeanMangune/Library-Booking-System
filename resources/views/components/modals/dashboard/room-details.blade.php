    <div x-show="showRoomModal" x-cloak class="modal p-4" :class="{ 'modal-open': showRoomModal }" @keydown.escape.window="showRoomModal = false">
        <div class="modal-box w-11/12 max-w-sm p-0 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col transform transition-all" 
             @click.stop
             x-show="showRoomModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            
            <div class="bg-indigo-600 h-24 relative">
                <!-- Abstract waves background -->
                <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDIiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgNDAyIDIwMCI+PHBhdGggZD0iTTAgMTc5YzUuMyAzLjggMTAuOCA3IDE2LjYgOS40IDE1LjcgNi40IDM2LjggNi42IDYxLjItLjhDMTMwIDE2Mi40IDE1OSA5MiAxOTQgNjVZMzg2IDQydjMwYzAgMCAwIDAgMCAwaC0xM1Y0MmgxM1pNMzUzIDc1djMwYzAgMCAwIDAgMCAwSDF2LTMwaDM1MlpNMTIgOTFWNjBMMCA2MHZNMThjMjk2IDAtMjk2IDAgMCAwcy0yOTYgMC0yOTYgMFY5MXoiIGZpbGw9IiNmZmYiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvc3ZnPg==')]"></div>
                <!-- Banner gradient -->
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-indigo-900/60"></div>
                <button @click="showRoomModal = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-full text-white backdrop-blur-md transition-all">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            
            <div class="px-6 pb-6 pt-0 relative bg-white">
                <div class="w-20 h-20 -mt-10 mb-4 bg-white rounded-2xl shadow-lg border border-gray-100 flex items-center justify-center relative z-10 mx-auto">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-door-open text-3xl text-indigo-600"></i>
                    </div>
                </div>

                <div class="text-center mb-6">
                    <h3 class="text-xl font-black text-gray-900 tracking-tight" x-text="selectedRoom?.name"></h3>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mt-0.5" x-text="selectedRoom?.location || 'General Area'"></p>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-6">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 flex flex-col items-center justify-center">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider mb-1">Capacity</span>
                        <div class="flex items-center gap-1.5 text-indigo-600">
                            <i class="fa-solid fa-users text-sm"></i>
                            <span class="font-bold text-lg" x-text="selectedRoom?.capacity || 'N/A'"></span>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 flex flex-col items-center justify-center">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider mb-1">Status</span>
                        <div class="flex items-center gap-1.5" :class="roomStatusTone(selectedRoom)">
                            <i class="fa-solid fa-circle text-[10px] animate-pulse"></i>
                            <span class="font-bold text-sm tracking-wide" x-text="formatRoomStatusLabel(selectedRoom)"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-6 rounded-xl border border-gray-100 bg-slate-50/70 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500">Room State</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider"
                              :class="roomStatusBadgeClasses(selectedRoom)"
                              x-text="formatRoomStatusLabel(selectedRoom)"></span>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-slate-600" x-text="roomStatusDescription(selectedRoom)"></p>
                    <template x-if="roomStatusCode(selectedRoom) === 'maintenance' && (selectedRoom?.status_start_at || selectedRoom?.status_end_at)">
                        <p class="mt-2 text-[11px] font-semibold text-amber-700">
                            Maintenance window:
                            <span x-text="formatDateTimeLabel(selectedRoom?.status_start_at) + ' to ' + formatDateTimeLabel(selectedRoom?.status_end_at)"></span>
                        </p>
                    </template>
                    <template x-if="selectedRoom?.description">
                        <p class="mt-2 text-[11px] text-slate-500" x-text="selectedRoom.description"></p>
                    </template>
                </div>

                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 mb-2 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Today's Approvals</h4>
                            <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Upcoming & ongoing</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center text-lg font-black shadow-md">
                            <span x-text="selectedRoomBookings?.length || 0"></span>
                        </div>
                    </div>
                    
                    <div x-show="selectedRoomBookings?.length > 0" x-cloak class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-1">
                        <template x-for="booking in selectedRoomBookings" :key="booking.id">
                            <div class="bg-white border text-left border-indigo-100 rounded-lg p-2.5 flex items-center gap-3 cursor-pointer hover:border-indigo-300 transition-colors" @click="viewBooking(booking)">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate" x-text="booking.user_name"></p>
                                    <p class="text-[10px] text-gray-500 truncate" x-text="booking.formatted_time"></p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                            </div>
                        </template>
                    </div>
                    <div x-show="!selectedRoomBookings?.length" x-cloak class="mt-2 text-center py-4 bg-white/50 rounded-lg border border-dashed border-indigo-200">
                        <i class="fa-solid fa-bed text-indigo-200 text-2xl mb-1"></i>
                        <p class="text-xs font-medium text-gray-500">No bookings scheduled today.</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-indigo-100 mt-2">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Upcoming Bookings</h4>
                            <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Later dates</p>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-black shadow-sm border border-indigo-200">
                            <span x-text="selectedRoomUpcomingBookings?.length || 0"></span>
                        </div>
                    </div>
                    
                    <div x-show="selectedRoomUpcomingBookings?.length > 0" x-cloak class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-1 pb-2">
                        <template x-for="booking in selectedRoomUpcomingBookings" :key="booking.id">
                            <div class="bg-white border text-left border-indigo-100 rounded-lg p-2.5 flex items-center gap-3 cursor-pointer hover:border-indigo-300 transition-colors" @click="viewBooking(booking)">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-calendar-day text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate" x-text="booking.user_name"></p>
                                    <p class="text-[10px] text-gray-500 truncate" x-text="(new Date(booking.date).toLocaleDateString(undefined, {weekday: 'short', month: 'short', day: 'numeric'})) + ' | ' + (booking.formatted_time || '')"></p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                            </div>
                        </template>
                    </div>
                    <div x-show="!selectedRoomUpcomingBookings?.length" x-cloak class="mt-2 text-center py-4 bg-white/50 rounded-lg border border-dashed border-indigo-200 mb-2">
                        <i class="fa-solid fa-calendar-xmark text-indigo-200 text-2xl mb-1"></i>
                        <p class="text-xs font-medium text-gray-500">No upcoming bookings scheduled.</p>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showRoomModal = false">close</button>
</div>

