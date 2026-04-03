    <div x-show="showViewModal" x-cloak class="modal p-4" :class="{ 'modal-open': showViewModal }" @keydown.escape.window="showViewModal = false">
            <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col transform transition-all"
                 @click.stop
                 x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Booking Details</h3>
                        <button @click="showViewModal = false" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>
                </div>
                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <dl class="space-y-4 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Room</dt><dd class="font-semibold text-gray-900" x-text="viewEvent?.room_name || '—'"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Time</dt><dd class="font-semibold text-gray-900" x-text="viewEvent?.formatted_time || '—'"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Booked by</dt><dd class="font-semibold text-gray-900" x-text="viewEvent?.user_name || '—'"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-semibold text-emerald-700" x-text="(viewEvent?.status || '—').charAt(0).toUpperCase() + (viewEvent?.status || '').slice(1)"></dd></div>
                    </dl>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-2xl">
                    <button @click="showViewModal = false" class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">Close</button>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showViewModal = false">close</button>
</div>

