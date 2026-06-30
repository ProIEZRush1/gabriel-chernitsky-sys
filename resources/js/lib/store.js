import { ref, watch } from 'vue';

// Reactive, localStorage-backed store. The same key returns the SAME ref across
// every component in the app, so pages like Auxiliar y Configuraciones comparten
// las mismas listas en vivo y se sincronizan al instante.
const PREFIX = 'gc:';
const cache = new Map();

export function useStored(key, defaultValue) {
    if (cache.has(key)) return cache.get(key);

    let initial = typeof defaultValue === 'function' ? defaultValue() : defaultValue;
    try {
        const raw = localStorage.getItem(PREFIX + key);
        if (raw !== null) initial = JSON.parse(raw);
    } catch (e) {
        /* almacenamiento corrupto: usamos el valor por defecto */
    }

    const data = ref(initial);
    watch(
        data,
        (val) => {
            try {
                localStorage.setItem(PREFIX + key, JSON.stringify(val));
            } catch (e) {
                /* sin espacio en el navegador */
            }
        },
        { deep: true },
    );

    cache.set(key, data);
    return data;
}

export function uid() {
    return 'id-' + Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
}

export function todayISO() {
    return new Date().toISOString().slice(0, 10);
}
