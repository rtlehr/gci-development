<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    FileText,
    HelpCircle,
    LayoutGrid,
    LifeBuoy,
    Settings,
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

import type { NavItem } from '@/types'
import TicketQuickLink from '@/components/TicketQuickLink.vue'
import DevUserSwitcher from '@/components/dev/DevUserSwitcher.vue'

type NavCategory = {
    title: string
    items: NavItem[]
}

const page = usePage()
const isImpersonating = computed(() => page.props.dev?.isImpersonating === true)
const { can, user } = useAuth()
const devDebug = computed(() => page.props.dev?.debug === true)

const allMainNavCategories: NavCategory[] = [
    {
        title: 'Navigation',
        items: [
            {
                title: 'Admin Portal',
                href: '/admin',
                icon: LayoutGrid,
                permission: 'view_admin',
            },
            {
                title: 'View Portal',
                href: '/portal/dashboard',
                icon: LayoutGrid,
                permission: 'view_admin',
            },
        ],
    },
    {
        title: 'Quick Access',
        items: [
            {
                title: 'Support Tickets',
                href: '/admin/tickets',
                icon: LifeBuoy,
                permission: 'access_tickets',
            },
            {
                title: 'Content Pages',
                href: '/admin/content-pages',
                icon: FileText,
                permission: 'view_admin',
            },
            {
                title: 'Page Help',
                href: '/admin/page-help',
                icon: FileText,
    HelpCircle,
                permission: 'view_admin',
            },
            {
                title: 'Site Settings',
                href: '/admin/site-settings',
                icon: Settings,
                permission: 'view_admin',
            },
        ],
    },
]

const mainNavCategories = computed(() =>
    allMainNavCategories
        .map((category) => ({
            ...category,
            items: category.items.filter((item) => {
                if (!item.permission) return true
                return can(item.permission)
            }),
        }))
        .filter((category) => category.items.length > 0),
)
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <div
            v-if="devDebug && isImpersonating && user"
            class="rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-xs text-yellow-800"
        >
            <div class="font-semibold">Impersonating</div>
            <div>
                {{ user.username }}
                <span v-if="user.person_code">({{ user.person_code }})</span>
            </div>
        </div>

        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div
                v-for="category in mainNavCategories"
                :key="category.title"
                class="px-2 py-2"
            >
                <div class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ category.title }}
                </div>

                <NavMain :items="category.items" />
            </div>
        </SidebarContent>

        <SidebarFooter>
            <TicketQuickLink />
            <DevUserSwitcher />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
