<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    organization: any
    parents: any[]
}>()

const form = useForm({
    name: props.organization.name,
    parent_id: props.organization.parent_id ?? 1,
    status: props.organization.status,
    notes: props.organization.notes ?? '',
})

const submit = () => {
    form.put(`/organizations/${props.organization.id}`)
}
</script>

<template>
    <div class="p-6 space-y-6">

        <div class="rounded-2xl border bg-background p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Edit Organization</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6">

            <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">

                <!-- Name -->
                <div>
                    <label class="text-sm font-medium">Name *</label>
                    <input v-model="form.name" class="input w-full mt-1" />
                </div>

                <!-- Parent -->
                <div>
                    <label class="text-sm font-medium">Parent Organization</label>
                    <select v-model="form.parent_id" class="input w-full mt-1">
                        <option v-for="p in parents" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-sm font-medium">Status</label>
                    <select v-model="form.status" class="input w-full mt-1">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label class="text-sm font-medium">Notes</label>
                    <textarea v-model="form.notes" class="input w-full mt-1" />
                </div>

            </div>

            <div class="flex gap-3 justify-end">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>

                <Link href="/organizations">
                    <Button type="button" variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>

        </form>
    </div>
</template>