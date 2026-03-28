/**
 * Alpine component for the login page signup modal (QC ID scan + form).
 * Exposed as window.signupLoginApp for the guest layout + CDN Alpine build.
 */

function readSignupOldInput() {
    return window.signupOldInput && typeof window.signupOldInput === 'object' ? window.signupOldInput : {};
}

function normalizeOcrText(value) {
    return String(value || '')
        .toUpperCase()
        .replace(/\r/g, '')
        .replace(/[^A-Z0-9,./\-\n\s]/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .replace(/\n{2,}/g, '\n')
        .trim();
}

async function buildQcCanvas(file) {
    return new Promise((resolve) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(objectUrl);
            const canvas = document.createElement('canvas');
            const scale = Math.max(1, 2800 / Math.max(img.width, img.height));
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const { data } = imageData;
            for (let i = 0; i < data.length; i += 4) {
                const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                const contrast = Math.min(255, Math.max(0, ((gray - 128) * 1.7) + 128));
                data[i] = contrast;
                data[i + 1] = contrast;
                data[i + 2] = contrast;
            }
            ctx.putImageData(imageData, 0, 0);

            resolve(canvas);
        };
        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(null);
        };
        img.src = objectUrl;
    });
}

function createQcCropCanvas(sourceCanvas, rect, threshold = false) {
    const crop = document.createElement('canvas');
    const sx = Math.max(0, Math.round(sourceCanvas.width * rect.x));
    const sy = Math.max(0, Math.round(sourceCanvas.height * rect.y));
    const sw = Math.max(1, Math.round(sourceCanvas.width * rect.w));
    const sh = Math.max(1, Math.round(sourceCanvas.height * rect.h));

    crop.width = sw;
    crop.height = sh;

    const ctx = crop.getContext('2d');
    ctx.drawImage(sourceCanvas, sx, sy, sw, sh, 0, 0, sw, sh);

    if (threshold) {
        const imageData = ctx.getImageData(0, 0, sw, sh);
        const { data } = imageData;
        for (let i = 0; i < data.length; i += 4) {
            const value = data[i] > 145 ? 255 : 0;
            data[i] = value;
            data[i + 1] = value;
            data[i + 2] = value;
        }
        ctx.putImageData(imageData, 0, 0);
    }

    return crop;
}

async function recognizeQcCanvas(canvas, ocrConfig = {}, progressRef = null) {
    const options = {
        preserve_interword_spaces: '1',
        ...ocrConfig,
    };

    if (progressRef) {
        options.logger = (message) => {
            if (message.status) {
                progressRef.status = message.status;
            }
            if (typeof message.progress === 'number') {
                progressRef.progress = message.progress * 100;
            }
        };
    }

    const result = await window.Tesseract.recognize(canvas, 'eng', options);
    return normalizeOcrText(result?.data?.text || '');
}

async function collectQcOcrTextFromFile(file, progressRef = null) {
    const enhancedCanvas = await buildQcCanvas(file);
    if (!enhancedCanvas) {
        throw new Error('Unable to prepare the QC ID image for OCR.');
    }

    const fullText = await recognizeQcCanvas(enhancedCanvas, { tessedit_pageseg_mode: 6 }, progressRef);
    const sparseText = await recognizeQcCanvas(enhancedCanvas, { tessedit_pageseg_mode: 11 });

    const bottomStrip = createQcCropCanvas(enhancedCanvas, { x: 0.62, y: 0.76, w: 0.34, h: 0.14 }, true);
    const dateStrip = createQcCropCanvas(enhancedCanvas, { x: 0.25, y: 0.39, w: 0.48, h: 0.15 }, true);

    const bottomText = await recognizeQcCanvas(bottomStrip, {
        tessedit_pageseg_mode: 7,
        tessedit_char_whitelist: '0123456789 ',
    });

    const dateText = await recognizeQcCanvas(dateStrip, {
        tessedit_pageseg_mode: 7,
        tessedit_char_whitelist: '0123456789/ -',
    });

    return normalizeOcrText([fullText, sparseText, dateText, bottomText].filter(Boolean).join('\n'));
}

function formatIsoDateFromVerification(value) {
    if (!value || typeof value !== 'string') {
        return '';
    }
    const s = value.trim();
    const m = s.match(/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/);
    if (m) {
        return `${m[1]}-${m[2]}-${m[3]}`;
    }
    return '';
}

