<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    propiedad: { type: Object, required: true },
});

const form = useForm({
    nombre: props.propiedad.nombre ?? '',
    tipo: props.propiedad.tipo ?? 'departamento',
    direccion: props.propiedad.direccion ?? '',
    ciudad: props.propiedad.ciudad ?? '',
    valor_comercial: props.propiedad.valor_comercial ?? '',
    estado: props.propiedad.estado ?? 'disponible',
    notas: props.propiedad.notas ?? '',
});

const submit = () => form.put(route('propiedades.update', props.propiedad.id));
</script>

<template>
    <Head title="Editar propiedad" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Editar propiedad</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <form
                @submit.prevent="submit"
                class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
            >
                <div>
                    <InputLabel for="nombre" value="Nombre de la propiedad" />
                    <input id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="tipo" value="Tipo" />
                        <select id="tipo" v-model="form.tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="casa">Casa</option>
                            <option value="departamento">Departamento</option>
                            <option value="local">Local comercial</option>
                            <option value="terreno">Terreno</option>
                            <option value="oficina">Oficina</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.tipo" />
                    </div>
                    <div>
                        <InputLabel for="estado" value="Estado" />
                        <select id="estado" v-model="form.estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="disponible">Disponible</option>
                            <option value="rentada">Rentada</option>
                            <option value="mantenimiento">En mantenimiento</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.estado" />
                    </div>
                </div>

                <div>
                    <InputLabel for="direccion" value="Dirección" />
                    <input id="direccion" v-model="form.direccion" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                    <InputError class="mt-2" :message="form.errors.direccion" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="ciudad" value="Ciudad" />
                        <input id="ciudad" v-model="form.ciudad" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.ciudad" />
                    </div>
                    <div>
                        <InputLabel for="valor_comercial" value="Valor comercial (MXN)" />
                        <input id="valor_comercial" v-model="form.valor_comercial" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="form.errors.valor_comercial" />
                    </div>
                </div>

                <div>
                    <InputLabel for="notas" value="Notas" />
                    <textarea id="notas" v-model="form.notas" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <InputError class="mt-2" :message="form.errors.notas" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('propiedades.index')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">Actualizar propiedad</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
