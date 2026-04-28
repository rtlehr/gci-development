<template>
    <div class="p-6 space-y-6">
        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Create Group</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Add a new group.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                <div>
                    <Label for="group_name">Group Name</Label>

                    <Input
                        id="group_name"
                        v-model="form.group_name"
                        type="text"
                        class="mt-1"
                    />

                    <p v-if="form.errors.group_name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.group_name }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Save Group
                </Button>

                <Link href="/admin/groups">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const form = useForm({
    group_name: '',
})

function submit() {
    form.post('/admin/groups')
}
</script>