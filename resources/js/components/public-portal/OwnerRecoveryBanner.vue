<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const active = computed(
    () => Boolean((page.props.ownerRecovery as any)?.active),
);

function exitRecovery(): void {
    router.post('/owner-recovery/logout');
}
</script>

<template>
    <div
        v-if="active"
        class="bg-red-100 px-4 py-2 text-sm text-red-950"
        role="status"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <p>
                <strong>Owner Recovery Session:</strong>
                You are using emergency local Owner access. This session is being logged.
            </p>

            <button
                type="button"
                class="shrink-0 font-semibold underline underline-offset-4"
                @click="exitRecovery"
            >
                Exit recovery session
            </button>
        </div>
    </div>
</template>
