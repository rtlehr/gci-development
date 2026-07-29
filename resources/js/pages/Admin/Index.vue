<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Clock3,
    CornerDownLeft,
    ExternalLink,
    Search,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import SectionHeader from '@/components/layout/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useAuth } from '@/composables/useAuth';
import {
    adminPortalGroupOrder,
    adminPortalItems,
    adminPortalSearchText,
    normalizeAdminPortalSearch,
    type AdminPortalGroup,
    type AdminPortalItem,
} from '@/support/adminPortal';

type RecentTool = {
    id: string;
    title: string;
    href: string;
    visitedAt: number;
};

const RECENT_STORAGE_KEY = 'irad_admin_recent_tools';
const RECENT_LIMIT = 6;

const { username, can } = useAuth();
const query = ref('');
const searchInput = ref<HTMLInputElement | null>(null);
const activeResultIndex = ref(0);
const recentTools = ref<RecentTool[]>([]);

const canAccessItem = (item: AdminPortalItem): boolean => {
    if (item.permission && !can(item.permission)) {
        return false;
    }

    if (item.permissionsAny?.length && !item.permissionsAny.some((permission) => can(permission))) {
        return false;
    }

    return true;
};

const visibleItems = computed(() => adminPortalItems.filter(canAccessItem));
const actionableItems = computed(() =>
    visibleItems.value.filter((item) => item.href && !item.comingSoon),
);
const normalizedQuery = computed(() => normalizeAdminPortalSearch(query.value));

const searchResults = computed(() => {
    if (!normalizedQuery.value) {
        return [];
    }

    const terms = normalizedQuery.value.split(' ').filter(Boolean);

    return visibleItems.value
        .map((item) => {
            const haystack = adminPortalSearchText(item);
            const matchedTerms = terms.filter((term) => haystack.includes(term));
            const title = normalizeAdminPortalSearch(item.title);

            let score = matchedTerms.length * 10;

            if (title === normalizedQuery.value) score += 50;
            if (title.startsWith(normalizedQuery.value)) score += 30;
            if (title.includes(normalizedQuery.value)) score += 20;
            if (item.keywords.some((keyword) => normalizeAdminPortalSearch(keyword) === normalizedQuery.value)) {
                score += 25;
            }

            return { item, score, matches: matchedTerms.length };
        })
        .filter((result) => result.matches === terms.length)
        .sort((a, b) => b.score - a.score || a.item.title.localeCompare(b.item.title))
        .map((result) => result.item);
});

const groupedSections = computed(() =>
    adminPortalGroupOrder
        .map((group) => ({
            group,
            items: visibleItems.value.filter((item) => item.group === group),
        }))
        .filter((section) => section.items.length > 0),
);

const quickActions = computed(() =>
    visibleItems.value
        .filter((item) => item.quick && item.href && !item.comingSoon)
        .slice(0, 7),
);

const recentResolvedItems = computed(() =>
    recentTools.value
        .map((recent) => {
            const item = visibleItems.value.find((candidate) => candidate.id === recent.id);

            return item?.href && !item.comingSoon ? item : null;
        })
        .filter((item): item is AdminPortalItem => item !== null),
);

function loadRecentTools(): void {
    try {
        const parsed = JSON.parse(localStorage.getItem(RECENT_STORAGE_KEY) ?? '[]');

        if (Array.isArray(parsed)) {
            recentTools.value = parsed
                .filter((item) =>
                    item &&
                    typeof item.id === 'string' &&
                    typeof item.title === 'string' &&
                    typeof item.href === 'string' &&
                    typeof item.visitedAt === 'number',
                )
                .sort((a, b) => b.visitedAt - a.visitedAt)
                .slice(0, RECENT_LIMIT);
        }
    } catch {
        recentTools.value = [];
    }
}

function rememberTool(item: AdminPortalItem): void {
    if (!item.href || item.comingSoon) {
        return;
    }

    const next: RecentTool[] = [
        {
            id: item.id,
            title: item.title,
            href: item.href,
            visitedAt: Date.now(),
        },
        ...recentTools.value.filter((recent) => recent.id !== item.id),
    ].slice(0, RECENT_LIMIT);

    recentTools.value = next;
    localStorage.setItem(RECENT_STORAGE_KEY, JSON.stringify(next));
}

