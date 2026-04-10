export function createApprovalSuccessModalState() {
    return {
        showSuccessModal: false,
        approvedBooking: null,
        qrImageFailed: false,
        isDownloading: false,
        qrDownloadFormat: 'png',

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.approvedBooking = null;
            this.qrDownloadFormat = 'png';
            window.location.reload();
        },

        normalizeDownloadFormat(format) {
            return String(format || '').toLowerCase() === 'jpeg' ? 'jpeg' : 'png';
        },

        dataUrlToBlob(dataUrl) {
            const [meta, body] = String(dataUrl || '').split(',', 2);
            const mimeMatch = /^data:([^;]+)(;base64)?$/i.exec(meta || '');
            const mime = (mimeMatch && mimeMatch[1]) ? mimeMatch[1] : 'application/octet-stream';
            const isBase64 = (mimeMatch && mimeMatch[2]) === ';base64';

            const binary = isBase64 ? atob(body || '') : decodeURIComponent(body || '');
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i += 1) {
                bytes[i] = binary.charCodeAt(i);
            }

            return new Blob([bytes], { type: mime });
        },

        async blobToDataUrl(blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(String(reader.result || ''));
                reader.onerror = () => reject(new Error('Failed to read QR image data.'));
                reader.readAsDataURL(blob);
            });
        },

        async convertBlobToFormat(blob, format = 'png') {
            const targetFormat = this.normalizeDownloadFormat(format);
            const targetMime = targetFormat === 'jpeg' ? 'image/jpeg' : 'image/png';

            if (targetMime === 'image/png' && blob.type === 'image/png') {
                return blob;
            }

            if (targetMime === 'image/jpeg' && blob.type === 'image/jpeg') {
                return blob;
            }

            const dataUrl = await this.blobToDataUrl(blob);

            return new Promise((resolve, reject) => {
                const image = new Image();
                image.onload = () => {
                    const width = image.naturalWidth || image.width || 480;
                    const height = image.naturalHeight || image.height || 480;
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;

                    const context = canvas.getContext('2d');
                    if (!context) {
                        reject(new Error('Unable to prepare image conversion canvas.'));
                        return;
                    }

                    if (targetMime === 'image/jpeg') {
                        context.fillStyle = '#ffffff';
                        context.fillRect(0, 0, width, height);
                    }

                    context.drawImage(image, 0, 0, width, height);

                    canvas.toBlob((converted) => {
                        if (!converted) {
                            reject(new Error('Unable to convert QR image.'));
                            return;
                        }

                        resolve(converted);
                    }, targetMime, 0.92);
                };

                image.onerror = () => reject(new Error('Failed to load QR image for conversion.'));
                image.src = dataUrl;
            });
        },

        async downloadApprovedQr(booking) {
            if (!booking) {
                return;
            }

            const format = this.normalizeDownloadFormat(this.qrDownloadFormat);
            const sourceUrl = format === 'jpeg'
                ? (booking.qr_download_jpeg_url || booking.qr_code_url)
                : (booking.qr_download_png_url || booking.qr_code_url);

            const baseName = booking.booking_code || booking.qr_token || 'booking-qr';
            await this.downloadQr(sourceUrl, baseName, format);
        },

        async downloadQr(url, fileBase = 'booking-qr', format = 'png') {
            if (!url) {
                return;
            }

            const safeFormat = this.normalizeDownloadFormat(format);
            const extension = safeFormat === 'jpeg' ? 'jpg' : 'png';
            const filename = `${fileBase}.${extension}`;
            const targetMime = safeFormat === 'jpeg' ? 'image/jpeg' : 'image/png';

            this.isDownloading = true;

            try {
                let blob;

                if (url.startsWith('data:')) {
                    blob = this.dataUrlToBlob(url);
                } else {
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error('Failed to fetch QR image');
                    }
                    blob = await response.blob();
                }

                if (blob.type !== targetMime) {
                    blob = await this.convertBlobToFormat(blob, safeFormat);
                }

                if (window.showSaveFilePicker) {
                    const handle = await window.showSaveFilePicker({
                        suggestedName: filename,
                        types: [{
                            description: safeFormat === 'jpeg' ? 'JPEG image' : 'PNG image',
                            accept: { [targetMime]: [`.${extension}`] },
                        }],
                    });
                    const writable = await handle.createWritable();
                    await writable.write(blob);
                    await writable.close();
                } else {
                    const blobUrl = URL.createObjectURL(blob);
                    const anchor = document.createElement('a');
                    anchor.href = blobUrl;
                    anchor.download = filename;
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                    URL.revokeObjectURL(blobUrl);
                }
            } catch (error) {
                console.error('Download failed', error);
                window.notifyApp?.('error', 'Failed to save QR image');
            } finally {
                this.isDownloading = false;
            }
        },
    };
}
