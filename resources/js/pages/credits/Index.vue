<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Amount from '@/components/Amount.vue';
import BuyCreditsForm from '@/components/BuyCreditsForm.vue';
import Heading from '@/components/Heading.vue';
import { credits } from '@/routes';
import type { Money } from '@/types';

defineProps<{ balance: Money }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Credits', href: credits() }],
    },
});
</script>

<template>
    <Head title="Credits" />

    <div class="flex max-w-2xl flex-col gap-8 p-4">
        <section class="flex flex-col gap-2">
            <Heading title="Balance" />
            <p class="text-2xl font-semibold"><Amount :amount="balance" /></p>
        </section>

        <section class="flex flex-col gap-2">
            <Heading
                variant="small"
                title="Buy credits"
                description="Whole dollars, from $1 to $1,000. One credit is one US dollar."
            />
            <BuyCreditsForm />
        </section>
    </div>
</template>
