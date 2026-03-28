export function createApprovalSuccessModalState() {
    return {
        showSuccessModal: false,
        approvedBooking: null,
        qrImageFailed: false,
        isDownloading: false,

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.approvedBooking = null;
        },

        async downloadQr(url, filename = 'booking-qr.png') {
            if (!url) {
                return;
            }

            this.isDownloading = true;

            try {
                let blob;

                if (url.startsWith('data:')) {
                    const base64 = url.split(',')[1];
                    const binary = atob(base64);
                    const array = new Uint8Array(binary.length);
                    for (let i = 0; i < binary.length; i += 1) {
                        array[i] = binary.charCodeAt(i);
                    }
                    blob = new Blob([array], { type: 'image/png' });
                } else {
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok) {
                        throw new Error('Failed to fetch QR image');
                    }
                    blob = await response.blob();
                }

                if (window.showSaveFilePicker) {
                    const handle = await window.showSaveFilePicker({
                        suggestedName: filename,
                        types: [{ description: 'PNG image', accept: { 'image/png': ['.png'] } }],
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
