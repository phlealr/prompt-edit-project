<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { operatorReport } from '@/routes';
import type { OperatorReport } from '@/types';

const props = defineProps<{ report: OperatorReport }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Operator report', href: operatorReport() }],
    },
});

const range = useForm({ start: props.report.start, end: props.report.end });
const openDay = ref<string | null>(null);

function show(): void {
    // Keeps the dates typed, so a rejected range shows its message next to them.
    range.get(operatorReport().url, {
        preserveScroll: true,
        preserveState: true,
    });
}

// "14:03 UTC", the time Pixelforge's (cached) figures were fetched.
function asOfUtc(iso: string): string {
    return `${iso.slice(11, 16)} UTC`;
}

function toggleDay(date: string): void {
    openDay.value = openDay.value === date ? null : date;
}
</script>

<!-- Every amount shows all 9 decimals, to compare with Pixelforge exactly (ADR 0007). -->
<template>
    <Head title="Operator report" />

    <div class="flex flex-col gap-8 p-4 text-sm">
        <Heading
            title="Operator report"
            description="By the UTC day each generation completed. Exact amounts."
        />

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="show">
            <div class="grid gap-1">
                <Label for="start">Start (UTC)</Label>
                <Input id="start" v-model="range.start" type="date" />
            </div>
            <div class="grid gap-1">
                <Label for="end">End (UTC)</Label>
                <Input id="end" v-model="range.end" type="date" />
            </div>
            <Button type="submit" :disabled="range.processing">Show</Button>
            <p
                v-if="report.providerFiguresAsOf"
                class="pb-2 text-muted-foreground"
            >
                Pixelforge figures as of
                {{ asOfUtc(report.providerFiguresAsOf) }}
            </p>
            <InputError class="w-full" :message="range.errors.start" />
            <InputError class="w-full" :message="range.errors.end" />
        </form>

        <section class="grid gap-2">
            <Heading variant="small" title="Totals" />
            <dl class="grid grid-cols-2 gap-x-6 gap-y-1 sm:grid-cols-4">
                <dt class="text-muted-foreground">Generations</dt>
                <dd>{{ report.totals.generations }}</dd>
                <dt class="text-muted-foreground">User spend</dt>
                <dd class="tabular-nums">
                    {{ report.totals.userSpend.exact }}
                </dd>
                <dt class="text-muted-foreground">Provider cost</dt>
                <dd class="tabular-nums">
                    {{ report.totals.providerCost.exact }}
                </dd>
                <dt class="text-muted-foreground">Profit</dt>
                <dd class="tabular-nums">{{ report.totals.profit.exact }}</dd>
            </dl>
        </section>

        <section class="grid gap-2">
            <Heading variant="small" title="Per day" />
            <p v-if="report.providerUnavailable" class="text-destructive">
                Couldn't reach Pixelforge's usage report. Our figures are shown
                alone.
            </p>
            <table class="w-full text-left tabular-nums">
                <thead class="text-muted-foreground">
                    <tr>
                        <th class="py-1">Day</th>
                        <th>Generations</th>
                        <th>User spend</th>
                        <th>Provider cost</th>
                        <th>Pixelforge's cost</th>
                        <th>Difference</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="day in report.days" :key="day.date">
                        <tr
                            class="cursor-pointer border-t hover:bg-muted/50"
                            @click="toggleDay(day.date)"
                        >
                            <td class="py-1">{{ day.date }}</td>
                            <td>{{ day.figures.generations }}</td>
                            <td>{{ day.figures.userSpend.exact }}</td>
                            <td>{{ day.figures.providerCost.exact }}</td>
                            <td>
                                {{ day.pixelforgeCost?.exact ?? 'Unavailable' }}
                            </td>
                            <td
                                :class="{
                                    'font-semibold text-destructive':
                                        day.highlighted,
                                }"
                            >
                                {{ day.difference?.exact ?? 'Unavailable' }}
                            </td>
                            <td>{{ day.figures.profit.exact }}</td>
                        </tr>
                        <tr
                            v-for="user in openDay === day.date
                                ? day.users
                                : []"
                            :key="`${day.date}-${user.email}`"
                            class="text-muted-foreground"
                        >
                            <td class="py-1 pl-4">{{ user.email }}</td>
                            <td>{{ user.figures.generations }}</td>
                            <td>{{ user.figures.userSpend.exact }}</td>
                            <td>{{ user.figures.providerCost.exact }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ user.figures.profit.exact }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </section>

        <section class="grid gap-2">
            <Heading variant="small" title="Per user" />
            <table class="w-full text-left tabular-nums">
                <thead class="text-muted-foreground">
                    <tr>
                        <th class="py-1">User</th>
                        <th>Generations</th>
                        <th>User spend</th>
                        <th>Provider cost</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in report.users"
                        :key="user.email"
                        class="border-t"
                    >
                        <td class="py-1">{{ user.email }}</td>
                        <td>{{ user.figures.generations }}</td>
                        <td>{{ user.figures.userSpend.exact }}</td>
                        <td>{{ user.figures.providerCost.exact }}</td>
                        <td>{{ user.figures.profit.exact }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
