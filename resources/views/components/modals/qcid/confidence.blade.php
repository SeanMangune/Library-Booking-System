        <div x-show="showConfidenceModal" x-cloak class="modal p-4" :class="{ 'modal-open': showConfidenceModal }"
            @keydown.escape.window="closeConfidenceModal()">
            <div class="modal-box w-11/12 max-w-md p-0 bg-transparent border-0 shadow-none overflow-visible" @click.stop>
                <div
                    class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 max-h-[88vh] overflow-y-auto">
                    <div
                        class="relative overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-fuchsia-50 p-4">
                        <div
                            class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-indigo-200/50 blur-2xl">
                        </div>
                        <div
                            class="pointer-events-none absolute -left-10 -bottom-10 h-24 w-24 rounded-full bg-fuchsia-200/40 blur-2xl">
                        </div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Extraction
                                    confidence</p>
                                <h3 class="mt-1 text-lg font-bold text-slate-900" x-text="confidenceFieldTitle()"></h3>
                            </div>
                            <button type="button" @click="closeConfidenceModal()"
                                class="rounded-lg border border-slate-200 bg-white/80 px-2 py-1 text-slate-600 hover:bg-white">×</button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current status</p>
                        <p class="mt-1 text-sm font-semibold" :class="confidenceTextClass(confidenceField)"
                            x-text="confidenceLabel(confidenceField)"></p>
                        <p class="mt-3 text-sm text-slate-700" x-text="confidenceReason(confidenceField)"></p>
                    </div>

                    <p class="mt-4 text-sm text-slate-600" x-show="confidenceNeedsManualEntry(confidenceField)">
                        This field was not auto-filled because the text is unreadable or inconsistent across OCR passes.
                        Please enter it manually and double-check against the physical ID.
                    </p>

                    <div class="mt-5 flex justify-end">
                        <button type="button" @click="closeConfidenceModal()"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Got
                            it</button>
                    </div>
                </div>
            </div>
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40"
                @click="closeConfidenceModal()">close</button>
</div>

