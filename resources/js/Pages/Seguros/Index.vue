<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    seguros: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.q ?? '');
const ramo = ref(props.filters.ramo ?? '');

const ramos = { inmueble: 'Inmueble', auto: 'Auto', medico: 'Médico' };
const estados = { vigente: 'Vigente', por_vencer: 'Por vencer', vencido: 'Vencido', cancelado: 'Cancelado' };

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(v || 0));

const submitFilters = () => {
    router.get(route('seguros.index'), { q: search.value, ramo: ramo.value }, { preserveState: true, replace: true });
};

const destroy = (item) => {
    if (confirm(`¿Eliminar la póliza de "${item.asegurado}"?`)) {
        router.delete(route('seguros.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Seguros" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Seguros</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ page.props.flash.success }}
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="submitFilters" class="flex w-full max-w-xl gap-2">
                    <input v-model="search" type="search" placeholder="Buscar asegurado, aseguradora, póliza, agente…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="ramo" @change="submitFilters" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos los ramos</option>
                        <option value="inmueble">Inmueble</option>
                        <option value="auto">Auto</option>
                        <option value="medico">Médico</option>
                    </select>
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Buscar</button>
                </form>

                <Link :href="route('seguros.create')" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                    + Nueva póliza
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Asegurado</th>
                            <th class="px-6 py-3">Ramo</th>
                            <th class="px-6 py-3">Aseguradora</th>
                            <th class="px-6 py-3">Suma asegurada</th>
                            <th class="px-6 py-3">Prima</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in seguros" :key="item.id" class="text-sm text-slate-700 hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ item.asegurado }}</p>
                                <p class="text-xs text-slate-400">{{ item.numero_poliza }}<span v-if="item.propiedad"> · {{ item.propiedad.nombre }}</span></p>
                            </td>
                            <td class="px-6 py-4">{{ ramos[item.ramo] ?? item.ramo }}</td>
                            <td class="px-6 py-4">{{ item.aseguradora }}</td>
                            <td class="px-6 py-4">{{ currency(item.suma_asegurada) }}</td>
                            <td class="px-6 py-4">{{ currency(item.prima) }}</td>
                            <td class="px-6 py-4">
                                <span :class="['rounded-full px-3 py-1 text-xs font-semibold', item.estado === 'vigente' ? 'bg-emerald-100 text-emerald-700' : item.estado === 'por_vencer' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600']">
                                    {{ estados[item.estado] ?? item.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('seguros.edit', item.id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Editar</Link>
                                <button @click="destroy(item)" class="ml-4 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="seguros.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay pólizas registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
