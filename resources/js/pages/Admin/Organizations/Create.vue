<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import FormActions from '@/components/forms/FormActions.vue'
import FormField from '@/components/forms/FormField.vue'
import FormGrid from '@/components/forms/FormGrid.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

type ParentOrganization = { id: number; name: string }

const props = defineProps<{ parents: ParentOrganization[] }>()

const form = useForm({ name: '', parent_id: 1, status: 'active', notes: '' })

function submit(): void {
    form.post('/admin/organizations')
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Create Organization"
            description="Add a new organization to the IRAD hierarchy."
            eyebrow="Administration"
            back-href="/admin/organizations"
            back-label="Organizations"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />

            <FormSection
                title="Organization Details"
                description="Define the organization name, hierarchy, and current status."
            >
                <FormGrid>
                    <FormField label="Name" for-id="name" :error="form.errors.name" required>
                        <template #default="{ describedBy, invalid }">
                            <Input id="name" v-model="form.name" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </template>
                    </FormField>

                    <FormField label="Parent Organization" for-id="parent_id" :error="form.errors.parent_id">
                        <template #default="{ describedBy, invalid }">
                            <select id="parent_id" v-model="form.parent_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid">
                                <option v-for="parent in props.parents" :key="parent.id" :value="parent.id">{{ parent.name }}</option>
                            </select>
                        </template>
                    </FormField>

                    <FormField label="Status" for-id="status" :error="form.errors.status" description="Inactive organizations remain available for historical records.">
                        <template #default="{ describedBy, invalid }">
                            <select id="status" v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-describedby="describedBy" :aria-invalid="invalid">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </template>
                    </FormField>
                </FormGrid>
            </FormSection>

            <FormSection title="Additional Information" description="Add optional internal notes about this organization.">
                <FormField label="Notes" for-id="notes" :error="form.errors.notes">
                    <template #default="{ describedBy, invalid }">
                        <Textarea id="notes" v-model="form.notes" rows="5" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </template>
                </FormField>
            </FormSection>

            <FormActions cancel-href="/admin/organizations" submit-label="Save Organization" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
