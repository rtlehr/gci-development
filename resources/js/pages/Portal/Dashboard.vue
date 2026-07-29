<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, BriefcaseBusiness, LifeBuoy, Send } from 'lucide-vue-next';
import DashboardAlerts from '@/components/DashboardAlerts.vue';
import MySubmittedTickets from '@/components/MySubmittedTickets.vue';
import ProjectManagerPositionsCard from '@/components/dashboard/ProjectManagerPositionsCard.vue';
import TicketsAssignedToMe from '@/components/TicketsAssignedToMe.vue';
import { useAuth } from '@/composables/useAuth';
const { username } = useAuth();
withDefaults(defineProps<{ alerts?:any[]; assignedTickets?:any[]; submittedTickets?:any[]; assignedPositions?:any[]; summary?:{ unreadAlerts:number; assignedTickets:number; submittedTickets:number; assignedPositions:number } }>(), { alerts:()=>[], assignedTickets:()=>[], submittedTickets:()=>[], assignedPositions:()=>[], summary:()=>({unreadAlerts:0,assignedTickets:0,submittedTickets:0,assignedPositions:0}) });
</script>
<template>
<Head title="My Portal" />
<section class="border-b border-[#e3e3e3] bg-white"><div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8"><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">My Portal</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Welcome, {{ username }}</h1><p class="mt-3 max-w-2xl text-[#3a3a3a]/70">Review your alerts, requests, assigned work, and positions from one place.</p></div></section>
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<Link href="/portal/alerts" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm transition hover:-translate-y-0.5"><Bell class="h-6 w-6 text-[#005c43]"/><h2 class="mt-3 font-bold">Alerts</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.unreadAlerts }} unread</p></Link>
<Link href="/portal/tickets" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm transition hover:-translate-y-0.5"><Send class="h-6 w-6 text-[#005c43]"/><h2 class="mt-3 font-bold">My requests</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.submittedTickets }} recent</p></Link>
<article class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><LifeBuoy class="h-6 w-6 text-[#005c43]"/><h2 class="mt-3 font-bold">Assigned tickets</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.assignedTickets }} active</p></article>
<article class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm"><BriefcaseBusiness class="h-6 w-6 text-[#005c43]"/><h2 class="mt-3 font-bold">Assigned positions</h2><p class="mt-1 text-sm text-[#3a3a3a]/70">{{ summary.assignedPositions }} positions</p></article>
</div>
<ProjectManagerPositionsCard v-if="assignedPositions.length" :positions="assignedPositions" />
<div class="grid gap-6 lg:grid-cols-2"><DashboardAlerts :alerts="alerts"/><MySubmittedTickets :tickets="submittedTickets"/></div>
<TicketsAssignedToMe v-if="assignedTickets.length" :tickets="assignedTickets"/>
</div>
</template>
