function normalizeFormat(format) {
    return String(format || '').toLowerCase() === 'jpeg' ? 'jpeg' : 'png';
}

function dataUrlToBlob(dataUrl) {
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
}

function blobToDataUrl(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Failed to read QR image data.'));
        reader.readAsDataURL(blob);
    });
}

async function convertBlobToFormat(blob, format = 'png') {
    const safeFormat = normalizeFormat(format);
    const targetMime = safeFormat === 'jpeg' ? 'image/jpeg' : 'image/png';

    if (blob.type === targetMime) {
        return blob;
    }

    const dataUrl = await blobToDataUrl(blob);

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
}

export async function smartspaceQrDownload(url, fileBase = 'booking-qr', format = 'png') {
    if (!url) {
        throw new Error('Missing QR source URL.');
    }

    const safeFormat = normalizeFormat(format);
    const extension = safeFormat === 'jpeg' ? 'jpg' : 'png';
    const filename = `${fileBase}.${extension}`;
    const targetMime = safeFormat === 'jpeg' ? 'image/jpeg' : 'image/png';

    let blob;

    if (String(url).startsWith('data:')) {
        blob = dataUrlToBlob(String(url));
    } else {
        const response = await fetch(String(url), { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Failed to fetch QR image.');
        }

        blob = await response.blob();
    }

    if (blob.type !== targetMime) {
        blob = await convertBlobToFormat(blob, safeFormat);
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

        return;
    }

    const blobUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = blobUrl;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(blobUrl);
}

window.smartspaceQrDownload = async (url, fileBase = 'booking-qr', format = 'png') => {
    try {
        await smartspaceQrDownload(url, fileBase, format);
    } catch (error) {
        console.error('QR download failed', error);
        window.notifyApp?.('error', error?.message || 'Failed to save QR image');
    }
};
