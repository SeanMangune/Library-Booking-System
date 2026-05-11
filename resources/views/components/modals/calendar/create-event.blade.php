{{-- Create / Edit Calendar Event Modal (admin only) --}}
<div x-show="showCalendarEventModal" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     @keydown.escape.window="showCalendarEventModal = false">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showCalendarEventModal = false"></div>

    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span x-text="editingEventId ? 'Edit Event' : 'Create Event'"></span>
                </h2>
                <button @click="showCalendarEventModal = false" class="text-white/80 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <form @submit.prevent="submitEvent()" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            {{-- Title --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Event Title <span class="text-red-500">*</span></label>
                <input type="text" x-model="eventForm.title" required maxlength="255"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm"
                       placeholder="e.g., Foundation Day, Online Orientation">
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Event Type <span class="text-red-500">*</span></label>
                <select x-model="eventForm.type" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <option value="">Select type…</option>
                    <option value="holiday">🏖️ Holiday</option>
                    <option value="online_class">💻 Online Class</option>
                    <option value="school_event">🎓 School Event</option>
                    <option value="custom">📌 Custom Event</option>
                </select>
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input type="date" x-model="eventForm.date" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
            </div>

            {{-- All Day Toggle --}}
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="eventForm.is_all_day" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
                <span class="text-sm text-gray-700 font-medium">All-day event</span>
            </div>

            {{-- Time (if not all-day) --}}
            <div x-show="!eventForm.is_all_day" x-collapse class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                    <input type="time" x-model="eventForm.start_time"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                    <input type="time" x-model="eventForm.end_time"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea x-model="eventForm.description" rows="3" maxlength="2000"
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm resize-none"
                          placeholder="Optional details…"></textarea>
            </div>

            {{-- Custom Color --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Color (optional)</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="eventForm.color"
                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                    <span class="text-xs text-gray-500">Leave default for auto-color by type</span>
                    <button type="button" @click="eventForm.color = ''" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Reset</button>
                </div>
            </div>

            {{-- Error --}}
            <div x-show="eventFormError" x-cloak class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                <p class="text-sm text-red-700" x-text="eventFormError"></p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between gap-3 pt-2">
                <div>
                    <button type="button" x-show="editingEventId" @click="deleteEvent()"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold rounded-xl transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i>
                        Delete
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showCalendarEventModal = false"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="eventFormSubmitting"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md disabled:opacity-60">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span x-text="editingEventId ? 'Save Changes' : 'Create Event'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
