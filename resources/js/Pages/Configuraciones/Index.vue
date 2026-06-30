<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { AUX_LISTS, useLists } from '@/lib/auxiliar';
import { findSimilar, normalize } from '@/lib/similar';
import { alertWarning, confirmDelete, promptText } from '@/lib/swal';

const lists = useLists();

// Aviso de parecido al agregar/editar un valor en una tabla
const pending = ref(null); // { listKey, value, match, score, replaceIndex }
const drafts = ref({}); // texto del input "agregar" por lista

function commitNew(listKey) {
    const value = (drafts.value[listKey] || '').trim();
    if (!value) return;
    tryAdd(listKey, value, -1);
}

function tryAdd(listKey, value, replaceIndex) {
    const arr = lists.value[listKey] || [];
    const others = replaceIndex >= 0 ? arr.filter((_, i) => i !== replaceIndex) : arr;
    if (others.some((v) => normalize(v) === normalize(value))) {
        alertWarning('Ese valor ya existe en la tabla.');
        return;
    }
    const sim = findSimilar(value, others);
    if (sim && !sim.exact) {
        pending.value = { listKey, value, match: sim.match, score: Math.round(sim.score * 100), replaceIndex };
        return;
    }
    apply(listKey, value, replaceIndex);
}

function apply(listKey, value, replaceIndex) {
    const arr = [...(lists.value[listKey] || [])];
    if (replaceIndex >= 0) arr[replaceIndex] = value;
    else arr.push(value);
    lists.value = { ...lists.value, [listKey]: arr };
    drafts.value[listKey] = '';
}

function acceptPending() {
    apply(pending.value.listKey, pending.value.value, pending.value.replaceIndex);
    pending.value = null;
}
function cancelPending() {
    pending.value = null;
}

async function editValue(listKey, index) {
    const current = lists.value[listKey][index];
    const next = await promptText({
        title: 'Editar valor',
        inputLabel: 'Nuevo valor',
        inputValue: current,
        confirmButtonText: 'Guardar',
    });
    if (next == null) return;
    const v = next.trim();
    if (!v || v === current) return;
    tryAdd(listKey, v, index);
}

async function removeValue(listKey, index) {
    const arr = [...lists.value[listKey]];
    const ok = await confirmDelete({
        title: 'Eliminar valor',
        text: `Se eliminará "${arr[index]}" de la tabla.`,
    });
    if (!ok) return;
    arr.splice(index, 1);
    lists.value = { ...lists.value, [listKey]: arr };
}
</script>

<template>
    <Head title="Configuraciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Configuraciones</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-600">
                    Cada tabla guarda los valores que se pueden <strong>elegir en su columna</strong> del Auxiliar bancario.
                    Puedes agregar valores sin límite. Si capturas algo muy parecido a un dato existente, el sistema te avisa
                    para que lo <strong>aceptes o canceles</strong>. Los datos de <strong>Fecha</strong>,
                    <strong>Ingreso (abono)</strong> y <strong>Egreso (cargo)</strong> no usan listas.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="list in AUX_LISTS"
                    :key="list.key"
                    class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="flex items-center justify-between gap-2 rounded-t-2xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-3">
                        <h3 class="text-sm font-bold text-white">{{ list.label }}</h3>
                        <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold text-white">{{ (lists[list.key] || []).length }}</span>
                    </div>

                    <ul class="flex-1 divide-y divide-slate-100">
                        <li
                            v-for="(value, i) in lists[list.key]"
                            :key="i"
                            class="flex items-center justify-between gap-2 px-4 py-2 text-sm text-slate-700"
                        >
                            <span class="truncate">{{ value }}</span>
                            <span class="flex shrink-0 gap-2">
                                <button type="button" class="text-xs font-semibold text-[#7c3aed] hover:text-[#c026d3]" @click="editValue(list.key, i)">Editar</button>
                                <button type="button" class="text-xs font-semibold text-red-400 hover:text-red-600" @click="removeValue(list.key, i)">Eliminar</button>
                            </span>
                        </li>
                        <li v-if="!(lists[list.key] || []).length" class="px-4 py-4 text-center text-xs text-slate-400">Sin valores aún.</li>
                    </ul>

                    <div class="border-t border-slate-100 p-3">
                        <div class="flex gap-2">
                            <input
                                v-model="drafts[list.key]"
                                type="text"
                                placeholder="Agregar valor…"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"
                                @keyup.enter="commitNew(list.key)"
                            />
                            <button
                                type="button"
                                class="shrink-0 rounded-lg bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-3 text-sm font-semibold text-white hover:opacity-90"
                                @click="commitNew(list.key)"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <AppFooter />
        </div>

        <!-- Aviso global de parecido -->
        <div v-if="pending" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 p-4" @click.self="cancelPending">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                <h3 class="text-base font-bold text-slate-800">Dato muy parecido</h3>
                <p class="mt-2 text-sm text-slate-600">
                    El valor <span class="font-semibold text-amber-600">«{{ pending.value }}»</span>
                    se parece a <span class="font-semibold text-slate-800">«{{ pending.match }}»</span>
                    <span class="text-slate-400">({{ pending.score }}% de coincidencia)</span>.
                    ¿Deseas agregarlo de todas formas?
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100" @click="cancelPending">Cancelar</button>
                    <button type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white hover:opacity-90" @click="acceptPending">Aceptar y agregar</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
