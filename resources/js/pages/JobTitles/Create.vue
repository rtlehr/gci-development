<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import BooleanField from '@/components/forms/BooleanField.vue'
import FormActions from '@/components/forms/FormActions.vue'
import FormField from '@/components/forms/FormField.vue'
import FormGrid from '@/components/forms/FormGrid.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

type CloneSource = { id: number; name: string; skills_count: number; tasks_count: number }
const props = defineProps<{ cloneSources: CloneSource[] }>()

const form = useForm({ name: '', description: '', is_active: true, sort_order: 0, clone_job_title_id: null as number | null })
const selectedCloneSource = computed(() => props.cloneSources.find((jobTitle) => jobTitle.id === form.clone_job_title_id))

function submit(): void { form.post('/job-titles') }
</script>

<template>
    <PageContainer size="default">
        <PageHeader title="Create Job Title" description="Add a new master Job Title and optionally copy requirements from an existing title." eyebrow="Positions" back-href="/job-titles" back-label="Job Titles" />

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />

            <FormSection title="Job Title Details" description="Define the title, description, display order, and availability.">
                <FormGrid>
                    <FormField label="Name" for-id="name" :error="form.errors.name" required>
                        <template #default="{ describedBy, invalid }"><Input id="name" v-model="form.name" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>
                    </FormField>
                    <FormField label="Sort Order" for-id="sort_order" :error="form.errors.sort_order" description="Lower numbers appear first.">
                        <template #default="{ describedBy, invalid }"><Input id="sort_order" v-model="form.sort_order" type="number" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>
                    </FormField>
                </FormGrid>

                <FormField label="Description" for-id="description" :error="form.errors.description">
                    <template #default="{ describedBy, invalid }"><Textarea id="description" v-model="form.description" rows="5" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>
                </FormField>

                <BooleanField id="is_active" v-model="form.is_active" label="Active" description="Active Job Titles are available for new and updated positions." />
            </FormSection>

            <FormSection title="Requirement Template" description="Optionally copy skills and tasks from an existing Job Title.">
                <FormField label="Clone Requirements From" for-id="clone_job_title_id" :error="form.errors.clone_job_title_id" description="Copied requirements become independent records for the new Job Title.">
                    <template #default="{ describedBy, invalid }">
                        <select id="clone_job_title_id" v-model="form.clone_job_title_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option :value="null">Do not clone requirements</option>
                            <option v-for="jobTitle in cloneSources" :key="jobTitle.id" :value="jobTitle.id">{{ jobTitle.name }} — {{ jobTitle.skills_count }} skills, {{ jobTitle.tasks_count }} tasks</option>
                        </select>
                    </template>
                </FormField>

                <div v-if="selectedCloneSource" class="rounded-lg border bg-muted/30 p-4 text-sm">
                    <p class="font-medium">Requirements will be copied from {{ selectedCloneSource.name }}.</p>
                    <p class="mt-1 text-muted-foreground">{{ selectedCloneSource.skills_count }} skills and {{ selectedCloneSource.tasks_count }} tasks will be created for the new Job Title.</p>
                </div>
            </FormSection>

            <FormActions cancel-href="/job-titles" submit-label="Create Job Title" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
