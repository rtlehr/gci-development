<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { BriefcaseBusiness, Copy, ListChecks } from 'lucide-vue-next'
import PortalSectionNav from '@/components/portal/PortalSectionNav.vue'
import BooleanField from '@/components/forms/BooleanField.vue'
import FormField from '@/components/forms/FormField.vue'
import FormGrid from '@/components/forms/FormGrid.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

type CloneSource = { id: number; name: string; skills_count: number; tasks_count: number }
type Section = 'details' | 'template'
const props = defineProps<{ cloneSources: CloneSource[] }>()
const activeSection = ref<Section>('details')
const form = useForm({ name: '', description: '', is_active: true, sort_order: 0, clone_job_title_id: null as number | null })
const selectedCloneSource = computed(() => props.cloneSources.find((jobTitle) => jobTitle.id === form.clone_job_title_id))
const sections = computed(() => [
    { id: 'details', title: 'Job Title Details', description: 'Name, description, order, and status.', icon: BriefcaseBusiness, complete: Boolean(form.name) },
    { id: 'template', title: 'Requirement Template', description: 'Optionally clone skills and tasks.', icon: Copy, complete: Boolean(form.clone_job_title_id) },
])
function submit(): void { form.post('/portal/job-titles') }
</script>

<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Job Title</h1>
                <p class="mt-1 text-sm text-muted-foreground">Add a master Job Title and optionally copy its default skills and tasks.</p>
            </div>
            <Button as-child variant="outline"><Link href="/portal/job-titles">Back to List</Link></Button>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                <PortalSectionNav title="Job Title sections" :sections="sections" :active-section="activeSection" @update:active-section="activeSection = $event as Section" />
                <div class="min-w-0 space-y-6">
                    <ValidationSummary :errors="form.errors" />
                    <FormSection v-if="activeSection === 'details'" title="Job Title Details" description="Define the title, description, display order, and availability.">
                        <FormGrid>
                            <FormField label="Name" for-id="name" :error="form.errors.name" required><template #default="{ describedBy, invalid }"><Input id="name" v-model="form.name" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField>
                            <FormField label="Sort Order" for-id="sort_order" :error="form.errors.sort_order" description="Lower numbers appear first."><template #default="{ describedBy, invalid }"><Input id="sort_order" v-model="form.sort_order" type="number" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField>
                        </FormGrid>
                        <FormField label="Description" for-id="description" :error="form.errors.description"><template #default="{ describedBy, invalid }"><Textarea id="description" v-model="form.description" rows="5" :aria-describedby="describedBy" :aria-invalid="invalid" /></template></FormField>
                        <BooleanField id="is_active" v-model="form.is_active" label="Active" description="Active Job Titles are available for positions." />
                    </FormSection>
                    <FormSection v-else title="Requirement Template" description="Optionally copy default skills and tasks from an existing Job Title.">
                        <FormField label="Clone Requirements From" for-id="clone_job_title_id" :error="form.errors.clone_job_title_id" description="Copied requirements become independent records.">
                            <template #default="{ describedBy, invalid }"><select id="clone_job_title_id" v-model="form.clone_job_title_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :aria-describedby="describedBy" :aria-invalid="invalid"><option :value="null">Do not clone requirements</option><option v-for="jobTitle in cloneSources" :key="jobTitle.id" :value="jobTitle.id">{{ jobTitle.name }} — {{ jobTitle.skills_count }} skills, {{ jobTitle.tasks_count }} tasks</option></select></template>
                        </FormField>
                        <div v-if="selectedCloneSource" class="rounded-lg border bg-muted/30 p-4 text-sm"><div class="flex items-center gap-2 font-medium"><ListChecks class="h-4 w-4" /> Requirements will be copied from {{ selectedCloneSource.name }}.</div><p class="mt-1 text-muted-foreground">{{ selectedCloneSource.skills_count }} skills and {{ selectedCloneSource.tasks_count }} tasks will be created.</p></div>
                    </FormSection>
                    <div class="flex gap-3 border-t pt-5"><Button type="submit" :disabled="form.processing">{{ form.processing ? 'Creating…' : 'Create Job Title' }}</Button><Button as-child variant="outline"><Link href="/portal/job-titles">Cancel</Link></Button></div>
                </div>
            </div>
        </form>
    </div>
</template>
