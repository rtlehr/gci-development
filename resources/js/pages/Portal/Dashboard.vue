<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, BriefcaseBusiness, LifeBuoy, Send } from 'lucide-vue-next';
import DashboardAlerts from '@/components/DashboardAlerts.vue';
import CandidateOpportunitiesCard from '@/components/dashboard/CandidateOpportunitiesCard.vue';
import MySubmittedTickets from '@/components/MySubmittedTickets.vue';
import StaffingMatrix from '@/components/dashboard/StaffingMatrix.vue';
import TicketsAssignedToMe from '@/components/TicketsAssignedToMe.vue';
import { useAuth } from '@/composables/useAuth';
import { computed } from 'vue';

const { username } = useAuth();
const page = usePage();
const features = computed(() =>
    ((page.props.siteSettings as { features?: { support_tickets?: boolean; alerts?: boolean; candidate_opportunities?: boolean } })?.features ?? {}),
);
const supportTicketsEnabled = computed(() => features.value.support_tickets ?? true);
const alertsEnabled = computed(() => features.value.alerts ?? true);
const candidateOpportunitiesEnabled = computed(() => features.value.candidate_opportunities ?? true);

type StaffingSummary = {
    vacant: number;
    selected: number;
    filled: number;
    departing: number;
    onHold: number;
};

type StaffingColumn = {
    key: string;
    label: string;
    default_visible?: boolean;
    default_order?: number;
};

type DashboardSummary = {
    unreadAlerts: number;
    assignedTickets: number;
    submittedTickets: number;
    assignedPositions: number;
    positionsLabel?: string;
};

withDefaults(defineProps<{
    alerts?: any[];
    assignedTickets?: any[];
    submittedTickets?: any[];
    assignedPositions?: any[];
    pmoPositions?: any[];
    candidateOpportunities?: any[];
    showPmoPositions?: boolean;
    showProjectManagerPositions?: boolean;
    showCandidateOpportunities?: boolean;
    staffingSummary?: StaffingSummary;
    staffingColumns?: StaffingColumn[];
    staffingVisibleColumns?: string[];
    staffingColumnOrder?: string[];
    summary?: DashboardSummary;
}>(), {
    alerts: () => [],
    assignedTickets: () => [],
    submittedTickets: () => [],
    assignedPositions: () => [],
    pmoPositions: () => [],
    candidateOpportunities: () => [],
    showPmoPositions: false,
    showProjectManagerPositions: false,
    showCandidateOpportunities: false,
    staffingSummary: () => ({
        vacant: 0,
        selected: 0,
        filled: 0,
        departing: 0,
        onHold: 0,
    }),
    staffingColumns: () => [],
    staffingVisibleColumns: () => [],
    staffingColumnOrder: () => [],
    summary: () => ({
        unreadAlerts: 0,
        assignedTickets: 0,
        submittedTickets: 0,
        assignedPositions: 0,
        positionsLabel: 'assigned positions',
    }),
});
</script>

<template>
    <Head title="My Portal" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">My Portal</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Welcome, {{ username }}</h1>
            <p class="mt-3 max-w-2xl text-[#3a3a3a]/70">
                Review your assigned work, staffing, and available portal information from one place.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div
            v-if="!(showPmoPositions || showProjectManagerPositions)"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Link v-if="alertsEnabled" href="/portal/alerts" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm transition hover:-translate-y-0.5">
                <Bell class="h-6 w-6 text-[#005c43]" />
                <h2 class="mt-3 font-bold">Alerts</h2>
                <p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.unreadAlerts }} unread</p>
            </Link>

            <Link v-if="supportTicketsEnabled" href="/portal/tickets" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm transition hover:-translate-y-0.5">
                <Send class="h-6 w-6 text-[#005c43]" />
                <h2 class="mt-3 font-bold">My requests</h2>
                <p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.submittedTickets }} recent</p>
            </Link>

            <article v-if="supportTicketsEnabled" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm">
                <LifeBuoy class="h-6 w-6 text-[#005c43]" />
                <h2 class="mt-3 font-bold">Assigned tickets</h2>
                <p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.assignedTickets }} active</p>
            </article>

            <article v-if="candidateOpportunitiesEnabled" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm">
                <BriefcaseBusiness class="h-6 w-6 text-[#005c43]" />
                <h2 class="mt-3 font-bold">My opportunities</h2>
                <p class="mt-1 text-sm text-[#3a3a3a]/70">
                    {{ summary.assignedPositions }} {{ summary.positionsLabel ?? 'opportunities' }}
                </p>
            </article>
        </div>

        <StaffingMatrix
            v-if="showPmoPositions || showProjectManagerPositions"
            :positions="showPmoPositions ? pmoPositions : assignedPositions"
            :summary="staffingSummary"
            :columns="staffingColumns"
            :visible-columns="staffingVisibleColumns"
            :column-order="staffingColumnOrder"
            :title="showPmoPositions ? 'All Positions — Staffing Matrix' : 'My Assigned Positions'"
            :description="showPmoPositions
                ? 'All positions with staffing status and Candidate Workflow details.'
                : 'Positions where you are assigned as the project manager.'"
        />

        <CandidateOpportunitiesCard
            v-else-if="showCandidateOpportunities"
            :opportunities="candidateOpportunities"
        />

        <div v-if="alertsEnabled || supportTicketsEnabled" class="grid gap-6 lg:grid-cols-2">
            <DashboardAlerts v-if="alertsEnabled" :alerts="alerts" />
            <MySubmittedTickets v-if="supportTicketsEnabled" :tickets="submittedTickets" />
        </div>

        <TicketsAssignedToMe v-if="supportTicketsEnabled && assignedTickets.length" :tickets="assignedTickets" />
    </div>
</template>
