<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import type { GenerationCard } from '@/types';

const props = defineProps<{ generation: GenerationCard }>();

// The provider's URL works until it expires; the app keeps no copy (ADR 0012).
const expired = ref(false);
const failedToLoad = ref(false);

let expiryTimer: ReturnType<typeof setTimeout> | undefined;

// Switches to "Image expired" when the expiry passes while the page is open. Watched, because a
// card can become succeeded on a later reload of the page's data.
watch(
    () => props.generation.imageUrlExpiresAt,
    (imageUrlExpiresAt) => {
        clearTimeout(expiryTimer);

        if (imageUrlExpiresAt === null) {
            return;
        }

        const msUntilExpiry =
            new Date(imageUrlExpiresAt).getTime() - Date.now();
        expired.value = msUntilExpiry <= 0;

        if (!expired.value) {
            expiryTimer = setTimeout(() => {
                expired.value = true;
            }, msUntilExpiry);
        }
    },
    { immediate: true },
);
onBeforeUnmount(() => clearTimeout(expiryTimer));
</script>

<template>
    <div
        class="flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-muted text-sm text-muted-foreground"
    >
        <template
            v-if="generation.status === 'succeeded' && generation.imageUrl"
        >
            <span v-if="expired">Image expired</span>
            <span v-else-if="failedToLoad">Image unavailable</span>
            <a
                v-else
                :href="generation.imageUrl"
                target="_blank"
                rel="noopener"
                class="size-full"
            >
                <img
                    :src="generation.imageUrl"
                    :alt="generation.prompt"
                    loading="lazy"
                    class="size-full object-cover"
                    @error="failedToLoad = true"
                />
            </a>
        </template>
        <span v-else>{{ generation.status }}</span>
    </div>
</template>
