<script setup lang="ts">
import type { Money } from '@/types';

defineProps<{ amount: Money }>();
</script>

<template>
    <span :title="amount.exact" class="tabular-nums">{{ amount.user }}</span>
</template>
