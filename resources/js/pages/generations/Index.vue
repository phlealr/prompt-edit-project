<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { history } from '@/routes';

defineProps<{
    generations: { id: number; prompt: string; status: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'History', href: history() }],
    },
});

const page = usePage();
</script>

<template>
    <Head title="History" />

    <div class="flex flex-col gap-4 p-4">
        <Heading title="History" />

        <p
            v-if="page.flash.status"
            class="rounded-md border p-3 text-sm font-medium"
        >
            {{ page.flash.status }}
        </p>

        <ul class="flex flex-col gap-2">
            <li
                v-for="generation in generations"
                :key="generation.id"
                class="flex justify-between gap-4 rounded-md border p-3 text-sm"
            >
                <span class="truncate">{{ generation.prompt }}</span>
                <span class="text-muted-foreground">{{
                    generation.status
                }}</span>
            </li>
        </ul>
    </div>
</template>
