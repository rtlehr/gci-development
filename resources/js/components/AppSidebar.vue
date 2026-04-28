<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    LayoutGrid,
    CircleUserRound,
    ClipboardMinus,
    ArrowLeftRight,
    LifeBuoy,
    HelpCircle,
    Users,
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
import DevUserSwitcher from '@/components/dev/DevUserSwitcher.vue'

const page = usePage()

const { can, user } = useAuth()

const devDebug = computed(() => page.props.dev?.debug === true)

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
        icon: HelpCircle,
        permission: 'view_admin',
    },
    {
        title: 'Candidates',
        href: '/candidates',
        icon: Users,
        permission: 'view_admin',
    },
]

const mainNavItems = computed(() =>
    allMainNavItems.filter((item) => {
        if (!item.permission) {
            return true
        }

        return can(item.permission)
    }),
)
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">

        <div
            v-if="devDebug && user"
            class="rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-xs text-yellow-800"
        >
            <div class="font-semibold">Impersonating</div>
            <div>
                {{ user.username }} 
                <span v-if="user.person_code">
                    ({{ user.person_code }})
                </span>
            </div>
        </div>

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
            
            <DevUserSwitcher />

        </SidebarFooter>
    </Sidebar>

    <slot />
</template>