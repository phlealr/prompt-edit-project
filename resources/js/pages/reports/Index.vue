<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { operatorReport } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Operator report', href: operatorReport() }],
    },
});
</script>

<template>
    <Head title="Operator report" />

    <div class="flex flex-col gap-4 p-4">
        <Heading title="Operator report" />
    </div>
</template>
