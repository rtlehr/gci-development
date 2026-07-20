<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { CheckSquare, ChevronRight } from 'lucide-vue-next'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

type JobTitle = { id: number; name: string; description?: string | null; skills_count: number; tasks_count: number; is_active: boolean }
defineProps<{ jobTitles: JobTitle[] }>()
</script>

<template>
    <PageContainer>
        <PageHeader title="Job Title Tasks" description="Select a job title to maintain its default tasks." eyebrow="Positions" back-href="/job-titles" back-label="Job Titles" />
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="jobTitle in jobTitles" :key="jobTitle.id">
                <CardHeader class="pb-3"><CardTitle class="flex items-center gap-2 text-base"><CheckSquare class="h-4 w-4" />{{ jobTitle.name }}</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <p class="line-clamp-2 text-sm text-muted-foreground">{{ jobTitle.description || 'No description provided.' }}</p>
                    <div class="flex items-center justify-between text-sm"><span>{{ jobTitle.tasks_count }} task{{ jobTitle.tasks_count === 1 ? '' : 's' }}</span><span>{{ jobTitle.is_active ? 'Active' : 'Inactive' }}</span></div>
                    <Button as-child class="w-full"><Link :href="`/job-titles/${jobTitle.id}#tasks`">Manage Tasks<ChevronRight class="ml-2 h-4 w-4" /></Link></Button>
                </CardContent>
            </Card>
        </div>
    </PageContainer>
</template>
