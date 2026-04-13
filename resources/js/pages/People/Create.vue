<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Create Person</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Add a new person record.
                </p>
            </div>

            <Link href="/people">
                <Button variant="outline">Back to List</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="person_code">
                            Person Code <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="person_code"
                            v-model="form.person_code"
                            :class="form.errors.person_code ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.person_code" class="text-sm text-red-500">
                            {{ form.errors.person_code }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="employment_status">Employment Status</Label>
                        <Input
                            id="employment_status"
                            v-model="form.employment_status"
                            :class="form.errors.employment_status ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.employment_status" class="text-sm text-red-500">
                            {{ form.errors.employment_status }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="first_name">
                            First Name <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            :class="form.errors.first_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.first_name" class="text-sm text-red-500">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="last_name">
                            Last Name <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            :class="form.errors.last_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.last_name" class="text-sm text-red-500">
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="company_name">Company Name</Label>
                        <Input
                            id="company_name"
                            v-model="form.company_name"
                            :class="form.errors.company_name ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.company_name" class="text-sm text-red-500">
                            {{ form.errors.company_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="cell_phone">Cell Phone</Label>
                        <Input
                            id="cell_phone"
                            v-model="form.cell_phone"
                            :class="form.errors.cell_phone ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.cell_phone" class="text-sm text-red-500">
                            {{ form.errors.cell_phone }}
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            :class="form.errors.email ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        rows="5"
                        :class="form.errors.notes ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.notes" class="text-sm text-red-500">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Person' }}
                    </Button>

                    <Link href="/people">
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
    person_code: '',
    first_name: '',
    last_name: '',
    company_name: '',
    cell_phone: '',
    email: '',
    employment_status: '',
    notes: '',
})

function submit() {
    form.clearErrors()

    let hasError = false

    if (!form.person_code || form.person_code.trim() === '') {
        form.setError('person_code', 'Person code is required.')
        hasError = true
    }

    if (!form.first_name || form.first_name.trim() === '') {
        form.setError('first_name', 'First name is required.')
        hasError = true
    }

    if (!form.last_name || form.last_name.trim() === '') {
        form.setError('last_name', 'Last name is required.')
        hasError = true
    }

    if (hasError) return

    form.post('/people')
}
</script>