    <div x-show="showDeleteModal" x-cloak class="modal p-4" :class="{ 'modal-open': showDeleteModal }" @keydown.escape.window="closeDeleteModal()">
            <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-rose-600 to-red-700 px-6 py-6 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-triangle-exclamation text-8xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg">
                                <i class="w-6 h-6 text-white fa-icon fa-solid fa-trash-can text-2xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white tracking-tight">Delete Room</h2>
                                <p class="text-rose-100 text-sm">This action cannot be undone.</p>
                            </div>
                        </div>
                        <button @click="closeDeleteModal()" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex flex-col min-h-0">
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="flex items-center gap-2 text-green-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-circle-check text-base leading-none"></i>
                                    STATUS
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.status ? deleteRoom.status.charAt(0).toUpperCase() + deleteRoom.status.slice(1) : ''"></p>
                            </div>
                            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100/50">
                                <div class="flex items-center gap-2 text-indigo-600 text-xs font-semibold mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-building text-base leading-none"></i>
                                    ROOM NAME
                                </div>
                                <p class="text-gray-900 font-bold" x-text="deleteRoom?.name"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-2 text-gray-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-location-dot text-base leading-none"></i>
                                    LOCATION
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.location || '-'"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-2 text-gray-600 text-xs font-medium mb-1">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-users text-base leading-none"></i>
                                    CAPACITY
                                </div>
                                <p class="text-gray-900 font-semibold" x-text="deleteRoom?.capacity"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                        <button @click="closeDeleteModal()"
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete()" :disabled="isDeleting"
                                class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 shadow-sm shadow-rose-600/20">
                            <span class="flex items-center justify-center gap-2">
                                <i x-show="!isDeleting" class="w-4 h-4 fa-icon fa-solid fa-trash-can text-base leading-none"></i>
                                <i x-show="isDeleting" class="animate-spin w-4 h-4 fa-icon fa-solid fa-spinner text-base leading-none"></i>
                                <span x-text="isDeleting ? 'Deleting...' : 'Delete Room'"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeDeleteModal()">close</button>
</div>

