<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Insite Portal Access',
        description: 'Your enterprise identity is not currently authorized for Insite Portal.',
    },
});

defineProps<{
    enabled: boolean;
    message: string;
    sessionMinutes: number;
}>();
</script>

<template>
    <Head title="You do not have access" />

    <div class="space-y-6">
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 text-amber-950">
            <h1 class="text-xl font-semibold">You do not have access</h1>
            <p class="mt-2 text-sm leading-6">
                {{ message }}
            </p>
            <p class="mt-2 text-sm leading-6">
                Contact an Insite Portal administrator if you believe you should have access.
            </p>
        </div>

        <div v-if="enabled" class="rounded-lg border bg-card p-5 shadow-sm">
            <h2 class="text-lg font-semibold">Owner Recovery Access</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                This emergency login is restricted to the designated Owner account. Recovery sessions
                expire after {{ sessionMinutes }} minutes and are logged.
            </p>

            <Form
                action="/owner-recovery"
                method="post"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="mt-5 flex flex-col gap-5"
            >
                <div class="grid gap-2">
                    <Label for="email">Owner email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Owner recovery password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Button type="submit" class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Sign in with Owner Recovery Access
                </Button>
            </Form>
        </div>

        <div v-else class="rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">
            Owner Recovery Access is not enabled for this installation. Contact the system administrator.
        </div>
    </div>
</template>
