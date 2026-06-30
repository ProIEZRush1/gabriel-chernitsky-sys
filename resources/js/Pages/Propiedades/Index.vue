<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    propiedades: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.q ?? '');

const tipos = {
    casa: 'Casa',
    departamento: 'Departamento',
    local: 'Local comercial',
    terreno: 'Terreno',
    oficina: 'Oficina',
};

const estados = {
    disponible: 'Disponible',
    rentada: 'Rentada',
    mantenimiento: 'En mantenimiento',
};

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(v || 0));

const submitSearch = () => {
    router.get(route('propiedades.index'), { q: search.value }, { preserveState: true, replace: true });
};

const destroy = (item) => {
    if (confirm(`¿Eliminar la propiedad "${item.nombre}"?`)) {
        router.delete(route('propiedades.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Propiedades" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Propiedades</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div
                v-if="page.props.flash?.success"
                class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="submitSearch" class="flex w-full max-w-sm gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre, dirección o ciudad…"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"
                    />
                    <button
                        type="submit"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Buscar
                    </button>
                </form>

                <Link
                    :href="route('propiedades.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90"
                >
                    + Nueva propiedad
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Dirección</th>
                            <th class="px-6 py-3">Valor comercial</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in propiedades" :key="item.id" class="text-sm text-slate-700 hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ item.nombre }}
                                <div v-if="item.areas && item.areas.length" class="mt-1 flex flex-wrap gap-1">
                                    <span
                                        v-for="(area, i) in item.areas"
                                        :key="i"
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-[10px] font-medium',
                                            area.principal ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600',
                                        ]"
                                    >
                                        {{ area.nombre }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">{{ tipos[item.tipo] ?? item.tipo }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ item.direccion }}<span v-if="item.ciudad">, {{ item.ciudad }}</span></td>
                            <td class="px-6 py-4">{{ currency(item.valor_comercial) }}</td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-3 py-1 text-xs font-semibold',
                                        item.estado === 'rentada' ? 'bg-violet-100 text-violet-700'
                                            : item.estado === 'disponible' ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700',
                                    ]"
                                >
                                    {{ estados[item.estado] ?? item.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('propiedades.edit', item.id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Editar</Link>
                                <button @click="destroy(item)" class="ml-4 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="propiedades.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                Aún no hay propiedades registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
