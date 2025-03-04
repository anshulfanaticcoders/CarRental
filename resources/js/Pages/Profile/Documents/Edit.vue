<template>
  
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="block text-sm font-medium">Upload New Document</label>
                <input type="file" @change="handleFileUpload" class="border p-2 w-full" />
            </div>

            <Button type="submit">Update</Button>
        </form>
  
</template>

<script setup>
import { ref, defineProps } from "vue";
import { router } from "@inertiajs/vue3";
import Button from "@/Components/ui/button/Button.vue";
const props = defineProps({
    document: Object,
});

const file = ref(null);
const emit = defineEmits(["close"]);

const handleFileUpload = (event) => {
    file.value = event.target.files[0];
};

const submit = () => {
    let formData = new FormData();
    formData.append("document_file", file.value);

    router.post(`/user/documents/${props.document.id}`, formData, {
        onSuccess: () => {
            emit("close");
        }
    });
};
</script>
