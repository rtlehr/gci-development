<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import DashboardAlerts from '@/components/DashboardAlerts.vue'
import PermissionBlock from '@/components/PermissionBlock.vue'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import ProjectManagerPositionsCard from '@/components/dashboard/ProjectManagerPositionsCard.vue'
import TicketsAssignedToMe from '@/components/TicketsAssignedToMe.vue'
import { useAuth } from '@/composables/useAuth'
import { dashboard } from '@/routes'

type AssignedPosition = {
    id: number
    position_code: string | null
    title: string | null
    status: string | null
    candidates_count: number
    candidate_names: string[]
    current_stage: string
    current_stage_count: number
    days_open: number
    next_action: string
    next_action_tone: 'success' | 'warning' | 'danger' | 'info' | 'neutral'
}

const { username, role, permissions } = useAuth()

withDefaults(
    defineProps<{
        alerts: any[]
        assignedTickets?: any[]
        assignedPositions?: AssignedPosition[]
        showProjectManagerPositions?: boolean
    }>(),
    {
        assignedTickets: () => [],
        assignedPositions: () => [],
        showProjectManagerPositions: false,
    },
)

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p>User: {{ username }}</p>
                <p>Role: {{ role }}</p>
                <p>Permissions: {{ permissions.join(', ') }}</p>
            </div>

            <PermissionBlock
                role="admin"
                fallback="This section is only for admins."
            >
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="mb-2">This section is only for admins.</p>
                    <PlaceholderPattern />
                </div>
            </PermissionBlock>

            <PermissionBlock
                permission="access_tickets"
                fallback="This section is for users with the access_tickets permission."
            >
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <TicketsAssignedToMe :tickets="assignedTickets" />
                </div>
            </PermissionBlock>
        </div>

        <ProjectManagerPositionsCard
            v-if="showProjectManagerPositions"
            :positions="assignedPositions"
        />

        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min"
        >
            <DashboardAlerts :alerts="alerts" />
        </div>
    </div>
</template>
