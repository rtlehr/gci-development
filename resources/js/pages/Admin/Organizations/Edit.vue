<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
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

type Organization = { id: number; name: string; parent_id: number | null; status: string; notes: string | null }
type ParentOrganization = { id: number; name: string }

const props = defineProps<{ organization: Organization; parents: ParentOrganization[] }>()

const form = useForm({
    name: props.organization.name,
    parent_id: props.organization.parent_id ?? 1,
    status: props.organization.status,
    notes: props.organization.notes ?? '',
})

function submit(): void {
    form.put(`/admin/organizations/${props.organization.id}`)
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Edit Organization"
            :description="`Update ${organization.name}.`"
            eyebrow="Administration"
            back-href="/admin/organizations"
            back-label="Organizations"
        >
            <template #status>
                <Badge :variant="organization.status === 'active' ? 'default' : 'secondary'">{{ organization.status === 'active' ? 'Active' : 'Inactive' }}</Badge>
            </template>
            <template #meta><span>Organization ID: {{ organization.id }}</span></template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />

            <FormSection title="Organization Details" description="Update the organization name, hierarchy, and current status.">
                <FormGrid>
                    <FormField label="Name" for-id="name" :error="form.errors.name" required>
                        <template #default="{ describedBy, invalid }"><Input id="name" v-model="form.name" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>
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

            <FormSection title="Additional Information" description="Maintain optional internal notes about this organization.">
                <FormField label="Notes" for-id="notes" :error="form.errors.notes">
                    <template #default="{ describedBy, invalid }"><Textarea id="notes" v-model="form.notes" rows="5" :aria-describedby="describedBy" :aria-invalid="invalid" /></template>
                </FormField>
            </FormSection>

            <FormActions cancel-href="/admin/organizations" submit-label="Update Organization" :processing="form.processing" :dirty="form.isDirty" />
        </form>
    </PageContainer>
</template>
