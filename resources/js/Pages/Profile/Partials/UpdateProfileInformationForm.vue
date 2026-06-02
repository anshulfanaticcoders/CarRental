<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch, getCurrentInstance } from 'vue';
import { toast } from 'vue-sonner';
import { getCurrencyOptions, normalizeCurrencyCode } from '@/utils/currencyRegistry';
import { Toaster } from "@/Components/ui/sonner";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue
} from '@/Components/ui/select';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import loaderVariant from '../../../../assets/loader-variant.svg';
import { User, Phone, Mail, Building2, Map, Hash, Home, Receipt, FileText } from 'lucide-vue-next';

const user = usePage().props.auth.user;
const profile = usePage().props.auth.user.profile;

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

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
        preserveScroll: true, // Keeps scroll position unless overridden
        onSuccess: () => {
            toast.success(_t('customerprofilepages', 'profile_updated_success_toast'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: (errors) => {
            toast.error('Failed to update profile. Please check the form.');
            // Log errors for debugging

            // Get the first error field
            const firstErrorField = Object.keys(errors)[0];
            if (firstErrorField) {
                // Find the input element by its ID
                const inputElement = document.getElementById(firstErrorField);
                if (inputElement) {
                    // Scroll to the input smoothly
                    inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Optionally focus the input
                    inputElement.focus();
                }
            }
        },
    });
};

const currencies = ref(getCurrencyOptions(usePage().props.currency_supported || undefined));
const selectedCurrency = computed({
    get() {
        return form.currency || '';
    },
    set(newValue) {
        form.currency = newValue;
    }
});

const normalizeCurrencyValue = () => {
    if (!form.currency || currencies.value.length === 0) return;
    form.currency = normalizeCurrencyCode(form.currency, 'EUR');
};

const fetchCurrencies = () => {
    currencies.value = getCurrencyOptions(usePage().props.currency_supported || undefined);
    normalizeCurrencyValue();
};

onMounted(fetchCurrencies);


const profileCompletion = ref(0);

