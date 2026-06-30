<script setup>
import { computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Guardar' },
});

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const currency = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

const round2 = (v) => Math.round((Number(v) + Number.EPSILON) * 100) / 100;

// ----- IVA: cálculo interactivo importe antes de IVA <-> total con IVA -----
// `form.monto_mensual` es siempre el importe ANTES de IVA (lo que se guarda).
// `totalConIva` es el total con IVA incluido (campo auxiliar de la interfaz).
const factor = () => 1 + Number(props.form.iva_tasa || 0) / 100;

const totalConIva = ref(
    round2(Number(props.form.monto_mensual || 0) * (props.form.tiene_iva ? factor() : 1))
);

// Recordamos qué campo editó el usuario para resolver el cálculo al marcar IVA.
const fuente = ref('base');

const recalcular = () => {
    if (fuente.value === 'total') {
        // Se anotó el total con IVA -> recalculamos el importe antes de IVA.
        const total = Number(totalConIva.value || 0);
        props.form.monto_mensual = round2(props.form.tiene_iva ? total / factor() : total);
    } else {
        // Se anotó el importe antes de IVA -> recalculamos el total con IVA.
        const base = Number(props.form.monto_mensual || 0);
        totalConIva.value = round2(props.form.tiene_iva ? base * factor() : base);
    }
};

const onBaseInput = () => { fuente.value = 'base'; recalcular(); };
const onTotalInput = (e) => { fuente.value = 'total'; totalConIva.value = Number(e.target.value || 0); recalcular(); };
// Al marcar/desmarcar el check (form.tiene_iva ya cambió por v-model) o cambiar la tasa.
const onIvaToggle = () => recalcular();
const onTasaInput = () => recalcular();

const ivaMonto = computed(() => {
    if (!props.form.tiene_iva) return 0;
    return round2(Number(props.form.monto_mensual || 0) * Number(props.form.iva_tasa || 0) / 100);
});

const interesEstimado = computed(() => {
    const monto = Number(props.form.monto_mensual || 0);
    const tasa = Number(props.form.tasa_moratoria || 0);
    const meses = Number(props.form.meses_adeudo || 0);
    return currency((monto * (tasa / 100) + Number(props.form.recargo_fijo || 0)) * meses);
});

const aumentoPct = computed(() => Number(props.form.porcentaje_aumento || 0) + Number(props.form.inflacion_periodo || 0));

