<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'vue-toastification';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue
} from '@/Components/ui/select';

const toast = useToast();
const user = usePage().props.auth.user;
const profile = usePage().props.auth.user.profile;

const form = useForm({
    first_name: user.first_name,
    last_name: user.last_name,
    email: user.email,
    phone: user.phone,
    date_of_birth: profile?.date_of_birth || '',
    address_line1: profile?.address_line1 || '',
    address_line2: profile?.address_line2 || '',
    city: profile?.city || '',
    state: profile?.state || '',
    country: profile?.country || '',
    postal_code: profile?.postal_code || '',
    tax_identification: profile?.tax_identification || '',
    about: profile?.about || '',
    title: profile?.title || 'Mr.',
    gender: profile?.gender || 'male',
    currency: profile?.currency || '',
    avatar: profile?.avatar || '',
});


// Gender Selection
watch(() => form.title, (newTitle) => {
    if (newTitle === 'Mr.') {
        form.gender = 'male';
    } else if (newTitle === 'Miss') {
        form.gender = 'female';
    } else {
        form.gender = 'male';
    }
});


const avatarPreview = ref(profile?.avatar
    ? (profile.avatar.startsWith('http') ? profile.avatar : `/storage/${profile.avatar}`)
    : '/storage/avatars/default-avatar.svg');

