<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    form: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Guardar' },
});

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="ramo" value="Ramo" />
                <select id="ramo" v-model="form.ramo" :class="inputClass">
                    <option value="inmueble">Inmueble</option>
                    <option value="auto">Auto</option>
                    <option value="medico">Médico</option>
                </select>
                <InputError class="mt-2" :message="form.errors.ramo" />
            </div>
            <div>
                <InputLabel for="estado" value="Estado" />
                <select id="estado" v-model="form.estado" :class="inputClass">
                    <option value="vigente">Vigente</option>
                    <option value="por_vencer">Por vencer</option>
                    <option value="vencido">Vencido</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <InputError class="mt-2" :message="form.errors.estado" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="asegurado" value="Asegurado" />
                <input id="asegurado" v-model="form.asegurado" type="text" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.asegurado" />
            </div>
            <div>
                <InputLabel for="beneficiario" value="Beneficiario" />
                <input id="beneficiario" v-model="form.beneficiario" type="text" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.beneficiario" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="aseguradora" value="Aseguradora" />
                <input id="aseguradora" v-model="form.aseguradora" type="text" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.aseguradora" />
            </div>
            <div>
                <InputLabel for="numero_poliza" value="Número de póliza" />
                <input id="numero_poliza" v-model="form.numero_poliza" type="text" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.numero_poliza" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="agente_venta" value="Agente de venta" />
                <input id="agente_venta" v-model="form.agente_venta" type="text" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.agente_venta" />
            </div>
            <div>
                <InputLabel for="propiedad_id" value="Propiedad relacionada (opcional)" />
                <select id="propiedad_id" v-model="form.propiedad_id" :class="inputClass">
                    <option :value="null">— Ninguna —</option>
                    <option v-for="p in propiedades" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.propiedad_id" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="suma_asegurada" value="Suma asegurada (MXN)" />
                <input id="suma_asegurada" v-model="form.suma_asegurada" type="number" step="0.01" min="0" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.suma_asegurada" />
            </div>
            <div>
                <InputLabel for="prima" value="Prima (MXN)" />
                <input id="prima" v-model="form.prima" type="number" step="0.01" min="0" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.prima" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="vigencia_inicio" value="Vigencia inicio" />
                <input id="vigencia_inicio" v-model="form.vigencia_inicio" type="date" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.vigencia_inicio" />
            </div>
            <div>
                <InputLabel for="vigencia_fin" value="Vigencia fin" />
                <input id="vigencia_fin" v-model="form.vigencia_fin" type="date" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.vigencia_fin" />
            </div>
        </div>

        <div>
            <InputLabel for="condiciones" value="Condiciones" />
            <textarea id="condiciones" v-model="form.condiciones" rows="3" :class="inputClass"></textarea>
            <InputError class="mt-2" :message="form.errors.condiciones" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('seguros.index')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">{{ submitLabel }}</button>
        </div>
    </form>
</template>
