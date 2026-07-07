<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { toCsv, downloadBlob } from '@/lib/files';

const props = defineProps({
    pagos: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    resumen: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');
const mes = ref(props.filters.mes ?? '');
const desde = ref(props.filters.desde ?? '');
const hasta = ref(props.filters.hasta ?? '');
const montoMin = ref(props.filters.monto_min ?? '');
const montoMax = ref(props.filters.monto_max ?? '');

const estadoBadge = {
    pagado: 'bg-emerald-100 text-emerald-700',
    parcial: 'bg-amber-100 text-amber-700',
    vencido: 'bg-red-100 text-red-700',
    pendiente: 'bg-slate-100 text-slate-600',
};

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

const fecha = (v) => (v ? String(v).substring(0, 10) : '—');

const submitFilters = () => {
    router.get(route('rentas.reporte'), {
        q: q.value, mes: mes.value, desde: desde.value, hasta: hasta.value,
        monto_min: montoMin.value, monto_max: montoMax.value,
    }, { preserveState: true, replace: true });
};

const limpiarFiltros = () => {
    q.value = ''; mes.value = ''; desde.value = ''; hasta.value = ''; montoMin.value = ''; montoMax.value = '';
    submitFilters();
};

const exportCsv = () => {
    const header = ['Inquilino', 'Propiedad', 'Periodo', 'Vence renta', 'Monto', 'IVA', 'Recargo', 'Total', 'Pagado', 'Saldo', 'Estado'];
    const body = props.pagos.map((p) => [
        p.renta?.inquilino ?? '', p.renta?.propiedad?.nombre ?? '', p.periodo, fecha(p.fecha_vencimiento_renta),
        p.monto, p.iva, p.recargo_vigente, p.total_periodo, p.monto_pagado, p.saldo, p.estado_calculado,
    ]);
    downloadBlob('﻿' + toCsv([header, ...body]), 'reporte-rentas-generadas.csv', 'text/csv;charset=utf-8');
};
</script>

<template>
    <Head title="Reporte de rentas generadas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight text-slate-800">Reporte de rentas generadas</h2>
                <Link :href="route('rentas.index')" class="text-sm font-semibold text-[#7c3aed] hover:text-[#c026d3]">← Volver a rentas</Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <p class="text-sm text-slate-600">
                Todas las mensualidades (cuentas por cobrar) generadas para todos los arrendatarios, filtrables por inquilino, mes, rango de fechas e importe.
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] p-5 text-white shadow-lg">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Rentas generadas</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ resumen.cantidad ?? 0 }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] p-5 text-white shadow-lg">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Total facturado</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ currency(resumen.facturado) }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 p-5 text-white shadow-lg">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Total cobrado</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ currency(resumen.cobrado) }}</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-r from-rose-500 to-red-500 p-5 text-white shadow-lg">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Saldo por cobrar</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ currency(resumen.saldo) }}</p>
                </div>
            </div>

            <form @submit.prevent="submitFilters" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="w-56">
                    <label class="block text-xs font-semibold text-slate-500">Inquilino</label>
                    <input v-model="q" type="search" placeholder="Buscar inquilino…" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Mes</label>
                    <input v-model="mes" type="month" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Desde</label>
                    <input v-model="desde" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Hasta</label>
                    <input v-model="hasta" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div class="w-32">
                    <label class="block text-xs font-semibold text-slate-500">Importe mín.</label>
                    <input v-model="montoMin" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div class="w-32">
                    <label class="block text-xs font-semibold text-slate-500">Importe máx.</label>
                    <input v-model="montoMax" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Buscar</button>
                <button type="button" @click="limpiarFiltros" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Quitar filtros</button>
                <button type="button" @click="exportCsv" class="ml-auto rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">Descargar Excel (CSV)</button>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3">Inquilino</th>
                                <th class="px-4 py-3">Propiedad</th>
                                <th class="px-4 py-3">Periodo</th>
                                <th class="px-4 py-3">Vence renta</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3 text-right">Recargo</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Pagado</th>
                                <th class="px-4 py-3 text-right">Saldo</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Ver</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in pagos" :key="p.id" class="text-sm text-slate-700 hover:bg-violet-50/60">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ p.renta?.inquilino ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ p.renta?.propiedad?.nombre ?? '—' }}</td>
                                <td class="px-4 py-3">{{ p.periodo }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ fecha(p.fecha_vencimiento_renta) }}</td>
                                <td class="px-4 py-3 text-right">{{ currency(p.monto) }}</td>
                                <td class="px-4 py-3 text-right" :class="Number(p.recargo_vigente) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400'">{{ currency(p.recargo_vigente) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ currency(p.total_periodo) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600">{{ currency(p.monto_pagado) }}</td>
                                <td class="px-4 py-3 text-right font-bold" :class="Number(p.saldo) > 0 ? 'text-red-600' : 'text-emerald-600'">{{ currency(p.saldo) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold capitalize', estadoBadge[p.estado_calculado]]">{{ p.estado_calculado }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link v-if="p.renta_id" :href="route('rentas.show', p.renta_id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Estado de cuenta</Link>
                                </td>
                            </tr>
                            <tr v-if="pagos.length === 0">
                                <td colspan="11" class="px-6 py-10 text-center text-sm text-slate-400">Ninguna renta generada coincide con el filtro.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
