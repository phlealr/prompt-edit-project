<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import GenerationCardView from '@/components/GenerationCardView.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { generate, history } from '@/routes';
import type { GenerationCard, Paginated } from '@/types';

const props = defineProps<{
    generations: Paginated<GenerationCard>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'History', href: history() }],
    },
});

const page = usePage();

// While any card on this page is not final, its data is reloaded from our server every 3 seconds
// (ADR 0028); the cards update in place, and the page and scroll position are kept. 'rest' waits
// 3 seconds after each answer, so slow answers never pile up.
const RELOAD_INTERVAL_MS = 3000;

const anyNotFinal = computed(() =>
    props.generations.data.some(
        (generation) =>
            generation.status !== 'succeeded' && generation.status !== 'failed',
    ),
);

const reload = usePoll(
    RELOAD_INTERVAL_MS,
    { only: ['generations'] },
    { autoStart: false, mode: 'rest' },
);

watch(
    anyNotFinal,
    (shouldReload) => (shouldReload ? reload.start() : reload.stop()),
    { immediate: true },
);
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
