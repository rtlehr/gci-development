<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const productionActive = computed(
    () => Boolean((page.props.impersonation as any)?.active),
);

const developmentActive = computed(
    () =>
        Boolean((page.props.dev as any)?.available)
        && Boolean((page.props.dev as any)?.isImpersonating),
);

const active = computed(
    () => productionActive.value || developmentActive.value,
);

const userName = computed(
    () => (page.props.auth as any)?.user?.username ?? 'another user',
);

function stopImpersonating(): void {
    router.post(
        productionActive.value
            ? '/impersonation/stop'
            : '/dev/clear-user',
    );
}
</script>

<template>
    <div
        v-if="active"
        class="bg-amber-100 px-4 py-2 text-sm text-amber-950"
        role="status"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <p>
                You are viewing IRAD as <strong>{{ userName }}</strong>.
                <span v-if="productionActive">This session is being logged.</span>
            </p>

            <button
                type="button"
                class="shrink-0 font-semibold underline underline-offset-4"
                @click="stopImpersonating"
            >
                Return to your account
            </button>
        </div>
    </div>
</template>
