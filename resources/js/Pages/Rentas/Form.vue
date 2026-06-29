<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Guardar' },
});

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const interesEstimado = computed(() => {
    const monto = Number(props.form.monto_mensual || 0);
    const tasa = Number(props.form.tasa_moratoria || 0);
    const meses = Number(props.form.meses_adeudo || 0);
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(monto * (tasa / 100) * meses);
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="inquilino" value="Inquilino" />
                <input id="inquilino" v-model="form.inquilino" type="text" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.inquilino" />
            </div>
            <div>
                <InputLabel for="propiedad_id" value="Propiedad" />
                <select id="propiedad_id" v-model="form.propiedad_id" :class="inputClass">
                    <option :value="null">— Sin asignar —</option>
                    <option v-for="p in propiedades" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.propiedad_id" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
                <InputLabel for="monto_mensual" value="Renta mensual (MXN)" />
                <input id="monto_mensual" v-model="form.monto_mensual" type="number" step="0.01" min="0" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.monto_mensual" />
            </div>
            <div>
                <InputLabel for="dia_pago" value="Día de pago" />
                <input id="dia_pago" v-model="form.dia_pago" type="number" min="1" max="31" :class="inputClass" required />
                <InputError class="mt-2" :message="form.errors.dia_pago" />
            </div>
            <div>
                <InputLabel for="fecha_inicio" value="Fecha de inicio" />
                <input id="fecha_inicio" v-model="form.fecha_inicio" type="date" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.fecha_inicio" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
                <InputLabel for="estado_pago" value="Estado de pago" />
                <select id="estado_pago" v-model="form.estado_pago" :class="inputClass">
                    <option value="al_corriente">Al corriente</option>
                    <option value="con_adeudo">Con adeudo</option>
                    <option value="pagada">Pagada</option>
                </select>
                <InputError class="mt-2" :message="form.errors.estado_pago" />
            </div>
            <div>
                <InputLabel for="tasa_moratoria" value="Tasa moratoria (% mensual)" />
                <input id="tasa_moratoria" v-model="form.tasa_moratoria" type="number" step="0.01" min="0" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.tasa_moratoria" />
            </div>
            <div>
                <InputLabel for="meses_adeudo" value="Meses de adeudo" />
                <input id="meses_adeudo" v-model="form.meses_adeudo" type="number" min="0" :class="inputClass" />
                <InputError class="mt-2" :message="form.errors.meses_adeudo" />
            </div>
        </div>

        <div class="rounded-xl bg-violet-50 px-4 py-3 text-sm text-violet-800">
            Interés moratorio estimado: <span class="font-bold">{{ interesEstimado }}</span>
            <span class="text-violet-500"> (renta × tasa × meses de adeudo)</span>
        </div>

        <div>
            <InputLabel for="notas" value="Notas" />
            <textarea id="notas" v-model="form.notas" rows="3" :class="inputClass"></textarea>
            <InputError class="mt-2" :message="form.errors.notas" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('rentas.index')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</Link>
            <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90 disabled:opacity-50">{{ submitLabel }}</button>
        </div>
    </form>
</template>
