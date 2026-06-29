<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    seguro: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
});

const form = useForm({
    ramo: props.seguro.ramo ?? 'inmueble',
    asegurado: props.seguro.asegurado ?? '',
    beneficiario: props.seguro.beneficiario ?? '',
    aseguradora: props.seguro.aseguradora ?? '',
    numero_poliza: props.seguro.numero_poliza ?? '',
    agente_venta: props.seguro.agente_venta ?? '',
    suma_asegurada: props.seguro.suma_asegurada ?? '',
    prima: props.seguro.prima ?? '',
    condiciones: props.seguro.condiciones ?? '',
    vigencia_inicio: props.seguro.vigencia_inicio ? String(props.seguro.vigencia_inicio).substring(0, 10) : '',
    vigencia_fin: props.seguro.vigencia_fin ? String(props.seguro.vigencia_fin).substring(0, 10) : '',
    estado: props.seguro.estado ?? 'vigente',
    propiedad_id: props.seguro.propiedad_id ?? null,
});

const submit = () => form.put(route('seguros.update', props.seguro.id));
</script>

<template>
    <Head title="Editar póliza" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Editar póliza de seguro</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :propiedades="propiedades" submit-label="Actualizar póliza" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
