<script setup lang="ts">
defineProps<{
    items: Array<{
        id?: number;
        question: string;
        answer: string;
    }>;
}>();
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-[var(--portal-border)] bg-white">
        <details
            v-for="item in items"
            :key="item.id ?? item.question"
            class="group border-b border-[var(--portal-border)] last:border-b-0"
        >
            <summary
                class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold text-[var(--portal-text)] outline-none transition hover:bg-[var(--portal-page-bg)] focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--portal-primary)] [&::-webkit-details-marker]:hidden"
            >
                <span>{{ item.question }}</span>

                <span
                    class="relative h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-45"
                    aria-hidden="true"
                >
                    <span class="absolute left-1/2 top-1/2 h-0.5 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-current" />
                    <span class="absolute left-1/2 top-1/2 h-4 w-0.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-current" />
                </span>
            </summary>

            <div class="px-5 pb-5">
                <p class="whitespace-pre-line text-sm leading-7 text-[color:var(--portal-text-muted)]">
                    {{ item.answer }}
                </p>
            </div>
        </details>
    </div>
</template>
