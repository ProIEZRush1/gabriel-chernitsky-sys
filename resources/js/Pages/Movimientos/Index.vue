<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { confirmDelete } from '@/lib/swal';

const props = defineProps({
    movimientos: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.q ?? '');
const tipo = ref(props.filters.tipo ?? '');
const mes = ref(props.filters.mes ?? '');
const desde = ref(props.filters.desde ?? '');
const hasta = ref(props.filters.hasta ?? '');

const tipos = { pago: 'Pago', transferencia: 'Transferencia', cobro: 'Cobro', deposito: 'Depósito', retiro: 'Retiro' };
const ingresos = ['cobro', 'deposito'];

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

const fmtDate = (d) => (d ? String(d).substring(0, 10) : '—');

const submitFilters = () => {
    router.get(route('movimientos.index'), {
        q: search.value,
        tipo: tipo.value,
        mes: mes.value,
        desde: desde.value,
        hasta: hasta.value,
    }, { preserveState: true, replace: true });
};

const limpiarFiltroFecha = () => {
    mes.value = '';
    desde.value = '';
    hasta.value = '';
    submitFilters();
};

const destroy = async (item) => {
    const ok = await confirmDelete({
        title: 'Eliminar movimiento',
        text: `Se eliminará el movimiento "${item.concepto}". Esta acción no se puede deshacer.`,
    });
    if (ok) {
        router.delete(route('movimientos.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Auxiliar bancario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Auxiliar bancario</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ page.props.flash.success }}
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="submitFilters" class="flex w-full max-w-xl gap-2">
                    <input v-model="search" type="search" placeholder="Buscar concepto, auxiliar o referencia…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="tipo" @change="submitFilters" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos los tipos</option>
                        <option value="pago">Pago</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cobro">Cobro</option>
                        <option value="deposito">Depósito</option>
                        <option value="retiro">Retiro</option>
                    </select>
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Buscar</button>
                </form>

                <Link :href="route('movimientos.create')" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nuevo movimiento
                </Link>
            </div>

            <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Mes</label>
                    <input v-model="mes" @change="submitFilters" type="month" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Desde</label>
                    <input v-model="desde" @change="submitFilters" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Hasta</label>
                    <input v-model="hasta" @change="submitFilters" type="date" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                </div>
                <button type="button" @click="limpiarFiltroFecha" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Quitar filtro de fecha
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Auxiliar</th>
                            <th class="px-6 py-3">Concepto</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Renta</th>
                            <th class="px-6 py-3 text-right">Monto</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in movimientos" :key="item.id" class="text-sm text-slate-700 hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-500">{{ fmtDate(item.fecha) }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ item.auxiliar }}</td>
                            <td class="px-6 py-4">{{ item.concepto }}<span v-if="item.referencia" class="block text-xs text-slate-400">Ref: {{ item.referencia }}</span></td>
                            <td class="px-6 py-4">
                                <span :class="['rounded-full px-3 py-1 text-xs font-semibold', ingresos.includes(item.tipo) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600']">
                                    {{ tipos[item.tipo] ?? item.tipo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ item.renta?.inquilino ?? '—' }}</td>
                            <td class="px-6 py-4 text-right font-semibold" :class="ingresos.includes(item.tipo) ? 'text-emerald-600' : 'text-slate-700'">
                                {{ ingresos.includes(item.tipo) ? '+' : '−' }}{{ currency(item.monto) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('movimientos.edit', item.id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Editar</Link>
                                <button @click="destroy(item)" class="ml-4 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="movimientos.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay movimientos registrados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
