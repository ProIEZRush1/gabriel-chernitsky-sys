// Comparación difusa de textos para avisar cuando un dato nuevo se parece mucho
// a uno que ya existe en una lista.

export function normalize(s) {
    return (s ?? '')
        .toString()
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // quita acentos
        .replace(/\s+/g, ' ');
}

export function levenshtein(a, b) {
    a = normalize(a);
    b = normalize(b);
    if (a === b) return 0;
    if (!a.length) return b.length;
    if (!b.length) return a.length;

    const prev = new Array(b.length + 1);
    for (let j = 0; j <= b.length; j++) prev[j] = j;

    for (let i = 1; i <= a.length; i++) {
        let prevDiag = prev[0];
        prev[0] = i;
        for (let j = 1; j <= b.length; j++) {
            const tmp = prev[j];
            const cost = a[i - 1] === b[j - 1] ? 0 : 1;
            prev[j] = Math.min(prev[j] + 1, prev[j - 1] + 1, prevDiag + cost);
            prevDiag = tmp;
        }
    }
    return prev[b.length];
}

export function similarity(a, b) {
    const na = normalize(a);
    const nb = normalize(b);
    if (!na && !nb) return 1;
    if (!na || !nb) return 0;
    if (na === nb) return 1;
    const dist = levenshtein(na, nb);
    return 1 - dist / Math.max(na.length, nb.length);
}

// Devuelve { exact, match, score } o null. exact=true si ya existe idéntico
// (ignorando mayúsculas/acentos). Si no, el más parecido por encima del umbral.
export function findSimilar(value, list, threshold = 0.72) {
    const nv = normalize(value);
    if (!nv) return null;
    let best = null;
    let bestScore = 0;
    for (const item of list) {
        const ni = normalize(item);
        if (ni === nv) return { exact: true, match: item, score: 1 };
        const score = similarity(value, item);
        if (score > bestScore) {
            bestScore = score;
            best = item;
        }
    }
    if (best && bestScore >= threshold) {
        return { exact: false, match: best, score: bestScore };
    }
    return null;
}
