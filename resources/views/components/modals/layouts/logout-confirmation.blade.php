<div x-show="logoutOpen" x-cloak class="modal p-4" :class="{ 'modal-open': logoutOpen }" @keydown.escape.window="logoutOpen = false">
    <div class="modal-box w-11/12 max-w-md p-0 bg-transparent border-0 shadow-none overflow-visible" @click.stop>
        <div class="relative group">
            <div aria-hidden="true" class="pointer-events-none absolute -inset-x-10 -bottom-10 h-16 bg-gradient-to-r from-indigo-500 via-purple-500 to-teal-500 blur-3xl opacity-30"></div>
            <div class="bg-gradient-to-b from-white to-slate-50 rounded-3xl border border-gray-200 shadow-2xl max-h-[88vh] overflow-hidden flex flex-col">
                <div class="px-6 py-5 border-b border-gray-100 bg-white/60 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Logout</h3>
                        <p class="text-sm text-gray-500 mt-1">Are you sure you want to logout?</p>
                    </div>
                </div>
                <div class="p-6 flex items-center justify-end gap-3 flex-1 min-h-0 overflow-y-auto">
                    <button type="button" @click="logoutOpen = false" class="px-4 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm font-semibold text-gray-800 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="$refs.logoutForm.submit()" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="logoutOpen = false">close</button>
</div>
