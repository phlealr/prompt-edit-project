<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

const page = usePage();

// What a guest sees on the home page; signed-in users go straight to Generate.
const steps = [
    {
        title: 'Describe the image',
        text: 'Write a prompt, pick a size and a quality, and add up to 4 reference images.',
    },
    {
        title: 'See the price first',
        text: 'Before you submit, you see the range it can cost. The most it can cost is held from your credits.',
    },
    {
        title: 'Pay the exact cost',
        text: "When the image is ready, you pay the image service's exact cost plus 25%, and the rest of the hold comes back. Failed generations are free.",
    },
];
</script>

<template>
    <Head title="Generate images" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-foreground"
    >
        <main class="flex w-full max-w-2xl flex-col gap-10">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <AppLogoIcon class="size-10 fill-current" />
                    <span class="text-lg font-semibold">{{
                        page.props.name
                    }}</span>
                </div>
                <h1 class="text-3xl font-semibold">
                    Generate images from a prompt
                </h1>
                <p class="text-muted-foreground">
                    Buy credits in whole dollars, generate images, and see every
                    cent: the estimate before you submit, the exact charge
                    after.
                </p>
                <div class="flex gap-3">
                    <Button as-child>
                        <Link :href="register()">Create an account</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="login()">Sign in</Link>
                    </Button>
                </div>
            </div>

            <ol class="grid gap-4 sm:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="flex flex-col gap-1 rounded-xl border p-4 text-sm"
                >
                    <span class="text-muted-foreground">{{ index + 1 }}</span>
                    <span class="font-medium">{{ step.title }}</span>
                    <span class="text-muted-foreground">{{ step.text }}</span>
                </li>
            </ol>
        </main>
    </div>
</template>
