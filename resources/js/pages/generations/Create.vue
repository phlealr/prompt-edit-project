<script setup lang="ts">
import { Head, useForm, useHttp } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Amount from '@/components/Amount.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { generate } from '@/routes';
import { estimate as estimateRoute } from '@/routes/generate';
import type { Money } from '@/types';

const props = defineProps<{
    sizes: string[];
    qualities: string[];
    defaults: { size: string; quality: string };
    balance: Money;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Generate', href: generate() }],
    },
});

const MAX_PROMPT_LENGTH = 4000;
const MAX_REFERENCE_IMAGES = 4;
const ESTIMATE_DEBOUNCE_MS = 400; // ADR 0033
const PRICE_RETRY_MS = 10_000;
// Kept out of the template: a literal URL in an attribute confuses vue-tsc's scope tracking.
const REFERENCE_IMAGE_PLACEHOLDER = 'https://example.com/style.png';

type Estimate = {
    minimum: Money;
    maximum: Money;
    balance: Money;
    enoughCredits: boolean;
};

const form = useForm({
    prompt: '',
    size: props.defaults.size,
    quality: props.defaults.quality,
    reference_images: [] as string[],
});

// Counts Unicode characters, as the server's 4,000-character limit does.
const promptLength = computed(() => [...form.prompt].length);

// The estimate comes from the server; the page only shows it (ADR 0011).
const estimate = ref<Estimate | null>(null);
const priceUnavailable = ref(false);
const estimateError = ref<string | null>(null);
const estimateRequest = useHttp<Record<string, never>, Estimate>({});
estimateRequest.transform(() => form.data());

let retryTimer: ReturnType<typeof setTimeout> | undefined;
let leftPage = false;

// Shows "Price unavailable right now" and asks again in 10 seconds.
function markPriceUnavailable(): void {
    if (leftPage) {
        return;
    }

    priceUnavailable.value = true;
    retryTimer = setTimeout(requestEstimate, PRICE_RETRY_MS);
}

function requestEstimate(): void {
    if (leftPage) {
        return;
    }

    clearTimeout(retryTimer);
    estimateRequest.cancel();

    void estimateRequest
        .submit(estimateRoute(), {
            onSuccess: (response) => {
                estimate.value = response;
                estimateError.value = null;
                priceUnavailable.value = false;
            },
            // Invalid input (a prompt over 4,000 characters): no estimate until it is fixed.
            onError: (errors) => {
                estimate.value = null;
                estimateError.value = Object.values(errors)[0] ?? null;
                priceUnavailable.value = false;
            },
            onHttpException: markPriceUnavailable,
            onNetworkError: markPriceUnavailable,
        })
        .catch(() => {});
}

const requestEstimateSoon = useDebounceFn(
    requestEstimate,
    ESTIMATE_DEBOUNCE_MS,
);

watch(
    () => [form.prompt, form.size, form.quality, form.reference_images.length],
    requestEstimateSoon,
);

onMounted(requestEstimate);
onBeforeUnmount(() => {
    leftPage = true;
    clearTimeout(retryTimer);
    estimateRequest.cancel();
});

function addReferenceImage(): void {
    if (form.reference_images.length < MAX_REFERENCE_IMAGES) {
        form.reference_images.push('');
    }
}

function removeReferenceImage(index: number): void {
    form.reference_images.splice(index, 1);
}
</script>

<template>
    <Head title="Generate" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="Generate an image"
            description="Describe the image, pick its size and quality, and optionally add reference images to guide the style."
        />

        <form class="flex flex-col gap-6" @submit.prevent>
            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="prompt">Prompt</Label>
                    <span class="text-xs text-muted-foreground tabular-nums">
                        {{ promptLength }} / {{ MAX_PROMPT_LENGTH }}
                    </span>
                </div>
                <textarea
                    id="prompt"
                    v-model="form.prompt"
                    rows="5"
                    required
                    placeholder="A lighthouse on a cliff at dawn, oil painting"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                ></textarea>
                <InputError :message="form.errors.prompt" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="size">Size</Label>
                    <select
                        id="size"
                        v-model="form.size"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option v-for="size in sizes" :key="size" :value="size">
                            {{ size }}
                        </option>
                    </select>
                    <InputError :message="form.errors.size" />
                </div>

                <div class="grid gap-2">
                    <Label for="quality">Quality</Label>
                    <select
                        id="quality"
                        v-model="form.quality"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option
                            v-for="quality in qualities"
                            :key="quality"
                            :value="quality"
                        >
                            {{ quality }}
                        </option>
                    </select>
                    <InputError :message="form.errors.quality" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Reference images</Label>
                <div
                    v-for="(_url, index) in form.reference_images"
                    :key="index"
                    class="flex gap-2"
                >
                    <Input
                        v-model="form.reference_images[index]"
                        type="url"
                        :placeholder="REFERENCE_IMAGE_PLACEHOLDER"
                        :aria-label="`Reference image ${index + 1}`"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        @click="removeReferenceImage(index)"
                    >
                        Remove
                    </Button>
                </div>
                <InputError :message="form.errors.reference_images" />
                <div>
                    <Button
                        v-if="
                            form.reference_images.length < MAX_REFERENCE_IMAGES
                        "
                        type="button"
                        variant="outline"
                        @click="addReferenceImage"
                    >
                        Add reference image
                    </Button>
                </div>
            </div>

            <div class="grid gap-1 rounded-md border p-4 text-sm">
                <p>
                    Balance
                    <Amount :amount="estimate?.balance ?? balance" />
                </p>
                <p v-if="priceUnavailable" class="text-destructive">
                    Price unavailable right now
                </p>
                <p v-else-if="estimateError" class="text-destructive">
                    {{ estimateError }}
                </p>
                <p v-else-if="estimate">
                    Estimate
                    <Amount :amount="estimate.minimum" /> –
                    <Amount :amount="estimate.maximum" />
                </p>
                <p v-else class="text-muted-foreground">Estimating…</p>
                <p class="text-muted-foreground">
                    You're charged the exact amount when the image is ready.
                    Failed generations are free.
                </p>
            </div>

            <div>
                <p
                    v-if="
                        estimate && !estimate.enoughCredits && !priceUnavailable
                    "
                    class="mb-3 text-sm text-destructive"
                >
                    Not enough credits: this can cost up to
                    <Amount :amount="estimate.maximum" /> and your balance is
                    <Amount :amount="estimate.balance" />
                </p>
                <Button
                    type="submit"
                    :disabled="
                        priceUnavailable || !estimate || !estimate.enoughCredits
                    "
                >
                    Generate
                </Button>
            </div>
        </form>
    </div>
</template>
