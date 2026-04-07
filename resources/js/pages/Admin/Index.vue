<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useAuth } from '@/composables/useAuth'

const { username, securityLevel } = useAuth()
</script>

<template>
    <Head title="Admin" />

    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Admin Page</h1>

        <p>User: {{ username }}</p>
        <p>Security Level: {{ securityLevel }}</p>

        <div class="mt-6 p-4 border rounded">
            <p>This is protected admin content.</p>
        </div>
    </div>
</template>