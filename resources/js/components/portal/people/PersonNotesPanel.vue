<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    personId: { type: [Number, String], required: true },
    notes: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
})

const categoryDefinitions = [
    { value: 'kudos', label: 'Kudos', description: 'Positive feedback, recognition, and accomplishments.', badgeVariant: 'secondary' },
    { value: 'reprimand', label: 'Reprimand', description: 'Corrective or disciplinary documentation.', badgeVariant: 'destructive' },
    { value: 'general', label: 'General', description: 'General observations and person-related notes.', badgeVariant: 'outline' },
]

const form = useForm({
    category: 'general',
    note: '',
})

const groupedNotes = computed(() => categoryDefinitions.map((category) => ({
    ...category,
    notes: props.notes
        .filter((note) => note.category === category.value)
        .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()),
})))

const noteCount = computed(() => props.notes.length)

function formatDate(value) {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}

function submitNote() {
    if (!props.editable || !form.note.trim()) return

    form.post(`/portal/people/${props.personId}/notes`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => form.reset('note'),
    })
}
</script>

<template>
    <div class="space-y-6">
        <Card v-if="editable">
            <CardHeader>
                <CardTitle>Add Note</CardTitle>
                <CardDescription>
                    Add a categorized note. The date and the person entering the note are recorded automatically.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="person_note_category">Category</Label>
                    <Select v-model="form.category">
                        <SelectTrigger id="person_note_category" class="w-full md:w-64">
                            <SelectValue placeholder="Select a category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="kudos">Kudos</SelectItem>
                            <SelectItem value="reprimand">Reprimand</SelectItem>
                            <SelectItem value="general">General</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.category" class="text-sm text-red-500">{{ form.errors.category }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="person_note_text">Note</Label>
                    <Textarea
                        id="person_note_text"
                        v-model="form.note"
                        rows="5"
                        placeholder="Enter the note..."
                    />
                    <p v-if="form.errors.note" class="text-sm text-red-500">{{ form.errors.note }}</p>
                </div>

                <Button type="button" :disabled="form.processing || !form.note.trim()" @click="submitNote">
                    {{ form.processing ? 'Adding Note...' : 'Add Note' }}
                </Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Person Notes</CardTitle>
                <CardDescription>
                    {{ noteCount ? `${noteCount} recorded note${noteCount === 1 ? '' : 's'}, grouped by category.` : 'No categorized notes have been recorded.' }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-7">
                <section v-for="category in groupedNotes" :key="category.value" class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :variant="category.badgeVariant">{{ category.label }}</Badge>
                        <span class="text-sm text-muted-foreground">{{ category.description }}</span>
                    </div>

                    <div v-if="category.notes.length" class="space-y-3">
                        <article
                            v-for="note in category.notes"
                            :key="note.id"
                            class="rounded-lg border bg-muted/20 p-4"
                        >
                            <p class="whitespace-pre-line text-sm leading-6">{{ note.note }}</p>
                            <p class="mt-3 text-xs text-muted-foreground">
                                Entered {{ formatDate(note.created_at) }} by {{ note.entered_by_name || note.entered_by?.name || 'Unknown user' }}
                            </p>
                        </article>
                    </div>
                    <p v-else class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No {{ category.label.toLowerCase() }} notes.
                    </p>
                </section>
            </CardContent>
        </Card>
    </div>
</template>
