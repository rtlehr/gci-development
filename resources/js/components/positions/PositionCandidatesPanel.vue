<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Mail, Phone, UserPlus, Users, Workflow } from 'lucide-vue-next'
import FormField from '@/components/forms/FormField.vue'
import { Button } from '@/components/ui/button'

 type CandidateOption = {
    id: number
    full_name: string
    email?: string | null
    person_code?: string | null
    primary_phone?: string | null
    primary_phone_extension?: string | null
}

type WorkflowOption = {
    id: number
    name: string
    code?: string | null
    is_primary: boolean
}

type PositionCandidate = {
    id: number
    status: string
    submitted_at?: string | null
    person: {
        id: number
        full_name: string
        email?: string | null
        primary_phone?: string | null
        primary_phone_extension?: string | null
    } | null
    workflow: {
        id: number
        name: string
        step_name: string
        step_number?: number | null
        step_count: number
        status_code?: string | null
    } | null
}

const props = defineProps<{
    positionId: number
    candidateOptions: CandidateOption[]
    candidates: PositionCandidate[]
    workflows: WorkflowOption[]
    basePath?: string
}>()

const basePath = computed(() => props.basePath ?? '/positions')

const primaryWorkflow = props.workflows.find((workflow) => workflow.is_primary) ?? props.workflows[0]

const form = useForm({
    person_id: '' as number | '',
    workflow_id: primaryWorkflow?.id ?? null,
})

// Do not offer a person who is already attached, even if the server-provided
// options become stale during a partial Inertia update.
const existingCandidatePersonIds = computed(() => new Set(
    props.candidates.map((candidate) => candidate.person?.id).filter((id): id is number => id != null),
))
const availableCandidateOptions = computed(() =>
    props.candidateOptions.filter((person) => !existingCandidatePersonIds.value.has(Number(person.id))),
)
const selectedPerson = computed(() =>
    availableCandidateOptions.value.find((person) => Number(person.id) === Number(form.person_id)),
)
const isDuplicateSelection = computed(() =>
    form.person_id !== '' && existingCandidatePersonIds.value.has(Number(form.person_id)),
)
const sameNameExistingCandidate = computed(() => {
    if (!selectedPerson.value) return null
    return props.candidates.find((candidate) =>
        candidate.person &&
        candidate.person.id !== Number(selectedPerson.value?.id) &&
        candidate.person.full_name.trim().toLocaleLowerCase() === selectedPerson.value?.full_name.trim().toLocaleLowerCase(),
    ) ?? null
})

function addCandidate(): void {
    if (form.processing || !form.person_id || isDuplicateSelection.value) return
    form.post(`${basePath.value}/${props.positionId}/candidates`, {
        preserveScroll: true,
        onSuccess: () => form.reset('person_id'),
    })
}

function formatPhone(person: CandidateOption | PositionCandidate['person']): string {
    if (!person?.primary_phone) return 'No primary phone'
    return person.primary_phone_extension
        ? `${person.primary_phone} ext. ${person.primary_phone_extension}`
        : person.primary_phone
}

function formatDate(value?: string | null): string {
    if (!value) return '—'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString()
}

