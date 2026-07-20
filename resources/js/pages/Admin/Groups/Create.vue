<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import FormActions from '@/components/forms/FormActions.vue'
import FormField from '@/components/forms/FormField.vue'
import FormSection from '@/components/forms/FormSection.vue'
import ValidationSummary from '@/components/forms/ValidationSummary.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Input } from '@/components/ui/input'

const form = useForm({ group_name: '' })

function submit(): void {
    form.post('/admin/groups')
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Create Group"
            description="Add a group that can be used to organize people and application access."
            eyebrow="Administration"
            back-href="/admin/groups"
            back-label="Groups"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <ValidationSummary :errors="form.errors" />

            <FormSection
                title="Group Details"
                description="Enter the display name used throughout IRAD."
            >
                <FormField
                    label="Group Name"
                    for-id="group_name"
                    :error="form.errors.group_name"
                    description="Use a short, recognizable name."
                    required
                >
                    <template #default="{ describedBy, invalid }">
                        <Input
                            id="group_name"
                            v-model="form.group_name"
                            type="text"
                            autocomplete="off"
                            autofocus
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid"
                        />
                    </template>
                </FormField>
            </FormSection>

            <FormActions
                cancel-href="/admin/groups"
                submit-label="Save Group"
                :processing="form.processing"
                :dirty="form.isDirty"
            />
        </form>
    </PageContainer>
</template>
