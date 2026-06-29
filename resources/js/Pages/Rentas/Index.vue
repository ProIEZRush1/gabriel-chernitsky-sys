<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    rentas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.q ?? '');
const estado = ref(props.filters.estado_pago ?? '');

const estados = { al_corriente: 'Al corriente', con_adeudo: 'Con adeudo', pagada: 'Pagada' };

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(v || 0));

const submitFilters = () => {
    router.get(route('rentas.index'), { q: search.value, estado_pago: estado.value }, { preserveState: true, replace: true });
};

const destroy = (item) => {
    if (confirm(`¿Eliminar la renta de "${item.inquilino}"?`)) {
        router.delete(route('rentas.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Rentas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Rentas</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ page.props.flash.success }}
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="submitFilters" class="flex w-full max-w-xl gap-2">
                    <input v-model="search" type="search" placeholder="Buscar por inquilino…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="estado" @change="submitFilters" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos los estados</option>
                        <option value="al_corriente">Al corriente</option>
                        <option value="con_adeudo">Con adeudo</option>
                        <option value="pagada">Pagada</option>
                    </select>
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Buscar</button>
                </form>

                <Link :href="route('rentas.create')" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nueva renta
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Inquilino</th>
                            <th class="px-6 py-3">Propiedad</th>
                            <th class="px-6 py-3">Renta mensual</th>
                            <th class="px-6 py-3">Estado de pago</th>
                            <th class="px-6 py-3">Meses adeudo</th>
                            <th class="px-6 py-3">Interés moratorio</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in rentas" :key="item.id" class="text-sm text-slate-700 hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ item.inquilino }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ item.propiedad?.nombre ?? '—' }}</td>
                            <td class="px-6 py-4">{{ currency(item.monto_mensual) }}</td>
                            <td class="px-6 py-4">
                                <span :class="['rounded-full px-3 py-1 text-xs font-semibold', item.estado_pago === 'al_corriente' ? 'bg-emerald-100 text-emerald-700' : item.estado_pago === 'con_adeudo' ? 'bg-red-100 text-red-700' : 'bg-violet-100 text-violet-700']">
                                    {{ estados[item.estado_pago] ?? item.estado_pago }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ item.meses_adeudo }}</td>
                            <td class="px-6 py-4 font-semibold" :class="item.interes_moratorio > 0 ? 'text-red-600' : 'text-slate-400'">{{ currency(item.interes_moratorio) }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('rentas.edit', item.id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Editar</Link>
                                <button @click="destroy(item)" class="ml-4 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="rentas.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay rentas registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
