<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'

const page = usePage()

const success = computed(() => page.props.flash?.success ?? '')
const error = computed(() => page.props.flash?.error ?? '')
const dismissedSuccess = ref('')
const dismissedError = ref('')

const showSuccess = computed(() => Boolean(success.value) && success.value !== dismissedSuccess.value)
const showError = computed(() => Boolean(error.value) && error.value !== dismissedError.value)

const dismissSuccess = () => {
    dismissedSuccess.value = success.value
}

const dismissError = () => {
    dismissedError.value = error.value
}

watch(success, (value, previousValue) => {
    if (value !== previousValue) {
        dismissedSuccess.value = ''
    }
})

watch(error, (value, previousValue) => {
    if (value !== previousValue) {
        dismissedError.value = ''
    }
})
</script>

<template>
    <div class="space-y-3" aria-label="Notifications">
        <div
            v-if="showSuccess"
            role="status"
            aria-live="polite"
            aria-atomic="true"
            class="flex items-start justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
        >
            <span>{{ success }}</span>
            <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition hover:bg-green-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-700 focus-visible:ring-offset-2"
                aria-label="Dismiss success message"
                @click="dismissSuccess"
            >
                <X class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>

        <div
            v-if="showError"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            class="flex items-start justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
            <span>{{ error }}</span>
            <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2"
                aria-label="Dismiss error message"
                @click="dismissError"
            >
                <X class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
