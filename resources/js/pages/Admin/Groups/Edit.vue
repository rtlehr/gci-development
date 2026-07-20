<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type Group = {
    id: number
    group_name: string | null
}

const props = defineProps<{
    group: Group
}>()

const form = useForm({
    group_name: props.group.group_name ?? '',
})

function submit(): void {
    form.put(`/admin/groups/${props.group.id}`)
}
</script>

<template>
    <PageContainer size="default">
        <PageHeader
            title="Edit Group"
            :description="`Update ${group.group_name || 'this group'}.`"
            eyebrow="Administration"
            back-href="/admin/groups"
            back-label="Groups"
        >
            <template #meta>
                <span>Group ID: {{ group.id }}</span>
            </template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="space-y-4 rounded-xl border bg-background p-6">
                <div class="space-y-2">
                    <Label for="group_name">Group Name</Label>
                    <Input
                        id="group_name"
                        v-model="form.group_name"
                        type="text"
                        autocomplete="off"
                    />
                    <p v-if="form.errors.group_name" class="text-sm text-destructive">
                        {{ form.errors.group_name }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Update Group' }}
                </Button>

                <Link href="/admin/groups">
                    <Button type="button" variant="outline">Cancel</Button>
                </Link>
            </div>
        </form>
    </PageContainer>
</template>
