<script setup>
const wa = 'https://wa.me/5215594356241';
</script>

<template>
    <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-500">
        <p>
            Desarrollado por
            <a :href="wa" target="_blank" rel="noopener" class="font-semibold text-[#7c3aed] transition hover:text-[#c026d3]">Overcloud</a>
        </p>
        <p class="mt-1">
            ¿Quieres tu sitio?
            <a :href="wa" target="_blank" rel="noopener" class="font-semibold text-[#7c3aed] transition hover:text-[#c026d3]">Escríbenos por WhatsApp</a>
        </p>
    </footer>
</template>
