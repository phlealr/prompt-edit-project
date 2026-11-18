<script setup lang="ts">
import { ref } from 'vue';
import Amount from '@/components/Amount.vue';
import { Badge } from '@/components/ui/badge';
import { exactTime, relativeTime } from '@/lib/relativeTime';
import type { GenerationCard } from '@/types';

defineProps<{ generation: GenerationCard }>();

const promptExpanded = ref(false);

const badgeVariant = {
    pending: 'secondary',
    queued: 'secondary',
    processing: 'default',
    succeeded: 'default',
    failed: 'destructive',
} as const;
</script>

<template>
    <article class="flex flex-col gap-3 rounded-xl border p-4 text-sm">
        <div
            class="flex aspect-square items-center justify-center rounded-lg bg-muted text-muted-foreground"
        >
            {{ generation.status }}
        </div>

        <div class="flex flex-col gap-1">
            <Badge :variant="badgeVariant[generation.status]" class="w-fit">
                {{ generation.status }}
            </Badge>
            <p
                v-if="generation.hint"
                :class="
                    generation.status === 'failed'
                        ? 'text-destructive'
                        : 'text-muted-foreground'
                "
            >
                {{ generation.hint }}
            </p>
        </div>

        <button
            type="button"
            class="text-left"
            :class="{ 'line-clamp-3': !promptExpanded }"
            :aria-expanded="promptExpanded"
            @click="promptExpanded = !promptExpanded"
        >
            {{ generation.prompt }}
        </button>

        <p class="text-muted-foreground">
            {{ generation.size }} · {{ generation.quality }} ·
            {{ generation.referenceImageCount }} reference
            {{ generation.referenceImageCount === 1 ? 'image' : 'images' }}
        </p>

        <div>
            <p>
                Estimate <Amount :amount="generation.estimateMinimum" /> –
                <Amount :amount="generation.estimateMaximum" />
            </p>
            <p v-if="generation.charge">
                Charged <Amount :amount="generation.charge" />
            </p>
            <p v-else-if="generation.status === 'failed'">Not charged</p>
        </div>

        <p class="text-xs text-muted-foreground">
            <time
                :datetime="generation.createdAt"
                :title="exactTime(generation.createdAt)"
            >
                {{ relativeTime(generation.createdAt) }}
            </time>
        </p>
    </article>
</template>
