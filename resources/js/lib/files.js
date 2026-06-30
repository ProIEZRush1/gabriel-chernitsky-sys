import { uid } from './store';

// Convierte un File del navegador en una entrada guardable (base64) para tenerlo
// en prevista y poder descargarlo después en su formato original.
export function fileToEntry(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () =>
            resolve({
                id: uid(),
                name: file.name,
                type: file.type || guessType(file.name),
                size: file.size,
                dataUrl: reader.result,
                updatedAt: new Date().toISOString(),
            });
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

export function guessType(name) {
    const n = (name || '').toLowerCase();
    if (n.endsWith('.pdf')) return 'application/pdf';
    if (/\.(png|jpe?g|gif|webp|svg|bmp)$/.test(n)) return 'image/*';
    if (/\.(txt|md|log)$/.test(n)) return 'text/plain';
    if (n.endsWith('.csv')) return 'text/csv';
    if (n.endsWith('.json')) return 'application/json';
    if (n.endsWith('.html')) return 'text/html';
    if (/\.docx?$/.test(n)) return 'application/msword';
    if (/\.xlsx?$/.test(n)) return 'application/vnd.ms-excel';
    return 'application/octet-stream';
}

export function kind(entry) {
    const t = (entry.type || '').toLowerCase();
    const n = (entry.name || '').toLowerCase();
    if (t.startsWith('image/') || /\.(png|jpe?g|gif|webp|svg|bmp)$/.test(n)) return 'image';
    if (t === 'application/pdf' || n.endsWith('.pdf')) return 'pdf';
    if (t.startsWith('text/') || /\.(txt|csv|md|json|html|xml|log)$/.test(n)) return 'text';
    if (/\.(docx?|xlsx?|pptx?|odt|ods|odp)$/.test(n) || t.includes('word') || t.includes('sheet') || t.includes('excel') || t.includes('presentation')) return 'office';
    return 'other';
}

export function iconFor(entry) {
    switch (kind(entry)) {
        case 'image':
            return '🖼️';
        case 'pdf':
            return '📕';
        case 'text':
            return '📄';
        case 'office': {
            const n = (entry.name || '').toLowerCase();
            if (/\.xlsx?$|\.ods$/.test(n)) return '📊';
            if (/\.pptx?$|\.odp$/.test(n)) return '📈';
            return '📝';
        }
        default:
            return '📎';
    }
}

export function formatSize(bytes) {
    const b = Number(bytes || 0);
    if (b < 1024) return b + ' B';
    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
    return (b / (1024 * 1024)).toFixed(1) + ' MB';
}

export function downloadEntry(entry) {
    const a = document.createElement('a');
    a.href = entry.dataUrl;
    a.download = entry.name || 'archivo';
    document.body.appendChild(a);
    a.click();
    a.remove();
}

export function downloadBlob(content, filename, mime) {
    const blob = new Blob([content], { type: mime || 'application/octet-stream' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
}

export function decodeText(dataUrl) {
    try {
        const b64 = dataUrl.slice(dataUrl.indexOf(',') + 1);
        return decodeURIComponent(escape(atob(b64)));
    } catch (e) {
        try {
            return atob(dataUrl.slice(dataUrl.indexOf(',') + 1));
        } catch (e2) {
            return '';
        }
    }
}

export function encodeText(text, type = 'text/plain') {
    const b64 = btoa(unescape(encodeURIComponent(text)));
    return `data:${type};charset=utf-8;base64,${b64}`;
}

// CSV -> string para descargar como Excel
export function toCsv(rows) {
    return rows
        .map((r) => r.map((c) => '"' + String(c ?? '').replace(/"/g, '""') + '"').join(','))
        .join('\r\n');
}
