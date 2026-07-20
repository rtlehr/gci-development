<script setup lang="ts">
import { computed } from 'vue'
import { History } from 'lucide-vue-next'
import DetailItem from '@/components/DetailItem.vue'
import DetailGrid from '@/components/detail/DetailGrid.vue'
import DetailCard from '@/components/show/DetailCard.vue'

const props = defineProps<{
    createdAt?: string | null
    updatedAt?: string | null
    createdBy?: string | null
    updatedBy?: string | null
}>()

function formatDateTime(value?: string | null): string {
    if (!value) return '—'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

const hasAuditData = computed(() => Boolean(
    props.createdAt || props.updatedAt || props.createdBy || props.updatedBy,
))
</script>

<template>
    <DetailCard
        v-if="hasAuditData"
        title="Audit information"
        description="Record creation and modification history"
        :icon="History"
    >
        <DetailGrid :columns="2">
            <DetailItem v-if="createdAt" label="Created" :value="formatDateTime(createdAt)" />
            <DetailItem v-if="createdBy" label="Created By" :value="createdBy" />
            <DetailItem v-if="updatedAt" label="Last Updated" :value="formatDateTime(updatedAt)" />
            <DetailItem v-if="updatedBy" label="Last Updated By" :value="updatedBy" />
        </DetailGrid>
    </DetailCard>
</template>
