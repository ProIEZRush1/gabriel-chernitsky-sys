<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import SmartInput from '@/Components/SmartInput.vue';
import { useStored, uid, todayISO } from '@/lib/store';
import { AUX_COLUMNS, useLists } from '@/lib/auxiliar';
import { normalize } from '@/lib/similar';
import { toCsv, downloadBlob } from '@/lib/files';

const lists = useLists();
const rows = useStored('auxiliar', () => []);

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

function newRow() {
    const row = { id: uid(), fecha: todayISO() };
    for (const c of AUX_COLUMNS) if (!(c.key in row)) row[c.key] = '';
    rows.value = [...rows.value, row];
}

function removeRow(id) {
    if (!confirm('¿Eliminar este renglón del auxiliar?')) return;
    rows.value = rows.value.filter((r) => r.id !== id);
}

function setCell(row, key, value) {
    row[key] = value;
    rows.value = [...rows.value];
}

// Agrega un valor nuevo a la tabla/lista correspondiente (Configuraciones) en vivo
function addToList(listKey, value) {
    const arr = lists.value[listKey] || [];
    if (!arr.some((v) => normalize(v) === normalize(value))) {
        lists.value = { ...lists.value, [listKey]: [...arr, value] };
    }
}

// Saldo acumulado (ingresos - egresos) renglón por renglón
const saldos = computed(() => {
    let acc = 0;
    return rows.value.map((r) => {
        acc += Number(r.ingreso || 0) - Number(r.egreso || 0);
        return acc;
    });
});

const totals = computed(() => {
    let ing = 0;
    let egr = 0;
    for (const r of rows.value) {
        ing += Number(r.ingreso || 0);
        egr += Number(r.egreso || 0);
    }
    return { ing, egr, saldo: ing - egr };
});

function exportCsv() {
    const header = [...AUX_COLUMNS.map((c) => c.label), 'Saldo'];
    const body = rows.value.map((r, i) => [...AUX_COLUMNS.map((c) => r[c.key] ?? ''), saldos.value[i]]);
    downloadBlob('﻿' + toCsv([header, ...body]), 'auxiliar-bancario.csv', 'text/csv;charset=utf-8');
}
</script>

<template>
    <Head title="Auxiliar bancario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Auxiliar bancario</h2>
        </template>

        <div class="mx-auto max-w-[1400px] space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="max-w-3xl text-sm text-slate-600">
                    Captura tipo hoja de cálculo. Cada celda (excepto Fecha, Ingreso y Egreso) toma sus valores de una lista
                    configurable. Si escribes un dato nuevo se agrega a su tabla de
                    <Link :href="route('configuraciones.index')" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Configuraciones</Link>,
                    y si se parece a uno existente te pide confirmarlo.
                </p>
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="exportCsv">Descargar Excel (CSV)</button>
                    <button type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="newRow">+ Nuevo renglón</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="border-b border-slate-200 px-2 py-3 text-center">#</th>
                                <th v-for="c in AUX_COLUMNS" :key="c.key" class="whitespace-nowrap border-b border-slate-200 px-2 py-3" :class="c.width">{{ c.label }}</th>
                                <th class="whitespace-nowrap border-b border-slate-200 px-3 py-3 text-right">Saldo</th>
                                <th class="border-b border-slate-200 px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in rows" :key="row.id" class="hover:bg-slate-50/60">
                                <td class="border-b border-slate-100 px-2 py-1 text-center text-xs text-slate-400">{{ i + 1 }}</td>
                                <td v-for="c in AUX_COLUMNS" :key="c.key" class="border-b border-slate-100 px-1 py-1 align-top" :class="c.width">
                                    <input
                                        v-if="c.type === 'date'"
                                        type="date"
                                        :value="row[c.key]"
                                        class="w-full rounded-md border-0 bg-transparent px-2 py-1.5 text-sm text-slate-700 focus:ring-2 focus:ring-inset focus:ring-[#7c3aed]"
                                        @change="setCell(row, c.key, $event.target.value)"
                                    />
                                    <input
                                        v-else-if="c.type === 'money'"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :value="row[c.key]"
                                        placeholder="0.00"
                                        class="w-28 rounded-md border-0 bg-transparent px-2 py-1.5 text-right text-sm text-slate-700 focus:ring-2 focus:ring-inset focus:ring-[#7c3aed]"
                                        :class="c.key === 'ingreso' ? 'text-emerald-600' : 'text-rose-500'"
                                        @input="setCell(row, c.key, $event.target.value)"
                                    />
                                    <SmartInput
                                        v-else
                                        compact
                                        :model-value="row[c.key]"
                                        :options="lists[c.list] || []"
                                        :list-name="c.label"
                                        @update:model-value="setCell(row, c.key, $event)"
                                        @add="addToList(c.list, $event)"
                                    />
                                </td>
                                <td class="whitespace-nowrap border-b border-slate-100 px-3 py-1 text-right font-semibold" :class="saldos[i] < 0 ? 'text-rose-600' : 'text-slate-700'">{{ money(saldos[i]) }}</td>
                                <td class="border-b border-slate-100 px-2 py-1 text-center">
                                    <button type="button" class="text-rose-400 hover:text-rose-600" title="Eliminar renglón" @click="removeRow(row.id)">✕</button>
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td :colspan="AUX_COLUMNS.length + 3" class="px-6 py-12 text-center text-sm text-slate-400">
                                    Aún no hay renglones. Usa <strong>+ Nuevo renglón</strong> para empezar a capturar.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length">
                            <tr class="bg-slate-50 text-sm font-bold text-slate-700">
                                <td :colspan="AUX_COLUMNS.length - 1" class="px-3 py-3 text-right">Totales</td>
                                <td class="px-2 py-3 text-right text-emerald-600">{{ money(totals.ing) }}</td>
                                <td class="px-2 py-3 text-right text-rose-500">{{ money(totals.egr) }}</td>
                                <td class="px-3 py-3 text-right" :class="totals.saldo < 0 ? 'text-rose-600' : 'text-slate-800'">{{ money(totals.saldo) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <AppFooter />
        </div>
    </AuthenticatedLayout>
</template>
