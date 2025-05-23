<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <section class="space-y-6 mt-[3rem]">
        <header>
            <h2 class="text-[1.5rem] font-medium text-gray-900">{{ _t('customerprofilepages', 'delete_account_header') }}</h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ _t('customerprofilepages', 'delete_account_subheader') }}
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">{{ _t('customerprofilepages', 'delete_account_button') }}</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ _t('customerprofilepages', 'confirm_delete_modal_header') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ _t('customerprofilepages', 'confirm_delete_modal_subheader') }}
                </p>

                <div class="mt-6">
                    <InputLabel for="password" :value="_t('customerprofilepages', 'password_label')" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="_t('customerprofilepages', 'password_placeholder')"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> {{ _t('customerprofilepages', 'cancel_button') }} </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ _t('customerprofilepages', 'delete_account_button') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
