<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

</script>

<template>
<MyProfileLayout>
        <Head title="Inbox" />
        <Card>
            <CardHeader>
                <CardTitle>Inbox</CardTitle>
                <CardDescription>Messages from vendors and support appear here.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="text-sm text-slate-500">Inbox</div>
            </CardContent>
        </Card>
    </MyProfileLayout>
</template>

<style>

</style>
