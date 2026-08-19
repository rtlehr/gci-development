<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const page = usePage();
const { user, can } = useAuth();
const supportEnabled = computed(() =>
    ((page.props.siteSettings as { features?: { support_tickets?: boolean } })?.features?.support_tickets ?? true),
);

const cmsItems = computed(
    () => (page.props.contentNavigation as Array<{ label: string; href: string }>) ?? [],
);

const supportHref = computed(() => {
    if (!supportEnabled.value) return null;
    if (!user.value) return '/home#support';
    if (can('portal_view_own_tickets')) return '/portal/tickets';
    if (can('portal_create_tickets')) return '/portal/tickets/create';

    return null;
});

const items = computed(() => {
    const result = [{ label: 'Home', href: '/home' }];

    if (user.value && can('access_portal') && can('portal_view_dashboard')) {
        result.push({ label: 'My Portal', href: '/portal/dashboard' });
    }

    result.push(...cmsItems.value);

    if (supportHref.value) {
        result.push({ label: 'Support', href: supportHref.value });
    }

    return result;
});

function isActive(href: string): boolean {
    if (href === '/home') return page.url === '/home' || page.url === '/';

    return !href.includes('#') && page.url.startsWith(href);
}
</script>

<template>
    <nav aria-label="Primary navigation" class="hidden items-center gap-1 lg:flex">
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            :aria-current="isActive(item.href) ? 'page' : undefined"
            class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-[var(--portal-primary-soft)] hover:text-[var(--portal-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--portal-primary)]"
            :class="isActive(item.href)
                ? 'bg-[var(--portal-primary-soft)] text-[var(--portal-primary)]'
                : 'text-[var(--portal-text)]'"
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