const fetchProfileCompletion = async () => {
    try {
        const response = await fetch(route('profile.completion'));
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


const dateOfBirth = ref(null);

const minimumDateOfBirth = computed(() => {
    const today = new Date();
    const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    return eighteenYearsAgo.toISOString().split('T')[0];
});

// Watch dateOfBirth to update form.date_of_birth
watch(dateOfBirth, (newValue) => {
    if (newValue) {
        form.date_of_birth = newValue.toISOString().split('T')[0];
    } else {
        form.date_of_birth = '';
    }
});

// Initialize dateOfBirth with existing value on mount
onMounted(() => {
    if (form.date_of_birth) {
        dateOfBirth.value = new Date(form.date_of_birth);
    }
});

</script>

<template>
    <section v-bind="$attrs">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Identity header: avatar + name + title -->
            <div class="profile-id">
                <div class="profile-id-avatar">
                    <img :src="avatarPreview" alt="User Avatar" />
                    <button type="button" @click="() => $refs.avatarInput.click()" class="profile-id-edit"
                        aria-label="Change photo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M17.414 2.586a2 2 0 00-2.828 0L6 11.172V14h2.828l8.586-8.586a2 2 0 000-2.828zM5 15v1a2 2 0 002 2h1a2 2 0 002-2v-1H5z" />
                        </svg>
                    </button>
                </div>
                <input ref="avatarInput" id="avatar" type="file" accept="image/*" class="hidden"
                    @change="handleAvatarUpload" />
                <div class="profile-id-meta">
                    <p class="profile-id-name">{{ form.first_name }} {{ form.last_name }}</p>
                    <p class="profile-id-email">{{ form.email }}</p>
                    <InputError class="mt-1" :message="form.errors.avatar" />
                </div>
                <div class="profile-id-title">
                    <InputLabel for="title" :value="_t('customerprofilepages', 'title_label')" />
                    <Select v-model="form.title">
                        <SelectTrigger class="h-11 rounded-[10px] min-w-[130px] mt-1 bg-white">
                            <SelectValue :placeholder="_t('customerprofilepages', 'select_title_placeholder')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>{{ _t('customerprofilepages', 'title_label') }}</SelectLabel>
                                <SelectItem value="Mr.">{{ _t('customerprofilepages', 'title_mr') }}</SelectItem>
                                <SelectItem value="Miss">{{ _t('customerprofilepages', 'title_miss') }}</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-1" :message="form.errors.title" />
                </div>
            </div>

            <div class="vr-form-grid">
                <!-- First and Last Name -->

                <div class="w-full">
                    <InputLabel for="first_name" :value="_t('customerprofilepages', 'first_name_label')" />
                    <div class="vr-ifield">
                        <User class="vr-ifield-ic" />
                        <TextInput id="first_name" type="text" class="block w-full" v-model="form.first_name" required />
                    </div>
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div class="w-full">
                    <InputLabel for="last_name" :value="_t('customerprofilepages', 'last_name_label')" />
                    <div class="vr-ifield">
                        <User class="vr-ifield-ic" />
                        <TextInput id="last_name" type="text" class="block w-full" v-model="form.last_name" required />
                    </div>
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
                <!-- First and Last Name// -->


                <div>
                    <InputLabel for="phone" :value="_t('customerprofilepages', 'phone_number_label')" />
                    <div class="vr-ifield">
                        <Phone class="vr-ifield-ic" />
                        <TextInput id="phone" type="tel" class="block w-full" v-model="form.phone" required />
                    </div>
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>


                <div>
                    <InputLabel for="email" :value="_t('customerprofilepages', 'email_label')" />
                    <div class="vr-ifield">
                        <Mail class="vr-ifield-ic" />
                        <TextInput id="email" type="email" class="block w-full" v-model="form.email" required readonly />
                    </div>
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>


                <div>
                    <InputLabel for="date_of_birth" :value="_t('customerprofilepages', 'date_of_birth_label')"
                        class="mb-1" />
                    <VueDatePicker v-model="dateOfBirth" :enable-time-picker="false" uid="date-of-birth" auto-apply
                        :placeholder="_t('customerprofilepages', 'date_of_birth_placeholder')" class="w-full"
                        :max-date="minimumDateOfBirth" :start-date="minimumDateOfBirth" />
                    <InputError class="mt-2" :message="form.errors.date_of_birth" />
                </div>

                <div class="relative">
                    <InputLabel for="country" :value="_t('customerprofilepages', 'country_label')" class="mb-1" />
                    <Select v-model="form.country">
                        <SelectTrigger class="w-full h-12 rounded-[10px]">
                            <SelectValue :placeholder="_t('customerprofilepages', 'select_country_placeholder')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>{{ _t('customerprofilepages', 'country_label') }}</SelectLabel>
                                <SelectItem v-for="country in countries" :key="country.code" :value="country.code">
                                    <div class="flex items-center gap-2">
                                        <img :src="getFlagUrl(country.code)" :alt="`${country.name} flag`"
                                            class="w-[1.5rem] h-[1rem] rounded-sm" />
                                        {{ country.name }}
                                    </div>
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.country" />

                    <!-- Dynamic Flag -->
                    <!-- <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag" class="absolute right-3 top-1/2 transform translate-x-[-10%] translate-y-[-10%] w-[2.1rem] h-[1.5rem] rounded
                        max-[768px]:translate-y-[-70%]" /> -->
                </div>



                <div>
                    <InputLabel for="currency" :value="_t('customerprofilepages', 'currency_label')" class="mb-1" />
                    <Select v-model="selectedCurrency">
                        <SelectTrigger class="w-full h-12 rounded-[10px]">
                            <SelectValue :placeholder="_t('customerprofilepages', 'select_currency_placeholder')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>{{ _t('customerprofilepages', 'currency_label') }}</SelectLabel>
                        <SelectItem v-for="currency in currencies" :key="currency.code" :value="currency.code">
                            {{ currency.code }} ({{ currency.symbol }})
                        </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.currency" />
                </div>


                <div>
                    <InputLabel for="city" :value="_t('customerprofilepages', 'city_label')" />
                    <div class="vr-ifield">
                        <Building2 class="vr-ifield-ic" />
                        <TextInput id="city" type="text" class="block w-full" v-model="form.city" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.city" />
                </div>


                <div>
                    <InputLabel for="state" :value="_t('customerprofilepages', 'state_label')" />
                    <div class="vr-ifield">
                        <Map class="vr-ifield-ic" />
                        <TextInput id="state" type="text" class="block w-full" v-model="form.state" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.state" />
                </div>

                <div>
                    <InputLabel for="postal_code" :value="_t('customerprofilepages', 'postal_code_label')" />
                    <div class="vr-ifield">
                        <Hash class="vr-ifield-ic" />
                        <TextInput id="postal_code" type="text" class="block w-full" v-model="form.postal_code" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.postal_code" />
                </div>

                <div class="col-span-2">
                    <InputLabel for="address_line1" :value="_t('customerprofilepages', 'address_line1_label')" />
                    <div class="vr-ifield">
                        <Home class="vr-ifield-ic" />
                        <TextInput id="address_line1" type="text" class="block w-full" v-model="form.address_line1" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.address_line1" />
                </div>


                <h3 class="vr-section col-span-2">{{ _t('customerprofilepages', 'profile_section_title') }}</h3>

                <div class="col-span-2">
                    <p class="mb-[1rem] text-customLightGrayColor font-medium max-[768px]:text-[0.95rem]">{{
                        _t('customerprofilepages', 'who_am_i_prompt') }}
                    </p>
                    <InputLabel for="about" :value="_t('customerprofilepages', 'about_label')" />
                    <div class="vr-ifield vr-ifield--area">
                        <FileText class="vr-ifield-ic" />
                        <TextArea id="about" class="block w-full" v-model="form.about" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.about" />
                </div>



                <div class="col-span-2">
                    <InputLabel for="address_line2" :value="_t('customerprofilepages', 'address_line2_label')" />
                    <div class="vr-ifield">
                        <Home class="vr-ifield-ic" />
                        <TextInput id="address_line2" type="text" class="block w-full" v-model="form.address_line2" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.address_line2" />
                </div>


                <div class="col-span-2">
                    <span class="vr-section" style="display:inline-block;margin-bottom:10px">{{
                        _t('customerprofilepages', 'tax_identification_number_title') }}</span>
                    <InputLabel for="tax_identification"
                        :value="_t('customerprofilepages', 'tax_identification_number_label')" />
                    <div class="vr-ifield">
                        <Receipt class="vr-ifield-ic" />
                        <TextInput id="tax_identification" type="text" class="block w-full"
                            v-model="form.tax_identification" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.tax_identification" />
                </div>

                <div class="vr-form-actions col-span-2">
                    <PrimaryButton :disabled="form.processing">{{ _t('customerprofilepages',
                        'update_profile_button') }} </PrimaryButton>
                </div>
            </div>
        </form>
    </section>
    <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
        <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
    </div>
    <Toaster position="bottom-right" :toastOptions="{
        style: { background: 'black', color: 'white', border: '1px solid #333' }
    }" />
</template>

<style scoped>
input,
textarea,
select {
    border-radius: 10px;
    border: 1px solid #e2e8f0 !important;
    padding: 0.7rem 0.85rem;
    background: #f8fafc;
    transition: border-color 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s cubic-bezier(0.22, 1, 0.36, 1), background 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

input:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: #153b4f !important;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(21, 59, 79, 0.12);
}

/* leading-icon fields */
.vr-ifield {
    position: relative;
    margin-top: 0.25rem;
}

.vr-ifield-ic {
    position: absolute;
    left: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    width: 17px;
    height: 17px;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
}

.vr-ifield :deep(input),
.vr-ifield :deep(textarea) {
    padding-left: 2.45rem !important;
}

.vr-ifield--area .vr-ifield-ic {
    top: 0.95rem;
    transform: none;
}

.vr-section {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eef2f6;
}

.profile-id {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 18px;
    background: linear-gradient(135deg, #f0f8fc, #ffffff);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    flex-wrap: wrap;
}

.profile-id-avatar {
    position: relative;
    width: 84px;
    height: 84px;
    flex: 0 0 84px;
}

.profile-id-avatar img {
    width: 84px;
    height: 84px;
    border-radius: 20px;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 6px 16px rgba(21, 59, 79, 0.18);
}

.profile-id-edit {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    background: #153b4f;
    color: #fff;
    border-radius: 999px;
    box-shadow: 0 4px 10px rgba(21, 59, 79, 0.3);
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.profile-id-edit:hover {
    transform: scale(1.08);
}

.profile-id-edit svg {
    width: 15px;
    height: 15px;
}

.profile-id-meta {
    flex: 1;
    min-width: 160px;
}

.profile-id-name {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.profile-id-email {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 3px;
}

.profile-id-title {
    flex: 0 0 auto;
}

@media (max-width: 640px) {
    .profile-id-title {
        width: 100%;
    }
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

:deep(.dp__input) {
    padding: 0.7rem 0.85rem 0.7rem 2.5rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    width: 100%;
}

:deep(.dp__menu) {
    border-radius: 0.5rem;
    padding: 0.5rem;
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

    :deep(.dp__input) {
        font-size: 0.75rem;
    }
}
</style>
