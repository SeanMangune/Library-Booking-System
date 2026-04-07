<div x-show="showStatsModal" x-cloak class="modal p-4 z-50" :class="{ 'modal-open': showStatsModal }" @keydown.escape.window="showStatsModal = false">
    <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col transform transition-all relative" 
         @click.stop
         x-show="showStatsModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">
        
        <div class="bg-indigo-600 h-24 relative">
            <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDIiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgNDAyIDIwMCI+PHBhdGggZD0iTTAgMTc5YzUuMyAzLjggMTAuOCA3IDE2LjYgOS40IDE1LjcgNi40IDM2LjggNi42IDYxLjItLjhDMTMwIDE2Mi40IDE1OSA5MiAxOTQgNjVZMzg2IDQydjMwYzAgMCAwIDAgMCAwaC0xM1Y0MmgxM1pNMzUzIDc1djMwYzAgMCAwIDAgMCAwSDF2LTMwaDM1MlpNMTIgOTFWNjBMMCA2MHZNMThjMjk2IDAtMjk2IDAgMCAwcy0yOTYgMC0yOTYgMFY5MXoiIGZpbGw9IiNmZmYiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvc3ZnPg==')]"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-indigo-900/60"></div>
            <button @click="showStatsModal = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-full text-white backdrop-blur-md transition-all">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        
        <div class="px-6 pb-6 pt-0 relative bg-white">
            <div class="w-20 h-20 -mt-10 mb-4 bg-white rounded-2xl shadow-lg border border-gray-100 flex items-center justify-center relative z-10 mx-auto">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-list-check text-3xl text-indigo-600"></i>
                </div>
            </div>

            <div class="text-center mb-6">
                <h3 class="text-xl font-black text-gray-900 tracking-tight" x-text="statsModalTitle"></h3>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mt-0.5"><span x-text="statsModalList?.length || 0"></span> Bookings</p>
            </div>

            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 mb-4 flex flex-col gap-3">
                <div x-show="statsModalList?.length > 0" x-cloak class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <template x-for="booking in statsModalList" :key="booking.id">
                        <div class="bg-white border text-left border-indigo-100 rounded-lg p-3 flex items-center gap-3 cursor-pointer hover:border-indigo-300 transition-colors" @click="viewBooking(booking)">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-calendar text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold text-gray-900 truncate" x-text="booking.room?.name || booking.room_name || 'Room'"></p>
                                    <span class="text-[10px] uppercase font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded" x-text="booking.status"></span>
                                </div>
                                <p class="text-xs text-gray-500 truncate" x-text="(booking.user?.name || booking.user_name || 'Unknown') + ' | ' + new Date(booking.date).toLocaleDateString(undefined, {month: 'short', day: 'numeric', year: 'numeric'}) + ' ' + (booking.formatted_time || '')"></p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                        </div>
                    </template>
                </div>
                <div x-show="!statsModalList?.length" x-cloak class="text-center py-6 bg-white/50 rounded-lg border border-dashed border-indigo-200">
                    <i class="fa-solid fa-folder-open text-indigo-200 text-3xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-500">No bookings found for this category.</p>
                </div>
            </div>

            <a href="{{ route('approvals.index') }}" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition-colors">
                <span>Proceed to Approvals</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
    <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" style="z-index: 40;" @click="showStatsModal = false"></button>
</div>
