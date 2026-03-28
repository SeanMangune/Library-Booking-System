@php
    $signupStandalone = $signupStandalone ?? false;
@endphp
<form method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    <input type="hidden" name="ocr_text" x-model="signup.ocr_text">

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h4 class="text-xl font-bold text-slate-900">Upload QC ID</h4>
                        <p class="text-sm text-slate-500">Use a clear image for OCR scanning and auto-fill.</p>
                    </div>
                    <button type="button"
                            @click="scanSignupQcId()"
                            :disabled="scan.isProcessing"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                            x-text="scan.isProcessing ? 'Scanning...' : 'Re-read QC ID'"></button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 p-4">
                        <input type="file"
                               name="qcid_image"
                               accept="image/png,image/jpeg,image/jpg,image/webp"
                               @click="$event.target.value=''"
                               @change="onSignupQcImageChange($event)"
                               required
                               class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-teal-700">
                        <p class="mt-3 text-xs text-slate-500">Accepted: JPG, PNG, WEBP up to 25 MB</p>

                        <div x-show="scan.previewUrl" x-cloak class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <img :src="scan.previewUrl" alt="QC ID preview" class="h-40 w-full object-cover">
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">Verification snapshot</p>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700" x-show="!scan.status" x-cloak>Waiting</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700" x-show="scan.status" x-cloak>Captured</span>
                        </div>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Cardholder</dt><dd class="font-medium text-slate-800" x-text="signup.name || '—'"></dd></div>
                            <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">QC ID number</dt><dd class="font-medium text-slate-800" x-text="signup.qcid_number || '—'"></dd></div>
                            <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Birth date</dt><dd class="font-medium text-slate-800" x-text="signup.date_of_birth || '—'"></dd></div>
                        </dl>
                    </div>
                </div>

                <div x-show="scan.error" x-cloak class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="scan.error"></div>
                <div x-show="scan.status && scan.status !== 'Fake QC ID detected.' && scan.status !== 'Invalid ID detected.'" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" x-text="scan.status"></div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
                <h4 class="text-xl font-bold text-slate-900">Registration details</h4>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Full Name</label>
                        <input name="name" type="text" value="{{ old('name') }}" x-model="signup.name" required autocomplete="name"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Mobile Number</label>
                        <input name="phone_number" type="text" value="{{ old('phone_number') }}" required autocomplete="tel" placeholder="09xxxxxxxxx"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">QC ID Number</label>
                        <input name="qcid_number" type="text" value="{{ old('qcid_number') }}" x-model="signup.qcid_number" required placeholder="### ### ########"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Account Type</label>
                        <select name="user_type" x-model="signup.user_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select account type</option>
                            <option value="student" @selected(old('user_type') === 'student')>Student</option>
                            <option value="employee" @selected(old('user_type') === 'employee')>Employee</option>
                            <option value="alumni" @selected(old('user_type') === 'alumni')>Alumni</option>
                        </select>
                    </div>
                    <div x-show="signup.user_type === 'employee'" x-cloak>
                        <label class="block text-sm font-semibold text-slate-700">Employee Category</label>
                        <select name="employee_category" x-model="signup.employee_category" :required="signup.user_type === 'employee'" :disabled="signup.user_type !== 'employee'"
                                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select employee category</option>
                            <option value="professor" @selected(old('employee_category') === 'professor')>Professor / Faculty</option>
                            <option value="academic_staff" @selected(old('employee_category') === 'academic_staff')>Academic Staff</option>
                            <option value="administrative_staff" @selected(old('employee_category') === 'administrative_staff')>Administrative Staff</option>
                            <option value="it_personnel" @selected(old('employee_category') === 'it_personnel')>IT Personnel</option>
                            <option value="registrar_personnel" @selected(old('employee_category') === 'registrar_personnel')>Registrar Personnel</option>
                            <option value="guidance_personnel" @selected(old('employee_category') === 'guidance_personnel')>Guidance Personnel</option>
                            <option value="security_personnel" @selected(old('employee_category') === 'security_personnel')>Security Personnel</option>
                            <option value="maintenance_personnel" @selected(old('employee_category') === 'maintenance_personnel')>Maintenance Personnel</option>
                            <option value="other" @selected(old('employee_category') === 'other')>Other</option>
                        </select>
                    </div>
                    <div x-show="signup.user_type === 'student'" x-cloak>
                        <label class="block text-sm font-semibold text-slate-700">Course / Department</label>
                        <select name="course" x-model="signup.course" :required="signup.user_type === 'student'" :disabled="signup.user_type !== 'student'"
                                :class="{'border-red-400 bg-red-50': $el.required && !signup.course && signup.user_type === 'student', 'border-slate-200': signup.course || signup.user_type !== 'student'}"
                                class="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select course or department</option>
                            <option value="BSIT" @selected(old('course') === 'BSIT')>BSIT</option>
                            <option value="BSIE" @selected(old('course') === 'BSIE')>BSIE</option>
                            <option value="BSENT" @selected(old('course') === 'BSENT')>BSENT</option>
                            <option value="BSCS" @selected(old('course') === 'BSCS')>BSCS</option>
                            <option value="BSCPE" @selected(old('course') === 'BSCPE')>BSCPE</option>
                            <option value="BSED" @selected(old('course') === 'BSED')>BSED</option>
                            <option value="BEED" @selected(old('course') === 'BEED')>BEED</option>
                            <option value="BSOA" @selected(old('course') === 'BSOA')>BSOA</option>
                            <option value="BSA" @selected(old('course') === 'BSA')>BSA</option>
                            <option value="BSBA" @selected(old('course') === 'BSBA')>BSBA</option>
                            <option value="OTHER" @selected(old('course') === 'OTHER')>OTHER</option>
                        </select>
                        <template x-if="$el.required && !signup.course && signup.user_type === 'student'">
                            <p class="text-xs text-red-600 mt-1">Please select a course or department.</p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Sex</label>
                        <input name="sex" type="text" value="{{ old('sex') }}" x-model="signup.sex"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Civil Status</label>
                        <input name="civil_status" type="text" value="{{ old('civil_status') }}" x-model="signup.civil_status"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Date of Birth</label>
                        <input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" x-model="signup.date_of_birth"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Date Issued</label>
                        <input name="date_issued" type="date" value="{{ old('date_issued') }}" x-model="signup.date_issued"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Valid Until</label>
                        <input name="valid_until" type="date" value="{{ old('valid_until') }}" x-model="signup.valid_until"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Address</label>
                        <textarea name="address" rows="2" x-model="signup.address"
                                  class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address') }}</textarea>
                    </div>
                    <div x-data="{
                        pwd: '',
                        get hasMin() { return this.pwd.length >= 8 },
                        get hasUpper() { return /[A-Z]/.test(this.pwd) },
                        get hasNumber() { return /\d/.test(this.pwd) },
                        get strength() {
                            let s = 0;
                            if (this.hasMin) s++;
                            if (this.hasUpper) s++;
                            if (this.hasNumber) s++;
                            return s;
                        }
                    }">
                        <label class="block text-sm font-semibold text-slate-700">Password</label>
                        <div class="relative mt-1">
                            <input name="password" :type="showSignupPassword ? 'text' : 'password'" required autocomplete="new-password"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   x-model="pwd">
                            <button type="button"
                                    @click="showSignupPassword = !showSignupPassword"
                                    :aria-label="showSignupPassword ? 'Hide password' : 'Show password'"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                <i x-show="!showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                <i x-show="showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                            </button>
                        </div>
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center gap-2 text-xs" :class="hasMin ? 'text-emerald-700' : 'text-slate-500'">
                                <i class="fa-solid fa-circle-check" x-show="hasMin"></i>
                                <i class="fa-regular fa-circle" x-show="!hasMin"></i>
                                At least 8 characters
                            </div>
                            <div class="flex items-center gap-2 text-xs" :class="hasUpper ? 'text-emerald-700' : 'text-slate-500'">
                                <i class="fa-solid fa-circle-check" x-show="hasUpper"></i>
                                <i class="fa-regular fa-circle" x-show="!hasUpper"></i>
                                At least one uppercase letter
                            </div>
                            <div class="flex items-center gap-2 text-xs" :class="hasNumber ? 'text-emerald-700' : 'text-slate-500'">
                                <i class="fa-solid fa-circle-check" x-show="hasNumber"></i>
                                <i class="fa-regular fa-circle" x-show="!hasNumber"></i>
                                At least one number
                            </div>
                            <div class="mt-1">
                                <span class="text-xs font-semibold" :class="{
                                    'text-rose-600': strength === 1,
                                    'text-amber-600': strength === 2,
                                    'text-emerald-700': strength === 3
                                }">
                                    <template x-if="strength === 1">Weak</template>
                                    <template x-if="strength === 2">Medium</template>
                                    <template x-if="strength === 3">Strong</template>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                        <div class="relative mt-1">
                            <input name="password_confirmation" :type="showSignupConfirmPassword ? 'text' : 'password'" required autocomplete="new-password"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button"
                                    @click="showSignupConfirmPassword = !showSignupConfirmPassword"
                                    :aria-label="showSignupConfirmPassword ? 'Hide password' : 'Show password'"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                <i x-show="!showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                <i x-show="showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700">
                        Create Account
                    </button>
                    @if ($signupStandalone)
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                            Back to login
                        </a>
                    @else
                        <button type="button" @click="signupOpen = false" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                            Close
                        </button>
                    @endif
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h4 class="text-xl font-bold text-slate-900">How verification works</h4>
                <ol class="mt-3 space-y-3 text-sm text-slate-600">
                    <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">1</span><span>Upload your QC ID image.</span></li>
                    <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-100 font-bold text-violet-700">2</span><span>Scan and review captured details.</span></li>
                    <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">3</span><span>Submit your account for instant portal access.</span></li>
                </ol>
            </section>

            <section class="rounded-2xl border border-indigo-900/40 bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 p-4 text-white shadow-lg">
                <h4 class="text-xl font-bold">Registration status</h4>
                <p class="text-sm text-indigo-200">Live signup progress</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-indigo-200">Image</dt><dd class="font-semibold" x-text="scan.previewUrl ? 'Uploaded' : 'Waiting'"></dd></div>
                    <div class="flex items-center justify-between"><dt class="text-indigo-200">OCR</dt><dd class="font-semibold" x-text="scan.isVerified ? 'Captured' : (scan.idAssessment ? 'Rejected' : 'Not captured')"></dd></div>
                    <div class="flex items-center justify-between"><dt class="text-indigo-200">QC ID number</dt><dd class="font-semibold" x-text="signup.qcid_number || '—'"></dd></div>
                    <div class="flex items-center justify-between"><dt class="text-indigo-200">Ready</dt><dd class="font-semibold" x-text="(scan.isVerified && signup.name && signup.qcid_number && signup.ocr_text) ? 'Yes' : 'No'"></dd></div>
                </dl>
            </section>
        </aside>
    </div>
</form>
