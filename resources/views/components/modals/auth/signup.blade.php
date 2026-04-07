<div x-show="signupOpen" x-cloak class="modal p-4 sm:p-6" :class="{ 'modal-open': signupOpen }" @keydown.escape.window="signupOpen = false">
    <div class="modal-box w-11/12 max-w-6xl p-0 bg-slate-50 overflow-y-auto max-h-[95vh] rounded-3xl border border-indigo-100 shadow-[0_30px_100px_-30px_rgba(30,41,59,0.75)]" @click.stop>
                    <div class="signup-hero px-6 py-6 sm:px-8 border-b border-white/10 shadow-2xl">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="relative z-10">
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <span class="inline-flex rounded-lg bg-indigo-500/20 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-200 backdrop-blur-md ring-1 ring-white/20">
                                        QC Citizen Verification
                                    </span>
                                    <span class="signup-badge-glow inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-[10px] font-bold text-emerald-300 backdrop-blur-md">
                                        <i class="fa-solid fa-shield-halved text-[9px]"></i>
                                        SECURE SERVER-SIDE SCANNING
                                    </span>
                                </div>
                                <h3 class="text-4xl font-black tracking-tight text-white">Create your SmartSpace account</h3>
                                <p class="mt-2 max-w-2xl text-base text-indigo-100/80 font-medium">Upload your Quezon City Citizen ID for instant verification and auto-fill.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[430px]">
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Current status</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.isVerified ? 'Ready to submit' : (scan.idAssessment === 'Fake QC ID' ? 'Fake QC ID' : (scan.idAssessment === 'INVALID' ? 'Invalid ID' : 'Not submitted'))"></p>
                                </div>
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Detected ID</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.idAssessment || (signup.ocr_text ? 'Scanning...' : 'Not verified')"></p>
                                </div>
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Confidence</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.confidenceLabel || '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="signup-scroll-area p-5 sm:p-6">
                        <form method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data" class="space-y-5"
                              @submit.prevent="validateAndSubmitSignup($el)">
                            @csrf
                            <input type="hidden" name="ocr_text" x-model="signup.ocr_text">
                            <input type="hidden" name="qr_validated_id" x-model="scan.qrIdNumber">
                            <input type="hidden" name="otp_token" x-model="otpToken">
                            <input type="hidden" name="qcid_temp_upload" x-model="signup.qcid_temp_upload">
                            <input type="hidden" name="date_of_birth" x-model="signup.date_of_birth">
                            <input type="hidden" name="date_issued" x-model="signup.date_issued">
                            <input type="hidden" name="valid_until" x-model="signup.valid_until">


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
                                                          :required="!signup.qcid_temp_upload"
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
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Cardholder</dt><dd class="font-medium text-slate-800" x-text="signup.name || '-'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">QC ID number</dt><dd class="font-medium text-slate-800" x-text="signup.qcid_number || '-'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Birth date</dt><dd class="font-medium text-slate-800" x-text="signup.date_of_birth || '-'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Sex</dt><dd class="font-medium text-slate-800" x-text="signup.sex || '-'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Address</dt><dd class="truncate font-medium text-slate-800" x-text="signup.address || '-'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3">
                                                        <dt class="text-slate-500">QR Validation</dt>
                                                        <dd>
                                                            <template x-if="scan.isQrVerified === true">
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 animate-in fade-in zoom-in duration-300">
                                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                    Verified
                                                                </span>
                                                            </template>
                                                            <template x-if="scan.isQrVerified === false">
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 animate-in fade-in slide-in-from-right-2 duration-300" x-text="scan.qrData ? 'QR Detected (unreadable data)' : 'No QR Found'">
                                                                </span>
                                                            </template>
                                                            <template x-if="scan.isQrVerified === null && scan.status">
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 animate-pulse">
                                                                    Verifying...
                                                                </span>
                                                            </template>
                                                            <template x-if="!scan.status">
                                                                <span class="text-slate-400">-</span>
                                                            </template>
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>

                                        <div x-show="scan.error" x-cloak class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="scan.error"></div>
                                        <!-- Show progress/status except for redundant fake/invalid messages -->
                                        <div x-show="scan.status && scan.status !== 'Fake QC ID detected.' && scan.status !== 'Invalid ID detected.'" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" x-text="scan.status"></div>
                                    </section>

                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
                                        <h4 class="text-xl font-bold text-slate-900">Registration details</h4>
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Username</label>
                                                <input name="username" type="text" value="{{ old('username') }}" x-model="signup.username" required autocomplete="username"
                                                      maxlength="15"
                                                      @input="signup.username = sanitizeUsername(signup.username || '')"
                                                       placeholder="Unique handle (e.g. juan_dela_cruz)"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                  <p class="mt-1 text-xs text-slate-400" x-text="(signup.username || '').length + '/15 characters'"></p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Email</label>
                                                  <input name="email" type="email" value="{{ old('email') }}" x-model="signup.email" required autocomplete="email"
                                                      pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$"
                                                      title="Use a real email."
                                                      @input="signup.email = String(signup.email || '').replace(/\s+/g, '').toLowerCase(); signupEmailError = validateSignupEmail(signup.email)"
                                                      @blur="signupEmailError = validateSignupEmail(signup.email)"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                  <p x-show="signupEmailError" x-cloak class="mt-1 text-xs text-red-600" x-text="signupEmailError"></p>
                                                  <p x-show="!signupEmailError" class="mt-1 text-xs text-slate-400">Use a valid email address.</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Full Name</label>
                                                <input name="name" type="text" value="{{ old('name') }}" x-model="signup.name" required autocomplete="name"
                                                       maxlength="50"
                                                       @input="signup.name = (signup.name || '').replace(/[0-9]/g, '').substring(0, 50)"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <p class="mt-1 text-xs text-slate-400" x-text="(signup.name || '').length + '/50 characters'"></p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Mobile Number</label>
                                                <div class="relative mt-1 flex rounded-xl shadow-sm">
                                                    <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 px-3 text-slate-500 sm:text-sm font-semibold">
                                                        +63
                                                    </span>
                                                    <input name="phone_number" type="text" value="{{ old('phone_number') }}" required autocomplete="tel" placeholder="09xxxxxxxxx"
                                                           maxlength="11"
                                                           @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '').substring(0, 11)"
                                                           class="block w-full min-w-0 flex-1 rounded-none rounded-r-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">QC ID Number</label>
                                                  <input name="qcid_number" type="text" value="{{ old('qcid_number') }}" x-model="signup.qcid_number" required placeholder="14-digit QC ID number"
                                                      maxlength="14"
                                                      inputmode="numeric"
                                                      pattern="\d{14}"
                                                      @input="signup.qcid_number = normalizeQcIdValue(signup.qcid_number || '')"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                  <p class="mt-1 text-xs text-slate-400" x-text="(signup.qcid_number || '').length + '/14 digits'"></p>
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
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select course or department</option>
                                                    <optgroup label="College of Business Administration & Accountancy">
                                                        <option value="BSA" @selected(old('course') === 'BSA')>BSA - Bachelor of Science in Accountancy</option>
                                                        <option value="BSMA" @selected(old('course') === 'BSMA')>BSMA - Bachelor of Science in Management Accounting</option>
                                                        <option value="BS Entrep" @selected(old('course') === 'BS Entrep')>BS Entrep - Bachelor of Science in Entrepreneurship</option>
                                                    </optgroup>
                                                    <optgroup label="College of Education">
                                                        <option value="BECEd" @selected(old('course') === 'BECEd')>BECEd - Bachelor of Early Childhood Education</option>
                                                    </optgroup>
                                                    <optgroup label="College of Engineering">
                                                        <option value="BSIE" @selected(old('course') === 'BSIE')>BSIE - Bachelor of Science in Industrial Engineering</option>
                                                        <option value="BSECE" @selected(old('course') === 'BSECE')>BSECE - Bachelor of Science in Electronics Engineering</option>
                                                        <option value="BSCpE" @selected(old('course') === 'BSCpE')>BSCpE - Bachelor of Science in Computer Engineering</option>
                                                    </optgroup>
                                                    <optgroup label="College of Computer Studies">
                                                        <option value="BSCS" @selected(old('course') === 'BSCS')>BSCS - Bachelor of Science in Computer Science</option>
                                                        <option value="BSIS" @selected(old('course') === 'BSIS')>BSIS - Bachelor of Science in Information System</option>
                                                        <option value="BSIT" @selected(old('course') === 'BSIT')>BSIT - Bachelor of Science in Information Technology</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Sex</label>
                                                <select name="sex" x-model="signup.sex"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select sex</option>
                                                    <option value="MALE" @selected(old('sex') === 'MALE')>Male</option>
                                                    <option value="FEMALE" @selected(old('sex') === 'FEMALE')>Female</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Civil Status</label>
                                                <select name="civil_status" x-model="signup.civil_status"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select civil status</option>
                                                    <option value="SINGLE" @selected(old('civil_status') === 'SINGLE')>Single</option>
                                                    <option value="MARRIED" @selected(old('civil_status') === 'MARRIED')>Married</option>
                                                    <option value="WIDOWED" @selected(old('civil_status') === 'WIDOWED')>Widowed</option>
                                                    <option value="DIVORCED" @selected(old('civil_status') === 'DIVORCED')>Divorced</option>
                                                    <option value="SEPARATED" @selected(old('civil_status') === 'SEPARATED')>Separated</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Date of Birth</label>
                                                  <input type="date" value="{{ old('date_of_birth') }}" x-model="signup.date_of_birth" disabled
                                                       max="{{ now()->subYears(15)->format('Y-m-d') }}"
                                                      class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-500">
                                                  <p class="mt-1 text-xs text-slate-400">Auto-filled from your QC ID scan and locked.</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Date Issued</label>
                                                  <input type="date" value="{{ old('date_issued') }}" x-model="signup.date_issued" disabled
                                                      class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-500">
                                                  <p class="mt-1 text-xs text-slate-400">Auto-filled from your QC ID scan and locked.</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Valid Until</label>
                                                  <input type="date" value="{{ old('valid_until') }}" x-model="signup.valid_until" disabled
                                                      class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-500">
                                                  <p class="mt-1 text-xs text-slate-400">Auto-filled from your QC ID scan and locked.</p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-semibold text-slate-700">Address</label>
                                                <textarea name="address" rows="2" x-model="signup.address" maxlength="180"
                                                          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address') }}</textarea>
                                                <p class="mt-1 text-xs text-slate-400" x-text="(signup.address || '').length + '/180 characters'"></p>
                                            </div>
                                            <div class="md:col-span-2" x-data="{
                                                get hasMin() { return signupPassword.length >= 8 },
                                                get hasMax() { return signupPassword.length <= 16 },
                                                get hasUpper() { return /[A-Z]/.test(signupPassword) },
                                                get hasNumber() { return /\d/.test(signupPassword) },
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
                                                           x-model="signupPassword" minlength="8" maxlength="16"
                                                           @input="if (signupPassword.length > 16) signupPassword = signupPassword.substring(0, 16)"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                           :class="signupPassword.length > 0 && signupPassword.length < 8 ? 'border-red-300 focus:ring-red-400' : ''">
                                                    <button type="button"
                                                            @click="showSignupPassword = !showSignupPassword"
                                                            :aria-label="showSignupPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
                                                </div>
                                                <!-- Password Requirements Checklist -->
                                                <div x-show="signupPassword.length > 0" x-cloak x-transition class="mt-2 space-y-1">
                                                    <div class="flex items-center gap-2 text-xs transition-colors duration-200"
                                                         :class="signupPassword.length >= 8 ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none"
                                                           :class="signupPassword.length >= 8 ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>8-16 characters (<span x-text="signupPassword.length"></span>/16)</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs" :class="hasUpper ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none" :class="hasUpper ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>At least one uppercase letter</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs" :class="hasNumber ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none" :class="hasNumber ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>At least one number</span>
                                                    </div>
                                                    <!-- Strength meter bar -->
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <div class="flex-1 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                                            <div class="h-full rounded-full transition-all duration-300"
                                                                 :style="`width: ${strength * 33.33}%`"
                                                                 :class="{
                                                                     'bg-rose-500': strength === 1,
                                                                     'bg-amber-500': strength === 2,
                                                                     'bg-emerald-500': strength === 3
                                                                 }"></div>
                                                        </div>
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
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                                                <div class="relative mt-1">
                                                    <input name="password_confirmation" :type="showSignupConfirmPassword ? 'text' : 'password'" required autocomplete="new-password"
                                                           x-model="signupConfirmPassword" minlength="8" maxlength="16"
                                                           @input="if (signupConfirmPassword.length > 16) signupConfirmPassword = signupConfirmPassword.substring(0, 16)"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                           :class="signupConfirmPassword.length > 0 && signupConfirmPassword !== signupPassword ? 'border-red-300 focus:ring-red-400' : ''">
                                                    <button type="button"
                                                            @click="showSignupConfirmPassword = !showSignupConfirmPassword"
                                                            :aria-label="showSignupConfirmPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
                                                </div>
                                                <!-- Password Match Warning -->
                                                <div x-show="signupConfirmPassword.length > 0" x-cloak x-transition class="mt-2">
                                                    <div class="flex items-center gap-2 text-xs transition-colors duration-200"
                                                         :class="signupConfirmPassword === signupPassword ? 'text-emerald-600' : 'text-red-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none"
                                                           :class="signupConfirmPassword === signupPassword ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                                                        <span x-text="signupConfirmPassword === signupPassword ? 'Passwords match' : 'Passwords do not match'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 pt-2">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700">
                                                Create Account
                                            </button>
                                            <button type="button" @click="signupOpen = false" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                                                Close
                                            </button>
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
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">QC ID number</dt><dd class="font-semibold" x-text="signup.qcid_number || '-'"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">Ready</dt><dd class="font-semibold" x-text="(scan.isVerified && signup.name && signup.qcid_number && signup.ocr_text) ? 'Yes' : 'No'"></dd></div>
                                        </dl>
                                    </section>
                                </aside>
                            </div>
                        </form>
                    </div>
    </div>
    <button type="button" class="modal-backdrop fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="signupOpen = false">close</button>
</div>

