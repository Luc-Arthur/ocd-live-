// Photo du vendeur : réduite dans le navigateur avant l'envoi (les vendeurs sont souvent en 3G).
// 1080 px de large au plus, JPEG qualité 0,8 : une photo de téléphone passe de ~4 Mo à ~200 Ko.
export async function compressPhoto(file, { maxWidth = 1080, quality = 0.8 } = {}) {
    const url = URL.createObjectURL(file);
    try {
        const img = new Image();
        img.decoding = 'async';
        img.src = url;
        await img.decode(); // l'orientation de l'appareil photo (EXIF) est appliquée par le navigateur

        const scale = Math.min(1, maxWidth / img.naturalWidth);
        const w = Math.round(img.naturalWidth * scale);
        const h = Math.round(img.naturalHeight * scale);
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff'; // fond blanc si l'image a de la transparence
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);

        const blob = await new Promise((resolve, reject) => canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('toBlob'))), 'image/jpeg', quality));
        return new File([blob], 'photo.jpg', { type: 'image/jpeg' });
    } finally {
        URL.revokeObjectURL(url);
    }
}
