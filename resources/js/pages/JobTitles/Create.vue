<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type CloneSource = {
    id: number;
    name: string;
    skills_count: number;
    tasks_count: number;
};

const props = defineProps<{
    cloneSources: CloneSource[];
}>();

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
    clone_job_title_id: null as number | null,
});

const selectedCloneSource = computed(() =>
    props.cloneSources.find(
        (jobTitle) => jobTitle.id === form.clone_job_title_id,
    ),
);

function submit(): void {
    form.post('/job-titles');
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Create Job Title"
            description="Add a new master Job Title and optionally copy requirements from an existing title."
            eyebrow="Positions"
            back-href="/job-titles"
            back-label="Job Titles"
        />

        <div class="rounded-xl border bg-background p-6">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-red-500">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
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

                <div class="space-y-2">
                    <Label for="sort_order">Sort Order</Label>
                    <Input
                        id="sort_order"
                        v-model="form.sort_order"
                        type="number"
                    />
                    <p
                        v-if="form.errors.sort_order"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.sort_order }}
                    </p>
                </div>

                <div class="space-y-3 rounded-xl border bg-muted/30 p-5">
                    <div>
                        <Label for="clone_job_title_id">Clone Job Skills</Label>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Optionally copy all Required Skills, Desired Skills,
                            and Tasks from an existing Job Title.
                        </p>
                    </div>

                    <select
                        id="clone_job_title_id"
                        v-model="form.clone_job_title_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option :value="null">Do not clone requirements</option>
                        <option
                            v-for="jobTitle in cloneSources"
                            :key="jobTitle.id"
                            :value="jobTitle.id"
                        >
                            {{ jobTitle.name }} — {{ jobTitle.skills_count }}
                            skills, {{ jobTitle.tasks_count }} tasks
                        </option>
                    </select>

                    <p
                        v-if="form.errors.clone_job_title_id"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.clone_job_title_id }}
                    </p>

                    <div
                        v-if="selectedCloneSource"
                        class="rounded-lg border bg-background p-4 text-sm"
                    >
                        <p class="font-medium">
                            Requirements will be copied from
                            {{ selectedCloneSource.name }}.
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ selectedCloneSource.skills_count }} skills and
                            {{ selectedCloneSource.tasks_count }} tasks will be
                            created as independent records for the new Job Title.
                        </p>
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-center justify-between rounded-lg border p-4"
                >
                    <span class="text-sm font-medium">Active</span>
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-5 w-5"
                    />
                </label>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? 'Saving...'
                                : 'Create Job Title'
                        }}
                    </Button>

                    <Link href="/job-titles">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </div>
    </PageContainer>
</template>
