<script setup lang="ts">
import type { Component } from 'vue'
import DetailCard from '@/components/show/DetailCard.vue'

defineProps<{
    title: string
    description?: string
    icon?: Component
}>()
</script>

<template>
    <DetailCard :title="title" :description="description" :icon="icon">
        <slot />
    </DetailCard>
</template>
