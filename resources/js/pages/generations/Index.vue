<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import GenerationCardView from '@/components/GenerationCardView.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { generate, history } from '@/routes';
import type { GenerationCard, Paginated } from '@/types';

defineProps<{
    generations: Paginated<GenerationCard>;
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

        <div
            v-if="generations.data.length === 0"
            class="flex flex-col items-start gap-3 rounded-xl border p-6"
        >
            <p>No generations yet</p>
            <Button as-child>
                <Link :href="generate()">Generate an image</Link>
            </Button>
        </div>

        <div
            v-else
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <GenerationCardView
                v-for="generation in generations.data"
                :key="generation.id"
                :generation="generation"
            />
        </div>

        <nav
            v-if="generations.prev_page_url || generations.next_page_url"
            class="flex justify-between"
        >
            <Button v-if="generations.prev_page_url" variant="outline" as-child>
                <Link :href="generations.prev_page_url">Previous</Link>
            </Button>
            <span v-else></span>
            <Button v-if="generations.next_page_url" variant="outline" as-child>
                <Link :href="generations.next_page_url">Next</Link>
            </Button>
        </nav>
    </div>
</template>
