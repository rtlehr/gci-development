<script setup lang="ts">
import { computed } from 'vue'
import StatusBadge from '@/components/data/StatusBadge.vue'

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral'

const props = defineProps<{
    status?: string | null
}>()

const label = computed(() => props.status?.trim() || 'Unknown')

const tone = computed<Tone>(() => {
    const status = label.value.toLowerCase()

    if (
        [
            'selected',
            'hired',
            'approved',
            'completed',
            'active',
            'offer accepted',
        ].includes(status)
    ) {
        return 'success'
    }

    if (
        [
            'submitted',
            'new',
            'review',
            'in review',
            'screening',
            'interview',
            'interviewing',
            'offer',
            'in process',
        ].includes(status)
    ) {
        return status === 'submitted' || status === 'new'
            ? 'info'
            : 'warning'
    }

    if (
        [
            'rejected',
            'declined',
            'disqualified',
            'cancelled',
            'canceled',
        ].includes(status)
    ) {
        return 'danger'
    }

    if (['withdrawn', 'inactive', 'closed'].includes(status)) {
        return 'neutral'
    }

    return 'info'
})
</script>

<template>
    <StatusBadge
        :label="label"
        :tone="tone"
    />
</template>