function openItem(item: AdminPortalItem): void {
    if (!item.href || item.comingSoon) {
        return;
    }

    rememberTool(item);
    router.visit(item.href);
}

function clearSearch(): void {
    query.value = '';
    activeResultIndex.value = 0;
    nextTick(() => searchInput.value?.focus());
}

function focusSearch(): void {
    nextTick(() => searchInput.value?.focus());
}

function onSearchKeydown(event: KeyboardEvent): void {
    if (!searchResults.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeResultIndex.value = (activeResultIndex.value + 1) % searchResults.value.length;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeResultIndex.value =
            (activeResultIndex.value - 1 + searchResults.value.length) % searchResults.value.length;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        const item = searchResults.value[activeResultIndex.value];

        if (item) openItem(item);
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        clearSearch();
    }
}

function onGlobalKeydown(event: KeyboardEvent): void {
    const target = event.target as HTMLElement | null;
    const isTyping =
        target?.tagName === 'INPUT' ||
        target?.tagName === 'TEXTAREA' ||
        target?.isContentEditable;

    if (event.key === '/' && !isTyping) {
        event.preventDefault();
        focusSearch();
    }
}

onMounted(() => {
    loadRecentTools();
    window.addEventListener('keydown', onGlobalKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
});
</script>

<template>
    <Head title="Admin Portal" />

    <PageContainer v-if="can('view_admin')" class="space-y-8">
        <PageHeader
            eyebrow="Administration"
            title="Admin Portal"
            :description="`Welcome, ${username}. Search or browse every administrative page, tool, setting, and action from one place.`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/portal/dashboard">
                        Return to Portal
                        <ExternalLink class="ml-2 h-4 w-4" />
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <section aria-labelledby="admin-search-heading" class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="admin-search-heading" class="text-lg font-semibold">
                            Find an admin tool
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Search modules, pages, settings, actions, reports, and configuration areas.
                        </p>
                    </div>

                    <div class="hidden items-center gap-1 rounded-md border bg-muted/40 px-2 py-1 text-xs text-muted-foreground sm:flex">
                        <span>/</span>
                        <span>to focus</span>
                    </div>
                </div>

                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />

                    <Input
                        ref="searchInput"
                        v-model="query"
                        class="h-14 rounded-xl pl-12 pr-12 text-base"
                        placeholder="Search admin pages, tools, settings, and actions..."
                        role="combobox"
                        aria-label="Search admin pages, tools, settings, and actions"
                        :aria-expanded="normalizedQuery.length > 0"
                        aria-controls="admin-search-results"
                        @keydown="onSearchKeydown"
                        @input="activeResultIndex = 0"
                    />

                    <Button
                        v-if="query"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute right-2 top-1/2 -translate-y-1/2"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <div
                    v-if="normalizedQuery"
                    id="admin-search-results"
                    class="mt-4 overflow-hidden rounded-xl border"
                    role="listbox"
                >
                    <template v-if="searchResults.length">
                        <button
                            v-for="(item, index) in searchResults"
                            :key="item.id"
                            type="button"
                            class="flex w-full items-start gap-4 border-b px-4 py-4 text-left transition last:border-b-0 hover:bg-muted/50"
                            :class="index === activeResultIndex ? 'bg-muted/70' : ''"
                            role="option"
                            :aria-selected="index === activeResultIndex"
                            :disabled="!item.href || item.comingSoon"
                            @mouseenter="activeResultIndex = index"
                            @click="openItem(item)"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-background text-primary">
                                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">{{ item.title }}</span>
                                    <span class="text-xs text-muted-foreground">{{ item.group }}</span>
                                    <span
                                        v-if="item.comingSoon"
                                        class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                                    >
                                        Coming soon
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">{{ item.description }}</p>
                            </div>

                            <CornerDownLeft
                                v-if="item.href && !item.comingSoon"
                                class="mt-2 h-4 w-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </button>
                    </template>

                    <div v-else class="px-6 py-10 text-center">
                        <Search class="mx-auto h-8 w-8 text-muted-foreground" aria-hidden="true" />
                        <h3 class="mt-3 font-semibold">No authorized tools matched “{{ query }}”</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Try People, Create Position, Page Help, Roles, Organizations, Workflows, or Site Settings.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="quickActions.length" class="space-y-4">
            <SectionHeader
                title="Common actions"
                description="Start frequently used administrative workflows."
            />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="item in quickActions"
                    :key="item.id"
                    :href="item.href!"
                    class="group flex items-center gap-3 rounded-xl border bg-card p-4 transition hover:border-primary/40 hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    @click="rememberTool(item)"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-muted/40 text-primary">
                        <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">{{ item.title }}</div>
                        <div class="text-xs text-muted-foreground">{{ item.action }}</div>
                    </div>
                    <ArrowRight class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5" />
                </Link>
            </div>
        </section>

        <section v-if="recentResolvedItems.length" class="space-y-4">
            <SectionHeader
                title="Recently used"
                description="Return to administrative tools you opened recently on this device."
            />

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="item in recentResolvedItems"
                    :key="item.id"
                    :href="item.href!"
                    class="inline-flex items-center gap-2 rounded-lg border bg-card px-3 py-2 text-sm font-medium transition hover:border-primary/40 hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    @click="rememberTool(item)"
                >
                    <Clock3 class="h-4 w-4 text-muted-foreground" />
                    {{ item.title }}
                </Link>
            </div>
        </section>

        <section class="space-y-6">
            <SectionHeader
                title="Administrative areas"
                description="Browse every available tool by functional area."
            />

            <div class="space-y-8">
                <section
                    v-for="section in groupedSections"
                    :key="section.group"
                    :aria-labelledby="`admin-group-${section.group.replace(/\s+/g, '-').toLowerCase()}`"
                    class="space-y-3"
                >
                    <div class="flex items-center justify-between gap-4">
                        <h3
                            :id="`admin-group-${section.group.replace(/\s+/g, '-').toLowerCase()}`"
                            class="text-base font-semibold"
                        >
                            {{ section.group }}
                        </h3>
                        <span class="text-xs text-muted-foreground">{{ section.items.length }} tools</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <template v-for="item in section.items" :key="item.id">
                            <Link
                                v-if="item.href && !item.comingSoon"
                                :href="item.href"
                                class="group rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                @click="rememberTool(item)"
                            >
                                <Card class="h-full transition group-hover:-translate-y-0.5 group-hover:border-primary/40 group-hover:shadow-md">
                                    <CardHeader>
                                        <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg border bg-muted/40 text-primary">
                                            <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <CardTitle class="text-base">{{ item.title }}</CardTitle>
                                        <CardDescription>{{ item.description }}</CardDescription>
                                    </CardHeader>
                                    <CardContent class="flex items-center justify-between text-sm font-medium text-primary">
                                        <span>{{ item.action ?? 'Open' }} tool</span>
                                        <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                                    </CardContent>
                                </Card>
                            </Link>

                            <Card v-else class="h-full border-dashed opacity-70">
                                <CardHeader>
                                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg border bg-muted/40 text-muted-foreground">
                                        <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CardTitle class="text-base">{{ item.title }}</CardTitle>
                                        <span class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground">
                                            Coming soon
                                        </span>
                                    </div>
                                    <CardDescription>{{ item.description }}</CardDescription>
                                </CardHeader>
                            </Card>
                        </template>
                    </div>
                </section>
            </div>
        </section>

        <section class="rounded-2xl border bg-muted/25 p-5">
            <div class="flex items-start gap-3">
                <Sparkles class="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                <div>
                    <h2 class="font-semibold">Designed to grow with IRAD</h2>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        New administrative modules only need one catalog entry to appear in search, the correct section,
                        common actions, and recent tools. Favorites and persistent search history can be added after usage patterns are established.
                    </p>
                </div>
            </div>
        </section>
    </PageContainer>
</template>
