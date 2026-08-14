<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BriefcaseBusiness,
    ClipboardList,
    LayoutDashboard,
    ListChecks,
    Menu,
    UserSearch,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import HeaderAlertBell from '@/components/HeaderAlertBell.vue';
import PageHelpButton from '@/components/ui/PageHelpButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Button } from '@/components/ui/button';
import PortalAccountMenu from './PortalAccountMenu.vue';
import PortalManageMenu from './PortalManageMenu.vue';
import PublicPortalNavigation from './PublicPortalNavigation.vue';

const page = usePage();
const contentNavigation = computed(() => (page.props.contentNavigation as Array<{ label: string; href: string }>) ?? []);
const siteSettings = computed(() => page.props.siteSettings as {
    branding: {
        program_mark: string;
        portal_name: string;
    };
});
const { user, username, can } = useAuth();
const supportEnabled = computed(() =>
    ((page.props.siteSettings as { features?: { support_tickets?: boolean } })?.features?.support_tickets ?? true),
);
const mobileOpen = ref(false);

const canOpenAdminDashboard = computed(() => can('view_admin'));

const manageGroups = [
    {
        label: 'Workforce',
        items: [
            { label: 'People', href: '/portal/people', icon: Users, permission: 'portal_view_directory' },
            { label: 'Candidates', href: '/portal/candidates', icon: UserSearch, permission: 'portal_view_positions' },
        ],
    },
    {
        label: 'Staffing',
        items: [
            { label: 'Positions', href: '/portal/positions', icon: BriefcaseBusiness, permission: 'portal_view_positions' },
            { label: 'Job Titles', href: '/portal/job-titles', icon: BadgeCheck, permission: 'portal_view_positions' },
            { label: 'Job Title Requirements', href: '/portal/job-title-requirements', icon: ListChecks, permission: 'portal_view_positions' },
        ],
    },
    {
        label: 'Operations',
        items: [
            { label: 'Requests', href: '/portal/requests', icon: ClipboardList, disabled: true },
        ],
    },
];

const visibleManageGroups = computed(() =>
    manageGroups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => item.disabled || !item.permission || can(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0),
);

const canViewPortalDashboard = computed(
    () => can('access_portal') && can('portal_view_dashboard'),
);

const supportHref = computed(() => {
    if (!supportEnabled.value) return null;
    if (can('portal_view_own_tickets')) return '/portal/tickets';
    if (can('portal_create_tickets')) return '/portal/tickets/create';

    return null;
});

function isActive(href: string): boolean {
    if (href === '/') return page.url === '/';
    return !href.includes('#') && page.url.startsWith(href);
}

function closeMobile(): void {
    mobileOpen.value = false;
}
</script>

<template>
    <header class="border-b border-[var(--portal-border)] bg-[var(--portal-surface)]">
        <div class="mx-auto flex h-20 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <Link
                href="/"
                class="flex shrink-0 items-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--portal-primary)]"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--portal-primary)] text-white">
                    <AppLogoIcon class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-sm font-semibold tracking-wide text-[var(--portal-primary)]">{{ siteSettings.branding.program_mark }}</span>
                    <span class="block text-base font-bold text-[var(--portal-text)]">{{ siteSettings.branding.portal_name }}</span>
                </span>
            </Link>

            <div class="min-w-0 flex-1">
                <PublicPortalNavigation />
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                <PortalManageMenu v-if="user && can('access_portal')" />

                <Button
                    v-if="user && canOpenAdminDashboard"
                    as-child
                    class="hidden gap-2 bg-[var(--portal-primary)] text-white hover:bg-[var(--portal-primary-hover)] sm:inline-flex"
                >
                    <Link href="/admin">
                        <LayoutDashboard class="h-4 w-4" />
                        Dashboard
                    </Link>
                </Button>

                <HeaderAlertBell v-if="user" />
                <PageHelpButton />
                <PortalAccountMenu />

                <Button
                    variant="ghost"
                    size="icon"
                    class="lg:hidden"
                    aria-label="Toggle navigation"
                    aria-controls="portal-mobile-navigation"
                    :aria-expanded="mobileOpen"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Menu class="h-5 w-5" />
                </Button>
            </div>
        </div>

        <div id="portal-mobile-navigation" v-if="mobileOpen" class="border-t border-[var(--portal-border)] bg-[var(--portal-surface)] px-4 py-4 lg:hidden">
            <nav aria-label="Mobile navigation" class="mx-auto grid max-w-7xl gap-4">
                <div v-if="user" class="border-b border-[var(--portal-border)] px-2 pb-3">
                    <div class="text-sm font-semibold text-[var(--portal-text)]">{{ username }}</div>
                    <div class="mt-0.5 text-xs text-muted-foreground">Authenticated portal user</div>
                </div>

                <section>
                    <div class="px-2 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Portal
                    </div>
                    <div class="grid gap-1">
                        <Link
                            href="/"
                            class="rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]"
                            :class="isActive('/') ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]' : ''"
                            @click="closeMobile"
                        >
                            Home
                        </Link>
                        <Link
                            v-if="user && canViewPortalDashboard"
                            href="/portal/dashboard"
                            class="rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]"
                            :class="isActive('/portal/dashboard') ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]' : ''"
                            @click="closeMobile"
                        >
                            My Portal
                        </Link>
                        <Link v-for="item in contentNavigation" :key="item.href" :href="item.href" class="rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]" :class="isActive(item.href) ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]' : ''" @click="closeMobile">{{ item.label }}</Link>
                        <Link v-if="user && supportHref" :href="supportHref" class="rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]" :class="isActive('/portal/tickets') ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]' : ''" @click="closeMobile">Support</Link>
                        <a v-else-if="supportEnabled" href="/#support" class="rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]" @click="closeMobile">Support</a>
                    </div>
                </section>

                <section v-if="user && visibleManageGroups.length">
                    <div class="px-2 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Manage
                    </div>

                    <div class="grid gap-3">
                        <div v-for="group in visibleManageGroups" :key="group.label">
                            <div class="px-3 pb-1 text-xs font-medium text-muted-foreground">{{ group.label }}</div>

                            <div class="grid gap-1">
                                <template v-for="item in group.items" :key="item.label">
                                    <div
                                        v-if="item.disabled"
                                        class="flex cursor-not-allowed items-center gap-2 rounded-md px-3 py-2 font-medium text-muted-foreground opacity-50"
                                        :title="`${item.label} will be added in a later portal phase`"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        {{ item.label }}
                                    </div>

                                    <Link
                                        v-else
                                        :href="item.href"
                                        class="flex items-center gap-2 rounded-md px-3 py-2 font-medium hover:bg-[var(--portal-primary-soft)]"
                                        :class="isActive(item.href) ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]' : ''"
                                        @click="closeMobile"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        {{ item.label }}
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                <Link
                    v-if="user && canOpenAdminDashboard"
                    href="/admin"
                    class="flex items-center justify-center gap-2 rounded-md bg-[var(--portal-primary)] px-3 py-2 font-semibold text-white hover:bg-[var(--portal-primary-hover)]"
                    @click="closeMobile"
                >
                    <LayoutDashboard class="h-4 w-4" />
                    Dashboard
                </Link>
            </nav>
        </div>
    </header>
</template>