function applyVerificationToSignup(signup, verification) {
    if (!verification || typeof verification !== 'object') {
        return;
    }
    if (verification.cardholder_name && !signup.name) {
        signup.name = verification.cardholder_name;
    }
    if (verification.id_number && !signup.qcid_number) {
        signup.qcid_number = verification.id_number;
    }
    if (verification.sex && !signup.sex) {
        signup.sex = verification.sex;
    }
    if (verification.civil_status && !signup.civil_status) {
        signup.civil_status = verification.civil_status;
    }
    const dob = formatIsoDateFromVerification(verification.date_of_birth);
    if (dob && !signup.date_of_birth) {
        signup.date_of_birth = dob;
    }
    const di = formatIsoDateFromVerification(verification.date_issued);
    if (di && !signup.date_issued) {
        signup.date_issued = di;
    }
    const vu = formatIsoDateFromVerification(verification.valid_until);
    if (vu && !signup.valid_until) {
        signup.valid_until = vu;
    }
    if (verification.address && !signup.address) {
        signup.address = verification.address;
    }
}

export default function signupLoginApp(openInitially = false) {
    const old = readSignupOldInput();

    return {
        signupOpen: Boolean(openInitially),
        showLoginPassword: false,
        showSignupPassword: false,
        showSignupConfirmPassword: false,
        signup: {
            name: old.name || '',
            user_type: old.user_type || '',
            employee_category: old.employee_category || '',
            course: old.course || '',
            qcid_number: old.qcid_number || '',
            sex: old.sex || '',
            civil_status: old.civil_status || '',
            date_of_birth: old.date_of_birth || '',
            date_issued: old.date_issued || '',
            valid_until: old.valid_until || '',
            address: old.address || '',
            ocr_text: old.ocr_text || '',
        },
        scan: {
            previewUrl: '',
            isProcessing: false,
            error: '',
            status: '',
            idAssessment: '',
            confidenceLabel: '',
            isVerified: false,
        },
        signupFile: null,

        resetScanState() {
            this.scan.error = '';
            this.scan.status = '';
            this.scan.idAssessment = '';
            this.scan.confidenceLabel = '';
            this.scan.isVerified = false;
        },

        onSignupQcImageChange(event) {
            const file = event.target?.files?.[0];
            this.resetScanState();
            if (this.scan.previewUrl) {
                URL.revokeObjectURL(this.scan.previewUrl);
                this.scan.previewUrl = '';
            }
            this.signupFile = file || null;
            if (file) {
                this.scan.previewUrl = URL.createObjectURL(file);
            }
        },

        async scanSignupQcId() {
            const url = window.signupQcidVerifyUrl;
            if (!url) {
                this.scan.error = 'Verification URL is not configured.';
                return;
            }
            if (!this.signupFile) {
                this.scan.error = 'Choose a QC ID image first.';
                return;
            }
            if (!window.Tesseract) {
                this.scan.error = 'OCR is not available. Refresh the page and try again.';
                return;
            }

            this.scan.isProcessing = true;
            this.resetScanState();
            const progressRef = { status: '', progress: 0 };

            try {
                const extractedText = await collectQcOcrTextFromFile(this.signupFile, progressRef);
                if (!extractedText) {
                    throw new Error('No readable text was found in the image.');
                }

                this.signup.ocr_text = extractedText;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ocr_text: extractedText,
                        user_name: this.signup.name || null,
                    }),
                });

                const payload = await response.json();
                const verification = payload.verification || null;
                const combinedOcr = payload.ocr_text || extractedText;
                this.signup.ocr_text = combinedOcr;

                if (verification?.id_assessment) {
                    this.scan.idAssessment = verification.id_assessment;
                } else if (verification?.is_valid) {
                    this.scan.idAssessment = 'Valid QC ID';
                }

                if (typeof verification?.confidence_score === 'number') {
                    this.scan.confidenceLabel = `${Math.round(verification.confidence_score)}%`;
                }

                applyVerificationToSignup(this.signup, verification);

                if (!payload.success || !verification?.is_valid) {
                    this.scan.error = payload.message || 'This image is not recognized as a valid QC ID.';
                    this.scan.status = this.scan.error;
                    if (verification?.id_assessment === 'Fake QC ID') {
                        this.scan.status = 'Fake QC ID detected.';
                    } else if (verification?.id_assessment === 'INVALID') {
                        this.scan.status = 'Invalid ID detected.';
                    }
                    this.scan.isVerified = false;
                    return;
                }

                this.scan.isVerified = true;
                this.scan.status = payload.message || 'QC ID verified. Review the fields below.';
            } catch (e) {
                console.error(e);
                this.scan.error = e?.message || 'Failed to read the QC ID image.';
                this.scan.isVerified = false;
            } finally {
                this.scan.isProcessing = false;
            }
        },
    };
}

window.signupLoginApp = signupLoginApp;
