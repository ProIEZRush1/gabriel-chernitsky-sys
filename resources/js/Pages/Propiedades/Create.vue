<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    tipo: 'departamento',
    direccion: '',
    ciudad: '',
    valor_comercial: '',
    estado: 'disponible',
    notas: '',
    // La primera área es el inmueble principal (el que se renta) y no se puede quitar.
    areas: [{ nombre: 'Principal', renta: '', principal: true }],
});

const agregarArea = () => {
    form.areas.push({ nombre: '', renta: '', principal: false });
};

const quitarArea = (index) => {
    // Solo se quitan las áreas adicionales; el inmueble principal siempre permanece.
    if (form.areas[index]?.principal) return;
    form.areas.splice(index, 1);
};

const submit = () => form.post(route('propiedades.store'));
</script>

<template>
    <Head title="Nueva propiedad" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nueva propiedad</h2>
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

                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Áreas rentables</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Agrega los espacios que se rentan por separado (Local A, Local B, Piso 1, Piso 2…). Puedes agregar las que necesites; la primera es el inmueble principal y siempre permanece.</p>
                        </div>
                        <button type="button" @click="agregarArea" class="shrink-0 rounded-lg border border-[#7c3aed] px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-[#7c3aed] hover:text-white">+ Agregar área</button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div v-for="(area, index) in form.areas" :key="index" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-3 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-slate-500">
                                    Nombre del área
                                    <span v-if="area.principal" class="ml-1 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700">Principal</span>
                                </label>
                                <input v-model="area.nombre" type="text" :placeholder="area.principal ? 'Inmueble principal' : 'Ej. Local A, Piso 2…'" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                            </div>
                            <div class="w-full sm:w-44">
                                <label class="block text-xs font-medium text-slate-500">Renta mensual (MXN)</label>
                                <input v-model="area.renta" type="number" step="0.01" min="0" placeholder="Opcional" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <button
                                type="button"
                                @click="quitarArea(index)"
                                :disabled="area.principal"
                                :class="[
                                    'shrink-0 rounded-lg px-3 py-2 text-xs font-semibold',
                                    area.principal ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-red-50 text-red-600 hover:bg-red-100',
                                ]"
                                :title="area.principal ? 'El inmueble principal no se puede quitar' : 'Quitar área'"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.areas" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('propiedades.index')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</Link>
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">Guardar propiedad</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
