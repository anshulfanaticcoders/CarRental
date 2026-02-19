<script setup>
import { computed } from 'vue';
import Editor from '@tinymce/tinymce-vue';

const props = defineProps({
    fields: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: Object,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
    locales: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const tinyMceApiKey = 'l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1';

const tinyMceInit = {
    height: 300,
    menubar: false,
    skin: 'oxide',
    content_css: 'default',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
    plugins: 'lists link image',
};

const firstLocale = computed(() => props.locales[0] || 'en');

/**
 * Get the current value for a field, respecting translatable vs non-translatable.
 */
function getFieldValue(field) {
    if (field.translatable) {
        return props.modelValue?.[props.locale]?.[field.key] ?? '';
    }
    return props.modelValue?.[field.key] ?? '';
}

/**
 * Set the value for a field, emitting the entire updated modelValue.
 */
function setFieldValue(field, value) {
    const updated = JSON.parse(JSON.stringify(props.modelValue));

    if (field.translatable) {
        if (!updated[props.locale]) {
            updated[props.locale] = {};
        }
        updated[props.locale][field.key] = value;
    } else {
        updated[field.key] = value;
    }

    emit('update:modelValue', updated);
}

/**
 * Repeater helpers
 */
function getRepeaterItems(field) {
    const raw = getFieldValue(field);
    if (Array.isArray(raw)) return raw;
    if (typeof raw === 'string' && raw) {
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }
    return [];
}

function addRepeaterItem(field) {
    const items = [...getRepeaterItems(field)];
    const newItem = {};
    (field.fields || []).forEach((sub) => {
        newItem[sub.key] = '';
    });
    items.push(newItem);
    setFieldValue(field, items);
}

function removeRepeaterItem(field, index) {
    const items = [...getRepeaterItems(field)];
    items.splice(index, 1);
    setFieldValue(field, items);
}

function updateRepeaterSubField(field, itemIndex, subKey, value) {
    const items = JSON.parse(JSON.stringify(getRepeaterItems(field)));
    if (items[itemIndex]) {
        items[itemIndex][subKey] = value;
    }
    setFieldValue(field, items);
}

/**
 * Determine whether a field should be visible for the current locale.
 * Non-translatable fields only show on the first locale tab.
 */
function isFieldVisible(field) {
    if (field.translatable) return true;
    return props.locale === firstLocale.value;
}

/**
 * Returns the appropriate input type for simple field types.
 */
function inputType(fieldType) {
    const map = {
        text: 'text',
        email: 'email',
        url: 'url',
        date: 'date',
        image: 'url',
    };
    return map[fieldType] || 'text';
}

/**
 * Check if a field type renders as a simple <input>.
 */
function isSimpleInput(fieldType) {
    return ['text', 'email', 'url', 'date', 'image'].includes(fieldType);
}
</script>

<template>
    <div class="space-y-6">
        <template v-for="field in fields" :key="field.key">
            <div v-if="isFieldVisible(field)">
                <!-- Non-translatable note -->
                <p
                    v-if="!field.translatable"
                    class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded px-2 py-1 mb-2 inline-block"
                >
                    Same for all languages
                </p>

                <!-- Simple input fields: text, email, url, date, image -->
                <div v-if="isSimpleInput(field.type)">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ field.label }}
                        <span v-if="field.type === 'image'" class="text-xs text-gray-500 ml-1">(Image URL)</span>
                    </label>
                    <input
                        :type="inputType(field.type)"
                        :value="getFieldValue(field)"
                        @input="setFieldValue(field, $event.target.value)"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                        :placeholder="field.label"
                    />
                </div>

                <!-- Textarea -->
                <div v-else-if="field.type === 'textarea'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ field.label }}
                    </label>
                    <textarea
                        :value="getFieldValue(field)"
                        @input="setFieldValue(field, $event.target.value)"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                        :placeholder="field.label"
                    ></textarea>
                </div>

                <!-- Richtext (TinyMCE) -->
                <div v-else-if="field.type === 'richtext'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ field.label }}
                    </label>
                    <Editor
                        :model-value="getFieldValue(field)"
                        @update:model-value="setFieldValue(field, $event)"
                        :api-key="tinyMceApiKey"
                        :init="tinyMceInit"
                        class="border border-gray-300 rounded-lg"
                    />
                </div>

                <!-- Repeater -->
                <div v-else-if="field.type === 'repeater'">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        {{ field.label }}
                    </label>

                    <div class="space-y-4">
                        <div
                            v-for="(item, itemIndex) in getRepeaterItems(field)"
                            :key="itemIndex"
                            class="relative p-4 bg-gray-50 border border-gray-200 rounded-lg"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-600">
                                    Item {{ itemIndex + 1 }}
                                </span>
                                <button
                                    type="button"
                                    @click="removeRepeaterItem(field, itemIndex)"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded hover:bg-red-100 transition-colors duration-200"
                                >
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="subField in (field.fields || [])" :key="subField.key">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        {{ subField.label }}
                                        <span v-if="subField.type === 'image'" class="text-gray-400 ml-1">(Image URL)</span>
                                    </label>
                                    <input
                                        :type="inputType(subField.type)"
                                        :value="item[subField.key] || ''"
                                        @input="updateRepeaterSubField(field, itemIndex, subField.key, $event.target.value)"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-white"
                                        :placeholder="subField.label"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addRepeaterItem(field)"
                        class="mt-3 inline-flex items-center px-4 py-2 text-sm font-medium text-customPrimaryColor bg-white border border-customPrimaryColor rounded-lg hover:bg-gray-50 transition-colors duration-200"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Item
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
