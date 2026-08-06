<script setup lang="ts">
type StaffingSummary = {
    vacant: number
    selected: number
    departing: number
    onHold: number
}

defineProps<{
    summary: StaffingSummary
}>()

const cards = [
    { key: 'vacant', label: 'Vacant', numberClass: 'text-red-700' },
    { key: 'selected', label: 'Selected', numberClass: 'text-green-600' },
    { key: 'departing', label: 'Departing', numberClass: 'text-amber-500' },
    { key: 'onHold', label: 'On-Hold', numberClass: 'text-muted-foreground' },
] as const
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Position staffing summary"
    >
        <article
            v-for="card in cards"
            :key="card.key"
            class="rounded-xl border border-[#d5d5d5] bg-white px-5 py-6 text-center shadow-sm"
        >
            <p
                class="text-3xl font-bold tabular-nums"
                :class="card.numberClass"
            >
                {{ summary[card.key] }}
            </p>
            <h2 class="mt-1 text-xl font-medium text-foreground">
                {{ card.label }}
            </h2>
        </article>
    </section>
</template>
