<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="form.first_name" required />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="form.last_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="form.email" type="email" required />
            </div>
            <div class="relative">
                <InputLabel for="country" value="Country" class="mb-1" />
                <Select v-model="form.country">
                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                        <SelectValue placeholder="Select Country" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Country</SelectLabel>
                            <SelectItem v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <!-- Dynamic Flag -->
                <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag"
                    class="absolute right-3 top-1/2 transform translate-x-[0%] translate-y-[0%] w-[2.1rem] h-[1.5rem] rounded" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <Input v-model="form.phone" required />
            </div>
            <div>
                <InputLabel for="password" value="Password *" />
                <Input v-model="form.password" type="password" required :class="{
                    'border-red-500': form.password.length > 0 && form.password.length < 8,
                    'border-green-500': form.password.length >= 8
                }" />
                <p v-if="form.password.length > 0 && form.password.length < 8" class="text-red-500 text-sm mt-1">
                    Password must be at least 8 characters long
                </p>
                <p v-else-if="form.password.length >= 8" class="text-green-500 text-sm mt-1">
                    Password length is valid
                </p>
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Confirm Password *" />
                <Input v-model="form.password_confirmation" type="password" required :class="{
                    'border-red-500': form.password_confirmation.length > 0 &&
                        form.password !== form.password_confirmation,
                    'border-green-500': form.password_confirmation.length > 0 &&
                        form.password === form.password_confirmation
                }" />
                <p v-if="form.password_confirmation.length > 0 &&
                    form.password !== form.password_confirmation" class="text-red-500 text-sm mt-1">
                    Passwords do not match
                </p>
                <p v-else-if="form.password_confirmation.length > 0 &&
                    form.password === form.password_confirmation" class="text-green-500 text-sm mt-1">
                    Passwords match
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="role" value="Role *" />
                    <Select v-model="form.role" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="customer">Customer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <InputLabel for="status" value="Status *" />
                    <Select v-model="form.status" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <!-- Error message for form submission attempt -->
            <div v-if="errorMessage" class="p-2 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ errorMessage }}
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="form.password.length < 8 ||
                    form.password !== form.password_confirmation">
                    Create User
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";
import Button from "@/Components/ui/button/Button.vue";

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    country: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    status: 'active'
});

const countries = ref([]);
const errorMessage = ref('');
const emit = defineEmits(['close']);

const fetchCountries = async () => {
    try {
        const response = await fetch('/countries.json'); // Ensure it's in /public
        countries.value = await response.json();
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

onMounted(fetchCountries);

// Get flag URL
const getFlagUrl = (countryCode) => {
    return `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`;
};

const submitForm = () => {
    // Reset error message
    errorMessage.value = '';

    // Check password length
    if (form.value.password.length < 8) {
        errorMessage.value = 'Password must be at least 8 characters long';
        return;
    }

    // Check password match
    if (form.value.password !== form.value.password_confirmation) {
        errorMessage.value = 'Passwords do not match';
        return;
    }

    if (!form.value.country) {
        errorMessage.value = 'Please select a country';
        return;
    }

    router.post("/users", form.value, {
        onSuccess: () => {
            form.value = {
                first_name: '',
                last_name: '',
                email: '',
                country: '',
                phone: '',
                password: '',
                password_confirmation: '',
                role: 'customer',
                status: 'active'
            };
            emit('close');
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || 'An error occurred';
        },
        preserveState: true,
        preserveScroll: true,
    });
};
</script>