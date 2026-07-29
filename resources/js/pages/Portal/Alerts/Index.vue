<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type Alert = { id:number; title:string; message?:string|null; action_url?:string|null; priority:string; type:string; read_at?:string|null; created_at:string };
type PageLink = { url:string|null; label:string; active:boolean };

const props = defineProps<{ alerts:{ data:Alert[]; links:PageLink[] }; filters:{ status?:string }; counts:{ all:number; unread:number } }>();

function setStatus(status: string) {
  router.get('/portal/alerts', status ? { status } : {}, { preserveState: true, replace: true });
}
function markRead(alert: Alert) { router.patch(`/alerts/${alert.id}/read`, {}, { preserveScroll: true }); }
function markAllRead() { router.patch('/alerts/read-all', {}, { preserveScroll: true }); }
function openAlert(alert: Alert) {
  router.patch(`/alerts/${alert.id}/read`, {}, { preserveScroll: true, onSuccess: () => alert.action_url && router.visit(alert.action_url) });
}
</script>

<template>
  <Head title="Alerts" />
  <section class="border-b border-[#e3e3e3] bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#005c43]">My Portal</p>
      <h1 class="mt-2 text-3xl font-bold tracking-tight">Alerts</h1>
      <p class="mt-3 text-[#3a3a3a]/70">Review notifications and items that need your attention.</p>
    </div>
  </section>

  <div class="mx-auto max-w-5xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex gap-2">
        <Button :variant="!filters.status ? 'default' : 'outline'" @click="setStatus('')">All ({{ counts.all }})</Button>
        <Button :variant="filters.status === 'unread' ? 'default' : 'outline'" @click="setStatus('unread')">Unread ({{ counts.unread }})</Button>
        <Button :variant="filters.status === 'read' ? 'default' : 'outline'" @click="setStatus('read')">Read</Button>
      </div>
      <Button v-if="counts.unread" variant="outline" @click="markAllRead">Mark all read</Button>
    </div>

    <div v-if="alerts.data.length === 0" class="rounded-xl border border-dashed bg-white p-8 text-center text-[#3a3a3a]/65">
      You do not have any alerts in this view.
    </div>

    <article v-for="alert in alerts.data" :key="alert.id" class="rounded-xl border border-[#e3e3e3] bg-white p-5 shadow-sm">
      <div class="flex items-start justify-between gap-4">
        <div class="flex gap-3">
          <Bell class="mt-0.5 h-5 w-5 text-[#005c43]" />
          <div>
            <h2 class="font-semibold">{{ alert.title }}</h2>
            <p v-if="alert.message" class="mt-1 text-sm text-[#3a3a3a]/70">{{ alert.message }}</p>
            <p class="mt-2 text-xs text-[#3a3a3a]/50">{{ new Date(alert.created_at).toLocaleString() }}</p>
          </div>
        </div>
        <Button v-if="!alert.read_at" size="sm" variant="ghost" @click="markRead(alert)"><CheckCircle2 class="mr-1 h-4 w-4" />Read</Button>
      </div>
      <button v-if="alert.action_url" class="mt-4 text-sm font-semibold text-[#005c43] hover:underline" @click="openAlert(alert)">View item</button>
    </article>

    <div class="flex flex-wrap justify-center gap-1">
      <template v-for="link in alerts.links" :key="link.label">
        <Link v-if="link.url" :href="link.url" class="rounded-md border px-3 py-2 text-sm" :class="link.active ? 'bg-[#005c43] text-white' : 'bg-white'" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
