<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type Ticket = { id:number; ticket_number:string; title:string; status:string; created_at:string };
defineProps<{ tickets: Ticket[] }>();
const label = (v:string) => v.replaceAll('_',' ').replace(/\b\w/g, c => c.toUpperCase());
</script>
<template>
  <section class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div><h2 class="flex items-center gap-2 text-lg font-semibold"><Send class="h-5 w-5 text-[#005c43]" />My submitted requests</h2><p class="mt-1 text-sm text-[#3a3a3a]/65">Recent support requests you submitted.</p></div>
      <Button as-child variant="outline" size="sm"><Link href="/portal/tickets">View all</Link></Button>
    </div>
    <div v-if="!tickets.length" class="rounded-lg border border-dashed p-6 text-sm text-[#3a3a3a]/65">You have not submitted any requests.</div>
    <div v-else class="space-y-3">
      <Link v-for="ticket in tickets" :key="ticket.id" :href="`/portal/tickets/${ticket.id}`" class="block rounded-lg border p-4 transition hover:bg-[#005c43]/5">
        <div class="font-semibold">{{ ticket.ticket_number }} — {{ ticket.title }}</div>
        <div class="mt-1 text-xs text-[#3a3a3a]/60">{{ label(ticket.status) }}</div>
      </Link>
    </div>
  </section>
</template>