function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
        form.avatar = file;
    }
}
const handleSubmit = () => {
    form.post(route('profile.update'), {
        onSuccess: () => {
            toast.success('Profile updated successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        },
    });
};

const currencies = ref([]);
const selectedCurrency = computed({
    get() {
        const currency = currencies.value.find(c => c.symbol === form.currency);
        return currency ? currency.code : '';
    },
    set(newValue) {
        const currency = currencies.value.find(c => c.code === newValue);
        form.currency = currency ? currency.symbol : '';
    }
});

const fetchCurrencies = async () => {
    try {
        const response = await fetch('/currency.json'); // Ensure it's in /public
        currencies.value = await response.json();
    } catch (error) {
        console.error("Error loading currencies:", error);
    }
};

onMounted(fetchCurrencies);


const profileCompletion = ref(0);

const fetchProfileCompletion = async () => {
    try {
        const response = await fetch('/profile/completion');
        const data = await response.json();
        profileCompletion.value = data.percentage;
    } catch (error) {
        console.error('Error fetching profile completion:', error);
    }
};

onMounted(fetchProfileCompletion);


const countries = ref([]);
const selectedCountry = ref("");


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


const minimumDateOfBirth = computed(() => {
    const today = new Date();
    const eighteenYearsAgo = new Date(
        today.getFullYear() - 18,
        today.getMonth(),
        today.getDate()
    );

    // Format the date as YYYY-MM-DD for the max attribute
    return eighteenYearsAgo.toISOString().split('T')[0];
});
</script>

<template>
    <header>
        <h2 class="text-[1.75rem] font-medium text-gray-900 max-[768px]:text-[1.2rem]">Personal Details</h2>
        <div>
            <div class="profile-completion">
                <p>Profile Completion: {{ profileCompletion }}%</p>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: profileCompletion + '%' }"></div>
                </div>
            </div>

        </div>
    </header>
    <section v-bind="$attrs">
        <form @submit.prevent="handleSubmit" class="mt-6 space-y-6">
            <div>
                <!-- Avatar preview with edit button -->
                <div class="relative w-24 h-24">
                    <img :src="avatarPreview" alt="User Avatar" class="w-24 h-24 rounded-full object-cover" />
                    <button type="button" @click="() => $refs.avatarInput.click()"
                        class="absolute bottom-0 right-0 bg-gray-700 text-white p-1 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M17.414 2.586a2 2 0 00-2.828 0L6 11.172V14h2.828l8.586-8.586a2 2 0 000-2.828zM5 15v1a2 2 0 002 2h1a2 2 0 002-2v-1H5z" />
                        </svg>
                    </button>
                </div>
                <input ref="avatarInput" id="avatar" type="file" accept="image/*" class="hidden"
                    @change="handleAvatarUpload" />
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>
            <div class="grid grid-cols-2 gap-8">
                <div class="col-span-2 w-[6rem]">
                    <InputLabel for="title" value="Title" />
                    <select id="title" class="mt-1 block w-full" v-model="form.title">
                        <option value="Mr." selected>Mr.</option>
                        <option value="Miss">Miss</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.title" />
                </div>

                <!-- First and Last Name -->

                <div class="w-full">
                    <InputLabel for="first_name" value="First Name (as on passport)" />
                    <TextInput id="first_name" type="text" class="mt-1 block w-full" v-model="form.first_name"
                        required />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div class="w-full">
                    <InputLabel for="last_name" value="Last Name (as on passport)" />
                    <TextInput id="last_name" type="text" class="mt-1 block w-full" v-model="form.last_name" required />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
                <!-- First and Last Name// -->


                <div>
                    <InputLabel for="phone" value="Phone Number" />
                    <TextInput id="phone" type="tel" class="mt-1 block w-full" v-model="form.phone" required />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>


                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" class="mt-1 block w-full bg-gray-200" v-model="form.email"
                        required readonly />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>


                <div>
                    <InputLabel for="date_of_birth" value="Date of Birth" />
                    <TextInput id="date_of_birth" type="date" v-model="form.date_of_birth" required
                        :max="minimumDateOfBirth" class="w-full" />
                    <small class="text-gray-500 mt-1 block">You must be at least 18 years old</small>
                    <InputError class="mt-2" :message="form.errors.date_of_birth" />
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
                    <InputError class="mt-2" :message="form.errors.country" />

                    <!-- Dynamic Flag -->
                    <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag" class="absolute right-3 top-1/2 transform translate-x-[-10%] translate-y-[-50%] w-[2.1rem] h-[1.5rem] rounded
                        max-[768px]:translate-y-[-70%]" />
                </div>



                <div>
                    <InputLabel for="currency" value="Currency" class="mb-1"/>
                    <Select v-model="selectedCurrency">
                        <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                            <SelectValue placeholder="Select Currency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Currency</SelectLabel>
                                <SelectItem v-for="currency in currencies" :key="currency.code" :value="currency.code">
                                    {{ currency.code }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.currency" />
                </div>


                <div>
                    <InputLabel for="city" value="City" />
                    <TextInput id="city" type="text" class="mt-1 block w-full" v-model="form.city" />
                    <InputError class="mt-2" :message="form.errors.city" />
                </div>


                <div>
                    <InputLabel for="state" value="State" />
                    <TextInput id="state" type="text" class="mt-1 block w-full" v-model="form.state" />
                    <InputError class="mt-2" :message="form.errors.state" />
                </div>

                <div>
                    <InputLabel for="postal_code" value="Postal Code" />
                    <TextInput id="postal_code" type="text" class="mt-1 block w-full" v-model="form.postal_code" />
                    <InputError class="mt-2" :message="form.errors.postal_code" />
                </div>

                <div class="col-span-2">
                    <InputLabel for="address_line1" value="Address Line 1" />
                    <TextInput id="address_line1" type="text" class="mt-1 block w-full" v-model="form.address_line1" />
                    <InputError class="mt-2" :message="form.errors.address_line1" />
                </div>


                <h2 class="text-[1.5rem] font-medium text-gray-900 max-[768px]:text-[1.2rem]">Profile</h2>

                <div class="col-span-2">
                    <p class="mb-[1rem] text-customLightGrayColor font-medium max-[768px]:text-[0.95rem]">Who am I?
                        <i>(optional)</i>
                    </p>
                    <InputLabel for="about" value="About" />
                    <TextArea id="about" class="mt-1 block w-full" v-model="form.about" />
                    <InputError class="mt-2" :message="form.errors.about" />
                </div>



                <div class="col-span-2">
                    <InputLabel for="address_line2" value="Address Line 2" />
                    <TextInput id="address_line2" type="text" class="mt-1 block w-full" v-model="form.address_line2" />
                    <InputError class="mt-2" :message="form.errors.address_line2" />
                </div>


                <div class="max-[768px]:col-span-2">
                    <h2 class="text-[1.5rem] font-medium text-gray-900 max-[768px]:text-[1.2rem] max-[768px]:mb-4">Tax
                        Identification Number</h2>
                    <InputLabel for="tax_identification" value="Tax Identification Number" />
                    <TextInput id="tax_identification" type="text" class="mt-1 block w-full"
                        v-model="form.tax_identification" />
                    <InputError class="mt-2" :message="form.errors.tax_identification" />
                </div>

                <div class="flex items-end gap-4 row-span-3 col-span-2">
                    <PrimaryButton :disabled="form.processing" class="w-[10rem]">Update Profile </PrimaryButton>
                </div>
            </div>
        </form>
    </section>
</template>

<style scoped>
input,
textarea,
select {
    border-radius: 0.75rem;
    border: 1px solid rgba(43, 43, 43, 0.50) !important;
    padding: 1rem;
}

.profile-completion {
    margin: 10px 0;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: #e0e0e0;
    /* Grey background for the remaining part */
    border-radius: 5px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background-color: #4caf50;
    /* Green color for filled portion */
    transition: width 0.5s ease-in-out;
}

@media screen and (max-width:768px) {
    input {
        font-size: 0.75rem;
    }

    select {
        font-size: 0.75rem;
    }

    textarea {
        font-size: 0.75rem;
    }

    label {
        font-size: 0.75rem !important;
    }
}
</style>