<script setup lang="ts">
import { ref } from 'vue'
import { Check, Copy } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
    code: string
    label?: string
}>(), {
    label: 'Implementation example',
})

const copied = ref(false)

async function copyCode() {
    await navigator.clipboard.writeText(props.code)
    copied.value = true
    window.setTimeout(() => {
        copied.value = false
    }, 1600)
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-slate-950 text-slate-100">
        <div class="flex items-center justify-between border-b border-slate-800 px-4 py-2">
            <span class="text-xs font-medium text-slate-400">{{ label }}</span>
            <Button type="button" size="sm" variant="ghost" class="text-slate-300 hover:bg-slate-800 hover:text-white" @click="copyCode">
                <Check v-if="copied" class="mr-2 h-3.5 w-3.5" aria-hidden="true" />
                <Copy v-else class="mr-2 h-3.5 w-3.5" aria-hidden="true" />
                {{ copied ? 'Copied' : 'Copy' }}
            </Button>
        </div>
        <pre class="overflow-x-auto p-4 text-xs leading-6"><code>{{ code }}</code></pre>
    </div>
</template>
