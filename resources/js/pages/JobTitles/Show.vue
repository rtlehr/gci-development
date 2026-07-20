<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import DetailItem from '@/components/DetailItem.vue'
import DetailGrid from '@/components/detail/DetailGrid.vue'
import DetailSection from '@/components/detail/DetailSection.vue'
import RequirementItem from '@/components/job-titles/RequirementItem.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { BriefcaseBusiness } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

type SkillRequirementType = 'required' | 'desired'

type Skill = {
    id: number
    name: string
    description?: string | null
    requirement_type: SkillRequirementType
    sort_order?: number | null
    is_active: boolean
}

type Task = {
    id: number
    name: string
    description?: string | null
    sort_order?: number | null
    is_active: boolean
}

type JobTitle = {
    id: number
    name: string
    description?: string | null
    is_active: boolean
    sort_order?: number | null
    skills?: Skill[]
    tasks?: Task[]
}

const props = defineProps<{ jobTitle: JobTitle }>()
const editingKey = ref<string | null>(null)

const sortByOrderAndName = <T extends { name: string; sort_order?: number | null }>(items: T[]) => {
    return [...items].sort((a, b) => {
        const orderDifference = (a.sort_order ?? 0) - (b.sort_order ?? 0)
        return orderDifference !== 0 ? orderDifference : a.name.localeCompare(b.name)
    })
}

const requiredSkills = computed(() =>
    sortByOrderAndName(
        (props.jobTitle.skills ?? []).filter((skill) => skill.requirement_type !== 'desired'),
    ),
)

const desiredSkills = computed(() =>
    sortByOrderAndName(
        (props.jobTitle.skills ?? []).filter((skill) => skill.requirement_type === 'desired'),
    ),
)

const sortedTasks = computed(() => sortByOrderAndName(props.jobTitle.tasks ?? []))

const skillForm = useForm({
    name: '',
    description: '',
    requirement_type: 'required' as SkillRequirementType,
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
    skillForm.post(`/job-titles/${props.jobTitle.id}/skills`, {
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
    taskForm.post(`/job-titles/${props.jobTitle.id}/tasks`, {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset()
            taskForm.is_active = true
            taskForm.sort_order = 0
        },
    })
}
</script>

<template>
    <PageContainer>
        <PageHeader
            :title="jobTitle.name"
            description="Manage Job Title information, Required Skills, Desired Skills, and Tasks."
            eyebrow="Job Title Requirements"
            back-href="/job-title-requirements"
            back-label="Job Title Requirements"
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link href="/job-titles">Job Titles</Link>
                </Button>
                <Button as-child>
                    <Link :href="`/job-titles/${jobTitle.id}/edit`">Edit Job Title</Link>
                </Button>
            </template>
        </PageHeader>

        <DetailSection
            title="Job title information"
            description="Core classification and display settings"
            :icon="BriefcaseBusiness"
        >
            <DetailGrid :columns="3">
                <DetailItem
                    class="sm:col-span-2 lg:col-span-3"
                    label="Description"
                    :value="jobTitle.description || 'No description provided.'"
                    multiline
                />
                <DetailItem label="Status" :value="jobTitle.is_active ? 'Active' : 'Inactive'" />
                <DetailItem label="Sort Order" :value="jobTitle.sort_order ?? 0" />
                <DetailItem label="Record ID" :value="jobTitle.id" copyable />
            </DetailGrid>
        </DetailSection>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card id="skills" class="scroll-mt-6">
                <CardHeader><CardTitle>Add Skill</CardTitle></CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submitSkill">
                        <div class="space-y-2">
                            <Label for="skill_name">Name</Label>
                            <Input id="skill_name" v-model="skillForm.name" />
                            <p v-if="skillForm.errors.name" class="text-sm text-destructive">{{ skillForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_description">Description</Label>
                            <Textarea id="skill_description" v-model="skillForm.description" rows="3" />
                            <p v-if="skillForm.errors.description" class="text-sm text-destructive">{{ skillForm.errors.description }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_requirement_type">Requirement</Label>
                            <select id="skill_requirement_type" v-model="skillForm.requirement_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="required">Required</option>
                                <option value="desired">Desired</option>
                            </select>
                            <p v-if="skillForm.errors.requirement_type" class="text-sm text-destructive">{{ skillForm.errors.requirement_type }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="skill_sort_order">Sort Order</Label>
                            <Input id="skill_sort_order" v-model="skillForm.sort_order" type="number" />
                            <p v-if="skillForm.errors.sort_order" class="text-sm text-destructive">{{ skillForm.errors.sort_order }}</p>
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
                <CardHeader><CardTitle>Add Task</CardTitle></CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submitTask">
                        <div class="space-y-2">
                            <Label for="task_name">Name</Label>
                            <Input id="task_name" v-model="taskForm.name" />
                            <p v-if="taskForm.errors.name" class="text-sm text-destructive">{{ taskForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task_description">Description</Label>
                            <Textarea id="task_description" v-model="taskForm.description" rows="3" />
                            <p v-if="taskForm.errors.description" class="text-sm text-destructive">{{ taskForm.errors.description }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="task_sort_order">Sort Order</Label>
                            <Input id="task_sort_order" v-model="taskForm.sort_order" type="number" />
                            <p v-if="taskForm.errors.sort_order" class="text-sm text-destructive">{{ taskForm.errors.sort_order }}</p>
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
                <CardHeader><CardTitle>Required Skills</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="requiredSkills.length" class="space-y-3">
                        <RequirementItem
                            v-for="(skill, index) in requiredSkills"
                            :key="skill.id"
                            :item="skill"
                            :index="index"
                            type="skill"
                            :job-title-id="jobTitle.id"
                            :editing="editingKey === `skill-${skill.id}`"
                            @start-edit="editingKey = $event"
                            @finish-edit="editingKey = null"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No required skills have been added.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Desired Skills</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="desiredSkills.length" class="space-y-3">
                        <RequirementItem
                            v-for="(skill, index) in desiredSkills"
                            :key="skill.id"
                            :item="skill"
                            :index="index"
                            type="skill"
                            :job-title-id="jobTitle.id"
                            :editing="editingKey === `skill-${skill.id}`"
                            @start-edit="editingKey = $event"
                            @finish-edit="editingKey = null"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No desired skills have been added.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Tasks</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="sortedTasks.length" class="space-y-3">
                        <RequirementItem
                            v-for="(task, index) in sortedTasks"
                            :key="task.id"
                            :item="task"
                            :index="index"
                            type="task"
                            :job-title-id="jobTitle.id"
                            :editing="editingKey === `task-${task.id}`"
                            @start-edit="editingKey = $event"
                            @finish-edit="editingKey = null"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No tasks have been added.</p>
                </CardContent>
            </Card>
        </div>
    </PageContainer>
</template>
