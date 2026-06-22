<template>
    <div class="p-6 max-w-3xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Job Title</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Add a new master Job Title.
                </p>
            </div>

            <Link href="/job-titles">
                <Button variant="outline">Back to List</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-sm text-red-500">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" rows="5" />
                    <p v-if="form.errors.description" class="text-sm text-red-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="sort_order">Sort Order</Label>
                    <Input id="sort_order" type="number" v-model="form.sort_order" />
                    <p v-if="form.errors.sort_order" class="text-sm text-red-500">
                        {{ form.errors.sort_order }}
                    </p>
                </div>

                <label class="flex items-center justify-between rounded-lg border p-4 cursor-pointer">
                    <span class="font-medium text-sm">Active</span>
                    <input type="checkbox" v-model="form.is_active" class="h-5 w-5" />
                </label>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Job Title' }}
                    </Button>

                    <Link href="/job-titles">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
})

function submit() {
    form.post('/job-titles')
}
</script>