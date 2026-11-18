<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import Amount from '@/components/Amount.vue';
import BuyCreditsForm from '@/components/BuyCreditsForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { exactTime } from '@/lib/relativeTime';
import { credits, history } from '@/routes';
import type { ActivityEntry, Money, Paginated } from '@/types';

defineProps<{
    summary: {
        held: Money | null;
        activity: Paginated<ActivityEntry>;
    };
}>();

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
            <p v-if="summary.held" class="text-sm text-muted-foreground">
                <Amount :amount="summary.held" /> of it is held for generations
                still running. It's charged or given back when they finish.
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

        <section class="flex flex-col gap-2">
            <Heading variant="small" title="Activity" />
            <p
                v-if="summary.activity.data.length === 0"
                class="text-sm text-muted-foreground"
            >
                No activity yet. Buy credits to start generating.
            </p>
            <ul v-else class="divide-y rounded-md border text-sm">
                <li
                    v-for="(entry, index) in summary.activity.data"
                    :key="index"
                    class="flex items-center justify-between gap-4 p-3"
                >
                    <div class="min-w-0">
                        <p v-if="entry.kind === 'purchase'">Bought credits</p>
                        <Link
                            v-else
                            :href="history()"
                            class="block truncate underline-offset-4 hover:underline"
                        >
                            Generation: {{ entry.prompt }}
                        </Link>
                        <time
                            class="text-xs text-muted-foreground"
                            :datetime="entry.createdAt"
                        >
                            {{ exactTime(entry.createdAt) }}
                        </time>
                    </div>
                    <span class="shrink-0">
                        <template v-if="entry.kind === 'purchase'">+</template
                        ><Amount :amount="entry.amount" />
                    </span>
                </li>
            </ul>
            <nav
                v-if="
                    summary.activity.prev_page_url ||
                    summary.activity.next_page_url
                "
                class="flex justify-between"
            >
                <Button
                    v-if="summary.activity.prev_page_url"
                    variant="outline"
                    as-child
                >
                    <Link :href="summary.activity.prev_page_url">Previous</Link>
                </Button>
                <span v-else></span>
                <Button
                    v-if="summary.activity.next_page_url"
                    variant="outline"
                    as-child
                >
                    <Link :href="summary.activity.next_page_url">Next</Link>
                </Button>
            </nav>
        </section>
    </div>
</template>
