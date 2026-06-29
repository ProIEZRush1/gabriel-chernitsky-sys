<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    form: { type: Object, required: true },
    rentas: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Guardar' },
});

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="auxiliar" value="Auxiliar / cuenta" />
                <input id="auxiliar" v-model="form.auxiliar" type="text" :class="inputClass" required placeholder="Ej. Cuenta Rentas BBVA" />
                <InputError class="mt-2" :message="form.errors.auxiliar" />
            </div>
            <div>
                <InputLabel for="tipo" value="Tipo de movimiento" />
                <select id="tipo" v-model="form.tipo" :class="inputClass">
                    <option value="cobro">Cobro</option>
                    <option value="pago">Pago</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="deposito">Depósito</option>
                    <option value="retiro">Retiro</option>
                </select>
                <InputError class="mt-2" :message="form.errors.tipo" />
            </div>
        </div>

        <div>
            <InputLabel for="concepto" value="Concepto" />
            <input id="concepto" v-model="form.concepto" type="text" :class="inputClass" required />
            <InputError class="mt-2" :message="form.errors.concepto" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
                <InputLabel for="monto" value="Monto (MXN)" />
                <input id="monto" v-model="form.monto" type="number" step="0.01" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.monto" />
            </div>
            <div>
                <InputLabel for="fecha" value="Fecha" />
                <input id="fecha" v-model="form.fecha" type="date" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.fecha" />
            </div>
            <div>
                <InputLabel for="referencia" value="Referencia" />
                <input id="referencia" v-model="form.referencia" type="text" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.referencia" />
            </div>
        </div>

        <div>
            <InputLabel for="renta_id" value="Renta relacionada (opcional)" />
            <select id="renta_id" v-model="form.renta_id" :class="inputClass">
                <option :value="null">— Ninguna —</option>
                <option v-for="r in rentas" :key="r.id" :value="r.id">{{ r.inquilino }}</option>
            </select>
            <InputError class="mt-2" :message="form.errors.renta_id" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('movimientos.index')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">{{ submitLabel }}</button>
        </div>
    </form>
</template>
