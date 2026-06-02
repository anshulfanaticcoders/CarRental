<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <p class="text-sm text-gray-600 mb-5 max-[768px]:text-[0.875rem]">
            {{ _t('customerprofilepages', 'update_password_subheader') }}
        </p>

        <form @submit.prevent="updatePassword" class="vr-form-grid">
            <div class="col-span-2">
                <InputLabel for="current_password" :value="_t('customerprofilepages', 'current_password_label')" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" :value="_t('customerprofilepages', 'new_password_label')" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" :value="_t('customerprofilepages', 'confirm_password_label')" />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="vr-form-actions col-span-2" style="justify-content: flex-start">
                <PrimaryButton :disabled="form.processing" class="">{{ _t('customerprofilepages', 'update_password_button') }}</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">{{ _t('customerprofilepages', 'saved_message') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>


<style scoped>
input,
textarea,
select {
    border-radius: 10px;
    border: 1px solid #e2e8f0 !important;
    padding: 0.7rem 0.85rem;
    transition: border-color 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

input:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: #153b4f !important;
    box-shadow: 0 0 0 3px rgba(21, 59, 79, 0.12);
}

/* responsive grid via flexbox wrap — no media/container queries needed */
.vr-form-grid {
    display: flex !important;
    flex-wrap: wrap;
    gap: 16px;
}

.vr-form-grid > * {
    flex: 1 1 calc(50% - 8px);
    min-width: 240px;
}

.vr-form-grid > .col-span-2,
.vr-form-grid > .full {
    flex-basis: 100%;
    min-width: 100%;
}
</style>