const rentaConAumento = computed(() => currency(Number(props.form.monto_mensual || 0) * (1 + aumentoPct.value / 100)));
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <!-- Datos del arrendatario -->
        <section class="space-y-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Arrendatario y propiedad</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="inquilino" value="Inquilino / arrendatario" />
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
        </section>

        <!-- Renta y fechas -->
        <section class="space-y-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Renta mensual y fechas</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel for="monto_mensual" value="Importe antes de IVA (MXN)" />
                    <input id="monto_mensual" v-model="form.monto_mensual" @input="onBaseInput" type="number" step="0.01" min="0" :class="inputClass" required />
                    <InputError class="mt-2" :message="form.errors.monto_mensual" />
                </div>
                <div>
                    <InputLabel for="dia_pago" value="Día de pago (vence la renta)" />
                    <input id="dia_pago" v-model="form.dia_pago" type="number" min="1" max="31" :class="inputClass" required />
                    <InputError class="mt-2" :message="form.errors.dia_pago" />
                </div>
                <div>
                    <InputLabel for="dias_gracia" value="Días de gracia (vence el pago)" />
                    <input id="dias_gracia" v-model="form.dias_gracia" type="number" min="0" max="60" :class="inputClass" />
                    <p class="mt-1 text-xs text-slate-400">Días tras el vencimiento antes de aplicar recargo.</p>
                    <InputError class="mt-2" :message="form.errors.dias_gracia" />
                </div>
            </div>

            <!-- IVA: check para agregarlo y cálculo interactivo base <-> total -->
            <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-5">
                <label class="flex items-center gap-3">
                    <input id="tiene_iva" v-model="form.tiene_iva" @change="onIvaToggle" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-[#7c3aed] focus:ring-[#7c3aed]" />
                    <span class="text-sm font-semibold text-slate-700">Esta renta causa IVA (desglosar y agregar al total)</span>
                </label>

                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <InputLabel for="iva_tasa" value="Tasa de IVA (%)" />
                        <input id="iva_tasa" v-model="form.iva_tasa" @input="onTasaInput" type="number" step="0.01" min="0" max="100" :class="inputClass" :disabled="!form.tiene_iva" />
                        <InputError class="mt-2" :message="form.errors.iva_tasa" />
                    </div>
                    <div>
                        <InputLabel for="total_con_iva" value="Total con IVA (MXN)" />
                        <input id="total_con_iva" :value="totalConIva" @input="onTotalInput" type="number" step="0.01" min="0" :class="inputClass" />
                        <p class="mt-1 text-xs text-slate-400">Puedes anotar el total con IVA; el importe antes de IVA se ajusta solo.</p>
                    </div>
                    <div class="flex items-end">
                        <div class="w-full rounded-lg bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-100">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ currency(form.monto_mensual) }}</span></div>
                            <div class="flex justify-between" :class="form.tiene_iva ? 'text-violet-700 font-semibold' : 'text-slate-400'">
                                <span>IVA{{ form.tiene_iva ? ` (${Number(form.iva_tasa || 0)}%)` : '' }}</span><span>{{ currency(ivaMonto) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between border-t border-slate-100 pt-1 font-bold text-slate-800"><span>Total</span><span>{{ currency(Number(form.monto_mensual || 0) + ivaMonto) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel for="fecha_inicio" value="Inicio del contrato" />
                    <input id="fecha_inicio" v-model="form.fecha_inicio" type="date" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.fecha_inicio" />
                </div>
                <div>
                    <InputLabel for="fecha_vencimiento_renta" value="Vencimiento del contrato de renta" />
                    <input id="fecha_vencimiento_renta" v-model="form.fecha_vencimiento_renta" type="date" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.fecha_vencimiento_renta" />
                </div>
            </div>
        </section>

        <!-- Adeudos y recargos -->
        <section class="space-y-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Adeudos y recargos por mora</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
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
                    <InputLabel for="recargo_fijo" value="Recargo fijo (MXN/mes)" />
                    <input id="recargo_fijo" v-model="form.recargo_fijo" type="number" step="0.01" min="0" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.recargo_fijo" />
                </div>
                <div>
                    <InputLabel for="meses_adeudo" value="Meses de adeudo" />
                    <input id="meses_adeudo" v-model="form.meses_adeudo" type="number" min="0" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.meses_adeudo" />
                </div>
            </div>
            <div class="rounded-xl bg-violet-50 px-4 py-3 text-sm text-violet-800">
                Recargo estimado por mora: <span class="font-bold">{{ interesEstimado }}</span>
                <span class="text-violet-500"> (recargo fijo + renta × tasa, por mes de adeudo)</span>
            </div>
        </section>

        <!-- Aumentos -->
        <section class="space-y-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Aumentos de renta</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel for="porcentaje_aumento" value="Aumento manual (%)" />
                    <input id="porcentaje_aumento" v-model="form.porcentaje_aumento" type="number" step="0.01" min="0" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.porcentaje_aumento" />
                </div>
                <div>
                    <InputLabel for="inflacion_periodo" value="Inflación del periodo (%)" />
                    <input id="inflacion_periodo" v-model="form.inflacion_periodo" type="number" step="0.01" min="0" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.inflacion_periodo" />
                </div>
                <div>
                    <InputLabel for="fecha_ultimo_aumento" value="Último aumento aplicado" />
                    <input id="fecha_ultimo_aumento" v-model="form.fecha_ultimo_aumento" type="date" :class="inputClass" />
                    <InputError class="mt-2" :message="form.errors.fecha_ultimo_aumento" />
                </div>
            </div>
            <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Renta con aumento ({{ aumentoPct.toFixed(2) }}% = manual + inflación):
                <span class="font-bold">{{ rentaConAumento }}</span>
                <span class="text-emerald-600"> — puedes aplicarlo desde el estado de cuenta.</span>
            </div>
        </section>

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
