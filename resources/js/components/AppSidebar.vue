<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    FileText,
    HelpCircle,
    LayoutDashboard,
    LifeBuoy,
    Settings,
    ShieldCheck,
    SquareArrowOutUpRight,
} from 'lucide-vue-next';

import AppLogo from '@/components/AppLogo.vue';
import DevUserSwitcher from '@/components/dev/DevUserSwitcher.vue';
import NavMain from '@/components/NavMain.vue';
import TicketQuickLink from '@/components/TicketQuickLink.vue';
import { useAuth } from '@/composables/useAuth';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

type NavCategory = {
    title: string;
    items: NavItem[];
};

const page = usePage();
const { can, user } = useAuth();

const isImpersonating = computed(
    () => page.props.dev?.isImpersonating === true,
);
const devSwitcherAvailable = computed(
    () => page.props.dev?.available === true,
);

const navigation: NavCategory[] = [
    {
        title: 'Admin',
        items: [
            {
                title: 'Admin Portal',
                href: '/admin',
                icon: LayoutDashboard,
                permission: 'view_admin',
            },
            {
                title: 'View Portal',
                href: '/portal/dashboard',
                icon: SquareArrowOutUpRight,
                permission: 'view_admin',
            },
        ],
    },
    {
        title: 'Management',
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
                permission: 'access_content_pages',
            },
            {
                title: 'Page Help',
                href: '/admin/page-help',
                icon: HelpCircle,
                permission: 'access_page_help',
            },
            {
                title: 'Site Settings',
                href: '/admin/site-settings',
                icon: Settings,
                permission: 'access_site_settings',
            },
            {
                title: 'Impersonation',
                href: '/admin/impersonation',
                icon: ShieldCheck,
                permission: 'view_impersonation_log',
            },
        ],
    },
];

const visibleNavigation = computed(() =>
    navigation
        .map((category) => ({
            ...category,
            items: category.items.filter(
                (item) => !item.permission || can(item.permission),
            ),
        }))
        .filter((category) => category.items.length > 0),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <div
            v-if="devSwitcherAvailable && isImpersonating && user"
            class="m-2 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-xs text-yellow-800"
            role="status"
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
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        tooltip="Admin Portal"
                    >
                        <Link href="/admin" aria-label="Open Admin Portal">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div
                v-for="category in visibleNavigation"
                :key="category.title"
                class="px-2 py-2"
            >
                <div
                    class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                >
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
