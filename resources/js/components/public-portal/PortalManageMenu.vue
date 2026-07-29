<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BriefcaseBusiness,
    ChevronDown,
    ClipboardList,
    ListChecks,
    UserSearch,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const { can } = useAuth();

const groups = [
    {
        label: 'Workforce',
        items: [
            {
                label: 'People',
                description: 'Manage personnel, contact details, and access.',
                href: '/portal/people',
                icon: Users,
                permission: 'portal_view_directory',
            },
            {
                label: 'Candidates',
                description: 'Manage candidates and hiring workflows.',
                href: '/portal/candidates',
                icon: UserSearch,
                permission: 'portal_view_positions',
            },
        ],
    },
    {
        label: 'Staffing',
        items: [
            {
                label: 'Positions',
                description: 'Manage staffing requirements and assignments.',
                href: '/portal/positions',
                icon: BriefcaseBusiness,
                permission: 'portal_view_positions',
            },
            {
                label: 'Job Titles',
                description: 'Manage titles, default skills, and tasks.',
                href: '/portal/job-titles',
                icon: BadgeCheck,
                permission: 'portal_view_positions',
            },
            {
                label: 'Job Title Requirements',
                description: 'Review skills and task requirements by title.',
                href: '/portal/job-title-requirements',
                icon: ListChecks,
                permission: 'portal_view_positions',
            },
        ],
    },
    {
        label: 'Operations',
        items: [
            {
                label: 'Requests',
                description: 'Manage operational requests and approvals.',
                href: '/portal/requests',
                icon: ClipboardList,
                disabled: true,
            },
        ],
    },
];


const visibleGroups = computed(() =>
    groups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => item.disabled || !item.permission || can(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0),
);

const managePrefixes = [
    '/portal/people',
    '/portal/candidates',
    '/portal/positions',
    '/portal/job-titles',
    '/portal/job-title-requirements',
    '/portal/requests',
];

const isManageActive = computed(() =>
    managePrefixes.some((prefix) => page.url.startsWith(prefix)),
);

function isActive(href: string): boolean {
    return page.url.startsWith(href);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                class="hidden gap-1.5 px-3 text-sm font-semibold lg:inline-flex"
                :class="isManageActive
                    ? 'bg-[#005c43]/10 text-[#005c43]'
                    : 'text-[#3a3a3a] hover:bg-[#005c43]/10 hover:text-[#005c43]'"
            >
                Manage
                <ChevronDown class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="w-[min(42rem,calc(100vw-2rem))] p-2"
        >
            <div class="grid gap-2 md:grid-cols-2">
                <div
                    v-for="group in visibleGroups"
                    :key="group.label"
                    class="rounded-lg border border-border/70 p-2"
                    :class="group.label === 'Operations' ? 'md:col-span-2' : ''"
                >
                    <DropdownMenuLabel class="px-2 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        {{ group.label }}
                    </DropdownMenuLabel>

                    <DropdownMenuSeparator class="mb-1" />

                    <template v-for="item in group.items" :key="item.label">
                        <div
                            v-if="item.disabled"
                            class="flex cursor-not-allowed items-start gap-3 rounded-md px-2 py-2.5 opacity-50"
                            :title="`${item.label} will be added in a later portal phase`"
                        >
                            <component :is="item.icon" class="mt-0.5 h-5 w-5 shrink-0 text-[#005c43]" />
                            <div class="min-w-0">
                                <div class="font-medium text-foreground">{{ item.label }}</div>
                                <div class="text-xs leading-5 text-muted-foreground">{{ item.description }}</div>
                            </div>
                        </div>

                        <DropdownMenuItem
                            v-else
                            as-child
                            class="cursor-pointer p-0 focus:bg-transparent"
                        >
                            <Link
                                :href="item.href"
                                class="flex w-full items-start gap-3 rounded-md px-2 py-2.5 transition hover:bg-[#005c43]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005c43]"
                                :class="isActive(item.href) ? 'bg-[#005c43]/10' : ''"
                            >
                                <component :is="item.icon" class="mt-0.5 h-5 w-5 shrink-0 text-[#005c43]" />
                                <div class="min-w-0">
                                    <div
                                        class="font-medium"
                                        :class="isActive(item.href) ? 'text-[#005c43]' : 'text-foreground'"
                                    >
                                        {{ item.label }}
                                    </div>
                                    <div class="text-xs leading-5 text-muted-foreground">{{ item.description }}</div>
                                </div>
                            </Link>
                        </DropdownMenuItem>
                    </template>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
