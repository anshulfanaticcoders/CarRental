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

const collapsed = reactive({});

function toggleCollapse(index) {
    collapsed[index] = !collapsed[index];
}

function isCollapsed(index) {
    return !!collapsed[index];
}

function getSectionConfig(section) {
    return props.templateSections.find((ts) => ts.type === section.type);
}

function getSectionLabel(section) {
    const match = getSectionConfig(section);
    return match ? match.label : section.type;
}

function getSectionFields(section) {
    const match = getSectionConfig(section);
    return match?.fields || [];
}

function hasSectionFields(section) {
    return getSectionFields(section).length > 0;
}

function emitUpdate(updatedSections) {
    emit('update:sections', updatedSections);
}

function deepClone() {
    return JSON.parse(JSON.stringify(props.sections));
}

function ensureTranslation(section, locale) {
    if (!section.translations) section.translations = {};
    if (!section.translations[locale]) {
        section.translations[locale] = { title: '', content: '', settings: null };
    }
    if (!section.translations[locale].settings) {
        section.translations[locale].settings = {};
    }
}

function toggleVisibility(index) {
    const updated = deepClone();
    updated[index].is_visible = !updated[index].is_visible;
    emitUpdate(updated);
}

function moveUp(index) {
    if (index <= 0) return;
    const updated = deepClone();
    [updated[index], updated[index - 1]] = [updated[index - 1], updated[index]];
    updated.forEach((s, i) => { s.sort_order = i; });
    const tc = collapsed[index];
    collapsed[index] = collapsed[index - 1];
    collapsed[index - 1] = tc;
    emitUpdate(updated);
}

function moveDown(index) {
    if (index >= props.sections.length - 1) return;
    const updated = deepClone();
    [updated[index], updated[index + 1]] = [updated[index + 1], updated[index]];
    updated.forEach((s, i) => { s.sort_order = i; });
    const tc = collapsed[index];
    collapsed[index] = collapsed[index + 1];
    collapsed[index + 1] = tc;
    emitUpdate(updated);
}

// --- Title & Content (for simple sections like hero, content) ---
function updateSectionTitle(index, value) {
    const updated = deepClone();
    ensureTranslation(updated[index], props.locale);
    updated[index].translations[props.locale].title = value;
    emitUpdate(updated);
}

function updateSectionContent(index, value) {
    const updated = deepClone();
    ensureTranslation(updated[index], props.locale);
    updated[index].translations[props.locale].content = value;
    emitUpdate(updated);
}

function getSectionTitle(section) {
    return section.translations?.[props.locale]?.title ?? '';
}

function getSectionContent(section) {
    return section.translations?.[props.locale]?.content ?? '';
}

// --- Settings fields (for structured sections like features, stats, cta) ---
function getSettingsValue(section, key) {
    return section.translations?.[props.locale]?.settings?.[key] ?? '';
}

function updateSettingsValue(index, key, value) {
    const updated = deepClone();
    ensureTranslation(updated[index], props.locale);
    updated[index].translations[props.locale].settings[key] = value;
    emitUpdate(updated);
}

// --- Repeater in settings ---
function getRepeaterItems(section, key) {
    const val = section.translations?.[props.locale]?.settings?.[key];
    if (Array.isArray(val)) return val;
    return [];
}

function addRepeaterItem(index, fieldDef) {
    const updated = deepClone();
    ensureTranslation(updated[index], props.locale);
    const settings = updated[index].translations[props.locale].settings;
    if (!settings[fieldDef.key]) settings[fieldDef.key] = [];
    const newItem = {};
    (fieldDef.fields || []).forEach(f => { newItem[f.key] = ''; });
    settings[fieldDef.key].push(newItem);
    emitUpdate(updated);
}

function removeRepeaterItem(index, fieldKey, itemIndex) {
    const updated = deepClone();
    ensureTranslation(updated[index], props.locale);
    updated[index].translations[props.locale].settings[fieldKey].splice(itemIndex, 1);
    emitUpdate(updated);
}

