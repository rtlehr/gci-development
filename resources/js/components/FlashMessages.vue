<template>
    <div class="space-y-3">
        <div
            v-if="showSuccess && success"
            class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
        >
            {{ success }}
        </div>

        <div
            v-if="showError && error"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const success = computed(() => page.props.flash?.success ?? '')
const error = computed(() => page.props.flash?.error ?? '')

const showSuccess = ref(true)
const showError = ref(true)

watch(success, (value) => {
    if (value) {
        showSuccess.value = true
        setTimeout(() => {
            showSuccess.value = false
        }, 2000)
    }
}, { immediate: true })

watch(error, (value) => {
    if (value) {
        showError.value = true
        setTimeout(() => {
            showError.value = false
        }, 2000)
    }
}, { immediate: true })
</script>