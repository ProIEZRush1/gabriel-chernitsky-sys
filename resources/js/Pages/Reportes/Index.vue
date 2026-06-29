<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { useStored } from '@/lib/store';
import { useLists, AUX_LISTS } from '@/lib/auxiliar';
import { toCsv, downloadBlob } from '@/lib/files';

const props = defineProps({
    server: { type: Object, default: () => ({}) },
});

const clientes = useStored('clientes', () => []);
const auxiliar = useStored('auxiliar', () => []);
const mantenimiento = useStored('mantenimiento', () => []);
const documentos = useStored('documentos', () => []);
const lists = useLists();

const money = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

const aux = computed(() => {
    let ing = 0;
    let egr = 0;
    for (const r of auxiliar.value) {
        ing += Number(r.ingreso || 0);
        egr += Number(r.egreso || 0);
    }
    return { ing, egr, saldo: ing - egr, n: auxiliar.value.length };
});

// Agrupar auxiliar por categoría
function groupSum(field) {
    const map = {};
    for (const r of auxiliar.value) {
        const k = r[field] || '(sin asignar)';
        if (!map[k]) map[k] = { ingreso: 0, egreso: 0, n: 0 };
        map[k].ingreso += Number(r.ingreso || 0);
        map[k].egreso += Number(r.egreso || 0);
        map[k].n += 1;
    }
    return Object.entries(map).sort((a, b) => b[1].egreso + b[1].ingreso - (a[1].egreso + a[1].ingreso));
}
const porCategoria = computed(() => groupSum('categoria'));
const porProyecto = computed(() => groupSum('proyecto'));

const mant = computed(() => {
    const est = {};
    let real = 0;
    let estimado = 0;
    for (const r of mantenimiento.value) {
        est[r.estado] = (est[r.estado] || 0) + 1;
        real += Number(r.costo_real || 0);
        estimado += Number(r.costo_estimado || 0);
    }
    return { est, real, estimado, n: mantenimiento.value.length };
});

const clientesPorEstado = computed(() => {
    const m = {};
    for (const c of clientes.value) m[c.estado || '—'] = (m[c.estado || '—'] || 0) + 1;
    return m;
});

const totalArchivos = computed(() => {
    let n = documentos.value.length;
    for (const c of clientes.value) n += (c.archivos || []).length;
    for (const r of mantenimiento.value) n += (r.archivos || []).length;
    return n;
});

const cards = computed(() => [
    { label: 'Propiedades', value: props.server.propiedades ?? 0, icon: '🏢' },
    { label: 'Seguros', value: props.server.seguros ?? 0, icon: '🛡️' },
    { label: 'Rentas', value: props.server.rentas ?? 0, icon: '🧾' },
    { label: 'Movimientos', value: props.server.movimientos ?? 0, icon: '🏦' },
    { label: 'Clientes / arrendatarios', value: clientes.value.length, icon: '👥' },
    { label: 'Renglones de auxiliar', value: aux.value.n, icon: '📒' },
    { label: 'Mantenimientos', value: mant.value.n, icon: '🛠️' },
    { label: 'Documentos / archivos', value: totalArchivos.value, icon: '📁' },
]);

function imprimir() {
    window.print();
}

