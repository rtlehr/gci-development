<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, Copy } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
    label: string
    value?: string | number | boolean | null
    href?: string
    copyable?: boolean
    multiline?: boolean
}>(), {
    value: null,
    copyable: false,
    multiline: false,
})

const copied = ref(false)
const displayValue = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') return '—'
    if (typeof props.value === 'boolean') return props.value ? 'Yes' : 'No'
    return String(props.value)
})

async function copyValue(): Promise<void> {
    if (!props.copyable || displayValue.value === '—' || !navigator.clipboard) return
    await navigator.clipboard.writeText(displayValue.value)
    copied.value = true
    window.setTimeout(() => { copied.value = false }, 1500)
}
</script>

<template>
    <div class="min-w-0">
        <dt class="text-sm font-medium text-muted-foreground">{{ label }}</dt>
        <dd class="mt-1 flex min-w-0 items-start gap-2 text-sm font-medium text-foreground">
            <a
                v-if="href && displayValue !== '—'"
                :href="href"
                :class="['min-w-0 underline-offset-4 hover:underline', multiline ? 'whitespace-pre-line' : 'truncate']"
            >
                {{ displayValue }}
            </a>
            <span v-else :class="['min-w-0', multiline ? 'whitespace-pre-line' : 'truncate']">
                {{ displayValue }}
            </span>
            <Button
                v-if="copyable && displayValue !== '—'"
                type="button"
                variant="ghost"
                size="icon"
                class="-mt-1 h-7 w-7 shrink-0"
                :aria-label="`Copy ${label}`"
                @click="copyValue"
            >
                <Check v-if="copied" class="h-3.5 w-3.5" aria-hidden="true" />
                <Copy v-else class="h-3.5 w-3.5" aria-hidden="true" />
            </Button>
        </dd>
    </div>
</template>
