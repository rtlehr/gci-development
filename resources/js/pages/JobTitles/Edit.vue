<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import BooleanField from '@/components/forms/BooleanField.vue'
import FormActions from '@/components/forms/FormActions.vue'
import FormField from '@/components/forms/FormField.vue'
import FormGrid from '@/components/forms/FormGrid.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps<{ jobTitle: { id: number; name: string; description?: string | null; is_active?: boolean; sort_order?: number } }>()
const form = useForm({ name: props.jobTitle.name ?? '', description: props.jobTitle.description ?? '', is_active: props.jobTitle.is_active ?? true, sort_order: props.jobTitle.sort_order ?? 0 })

function submit(): void { form.put(`/job-titles/${props.jobTitle.id}`) }
</script>

<template>
    <PageContainer size="default">
        <PageHeader title="Edit Job Title" :description="`Update ${jobTitle.name}.`" eyebrow="Positions" back-href="/job-titles" back-label="Job Titles">
            <template #status><Badge :variant="jobTitle.is_active ? 'default' : 'secondary'">{{ jobTitle.is_active ? 'Active' : 'Inactive' }}</Badge></template>
            <template #meta><span>Job Title ID: {{ jobTitle.id }}</span></template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />

            <FormSection title="Job Title Details" description="Update the title, description, display order, and availability.">
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

            <FormActions cancel-href="/job-titles" submit-label="Update Job Title" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
