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
                            <!-- Locale Tabs -->
                            <div class="flex border-b border-gray-200 mb-4">
                                <button
                                    v-for="locale in available_locales"
                                    :key="locale"
                                    type="button"
                                    @click="setActiveLocaleDialog(locale)"
                                    :class="[
                                        'py-2 px-4 font-semibold',
                                        activeLocaleDialog === locale ? 'border-b-2 border-primary text-primary' : 'text-gray-500 hover:text-gray-700'
                                    ]"
                                >
                                    {{ locale.toUpperCase() }}
                                </button>
                            </div>

                            <div v-if="faqForm.translations[activeLocaleDialog]">
                                <div class="mb-4">
                                    <label :for="'question-' + activeLocaleDialog">Question ({{ activeLocaleDialog.toUpperCase() }})</label>
                                    <Input :id="'question-' + activeLocaleDialog" v-model="faqForm.translations[activeLocaleDialog].question" required />
                                     <p v-if="errors && errors[`translations.${activeLocaleDialog}.question`]" class="text-red-500 text-sm">{{ errors[`translations.${activeLocaleDialog}.question`] }}</p>
                                </div>
                                <div class="mt-2">
                                    <label :for="'answer-' + activeLocaleDialog">Answer ({{ activeLocaleDialog.toUpperCase() }})</label>
                                    <Textarea :id="'answer-' + activeLocaleDialog" v-model="faqForm.translations[activeLocaleDialog].answer" required />
                                    <p v-if="errors && errors[`translations.${activeLocaleDialog}.answer`]" class="text-red-500 text-sm">{{ errors[`translations.${activeLocaleDialog}.answer`] }}</p>
                                </div>
                            </div>
                            <div v-else>
                                <p>Translations not available for {{ activeLocaleDialog.toUpperCase() }}</p>
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
                                    <Button variant="destructive" @click="openDeleteDialog(faq.id)">Delete</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredFaqs.length === 0">
                            <TableCell colspan="3" class="text-center">No FAQs found.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this FAQ? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from "vue"; // Added watch
import { router, usePage } from "@inertiajs/vue3"; // Added usePage
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { useToast } from 'vue-toastification'; // Added useToast

const props = defineProps({
    faqs: Array,
    search: String,
    available_locales: Array, // Added
    current_locale: String,   // Added
    errors: Object, // For server-side validation errors
});

const page = usePage(); // Added page instance
const toast = useToast(); // Added toast instance

const search = ref(props.search || "");
const faqs = ref([...props.faqs]); // This will be updated by router.reload or direct assignment
const isDialogOpen = ref(false);
const isEditing = ref(false);
const isDeleteDialogOpen = ref(false);

const activeLocaleDialog = ref(props.current_locale || (props.available_locales && props.available_locales.length > 0 ? props.available_locales[0] : 'en'));

const initialTranslations = {};
if (props.available_locales) {
    props.available_locales.forEach(locale => {
        initialTranslations[locale] = { question: "", answer: "" };
    });
} else {
    // Default if available_locales is not passed (should not happen with controller update)
    initialTranslations['en'] = { question: "", answer: "" };
}


const faqForm = ref({
    id: null,
    translations: JSON.parse(JSON.stringify(initialTranslations)) // Deep copy
});
const deleteFaqId = ref(null);

const setActiveLocaleDialog = (locale) => {
    activeLocaleDialog.value = locale;
};

const openDialog = () => {
    isEditing.value = false;
    // Reset form with initial empty translations for each locale
    const newInitialTranslations = {};
    if (props.available_locales) {
        props.available_locales.forEach(locale => {
            newInitialTranslations[locale] = { question: "", answer: "" };
        });
    } else {
        newInitialTranslations['en'] = { question: "", answer: "" };
    }
    faqForm.value = { id: null, translations: newInitialTranslations };
    activeLocaleDialog.value = props.current_locale || (props.available_locales && props.available_locales.length > 0 ? props.available_locales[0] : 'en');
    isDialogOpen.value = true;
};

const addFaq = async () => {
    router.post(route("admin.settings.faq.store"), { translations: faqForm.value.translations }, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Assuming controller returns updated faqs or flash message
            // For simplicity, we can reload the faqs prop if successful
            if (page.props.flash && page.props.flash.success) {
                toast.success(page.props.flash.success);
                 router.reload({ only: ['faqs'] }); // Reload faqs to get the latest list
            }
            isDialogOpen.value = false;
        },
        onError: (formErrors) => {
            console.error("Error adding FAQ:", formErrors);
            // Display validation errors using toast or inline messages
            Object.values(formErrors).forEach(error => toast.error(error));
        }
    });
};

const editFaq = (faq) => {
    isEditing.value = true;
    const newTranslations = {};
    props.available_locales.forEach(locale => {
        // The 'faq' object from props.faqs already has translated question/answer via accessors
        // We need to fetch the specific translations for editing
        // This assumes faq.translations is an array like [{locale: 'en', question: 'Q', answer: 'A'}, ...]
        // If faq.translations is an object keyed by locale from controller, adjust accordingly
        const existingTranslation = faq.translations.find(t => t.locale === locale);
        newTranslations[locale] = {
            question: existingTranslation ? existingTranslation.question : (locale === props.current_locale ? faq.question : ''),
            answer: existingTranslation ? existingTranslation.answer : (locale === props.current_locale ? faq.answer : '')
        };
    });
    faqForm.value = { id: faq.id, translations: newTranslations };
    activeLocaleDialog.value = props.current_locale || props.available_locales[0];
    isDialogOpen.value = true;
};

const updateFaq = async () => {
    router.put(route("admin.settings.faq.update", faqForm.value.id), { translations: faqForm.value.translations }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash && page.props.flash.success) {
                toast.success(page.props.flash.success);
                 router.reload({ only: ['faqs'] }); // Reload faqs
            }
            isDialogOpen.value = false;
        },
        onError: (formErrors) => {
            console.error("Error updating FAQ:", formErrors);
            Object.values(formErrors).forEach(error => toast.error(error));
        }
    });
};

const openDeleteDialog = (id) => {
    deleteFaqId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
    router.delete(route("admin.settings.faq.destroy", deleteFaqId.value), {
        onSuccess: (page) => {
            if (page.props.flash && page.props.flash.success) {
                toast.success(page.props.flash.success);
            }
            // faqs.value = faqs.value.filter((faq) => faq.id !== deleteFaqId.value); // No longer needed if reloading
            router.reload({ only: ['faqs'] }); // Reload faqs
            isDeleteDialogOpen.value = false;
        },
        onError: (errors) => {
            console.error("Error deleting FAQ:", errors);
            toast.error("Failed to delete FAQ.");
        }
    });
};

const handleSearch = () => {
    router.get(route("admin.settings.faq.index"), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const filteredFaqs = computed(() => {
    if (!search.value) {
        return props.faqs; // Use props.faqs directly for filtering
    }
    return props.faqs.filter((faq) =>
        (faq.question && faq.question.toLowerCase().includes(search.value.toLowerCase())) ||
        (faq.answer && faq.answer.toLowerCase().includes(search.value.toLowerCase()))
    );
});

// Watch for changes in props.faqs to update local faqs ref if needed,
// though direct use of props.faqs in computed is often cleaner.
watch(() => props.faqs, (newFaqs) => {
  faqs.value = [...newFaqs];
}, { deep: true });

</script>

<style scoped>
table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}
</style>
