<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { BriefcaseBusiness, ChevronRight, ListChecks, Sparkles } from 'lucide-vue-next'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

type JobTitle = {
    id: number
    name: string
    description?: string | null
    skills_count: number
    tasks_count: number
    is_active: boolean
}

defineProps<{ jobTitles: JobTitle[] }>()
</script>

<template>
    <PageContainer>
        <PageHeader
            title="Job Title Requirements"
            description="Select a job title to manage its Required and Desired skills together with its default tasks."
            eyebrow="Positions"
            back-href="/job-titles"
            back-label="Job Titles"
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="jobTitle in jobTitles" :key="jobTitle.id">
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <BriefcaseBusiness class="h-4 w-4" />
                        {{ jobTitle.name }}
                    </CardTitle>
                </CardHeader>

                <CardContent class="space-y-4">
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ jobTitle.description || 'No description provided.' }}
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border p-3">
                            <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                <Sparkles class="h-3.5 w-3.5" />
                                Skills
                            </div>
                            <p class="mt-1 text-lg font-semibold">{{ jobTitle.skills_count }}</p>
                        </div>

                        <div class="rounded-lg border p-3">
                            <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                <ListChecks class="h-3.5 w-3.5" />
                                Tasks
                            </div>
                            <p class="mt-1 text-lg font-semibold">{{ jobTitle.tasks_count }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Status</span>
                        <span>{{ jobTitle.is_active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <Button as-child class="w-full">
                        <Link :href="`/job-titles/${jobTitle.id}#skills`">
                            Manage Requirements
                            <ChevronRight class="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="jobTitles.length === 0"
            class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            No Job Titles are available. Create a Job Title before adding Skills or Tasks.
        </div>
    </PageContainer>
</template>
