<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const page = usePage();
const { user } = useAuth();

const items = computed(() => {
    const publicItems = [
        { label: 'Home', href: '/' },
        { label: 'Program', href: '/#program' },
        { label: 'Resources', href: '/#resources' },
        { label: 'FAQs', href: '/#faqs' },
        { label: 'Support', href: '/#support' },
    ];

    if (!user.value) return publicItems;

    return [
        { label: 'Home', href: '/' },
        { label: 'My Portal', href: '/portal/dashboard' },
        { label: 'Directory', href: '/portal/directory', disabled: true },
        { label: 'Positions', href: '/portal/positions', disabled: true },
        { label: 'Requests', href: '/portal/requests', disabled: true },
        { label: 'Resources', href: '/#resources' },
        { label: 'Support', href: '/#support' },
    ];
});

function isActive(href: string): boolean {
    if (href === '/') return page.url === '/';
    return !href.includes('#') && page.url.startsWith(href);
}
</script>

<template>
    <nav aria-label="Primary navigation" class="hidden items-center gap-1 lg:flex">
        <template v-for="item in items" :key="item.label">
            <span
                v-if="item.disabled"
                class="cursor-not-allowed rounded-md px-3 py-2 text-sm font-medium text-slate-400"
                :title="`${item.label} will be added in a later portal phase`"
            >
                {{ item.label }}
            </span>
            <Link
                v-else
                :href="item.href"
                class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-[#005c43]/10 hover:text-[#005c43] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005c43]"
                :class="isActive(item.href) ? 'bg-[#005c43]/10 text-[#005c43]' : 'text-[#3a3a3a]'"
            >
                {{ item.label }}
            </Link>
        </template>
    </nav>
</template>
