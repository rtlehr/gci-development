<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    CircleUserRound,
    ClipboardMinus,
    ArrowLeftRight,
} from 'lucide-vue-next'

import AppLogo from '@/components/AppLogo.vue'
import NavFooter from '@/components/NavFooter.vue'
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
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
]

const mainNavItems = computed(() =>
    allMainNavItems.filter((item) => {
        if (!item.permission) {
            return true
        }

        return can(item.permission)
    })
)

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>