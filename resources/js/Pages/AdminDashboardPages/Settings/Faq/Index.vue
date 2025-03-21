<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Manage FAQs</span>
                <Input v-model="search" placeholder="Search FAQ..." class="w-[300px]" @input="handleSearch" />

                <!-- Open Dialog -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger as-child>
                        <Button @click="openDialog">Create New FAQ</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{{ isEditing ? "Edit FAQ" : "Create FAQ" }}</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="isEditing ? updateFaq() : addFaq()">
                            <div>
                                <label for="question">Question</label>
                                <Input id="question" v-model="faqForm.question" required />
                            </div>
                            <div class="mt-2">
                                <label for="answer">Answer</label>
                                <Textarea id="answer" v-model="faqForm.answer" required />
                            </div>
                            <div class="mt-4 flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                                <Button type="submit">{{ isEditing ? "Update" : "Add" }} FAQ</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Question</TableHead>
                            <TableHead>Answer</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="faq in filteredFaqs" :key="faq.id">
                            <TableCell>{{ faq.question }}</TableCell>
                            <TableCell>{{ faq.answer }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="outline" @click="editFaq(faq)">Edit</Button>
                                    <Button variant="destructive" @click="deleteFaq(faq.id)">Delete</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";

const props = defineProps({
    faqs: Array,
    search: String,
});

const search = ref(props.search || "");
const faqs = ref([...props.faqs]); // Keep local state for immediate UI updates
const isDialogOpen = ref(false);
const isEditing = ref(false);
const faqForm = ref({ id: null, question: "", answer: "" });

const openDialog = () => {
    isEditing.value = false;
    faqForm.value = { id: null, question: "", answer: "" };
    isDialogOpen.value = true;
};

const addFaq = async () => {
    try {
        await router.post(route("admin.settings.faq.store"), faqForm.value, {
            preserveScroll: true, // Keeps scroll position
            onSuccess: (page) => {
                if (page.props.faqs) {
                    faqs.value = page.props.faqs; // Refresh FAQ list
                }
                isDialogOpen.value = false; // Close dialog
            },
        });
    } catch (error) {
        console.error("Error adding FAQ:", error);
    }
};

const editFaq = (faq) => {
    isEditing.value = true;
    faqForm.value = { ...faq };
    isDialogOpen.value = true;
};

const updateFaq = async () => {
    try {
        await router.put(route("admin.settings.faq.update", faqForm.value.id), faqForm.value);
        const index = faqs.value.findIndex((faq) => faq.id === faqForm.value.id);
        if (index !== -1) {
            faqs.value[index] = { ...faqForm.value };
        }
        isDialogOpen.value = false;
    } catch (error) {
        console.error(error);
    }
};

const deleteFaq = async (id) => {
    if (confirm("Are you sure you want to delete this FAQ?")) {
        try {
            await router.delete(route("admin.settings.faq.destroy", id));
            faqs.value = faqs.value.filter((faq) => faq.id !== id); // Remove from local list
        } catch (error) {
            console.error(error);
        }
    }
};

const handleSearch = () => {
    router.get(route("admin.settings.faq.index"), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const filteredFaqs = computed(() => {
    if (!search.value) {
        return faqs.value;
    }
    return faqs.value.filter((faq) =>
        faq.question.toLowerCase().includes(search.value.toLowerCase()) ||
        faq.answer.toLowerCase().includes(search.value.toLowerCase())
    );
});
</script>
