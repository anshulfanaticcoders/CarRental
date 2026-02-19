<script setup>
import { reactive } from 'vue';
import Editor from '@tinymce/tinymce-vue';

const props = defineProps({
    sections: {
        type: Array,
        required: true,
    },
    templateSections: {
        type: Array,
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

const emit = defineEmits(['update:sections']);

const tinyMceApiKey = 'l37l3e84opgzd4x6rdhlugh30o2l5mh5f5vvq3mieu4yn1j1';

const tinyMceInit = {
    height: 300,
    menubar: false,
    skin: 'oxide',
    content_css: 'default',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
    plugins: 'lists link image',
};

/** Track which sections are collapsed. Keyed by index. */
const collapsed = reactive({});

function toggleCollapse(index) {
    collapsed[index] = !collapsed[index];
}

function isCollapsed(index) {
    return !!collapsed[index];
}

/**
 * Get the label for a section from templateSections config.
 */
function getSectionLabel(section) {
    const match = props.templateSections.find((ts) => ts.type === section.type);
    return match ? match.label : section.type;
}

/**
 * Clone and emit the updated sections array.
 */
function emitUpdate(updatedSections) {
    emit('update:sections', updatedSections);
}

/**
 * Toggle visibility of a section.
 */
function toggleVisibility(index) {
    const updated = JSON.parse(JSON.stringify(props.sections));
    updated[index].is_visible = !updated[index].is_visible;
    emitUpdate(updated);
}

/**
 * Move section up in order.
 */
function moveUp(index) {
    if (index <= 0) return;
    const updated = JSON.parse(JSON.stringify(props.sections));
    const temp = updated[index];
    updated[index] = updated[index - 1];
    updated[index - 1] = temp;
    // Re-assign sort_order
    updated.forEach((s, i) => {
        s.sort_order = i;
    });
    // Swap collapsed state
    const tempCollapsed = collapsed[index];
    collapsed[index] = collapsed[index - 1];
    collapsed[index - 1] = tempCollapsed;
    emitUpdate(updated);
}

/**
 * Move section down in order.
 */
function moveDown(index) {
    if (index >= props.sections.length - 1) return;
    const updated = JSON.parse(JSON.stringify(props.sections));
    const temp = updated[index];
    updated[index] = updated[index + 1];
    updated[index + 1] = temp;
    // Re-assign sort_order
    updated.forEach((s, i) => {
        s.sort_order = i;
    });
    // Swap collapsed state
    const tempCollapsed = collapsed[index];
    collapsed[index] = collapsed[index + 1];
    collapsed[index + 1] = tempCollapsed;
    emitUpdate(updated);
}

/**
 * Update the title for a section in the active locale.
 */
function updateSectionTitle(index, value) {
    const updated = JSON.parse(JSON.stringify(props.sections));
    if (!updated[index].translations) {
        updated[index].translations = {};
    }
    if (!updated[index].translations[props.locale]) {
        updated[index].translations[props.locale] = { title: '', content: '', settings: null };
    }
    updated[index].translations[props.locale].title = value;
    emitUpdate(updated);
}

/**
 * Update the content for a section in the active locale.
 */
function updateSectionContent(index, value) {
    const updated = JSON.parse(JSON.stringify(props.sections));
    if (!updated[index].translations) {
        updated[index].translations = {};
    }
    if (!updated[index].translations[props.locale]) {
        updated[index].translations[props.locale] = { title: '', content: '', settings: null };
    }
    updated[index].translations[props.locale].content = value;
    emitUpdate(updated);
}

/**
 * Get section title for current locale.
 */
function getSectionTitle(section) {
    return section.translations?.[props.locale]?.title ?? '';
}

/**
 * Get section content for current locale.
 */
function getSectionContent(section) {
    return section.translations?.[props.locale]?.content ?? '';
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(section, index) in sections"
            :key="`section-${index}-${section.type}`"
            class="border border-gray-200 rounded-lg overflow-hidden"
        >
            <!-- Section Header (always visible) -->
            <div
                class="flex items-center justify-between px-4 py-3 bg-gray-50 cursor-pointer select-none"
                @click="toggleCollapse(index)"
            >
                <div class="flex items-center space-x-3">
                    <!-- Collapse indicator -->
                    <svg
                        class="w-4 h-4 text-gray-500 transition-transform duration-200"
                        :class="{ 'rotate-90': !isCollapsed(index) }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>

                    <span class="text-sm font-semibold text-gray-800">
                        {{ getSectionLabel(section) }}
                    </span>

                    <span class="text-xs text-gray-400 font-mono">
                        ({{ section.type }})
                    </span>

                    <!-- Visibility badge -->
                    <span
                        v-if="!section.is_visible"
                        class="text-xs text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded"
                    >
                        Hidden
                    </span>
                </div>

                <div class="flex items-center space-x-2" @click.stop>
                    <!-- Visibility toggle -->
                    <button
                        type="button"
                        @click="toggleVisibility(index)"
                        :title="section.is_visible ? 'Hide section' : 'Show section'"
                        class="p-1.5 rounded hover:bg-gray-200 transition-colors duration-200"
                    >
                        <!-- Eye open icon -->
                        <svg
                            v-if="section.is_visible"
                            class="w-4 h-4 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <!-- Eye closed icon -->
                        <svg
                            v-else
                            class="w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>

                    <!-- Move Up -->
                    <button
                        type="button"
                        @click="moveUp(index)"
                        :disabled="index === 0"
                        title="Move up"
                        class="p-1.5 rounded hover:bg-gray-200 transition-colors duration-200 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>

                    <!-- Move Down -->
                    <button
                        type="button"
                        @click="moveDown(index)"
                        :disabled="index === sections.length - 1"
                        title="Move down"
                        class="p-1.5 rounded hover:bg-gray-200 transition-colors duration-200 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Section Body (collapsible) -->
            <div v-if="!isCollapsed(index)" class="p-4 space-y-4 border-t border-gray-200">
                <!-- Section Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section Title ({{ locale.toUpperCase() }})
                    </label>
                    <input
                        type="text"
                        :value="getSectionTitle(section)"
                        @input="updateSectionTitle(index, $event.target.value)"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                        :placeholder="`Title for ${getSectionLabel(section)}`"
                    />
                </div>

                <!-- Section Content (TinyMCE) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section Content ({{ locale.toUpperCase() }})
                    </label>
                    <Editor
                        :model-value="getSectionContent(section)"
                        @update:model-value="updateSectionContent(index, $event)"
                        :api-key="tinyMceApiKey"
                        :init="tinyMceInit"
                        class="border border-gray-300 rounded-lg"
                    />
                </div>
            </div>
        </div>

        <p v-if="sections.length === 0" class="text-sm text-gray-500 italic py-4">
            No sections defined for this template. Select a template with sections to manage them here.
        </p>
    </div>
</template>
