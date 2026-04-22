<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    LayoutGrid,
    CircleUserRound,
    ClipboardMinus,
    ArrowLeftRight,
    LifeBuoy,
    HelpCircle,
    Users
} from 'lucide-vue-next'

import AppLogo from '@/components/AppLogo.vue'
import NavMain from '@/components/NavMain.vue'
import { useAuth } from '@/composables/useAuth'

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'

import { dashboard } from '@/routes'
import type { NavItem } from '@/types'
import TicketQuickLink from '@/components/TicketQuickLink.vue'

const { can } = useAuth()

const allMainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'People',
        href: '/people',
        icon: CircleUserRound,
    },
    {
        title: 'Positions',
        href: '/positions',
        icon: ClipboardMinus,
    },
    {
        title: 'User Permissions',
        href: '/admin/users',
        icon: ArrowLeftRight,
        permission: 'view_admin',
    },
    {
        title: 'Edit Permissions',
        href: '/admin/permissions',
        icon: ArrowLeftRight,
        permission: 'view_admin',
    },
    {
        title: 'Edit Roles',
        href: '/admin/roles',
        icon: ArrowLeftRight,
        permission: 'view_admin',
    },
    {
        title: 'View Tickets',
        href: '/admin/tickets',
        icon: LifeBuoy,
        permission: 'view_admin',
    },
    {
        title: 'Add Help Page',
        href: '/admin/page-help',
        icon: HelpCircle ,
        permission: 'view_admin',
    },
    {
        title: 'Candidates',
        href: '/candidates',
        icon: Users ,
        permission: 'view_admin',
    },
]

const mainNavItems = computed(() =>
    allMainNavItems.filter((item) => {
        if (!item.permission) {
            return true
        }

        return can(item.permission)
    })
)
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
            <TicketQuickLink />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>