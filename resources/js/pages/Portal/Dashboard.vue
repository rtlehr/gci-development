<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Bell, BriefcaseBusiness, LifeBuoy } from 'lucide-vue-next';
import DashboardAlerts from '@/components/DashboardAlerts.vue';
import ProjectManagerPositionsCard from '@/components/dashboard/ProjectManagerPositionsCard.vue';
import TicketsAssignedToMe from '@/components/TicketsAssignedToMe.vue';
import { useAuth } from '@/composables/useAuth';

const { username } = useAuth();

withDefaults(defineProps<{
    alerts?: any[];
    assignedTickets?: any[];
    assignedPositions?: any[];
}>(), {
    alerts: () => [],
    assignedTickets: () => [],
    assignedPositions: () => [],
});
</script>

<template>
    <Head title="My Portal" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">My Portal</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Welcome, {{ username }}</h1>
            <p class="mt-3 max-w-2xl text-[#3a3a3a]/70">Review your current work, alerts, tickets, and assigned positions from one place.</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><Bell class="h-6 w-6 text-[#005c43]" /><h2 class="mt-3 font-bold">Alerts</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ alerts.length }} unread alert{{ alerts.length === 1 ? '' : 's' }}</p></article>
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><LifeBuoy class="h-6 w-6 text-[#005c43]" /><h2 class="mt-3 font-bold">Assigned tickets</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ assignedTickets.length }} active ticket{{ assignedTickets.length === 1 ? '' : 's' }}</p></article>
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><BriefcaseBusiness class="h-6 w-6 text-[#005c43]" /><h2 class="mt-3 font-bold">Assigned positions</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ assignedPositions.length }} position{{ assignedPositions.length === 1 ? '' : 's' }}</p></article>
        </div>

        <ProjectManagerPositionsCard v-if="assignedPositions.length" :positions="assignedPositions" />

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><DashboardAlerts :alerts="alerts" /></section>
            <section class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><TicketsAssignedToMe :tickets="assignedTickets" /></section>
        </div>

        <div class="flex justify-end">
            <Link href="/" class="inline-flex items-center gap-2 text-sm font-semibold text-[#005c43] hover:underline">View public home <ArrowRight class="h-4 w-4" /></Link>
        </div>
    </div>
</template>
