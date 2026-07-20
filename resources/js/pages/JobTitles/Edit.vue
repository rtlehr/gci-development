<template>
    <PageContainer size="default">
        <PageHeader
            title="Edit Job Title"
            :description="`Update ${jobTitle.name}.`"
            eyebrow="Positions"
            back-href="/job-titles"
            back-label="Job Titles"
        >
            <template #status>
                <Badge :variant="jobTitle.is_active ? 'default' : 'secondary'">
                    {{ jobTitle.is_active ? 'Active' : 'Inactive' }}
                </Badge>
            </template>
        </PageHeader>

        <div class="border rounded-xl p-6 bg-background">
            <form
                @submit.prevent="submit"
                class="space-y-6"
            >
                <!-- Name -->
                <div class="space-y-2">
                    <Label for="name">
                        Name
                    </Label>

                    <Input
                        id="name"
                        v-model="form.name"
                    />

                    <p
                        v-if="form.errors.name"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">
                        Description
                    </Label>

                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                    />

                    <p
                        v-if="form.errors.description"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Sort Order -->
                <div class="space-y-2">
                    <Label for="sort_order">
                        Sort Order
                    </Label>

                    <Input
                        id="sort_order"
                        type="number"
                        v-model="form.sort_order"
                    />

                    <p
                        v-if="form.errors.sort_order"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.sort_order }}
                    </p>
                </div>

                <!-- Active -->
                <label
                    class="flex items-center justify-between rounded-lg border p-4 cursor-pointer"
                >
                    <span class="font-medium text-sm">
                        Active
                    </span>

                    <input
                        type="checkbox"
                        v-model="form.is_active"
                        class="h-5 w-5"
                    />
                </label>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Saving...'
                                : 'Update Job Title'
                        }}
                    </Button>

                    <Link href="/job-titles">
                        <Button
                            type="button"
                            variant="outline"
                        >
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </PageContainer>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Badge } from '@/components/ui/badge'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    jobTitle: {
        type: Object,
        required: true,
    },
})

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    name: props.jobTitle.name ?? '',
    description: props.jobTitle.description ?? '',
    is_active: props.jobTitle.is_active ?? true,
    sort_order: props.jobTitle.sort_order ?? 0,
})

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

function submit() {
    form.put(`/job-titles/${props.jobTitle.id}`)
}
</script>