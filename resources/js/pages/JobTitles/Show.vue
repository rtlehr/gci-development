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

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card id="skills" class="scroll-mt-6">
                <CardHeader>
                    <CardTitle>Add Skill</CardTitle>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submitSkill" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="skill_name">Name</Label>
                            <Input id="skill_name" v-model="skillForm.name" />

                            <p v-if="skillForm.errors.name" class="text-sm text-destructive">
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

                            <p v-if="skillForm.errors.description" class="text-sm text-destructive">
                                {{ skillForm.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_requirement_type">Requirement</Label>
                            <select
                                id="skill_requirement_type"
                                v-model="skillForm.requirement_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="required">Required</option>
                                <option value="desired">Desired</option>
                            </select>

                            <p v-if="skillForm.errors.requirement_type" class="text-sm text-destructive">
                                {{ skillForm.errors.requirement_type }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_sort_order">Sort Order</Label>
                            <Input
                                id="skill_sort_order"
                                v-model="skillForm.sort_order"
                                type="number"
                            />

                            <p v-if="skillForm.errors.sort_order" class="text-sm text-destructive">
                                {{ skillForm.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3">
                            <span class="text-sm font-medium">Active</span>
                            <input v-model="skillForm.is_active" type="checkbox" class="h-5 w-5" />
                        </label>

                        <Button type="submit" :disabled="skillForm.processing">
                            {{ skillForm.processing ? 'Adding...' : 'Add Skill' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card id="tasks" class="scroll-mt-6">
                <CardHeader>
                    <CardTitle>Add Task</CardTitle>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submitTask" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="task_name">Name</Label>
                            <Input id="task_name" v-model="taskForm.name" />

                            <p v-if="taskForm.errors.name" class="text-sm text-destructive">
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

                            <p v-if="taskForm.errors.description" class="text-sm text-destructive">
                                {{ taskForm.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task_sort_order">Sort Order</Label>
                            <Input
                                id="task_sort_order"
                                v-model="taskForm.sort_order"
                                type="number"
                            />

                            <p v-if="taskForm.errors.sort_order" class="text-sm text-destructive">
                                {{ taskForm.errors.sort_order }}
                            </p>
                        </div>

                        <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3">
                            <span class="text-sm font-medium">Active</span>
                            <input v-model="taskForm.is_active" type="checkbox" class="h-5 w-5" />
                        </label>

                        <Button type="submit" :disabled="taskForm.processing">
                            {{ taskForm.processing ? 'Adding...' : 'Add Task' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>

        <div class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Required Skills</CardTitle>
                </CardHeader>

                <CardContent>
                    <div v-if="requiredSkills.length" class="space-y-3">
                        <div
                            v-for="(skill, index) in requiredSkills"
                            :key="skill.id"
                            class="flex items-start justify-between gap-4 rounded-lg border p-4"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-muted text-sm font-semibold">
                                    {{ index + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <div class="text-sm font-medium">{{ skill.name }}</div>
                                    <p v-if="skill.description" class="mt-1 text-sm text-muted-foreground">
                                        {{ skill.description }}
                                    </p>
                                    <p class="mt-2 text-xs text-muted-foreground">
                                        Sort: {{ skill.sort_order ?? 0 }} · {{ skill.is_active ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>
                            </div>

                            <Button variant="destructive" size="sm" @click="deleteSkill(skill.id)">
                                Delete
                            </Button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No required skills have been added.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Desired Skills</CardTitle>
                </CardHeader>

                <CardContent>
                    <div v-if="desiredSkills.length" class="space-y-3">
                        <div
                            v-for="(skill, index) in desiredSkills"
                            :key="skill.id"
                            class="flex items-start justify-between gap-4 rounded-lg border p-4"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-muted text-sm font-semibold">
                                    {{ index + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <div class="text-sm font-medium">{{ skill.name }}</div>
                                    <p v-if="skill.description" class="mt-1 text-sm text-muted-foreground">
                                        {{ skill.description }}
                                    </p>
                                    <p class="mt-2 text-xs text-muted-foreground">
                                        Sort: {{ skill.sort_order ?? 0 }} · {{ skill.is_active ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>
                            </div>

                            <Button variant="destructive" size="sm" @click="deleteSkill(skill.id)">
                                Delete
                            </Button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No desired skills have been added.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Tasks</CardTitle>
                </CardHeader>

                <CardContent>
                    <div v-if="sortedTasks.length" class="space-y-3">
                        <div
                            v-for="(task, index) in sortedTasks"
                            :key="task.id"
                            class="flex items-start justify-between gap-4 rounded-lg border p-4"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-muted text-sm font-semibold">
                                    {{ index + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <div class="text-sm font-medium">{{ task.name }}</div>
                                    <p v-if="task.description" class="mt-1 text-sm text-muted-foreground">
                                        {{ task.description }}
                                    </p>
                                    <p class="mt-2 text-xs text-muted-foreground">
                                        Sort: {{ task.sort_order ?? 0 }} · {{ task.is_active ? 'Active' : 'Inactive' }}
                                    </p>
                                </div>
                            </div>

                            <Button variant="destructive" size="sm" @click="deleteTask(task.id)">
                                Delete
                            </Button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No tasks have been added.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

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

const sortByOrderAndName = (items) => {
    return [...items].sort((a, b) => {
        const orderDifference = (a.sort_order ?? 0) - (b.sort_order ?? 0)

        if (orderDifference !== 0) {
            return orderDifference
        }

        return a.name.localeCompare(b.name)
    })
}

const requiredSkills = computed(() =>
    sortByOrderAndName(
        (jobTitle.skills ?? []).filter(
            (skill) => skill.requirement_type !== 'desired',
        ),
    ),
)

const desiredSkills = computed(() =>
    sortByOrderAndName(
        (jobTitle.skills ?? []).filter(
            (skill) => skill.requirement_type === 'desired',
        ),
    ),
)

const sortedTasks = computed(() => sortByOrderAndName(jobTitle.tasks ?? []))

const skillForm = useForm({
    name: '',
    description: '',
    requirement_type: 'required',
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
            skillForm.requirement_type = 'required'
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