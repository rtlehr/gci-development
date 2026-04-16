<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

const props = defineProps({
    currentUser: {
        type: Object,
        required: true,
    },
    sourceUrl: {
        type: String,
        default: '',
    },
})

const form = useForm({
    title: '',
    request_type: 'bug',
    importance: 'nice_to_have',
    category: 'Other',
    description: '',
    source_url: props.sourceUrl || window.location.href,
    screenshot: null,
})

function handleScreenshotChange(event) {
    form.screenshot = event.target.files[0] || null
}

function submit() {
    form.clearErrors()

    let hasError = false

    if (!form.title || form.title.trim() === '') {
        form.setError('title', 'Title is required.')
        hasError = true
    }

    if (!form.description || form.description.trim() === '') {
        form.setError('description', 'Explanation of request is required.')
        hasError = true
    }

    if (hasError) return

    form.post('/tickets')
}
</script>

<template>
    <div class="p-6 max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Submit Request</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Report a bug or request an improvement.
                </p>
            </div>

            <Link href="/">
                <Button variant="outline">Cancel</Button>
            </Link>
        </div>

        <div class="border rounded-xl p-6 bg-background">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="title">
                            Title <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Short title for the request"
                            :class="form.errors.title ? 'border-red-500' : ''"
                        />
                        <p v-if="form.errors.title" class="text-sm text-red-500">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="user_name">User Name</Label>
                        <Input
                            id="user_name"
                            :model-value="currentUser.username"
                            disabled
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="request_type">Request Type</Label>
                        <select
                            id="request_type"
                            v-model="form.request_type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="bug">Bug</option>
                            <option value="improvement">Improvement</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="importance">Importance</Label>
                        <select
                            id="importance"
                            v-model="form.importance"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="show_stopper">Show Stopper</option>
                            <option value="asap">Needed ASAP</option>
                            <option value="nice_to_have">Nice to Have</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <select
                            id="category"
                            v-model="form.category"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="UI">UI</option>
                            <option value="Data">Data</option>
                            <option value="Permissions">Permissions</option>
                            <option value="Workflow">Workflow</option>
                            <option value="Performance">Performance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="source_url">Source Page</Label>
                        <Input
                            id="source_url"
                            v-model="form.source_url"
                            disabled
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="screenshot">Screenshot</Label>
                    <Input
                        id="screenshot"
                        type="file"
                        accept="image/*"
                        @change="handleScreenshotChange"
                        :class="form.errors.screenshot ? 'border-red-500' : ''"
                    />
                    <p class="text-xs text-muted-foreground">
                        Optional. Upload a screenshot to help explain the issue.
                    </p>
                    <p v-if="form.errors.screenshot" class="text-sm text-red-500">
                        {{ form.errors.screenshot }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">
                        Explanation of Request <span class="text-red-500">*</span>
                    </Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="8"
                        placeholder="Describe the bug or requested improvement..."
                        :class="form.errors.description ? 'border-red-500' : ''"
                    />
                    <p v-if="form.errors.description" class="text-sm text-red-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </Button>

                    <Link href="/">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>