function updateRepeaterField(sectionIndex, fieldKey, itemIndex, subKey, value) {
    const updated = deepClone();
    ensureTranslation(updated[sectionIndex], props.locale);
    updated[sectionIndex].translations[props.locale].settings[fieldKey][itemIndex][subKey] = value;
    emitUpdate(updated);
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(section, index) in sections"
            :key="`section-${index}-${section.type}`"
            class="border border-gray-200 rounded-lg overflow-hidden"
        >
            <!-- Section Header -->
            <div
                class="flex items-center justify-between px-4 py-3 bg-gray-50 cursor-pointer select-none"
                @click="toggleCollapse(index)"
            >
                <div class="flex items-center space-x-3">
                    <svg
                        class="w-4 h-4 text-gray-500 transition-transform duration-200"
                        :class="{ 'rotate-90': !isCollapsed(index) }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-800">{{ getSectionLabel(section) }}</span>
                    <span class="text-xs text-gray-400 font-mono">({{ section.type }})</span>
                    <span v-if="hasSectionFields(section)" class="text-xs text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">
                        Structured
                    </span>
                    <span v-if="!section.is_visible" class="text-xs text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded">
                        Hidden
                    </span>
                </div>

                <div class="flex items-center space-x-2" @click.stop>
                    <button type="button" @click="toggleVisibility(index)" :title="section.is_visible ? 'Hide section' : 'Show section'" class="p-1.5 rounded hover:bg-gray-200 transition-colors">
                        <svg v-if="section.is_visible" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                    <button type="button" @click="moveUp(index)" :disabled="index === 0" title="Move up" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                    </button>
                    <button type="button" @click="moveDown(index)" :disabled="index === sections.length - 1" title="Move down" class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                </div>
            </div>

            <!-- Section Body -->
            <div v-if="!isCollapsed(index)" class="p-4 space-y-4 border-t border-gray-200">

                <!-- Title field (always shown) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section Title ({{ locale.toUpperCase() }})
                    </label>
                    <input
                        type="text"
                        :value="getSectionTitle(section)"
                        @input="updateSectionTitle(index, $event.target.value)"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                        :placeholder="`Title for ${getSectionLabel(section)}`"
                    />
                </div>

                <!-- Content (TinyMCE) — only for sections WITHOUT custom fields -->
                <div v-if="!hasSectionFields(section)">
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

                <!-- Structured Fields — for sections WITH custom fields -->
                <div v-if="hasSectionFields(section)" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                        <p class="text-xs text-blue-700 font-medium">This section uses structured fields instead of free-form content.</p>
                    </div>

                    <div v-for="fieldDef in getSectionFields(section)" :key="fieldDef.key" class="space-y-2">

                        <!-- Repeater field -->
                        <div v-if="fieldDef.type === 'repeater'" class="border border-gray-200 rounded-lg p-4 bg-white">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-semibold text-gray-700">{{ fieldDef.label }} ({{ locale.toUpperCase() }})</label>
                                <button
                                    type="button"
                                    @click="addRepeaterItem(index, fieldDef)"
                                    class="text-xs bg-customPrimaryColor text-white px-3 py-1.5 rounded-md hover:bg-opacity-90 transition-colors"
                                >
                                    + Add {{ fieldDef.label.replace(/s$/, '') }}
                                </button>
                            </div>

                            <div v-if="getRepeaterItems(section, fieldDef.key).length === 0" class="text-sm text-gray-400 italic py-3 text-center border border-dashed border-gray-300 rounded-lg">
                                No items yet. Click "Add" to create one.
                            </div>

                            <div
                                v-for="(item, itemIdx) in getRepeaterItems(section, fieldDef.key)"
                                :key="itemIdx"
                                class="border border-gray-200 rounded-lg p-3 mb-3 bg-gray-50 relative"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-500">Item {{ itemIdx + 1 }}</span>
                                    <button
                                        type="button"
                                        @click="removeRepeaterItem(index, fieldDef.key, itemIdx)"
                                        class="text-xs text-red-500 hover:text-red-700 font-medium"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div v-for="subField in fieldDef.fields" :key="subField.key">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ subField.label }}</label>
                                        <textarea
                                            v-if="subField.type === 'textarea'"
                                            :value="item[subField.key] || ''"
                                            @input="updateRepeaterField(index, fieldDef.key, itemIdx, subField.key, $event.target.value)"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-customPrimaryColor focus:border-customPrimaryColor"
                                            rows="3"
                                        ></textarea>
                                        <input
                                            v-else
                                            :type="subField.type === 'image' ? 'url' : subField.type || 'text'"
                                            :value="item[subField.key] || ''"
                                            @input="updateRepeaterField(index, fieldDef.key, itemIdx, subField.key, $event.target.value)"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-customPrimaryColor focus:border-customPrimaryColor"
                                            :placeholder="subField.label"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Simple text/url/date/image fields -->
                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ fieldDef.label }} ({{ locale.toUpperCase() }})</label>
                            <textarea
                                v-if="fieldDef.type === 'textarea'"
                                :value="getSettingsValue(section, fieldDef.key)"
                                @input="updateSettingsValue(index, fieldDef.key, $event.target.value)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                                rows="3"
                                :placeholder="fieldDef.label"
                            ></textarea>
                            <input
                                v-else
                                :type="fieldDef.type === 'image' ? 'url' : fieldDef.type || 'text'"
                                :value="getSettingsValue(section, fieldDef.key)"
                                @input="updateSettingsValue(index, fieldDef.key, $event.target.value)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor bg-gray-50 focus:bg-white"
                                :placeholder="fieldDef.label"
                            />
                            <p v-if="fieldDef.type === 'image'" class="text-xs text-gray-400 mt-1">Paste an image URL</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="sections.length === 0" class="text-sm text-gray-500 italic py-4">
            No sections defined for this template. Select a template with sections to manage them here.
        </p>
    </div>
</template>
