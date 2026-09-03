<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Insite Portal Initial Setup',
        description: 'Sign in with the temporary bootstrap Owner credentials to configure this installation.',
    },
});

defineProps<{ status?: string }>();
</script>

<template>
    <Head title="Initial Owner Setup" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
        role="status"
        aria-live="polite"
    >
        {{ status }}
    </div>

    <div class="mb-5 rounded-md border bg-muted/40 p-4 text-sm text-muted-foreground">
        This login is available only during first-time installation. After setup is completed,
        Insite Portal returns to enterprise person-code authentication only.
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Owner email</Label>
                <Input id="email" type="email" name="email" required autofocus autocomplete="username" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Temporary password</Label>
                <PasswordInput id="password" name="password" required autocomplete="current-password" />
                <InputError :message="errors.password" />
            </div>

            <Button type="submit" class="w-full" :disabled="processing" data-test="bootstrap-login-button">
                <Spinner v-if="processing" />
                Sign in for initial setup
            </Button>
        </div>
    </Form>
</template>