function exportResumen() {
    const rows = [
        ['Reporte general del sistema — Gabriel Chernitsky'],
        [],
        ['Indicador', 'Valor'],
        ['Propiedades', props.server.propiedades ?? 0],
        ['Seguros', props.server.seguros ?? 0],
        ['Rentas', props.server.rentas ?? 0],
        ['Movimientos bancarios (módulo)', props.server.movimientos ?? 0],
        ['Clientes / arrendatarios', clientes.value.length],
        ['Auxiliar — ingresos', aux.value.ing],
        ['Auxiliar — egresos', aux.value.egr],
        ['Auxiliar — saldo', aux.value.saldo],
        ['Mantenimientos', mant.value.n],
        ['Mantenimiento — costo real', mant.value.real],
        ['Documentos / archivos', totalArchivos.value],
    ];
    downloadBlob('﻿' + toCsv(rows), 'reporte-general.csv', 'text/csv;charset=utf-8');
}
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Reportes</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">Resumen consolidado de todos los datos del sistema.</p>
                <div class="flex gap-2">
                    <button type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="exportResumen">Descargar Excel (CSV)</button>
                    <button type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="imprimir">Imprimir / PDF</button>
                </div>
            </div>

            <!-- Tarjetas -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="c in cards" :key="c.label" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-2xl">{{ c.icon }}</div>
                    <p class="mt-2 text-2xl font-extrabold text-slate-800">{{ c.value }}</p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ c.label }}</p>
                </div>
            </div>

            <!-- Finanzas del auxiliar -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-semibold uppercase text-emerald-600">Ingresos (abonos)</p>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-700">{{ money(aux.ing) }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <p class="text-xs font-semibold uppercase text-rose-600">Egresos (cargos)</p>
                    <p class="mt-1 text-2xl font-extrabold text-rose-700">{{ money(aux.egr) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">Saldo</p>
                    <p class="mt-1 text-2xl font-extrabold" :class="aux.saldo < 0 ? 'text-rose-600' : 'text-slate-800'">{{ money(aux.saldo) }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Por categoría -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-700">Auxiliar por categoría</h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <tr><th class="px-5 py-2 text-left">Categoría</th><th class="px-3 py-2 text-right">Ingreso</th><th class="px-3 py-2 text-right">Egreso</th><th class="px-5 py-2 text-right">#</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="[k, v] in porCategoria" :key="k">
                                <td class="px-5 py-2 font-medium text-slate-700">{{ k }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600">{{ money(v.ingreso) }}</td>
                                <td class="px-3 py-2 text-right text-rose-500">{{ money(v.egreso) }}</td>
                                <td class="px-5 py-2 text-right text-slate-400">{{ v.n }}</td>
                            </tr>
                            <tr v-if="!porCategoria.length"><td colspan="4" class="px-5 py-6 text-center text-slate-400">Sin datos del auxiliar.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Por proyecto -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-700">Auxiliar por proyecto</h3>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <tr><th class="px-5 py-2 text-left">Proyecto</th><th class="px-3 py-2 text-right">Ingreso</th><th class="px-3 py-2 text-right">Egreso</th><th class="px-5 py-2 text-right">#</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="[k, v] in porProyecto" :key="k">
                                <td class="px-5 py-2 font-medium text-slate-700">{{ k }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600">{{ money(v.ingreso) }}</td>
                                <td class="px-3 py-2 text-right text-rose-500">{{ money(v.egreso) }}</td>
                                <td class="px-5 py-2 text-right text-slate-400">{{ v.n }}</td>
                            </tr>
                            <tr v-if="!porProyecto.length"><td colspan="4" class="px-5 py-6 text-center text-slate-400">Sin datos del auxiliar.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Clientes por estado -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-700">Clientes / arrendatarios por estatus</h3>
                    <ul class="divide-y divide-slate-100 text-sm">
                        <li v-for="(n, k) in clientesPorEstado" :key="k" class="flex justify-between px-5 py-2.5"><span class="text-slate-600">{{ k }}</span><span class="font-semibold text-slate-800">{{ n }}</span></li>
                        <li v-if="!clientes.length" class="px-5 py-6 text-center text-slate-400">Sin clientes registrados.</li>
                    </ul>
                </div>

                <!-- Mantenimiento por estado -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h3 class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-700">Mantenimiento por estatus</h3>
                    <ul class="divide-y divide-slate-100 text-sm">
                        <li v-for="(n, k) in mant.est" :key="k" class="flex justify-between px-5 py-2.5"><span class="text-slate-600">{{ k }}</span><span class="font-semibold text-slate-800">{{ n }}</span></li>
                        <li v-if="!mantenimiento.length" class="px-5 py-6 text-center text-slate-400">Sin registros de mantenimiento.</li>
                    </ul>
                    <div class="flex justify-between border-t border-slate-100 px-5 py-3 text-sm">
                        <span class="font-semibold text-slate-500">Costo real total</span>
                        <span class="font-bold text-slate-800">{{ money(mant.real) }}</span>
                    </div>
                </div>
            </div>

            <AppFooter />
        </div>
    </AuthenticatedLayout>
</template>