function statusLabel(value?: string | null): string {
    if (!value) return 'Submitted'
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-xl border bg-background">
            <div class="border-b p-5">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-muted p-2"><UserPlus class="h-5 w-5" /></div>
                    <div>
                        <h2 class="font-semibold">Add Candidate</h2>
                        <p class="text-sm text-muted-foreground">
                            Select an existing person. Contact information and the position are filled automatically.
                        </p>
                    </div>
                </div>
            </div>

            <form class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,22rem)_auto] lg:items-end" @submit.prevent="addCandidate">
                <FormField label="Candidate" for-id="candidate_person_id" :error="form.errors.person_id" required>
                    <select
                        id="candidate_person_id"
                        v-model="form.person_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Select a person</option>
                        <option v-for="person in availableCandidateOptions" :key="person.id" :value="person.id">
                            {{ person.full_name }} — {{ person.email || 'No email' }} (Person #{{ person.id }}){{ person.person_code ? ` · Code: ${person.person_code}` : '' }}
                        </option>
                    </select>
                    <p v-if="isDuplicateSelection" class="mt-2 text-sm text-destructive" role="alert">
                        This person is already a candidate for this position.
                    </p>
                    <p v-if="sameNameExistingCandidate?.person" class="mt-2 text-sm text-amber-700 dark:text-amber-300" role="status">
                        A candidate named {{ sameNameExistingCandidate.person.full_name }} is already listed under Person #{{ sameNameExistingCandidate.person.id }}.
                        The selected record is Person #{{ selectedPerson?.id }}. Verify the person record before adding.
                    </p>
                </FormField>

                <FormField label="Workflow" for-id="candidate_workflow_id" :error="form.errors.workflow_id" required>
                    <select
                        id="candidate_workflow_id"
                        v-model="form.workflow_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="workflow in workflows" :key="workflow.id" :value="workflow.id">
                            {{ workflow.name }}{{ workflow.is_primary ? ' (Primary)' : '' }}
                        </option>
                    </select>
                </FormField>

                <Button type="submit" :disabled="form.processing || !form.person_id || !form.workflow_id || isDuplicateSelection">
                    {{ form.processing ? 'Adding…' : 'Add Candidate' }}
                </Button>
            </form>

            <div v-if="selectedPerson" class="mx-5 mb-5 grid gap-3 rounded-lg border bg-muted/20 p-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</p>
                    <p class="mt-1 text-sm">{{ selectedPerson.email || 'No email on file' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Primary Phone</p>
                    <p class="mt-1 text-sm">{{ formatPhone(selectedPerson) }}</p>
                </div>
            </div>

            <p v-if="!availableCandidateOptions.length" class="px-5 pb-5 text-sm text-muted-foreground">
                Every available person is already connected to this position.
            </p>
        </section>

        <section class="overflow-hidden rounded-xl border bg-background">
            <div class="border-b p-5">
                <h2 class="font-semibold">Position Candidates</h2>
                <p class="text-sm text-muted-foreground">
                    Review contact information and open each candidate's existing workflow.
                </p>
            </div>

            <div v-if="candidates.length" class="divide-y">
                <article v-for="candidate in candidates" :key="candidate.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-center">
                    <div class="min-w-0">
                        <Link
                            v-if="candidate.person"
                            :href="`/people/${candidate.person.id}`"
                            class="font-semibold hover:underline"
                        >
                            {{ candidate.person.full_name }}
                        </Link>
                        <span v-else class="font-semibold">Unknown Person</span>
                        <p v-if="candidate.person" class="mt-1 text-xs text-muted-foreground">Person #{{ candidate.person.id }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Added {{ formatDate(candidate.submitted_at) }}</p>
                    </div>

                    <div>
                        <Link
                            :href="`/candidates/${candidate.id}/edit`"
                            class="inline-flex items-center gap-2 font-medium hover:underline"
                        >
                            <Workflow class="h-4 w-4" />
                            {{ candidate.workflow?.step_name || statusLabel(candidate.status) }}
                        </Link>
                        <p v-if="candidate.workflow?.step_count" class="mt-1 text-xs text-muted-foreground">
                            <template v-if="candidate.workflow.step_number">
                                Step {{ candidate.workflow.step_number }} of {{ candidate.workflow.step_count }} ·
                            </template>
                            {{ candidate.workflow.name }}
                        </p>
                    </div>

                    <div class="space-y-2 text-sm">
                        <a
                            v-if="candidate.person?.email"
                            :href="`mailto:${candidate.person.email}`"
                            class="flex items-center gap-2 hover:underline"
                        >
                            <Mail class="h-4 w-4 text-muted-foreground" />
                            <span class="truncate">{{ candidate.person.email }}</span>
                        </a>
                        <span v-else class="flex items-center gap-2 text-muted-foreground">
                            <Mail class="h-4 w-4" /> No email
                        </span>

                        <span class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-muted-foreground" />
                            {{ formatPhone(candidate.person) }}
                        </span>
                    </div>

                    <Button variant="outline" as-child>
                        <Link :href="`/candidates/${candidate.id}/edit`">Open Workflow</Link>
                    </Button>
                </article>
            </div>

            <div v-else class="p-10 text-center">
                <Users class="mx-auto h-9 w-9 text-muted-foreground" />
                <h3 class="mt-3 font-medium">No candidates added</h3>
                <p class="mt-1 text-sm text-muted-foreground">Use the form above to connect the first candidate to this position.</p>
            </div>
        </section>
    </div>
</template>
