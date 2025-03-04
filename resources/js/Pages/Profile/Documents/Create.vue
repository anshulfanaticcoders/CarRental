<template>
    <Dialog>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="block text-sm font-medium">Document Type</label>
                <Select v-model="form.document_type" class="border p-2 w-full">
                    <SelectContent>
                        <SelectItem value="id_proof">ID Proof</SelectItem>
                        <SelectItem value="address_proof">Address Proof</SelectItem>
                        <SelectItem value="driving_license">Driving License</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium">Upload Document</label>
                <input type="file" @change="handleFileUpload" class="border p-2 w-full" />
            </div>

            <Button type="submit">Upload</Button>
        </form>
    </Dialog>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import Button from "@/Components/ui/button/Button.vue";
import { Dialog } from "@/Components/ui/dialog";
import { Select, SelectContent, SelectItem } from "@/Components/ui/select";
const form = ref({
    document_type: "",
    document_number: "",
    document_file: null,
});

const emit = defineEmits(["close"]);

const generateRandomNumber = () => {
    return Math.floor(1000000000 + Math.random() * 9000000000).toString(); // 10-digit random number
};

const handleFileUpload = (event) => {
    form.value.document_file = event.target.files[0];
};

const submit = () => {
    let formData = new FormData();
    formData.append("document_file", file.value);

    router.patch(`/user/documents/${props.document.id}`, formData, {
        onSuccess: () => {
            emit("close");
        }
    });
};

// Generate document number when component is mounted
onMounted(() => {
    form.value.document_number = generateRandomNumber();
});
</script>
