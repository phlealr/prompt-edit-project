<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/credits';

// Quick amounts only fill the field; only Buy credits buys (ADR 0029).
const QUICK_AMOUNTS = [5, 10, 25];

const page = usePage();
const form = useForm({ amount: '' });

function buy(): void {
    // Back to the same page: on Generate, everything typed there is kept.
    form.post(store().url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form class="grid gap-3" @submit.prevent="buy">
        <div class="grid gap-2">
            <Label for="credits-amount">Amount in dollars</Label>
            <div class="flex flex-wrap gap-2">
                <Input
                    id="credits-amount"
                    v-model="form.amount"
                    inputmode="numeric"
                    placeholder="10"
                    class="w-32"
                />
                <Button
                    v-for="amount in QUICK_AMOUNTS"
                    :key="amount"
                    type="button"
                    variant="outline"
                    @click="form.amount = String(amount)"
                >
                    ${{ amount }}
                </Button>
            </div>
            <InputError :message="form.errors.amount" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                Buy credits
            </Button>
            <p v-if="page.flash.purchase" class="text-sm font-medium">
                {{ page.flash.purchase }}
            </p>
        </div>
    </form>
</template>
