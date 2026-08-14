<script setup lang="ts">
type StaffingSummary = {
    vacant: number
    selected: number
    filled: number
    departing: number
    onHold: number
}

type StaffingState = 'vacant' | 'selected' | 'filled' | 'departing' | 'on_hold'

withDefaults(defineProps<{
    summary: StaffingSummary
    activeState?: StaffingState | null
}>(), {
    activeState: null,
})

const emit = defineEmits<{
    select: [state: StaffingState]
}>()

const cards = [
    { key: 'vacant', state: 'vacant', label: 'Vacant', numberClass: 'text-red-700' },
    { key: 'selected', state: 'selected', label: 'Selected', numberClass: 'text-sky-700' },
    { key: 'filled', state: 'filled', label: 'Filled', numberClass: 'text-green-600' },
    { key: 'departing', state: 'departing', label: 'Departing', numberClass: 'text-amber-500' },
    { key: 'onHold', state: 'on_hold', label: 'On-Hold', numberClass: 'text-muted-foreground' },
] as const
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Position staffing summary"
    >
        <button
            v-for="card in cards"
            :key="card.key"
            type="button"
            class="rounded-xl border bg-white px-5 py-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            :class="activeState === card.state ? 'border-primary ring-2 ring-primary/15' : 'border-[#d5d5d5]'"
            :aria-pressed="activeState === card.state"
            :aria-label="`Filter staffing table to ${card.label} positions`"
            @click="emit('select', card.state)"
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
            <p class="mt-1 text-xs text-muted-foreground">
                Click to filter
            </p>
        </button>
    </section>
</template>
