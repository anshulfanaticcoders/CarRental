<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-10">
            <h4 class="font-medium">Create Vehicle Category</h4>
            <form @submit.prevent="submitForm" class="flex flex-col gap-5 items-start">
                <Input v-model="form.name" placeholder="Name" />
                <Textarea v-model="form.description" placeholder="Description" />
                <div class="flex flex-col gap-5">
                    <InputLabel for="image" value="Image" class="" />
                    <input class="border-[1px] border-customPrimaryColor p-5" type="file" @change="handleFileUpload" accept="image/jpeg,image/png,image/jpg,image/gif" />
                </div>
                <Button type="submit">Create</Button>
            </form>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Input from "@/Components/ui/input/Input.vue";
import Button from "@/Components/ui/button/Button.vue";
import Textarea from "@/Components/ui/textarea/Textarea.vue";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";

const form = ref({
    name: "",
    description: "",
    image: null,
});
const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};
const submitForm = () => {
    router.post("/vehicle-categories", form.value);
};
</script>
