<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Amount from '@/components/Amount.vue';
import BuyCreditsForm from '@/components/BuyCreditsForm.vue';
import Heading from '@/components/Heading.vue';
import { credits } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Credits', href: credits() }],
    },
});

const page = usePage();
</script>

<template>
    <Head title="Credits" />

    <div class="flex max-w-2xl flex-col gap-8 p-4">
        <section v-if="page.props.balance" class="flex flex-col gap-2">
            <Heading title="Balance" />
            <p
                class="text-2xl font-semibold"
                :class="{ 'text-destructive': page.props.balance.belowZero }"
            >
                <Amount :amount="page.props.balance.amount" />
            </p>
            <p
                v-if="page.props.balance.belowZero"
                class="text-sm text-destructive"
            >
                Your balance is below zero because a generation cost more than
                its estimate. Buy credits to generate again.
            </p>
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
