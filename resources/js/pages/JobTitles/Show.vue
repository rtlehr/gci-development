<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ jobTitle.name }}
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Job Title details, skills, and tasks.
                </p>
            </div>

            <div class="flex gap-2">
                <Link href="/job-titles">
                    <Button variant="outline">Back to List</Button>
                </Link>

                <Link :href="`/job-titles/${jobTitle.id}/edit`">
                    <Button>Edit Job Title</Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Job Title Information</CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
                <div>
                    <div class="text-sm font-medium text-muted-foreground">
                        Description
                    </div>

                    <p class="text-sm whitespace-pre-line mt-1">
                        {{ jobTitle.description || 'No description provided.' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Status
                        </div>

                        <p class="text-sm mt-1">
                            {{ jobTitle.is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Sort Order
                        </div>

                        <p class="text-sm mt-1">
                            {{ jobTitle.sort_order ?? 0 }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Skills -->
            <Card>
                <CardHeader>
                    <CardTitle>Skills</CardTitle>
                </CardHeader>

                <CardContent class="space-y-6">
                    <form @submit.prevent="submitSkill" class="space-y-4 border rounded-lg p-4">
                        <h3 class="font-medium">
                            Add Skill
                        </h3>

                        <div class="space-y-2">
                            <Label for="skill_name">Name</Label>
                            <Input id="skill_name" v-model="skillForm.name" />

                            <p v-if="skillForm.errors.name" class="text-sm text-red-500">
                                {{ skillForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_description">Description</Label>
                            <Textarea
                                id="skill_description"
                                v-model="skillForm.description"
                                rows="3"
                            />

                            <p v-if="skillForm.errors.description" class="text-sm text-red-500">
                                {{ skillForm.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_sort_order">Sort Order</Label>
                            <Input
                                id="skill_sort_order"
                                type="number"
                                v-model="skillForm.sort_order"
                            />

                            <p v-if="skillForm.errors.sort_order" class="text-sm text-red-500">
                                {{ skillForm.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex items-center justify-between rounded-lg border p-3 cursor-pointer">
                            <span class="font-medium text-sm">
                                Active
                            </span>

                            <input
                                type="checkbox"
                                v-model="skillForm.is_active"
                                class="h-5 w-5"
                            />
                        </label>

                        <Button type="submit" :disabled="skillForm.processing">
                            {{ skillForm.processing ? 'Adding...' : 'Add Skill' }}
                        </Button>
                    </form>

                    <div>
                        <h3 class="font-medium mb-3">
                            Existing Skills
                        </h3>

                        <div v-if="jobTitle.skills?.length" class="space-y-3">
                            <div
                                v-for="skill in jobTitle.skills"
                                :key="skill.id"
                                class="border rounded-lg p-3 flex items-start justify-between gap-4"
                            >
                                <div>
                                    <div class="font-medium text-sm">
                                        {{ skill.name }}
                                    </div>

                                    <p class="text-sm text-muted-foreground mt-1">
                                        {{ skill.description || 'No description provided.' }}
                                    </p>

                                    <p class="text-xs text-muted-foreground mt-2">
                                        Sort: {{ skill.sort_order ?? 0 }}
                                        |
                                        {{ skill.is_active ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteSkill(skill.id)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <p v-else class="text-sm text-muted-foreground">
                            No skills have been added.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Tasks -->
            <Card>
                <CardHeader>
                    <CardTitle>Tasks</CardTitle>
                </CardHeader>

                <CardContent class="space-y-6">
                    <form @submit.prevent="submitTask" class="space-y-4 border rounded-lg p-4">
                        <h3 class="font-medium">
                            Add Task
                        </h3>

                        <div class="space-y-2">
                            <Label for="task_name">Name</Label>
                            <Input id="task_name" v-model="taskForm.name" />

                            <p v-if="taskForm.errors.name" class="text-sm text-red-500">
                                {{ taskForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task_description">Description</Label>
                            <Textarea
                                id="task_description"
                                v-model="taskForm.description"
                                rows="3"
                            />

                            <p v-if="taskForm.errors.description" class="text-sm text-red-500">
                                {{ taskForm.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task_sort_order">Sort Order</Label>
                            <Input
                                id="task_sort_order"
                                type="number"
                                v-model="taskForm.sort_order"
                            />

                            <p v-if="taskForm.errors.sort_order" class="text-sm text-red-500">
                                {{ taskForm.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex items-center justify-between rounded-lg border p-3 cursor-pointer">
                            <span class="font-medium text-sm">
                                Active
                            </span>

                            <input
                                type="checkbox"
                                v-model="taskForm.is_active"
                                class="h-5 w-5"
                            />
                        </label>

                        <Button type="submit" :disabled="taskForm.processing">
                            {{ taskForm.processing ? 'Adding...' : 'Add Task' }}
                        </Button>
                    </form>

                    <div>
                        <h3 class="font-medium mb-3">
                            Existing Tasks
                        </h3>

                        <div v-if="jobTitle.tasks?.length" class="space-y-3">
                            <div
                                v-for="task in jobTitle.tasks"
                                :key="task.id"
                                class="border rounded-lg p-3 flex items-start justify-between gap-4"
                            >
                                <div>
                                    <div class="font-medium text-sm">
                                        {{ task.name }}
                                    </div>

                                    <p class="text-sm text-muted-foreground mt-1">
                                        {{ task.description || 'No description provided.' }}
                                    </p>

                                    <p class="text-xs text-muted-foreground mt-2">
                                        Sort: {{ task.sort_order ?? 0 }}
                                        |
                                        {{ task.is_active ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteTask(task.id)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>

                        <p v-else class="text-sm text-muted-foreground">
                            No tasks have been added.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

const props = defineProps({
    jobTitle: {
        type: Object,
        required: true,
    },
})

const jobTitle = props.jobTitle

const skillForm = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
})

const taskForm = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
})

function submitSkill() {
    skillForm.post(`/job-titles/${jobTitle.id}/skills`, {
        preserveScroll: true,
        onSuccess: () => {
            skillForm.reset()
            skillForm.is_active = true
            skillForm.sort_order = 0
        },
    })
}

function submitTask() {
    taskForm.post(`/job-titles/${jobTitle.id}/tasks`, {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset()
            taskForm.is_active = true
            taskForm.sort_order = 0
        },
    })
}

function deleteSkill(skillId) {
    if (!confirm('Delete this skill?')) {
        return
    }

    router.delete(`/job-titles/${jobTitle.id}/skills/${skillId}`, {
        preserveScroll: true,
    })
}

function deleteTask(taskId) {
    if (!confirm('Delete this task?')) {
        return
    }

    router.delete(`/job-titles/${jobTitle.id}/tasks/${taskId}`, {
        preserveScroll: true,
    })
}
</script>