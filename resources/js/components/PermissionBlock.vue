<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'

// Props
const props = defineProps<{
    permission: string
    fallback?: string
}>()

const { can } = useAuth()
</script>

<template>
    <div>
        <!-- Allowed -->
        <template v-if="can(permission)">
            <slot />
        </template>

        <!-- Not allowed -->
        <template v-else>
            <div v-if="fallback" class="text-gray-500">
                {{ fallback }}
            </div>
        </template>
    </div>
</template>