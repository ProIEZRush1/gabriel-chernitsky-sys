<script setup>
import { ref, computed } from 'vue';
import { findSimilar, normalize } from '@/lib/similar';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Elegir o escribir…' },
    listName: { type: String, default: 'dato' },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'add']);

const datalistId = 'dl-' + Math.random().toString(36).slice(2, 9);

// Estado del aviso de "se parece a…"
const pending = ref(null); // { value, match, score }

const inputClass = computed(() =>
    props.compact
        ? 'w-full border-0 bg-transparent px-2 py-1.5 text-sm text-slate-700 focus:ring-2 focus:ring-inset focus:ring-[#7c3aed] focus:rounded-md'
        : 'w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]',
);

function canonical(value) {
    const nv = normalize(value);
    return props.options.find((o) => normalize(o) === nv);
}

function commit(rawValue) {
    const value = (rawValue ?? '').toString().trim();
    if (value === '') {
        emit('update:modelValue', '');
        return;
    }
    // ¿ya existe igual (ignorando acentos/mayúsculas)? usamos el valor canónico
    const exact = canonical(value);
    if (exact) {
        emit('update:modelValue', exact);
        return;
    }
    // ¿se parece a alguno? pedimos confirmación
    const sim = findSimilar(value, props.options);
    if (sim && !sim.exact) {
        pending.value = { value, match: sim.match, score: Math.round(sim.score * 100) };
        return;
    }
    // dato totalmente nuevo: se agrega a la lista/tabla y a la celda
    emit('add', value);
    emit('update:modelValue', value);
}

function onChange(e) {
    commit(e.target.value);
}

// Resoluciones del aviso
function acceptNew() {
    emit('add', pending.value.value);
    emit('update:modelValue', pending.value.value);
    pending.value = null;
}
function useExisting() {
    emit('update:modelValue', pending.value.match);
    pending.value = null;
}
function cancel() {
    // revertimos al valor previo
    emit('update:modelValue', props.modelValue);
    pending.value = null;
}
</script>

<template>
    <div class="relative">
        <input
            :value="modelValue"
            :list="datalistId"
            :placeholder="placeholder"
            :class="inputClass"
            @change="onChange"
            @keyup.enter="onChange"
            autocomplete="off"
        />
        <datalist :id="datalistId">
            <option v-for="opt in options" :key="opt" :value="opt" />
        </datalist>

        <!-- Aviso de parecido con Aceptar / Cancelar -->
        <div
            v-if="pending"
            class="absolute left-0 top-full z-50 mt-1 w-72 rounded-xl border border-amber-200 bg-white p-3 text-left shadow-xl"
        >
            <p class="text-xs leading-snug text-slate-600">
                <span class="font-semibold text-amber-600">«{{ pending.value }}»</span>
                se parece a un dato existente:
                <span class="font-semibold text-slate-800">«{{ pending.match }}»</span>
                <span class="text-slate-400"> ({{ pending.score }}%)</span>.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    @click="useExisting"
                >
                    Usar «{{ pending.match }}»
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-2.5 py-1 text-xs font-semibold text-white hover:opacity-90"
                    @click="acceptNew"
                >
                    Aceptar y agregar
                </button>
                <button
                    type="button"
                    class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100"
                    @click="cancel"
                >
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</template>
