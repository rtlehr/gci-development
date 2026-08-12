<script setup>
import {
    Building2,
    ContactRound,
    MessageSquareText,
    Paperclip,
    ShieldCheck,
    UserRound,
} from 'lucide-vue-next'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'

const props = defineProps({
    sections: { type: Array, required: true },
    activeSection: { type: String, required: true },
})

const emit = defineEmits(['update:activeSection'])

const icons = {
    details: UserRound,
    notes: MessageSquareText,
    organization: Building2,
    contact: ContactRound,
    access: ShieldCheck,
    attachments: Paperclip,
}

const mappedSections = () => props.sections.map((section) => ({
    ...section,
    icon: icons[section.id],
}))
</script>

<template>
    <PortalSectionNav
        title="Person sections"
        aria-label="Person sections"
        :sections="mappedSections()"
        :active-section="activeSection"
        @update:active-section="emit('update:activeSection', $event)"
    />
</template>
