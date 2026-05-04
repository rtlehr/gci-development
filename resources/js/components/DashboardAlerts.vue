<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { Bell, CheckCircle2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

type Alert = {
    id: number
    type: string
    priority: string
    title: string
    message?: string | null
    action_url?: string | null
    created_at: string
}

defineProps<{
    alerts: Alert[]
}>()

function markRead(alert: Alert) {
    router.patch(`/alerts/${alert.id}/read`, {}, {
        preserveScroll: true,
    })
}

function markAllRead() {
    router.patch('/alerts/read-all', {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <Bell class="h-5 w-5" />
                    Alerts
                </h2>

                <p class="text-sm text-muted-foreground">
                    Recent items that need your attention.
                </p>
            </div>

            <Button
                v-if="alerts.length"
                variant="outline"
                size="sm"
                @click="markAllRead"
            >
                Mark all read
            </Button>
        </div>

        <div v-if="!alerts.length" class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
            You do not have any new alerts.
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="alert in alerts"
                :key="alert.id"
                class="rounded-xl border p-4 space-y-2"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-medium">
                            {{ alert.title }}
                        </div>

                        <div v-if="alert.message" class="text-sm text-muted-foreground">
                            {{ alert.message }}
                        </div>
                    </div>

                    <Button
                        variant="ghost"
                        size="sm"
                        @click="markRead(alert)"
                    >
                        <CheckCircle2 class="mr-1 h-4 w-4" />
                        Read
                    </Button>
                </div>

                <div v-if="alert.action_url">
                    <Link
                        :href="alert.action_url"
                        class="text-sm underline text-primary"
                    >
                        View item
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>