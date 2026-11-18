<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    BookOpen,
    ChartColumn,
    CreditCard,
    FolderGit2,
    History,
    Sparkles,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavBalance from '@/components/NavBalance.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { credits, generate, history, operatorReport } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

// Operators also see the operator report (ADR 0016).
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Generate',
        href: generate(),
        icon: Sparkles,
    },
    {
        title: 'History',
        href: history(),
        icon: History,
    },
    {
        title: 'Credits',
        href: credits(),
        icon: CreditCard,
    },
    ...(page.props.isOperator
        ? [
              {
                  title: 'Operator report',
                  href: operatorReport(),
                  icon: ChartColumn,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/phlealr/prompt-edit-project',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://github.com/phlealr/prompt-edit-project#readme',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="generate()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavBalance />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
