<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Bell, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type HeaderAlert = {
    id: number;
    title: string;
    message?: string | null;
    type: string;
    priority: string;
    action_url?: string | null;
    created_at: string;
};

type HeaderAlerts = {
    count: number;
    recent: HeaderAlert[];
};

const page = usePage();
const featureEnabled = computed(() => {
    const enabled = (page.props.siteSettings as { features?: { alerts?: boolean } })?.features?.alerts ?? true;
    return enabled || page.url.startsWith('/admin');
});

const alerts = computed<HeaderAlerts>(() => {
    return (page.props.headerAlerts as HeaderAlerts) ?? {
        count: 0,
        recent: [],
    };
});


function normalizeActionUrl(url: string): string {
    const mappings: Array<[RegExp, string]> = [
        [/^\/people(\/.*)?$/, '/portal/people$1'],
        [/^\/positions(\/.*)?$/, '/portal/positions$1'],
        [/^\/candidates(\/.*)?$/, '/portal/candidates$1'],
        [/^\/job-titles(\/.*)?$/, '/portal/job-titles$1'],
        [/^\/job-title-requirements(\/.*)?$/, '/portal/job-title-requirements$1'],
        [/^\/tickets(\/.*)?$/, '/portal/tickets$1'],
    ];

    for (const [pattern, replacement] of mappings) {
        if (pattern.test(url)) {
            return url.replace(pattern, replacement);
        }
    }

    return url;
}

function markRead(alert: HeaderAlert) {
    router.patch(
        `/alerts/${alert.id}/read`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function markAllRead() {
    router.patch(
        '/alerts/read-all',
        {},
        {
            preserveScroll: true,
        },
    );
}

function viewItem(alert: HeaderAlert) {
    router.patch(
        `/alerts/${alert.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (alert.action_url) {
                    router.visit(normalizeActionUrl(alert.action_url));
                }
            },
        },
    );
}

</script>

<template>
    <DropdownMenu v-if="featureEnabled">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative" aria-label="Open alerts">
                <Bell class="h-5 w-5" />

                <span
                    v-if="alerts.count > 0"
                    class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-semibold text-white"
                >
                    {{ alerts.count > 99 ? '99+' : alerts.count }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between gap-2">
                <span>Alerts</span>

                <button
                    v-if="alerts.count > 0"
                    type="button"
                    class="text-xs font-normal text-muted-foreground underline hover:text-foreground"
                    @click="markAllRead"
                >
                    Mark all read
                </button>
            </DropdownMenuLabel>

            <DropdownMenuSeparator />

            <div
                v-if="alerts.recent.length === 0"
                class="p-4 text-sm text-muted-foreground"
            >
                You do not have any new alerts.
            </div>

            <template v-else>
                <DropdownMenuItem
                    v-for="alert in alerts.recent"
                    :key="alert.id"
                    class="flex cursor-default flex-col items-start gap-1 p-3"
                >
                    <div class="flex w-full items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-medium">
                                {{ alert.title }}
                            </div>

                            <div
                                v-if="alert.message"
                                class="line-clamp-2 text-xs text-muted-foreground"
                            >
                                {{ alert.message }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 text-muted-foreground hover:text-foreground"
                            title="Mark as read"
                            @click.stop="markRead(alert)"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                        </button>
                    </div>

                    <button
                        v-if="alert.action_url"
                        type="button"
                        class="text-xs text-primary underline"
                        @click.stop="viewItem(alert)"
                    >
                        View item
                    </button>

                </DropdownMenuItem>
            </template>

            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link href="/portal/alerts" class="justify-center font-medium">
                    View all alerts
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>