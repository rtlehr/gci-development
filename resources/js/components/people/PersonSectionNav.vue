<script setup>
import {
    Building2,
    CheckCircle2,
    ChevronRight,
    Circle,
    ContactRound,
    Paperclip,
    ShieldCheck,
    UserRound,
} from 'lucide-vue-next'

const props = defineProps({
    sections: { type: Array, required: true },
    activeSection: { type: String, required: true },
})

const emit = defineEmits(['update:activeSection'])

const icons = {
    details: UserRound,
    organization: Building2,
    contact: ContactRound,
    access: ShieldCheck,
    attachments: Paperclip,
}

function sectionIcon(section) {
    return icons[section.id] ?? Circle
}
</script>

<template>
    <aside class="self-start rounded-xl border bg-background p-3 shadow-sm lg:sticky lg:top-6">
        <div class="border-b px-2 pb-3 pt-1">
            <p class="text-sm font-semibold">Person sections</p>
        </div>

        <nav class="mt-2 space-y-1" aria-label="Person sections">
            <button
                v-for="section in sections"
                :key="section.id"
                type="button"
                class="flex w-full items-start gap-3 rounded-lg px-3 py-3 text-left transition"
                :class="activeSection === section.id ? 'bg-foreground text-background' : 'hover:bg-muted'"
                :aria-pressed="activeSection === section.id"
                @click="emit('update:activeSection', section.id)"
            >
                <component :is="sectionIcon(section)" class="mt-0.5 h-4 w-4 shrink-0" />

                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        {{ section.title }}
                        <CheckCircle2
                            v-if="section.complete"
                            class="h-3.5 w-3.5 shrink-0 opacity-80"
                        />
                    </span>
                    <span
                        class="mt-0.5 block text-xs"
                        :class="activeSection === section.id ? 'text-background/75' : 'text-muted-foreground'"
                    >
                        {{ section.description }}
                    </span>
                </span>

                <ChevronRight class="mt-1 h-4 w-4 shrink-0 opacity-70" />
            </button>
        </nav>
    </aside>
</template>
