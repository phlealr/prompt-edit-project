<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Coins } from '@lucide/vue';
import Amount from '@/components/Amount.vue';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { credits } from '@/routes';

const page = usePage();
</script>

<template>
    <SidebarMenu v-if="page.props.balance">
        <SidebarMenuItem>
            <SidebarMenuButton
                as-child
                :tooltip="`Balance ${page.props.balance.amount.user}`"
            >
                <Link :href="credits()">
                    <Coins />
                    <span>Balance</span>
                    <span
                        class="ml-auto"
                        :class="{
                            'text-destructive': page.props.balance.belowZero,
                        }"
                    >
                        <Amount :amount="page.props.balance.amount" />
                    </span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
