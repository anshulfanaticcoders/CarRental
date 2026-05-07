<template>
    <MyProfileLayout>
        <Head title="Bulk Vehicle Import" />

        <div class="space-y-6 py-4 sm:py-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Bulk Listing Rebuild</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">Import vehicles through one safe CSV flow</h1>
                        <p class="mt-3 text-sm leading-6 text-gray-600">
                            This is the new bulk vehicle import entrypoint. It uses one canonical CSV template, structured column rules,
                            and a preview stage before any vehicle can be created.
                        </p>
                    </div>

                    <div class="grid min-w-[220px] grid-cols-2 gap-3 rounded-2xl bg-[#153B4F0D] p-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-widest text-gray-400">Required</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ requiredColumns.length }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-widest text-gray-400">Optional</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ optionalColumns.length }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ flash.error }}
            </div>
            <div v-if="flash.info" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ flash.info }}
            </div>

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Step 1</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900">Download the canonical template</h2>
                                <p class="mt-2 text-sm text-gray-500">
                                    Use this exact header structure. The new importer will reject stale or malformed columns before import.
                                </p>
                            </div>

                            <a
                                :href="route('vendor.vehicles.bulk-import.template', { locale: page.props.locale })"
                                class="inline-flex items-center justify-center rounded-xl bg-[#153B4F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0f2b39]"
                            >
                                Download Template
                            </a>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-xl border border-gray-100">
                            <div class="grid grid-cols-[minmax(180px,1.2fr)_110px_minmax(160px,1fr)] bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-gray-500">
                                <div>Column</div>
                                <div>Required</div>
                                <div>Example</div>
                            </div>
                            <div
                                v-for="column in templateColumns"
                                :key="column.key"
                                class="grid grid-cols-[minmax(180px,1.2fr)_110px_minmax(160px,1fr)] gap-3 border-t border-gray-100 px-4 py-3 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">{{ column.label }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ column.description }}</p>
                                </div>
                                <div>
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="column.required ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ column.required ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div class="text-gray-600">{{ column.example || '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Step 2</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900">Upload for structure preview</h2>
                                <p class="mt-2 text-sm text-gray-500">
                                    This step only parses your CSV and checks the structure. It does not create vehicles yet.
                                </p>
                                <p class="mt-2 text-sm text-[#153B4F]">
                                    After preview, the page will jump to the result section below so you can immediately review errors and detected mappings.
                                </p>
                            </div>
                        </div>

                        <form class="mt-6 space-y-4" @submit.prevent="submitPreview">
                            <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 p-6">
                                <label class="block text-sm font-medium text-gray-700" for="csv_file">CSV file</label>
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt"
                                    class="mt-3 block w-full rounded-xl border border-gray-200 bg-white px-3 py-3 text-sm text-gray-600 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#153B4F] hover:file:bg-blue-100"
                                    @change="handleFileChange"
                                />
                                <p class="mt-3 text-xs text-gray-500">Max upload size: 4MB. We only inspect headers and preview rows at this stage.</p>
                                <p v-if="form.errors.csv_file" class="mt-2 text-sm text-red-600">{{ form.errors.csv_file }}</p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-[#153B4F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0f2b39] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="form.processing || !form.csv_file"
                                >
                                    {{ form.processing ? 'Checking CSV...' : 'Preview CSV Structure' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Image Library</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900">Upload reusable bulk images</h2>
                                <p class="mt-2 text-sm text-gray-500">
                                    Reference uploaded images in your CSV with tokens like <span class="font-semibold">bulkimg_123</span>,
                                    or use direct public image URLs if you prefer.
                                </p>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-gray-400">Library Size</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">{{ bulkImages.length }}</p>
                            </div>
                        </div>

                        <form class="mt-6 space-y-4" @submit.prevent="submitImageUpload">
                            <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 p-6">
                                <label class="block text-sm font-medium text-gray-700" for="bulk_vehicle_images">Bulk images</label>
                                <input
                                    id="bulk_vehicle_images"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    class="mt-3 block w-full rounded-xl border border-gray-200 bg-white px-3 py-3 text-sm text-gray-600 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#153B4F] hover:file:bg-blue-100"
                                    @change="handleImageSelection"
                                />
                                <p class="mt-3 text-xs text-gray-500">{{ VEHICLE_IMAGE_UPLOAD_HINT }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ VEHICLE_IMAGE_UPLOAD_DETAIL }}</p>
                                <p v-if="bulkImageError" class="mt-2 text-sm text-red-600">{{ bulkImageError }}</p>
                                <p v-if="imageUploadForm.errors.images" class="mt-2 text-sm text-red-600">{{ imageUploadForm.errors.images }}</p>
                                <p
                                    v-for="(message, key) in imageFieldErrors"
                                    :key="key"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ message }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm text-gray-500">
                                    {{ selectedImageCount ? `${selectedImageCount} image(s) selected` : 'No images selected yet' }}
                                </p>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-[#153B4F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0f2b39] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="imageUploadForm.processing || !selectedImageCount"
                                >
                                    {{ imageUploadForm.processing ? 'Uploading images...' : 'Upload Images' }}
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 space-y-3">
                            <div
                                v-if="!bulkImages.length"
                                class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-4 text-sm text-gray-500"
                            >
                                No bulk images uploaded yet.
                            </div>

                            <div
                                v-else
                                class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p class="text-gray-600">
                                    Showing <span class="font-semibold text-gray-900">{{ visibleBulkImages.length }}</span>
                                    of <span class="font-semibold text-gray-900">{{ bulkImages.length }}</span> uploaded images
                                </p>
                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <button
                                        v-if="selectedBulkImageIds.length"
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isBulkDeletingImages"
                                        @click="deleteSelectedImages"
                                    >
                                        {{ isBulkDeletingImages ? 'Deleting selected...' : `Delete Selected (${selectedBulkImageIds.length})` }}
                                    </button>
                                    <button
                                        v-if="hasMoreBulkImages"
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
                                        @click="showAllImagesDialog = true"
                                    >
                                        Show all images
                                    </button>
                                </div>
                            </div>

                            <div
                                v-for="image in visibleBulkImages"
                                :key="image.id"
                                class="flex flex-col gap-4 rounded-2xl border border-gray-100 p-4 sm:flex-row sm:items-start"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        :id="`bulk-image-${image.id}`"
                                        :checked="selectedBulkImageIds.includes(image.id)"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-[#153B4F] focus:ring-[#153B4F]"
                                        :disabled="isDeletingImage(image.id) || isBulkDeletingImages"
                                        @change="toggleImageSelection(image.id)"
                                    />
                                </div>
                                <img
                                    :src="image.thumbnail_url || image.url"
                                    :alt="image.original_name"
                                    class="h-24 w-24 rounded-xl object-cover"
                                />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ image.original_name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Token</p>
                                    <p class="mt-1 rounded-lg bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">
                                        {{ image.token }}
                                    </p>
                                    <p class="mt-2 text-xs text-gray-500">Uploaded {{ image.created_at }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2 sm:w-[220px] sm:justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="copyToClipboard(image.token)"
                                    >
                                        {{ copiedValue === image.token ? 'Copied Token' : 'Copy Token' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="copyToClipboard(image.url)"
                                    >
                                        {{ copiedValue === image.url ? 'Copied URL' : 'Copy URL' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isDeletingImage(image.id) || isBulkDeletingImages"
                                        @click="deleteImage(image.id)"
                                    >
                                        {{ isDeletingImage(image.id) ? 'Deleting...' : 'Delete' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">SIPP-critical fields</p>
                        <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                            <p class="text-sm font-semibold text-gray-900">Fill these fields carefully in every row</p>
                            <p class="mt-2 text-xs leading-5 text-gray-600">
                                SIPP is generated from your category and the core vehicle attributes below. Do not invent new values for these fields.
                                Use the exact accepted values shown on this page.
                            </p>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div
                                    v-for="field in sippGuidanceFields"
                                    :key="`sipp-${field.key}`"
                                    class="rounded-xl border border-blue-100 bg-white px-4 py-3"
                                >
                                    <p class="text-sm font-semibold text-gray-900">{{ field.label }}</p>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">{{ field.description }}</p>
                                    <div v-if="field.values?.length" class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="value in field.values"
                                            :key="`${field.key}-${value}`"
                                            class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                                        >
                                            {{ value }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Accepted Values</p>
                        <div class="mt-4 space-y-4">
                            <div class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">Available Categories</p>
                                <p class="mt-2 text-xs text-gray-500">
                                    Use a real vehicle category name, slug, or id from the current system. Values such as "Luxury" only work if that category actually exists.
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="category in availableCategories"
                                        :key="`category-${category.id}`"
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                                    >
                                        {{ category.name }} <span class="text-gray-400">({{ category.slug || category.id }})</span>
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">Your Saved Vendor Locations</p>
                                <p class="mt-2 text-xs text-gray-500">
                                    Each row must match one of your saved vendor locations below. You can use the location id, code, name, or IATA code in the CSV.
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="location in vendorLocations"
                                        :key="`location-${location.id}`"
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="location.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                    >
                                        #{{ location.id }}
                                        <span v-if="location.code" class="text-gray-500"> · {{ location.code }}</span>
                                        <span class="text-gray-400"> · {{ location.name }}</span>
                                        <span v-if="location.iata_code" class="text-gray-500"> · {{ location.iata_code }}</span>
                                    </span>
                                </div>
                            </div>

                            <div v-for="(values, field) in enumMap" :key="field" class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">{{ formatFieldLabel(field) }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="value in values"
                                        :key="`${field}-${value}`"
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                                    >
                                        {{ value }}
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">Protection Plan Columns</p>
                                <p class="mt-2 text-xs leading-5 text-gray-500">
                                    Use the dedicated Essential, Premium, and Premium Plus columns. Plan prices must be at least the daily price.
                                    Plan features are pipe-separated, for example:
                                    <span class="font-mono text-gray-700">Roadside assistance|Theft protection</span>
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">Custom Addons Format</p>
                                <p class="mt-2 text-xs leading-5 text-gray-500">
                                    Use one cell for all custom addons. Separate addons with semicolons, and format each addon as:
                                    <span class="font-mono text-gray-700">name~price~quantity~type~description</span>
                                </p>
                                <p class="mt-2 text-xs leading-5 text-gray-500">
                                    Example:
                                    <span class="font-mono text-gray-700">Baby Seat~12.50~1~custom~Child seat;GPS~8.00~1~custom~Navigation unit</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Accepted Header Aliases</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="(values, field) in aliases" :key="`alias-${field}`" class="rounded-xl border border-gray-100 p-4">
                                <p class="text-sm font-semibold text-gray-900">{{ field }}</p>
                                <p class="mt-2 text-xs leading-5 text-gray-500">{{ values.join(', ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="preview"
                ref="previewSectionRef"
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                tabindex="-1"
            >
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Preview Result</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900">Detected CSV structure</h2>
                        <p class="mt-2 text-sm text-gray-500">
                            This is only the structural preview stage. Full row mapping, validation, and import confirmation come next.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-gray-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-widest text-gray-400">Headers</p>
                            <p class="mt-1 text-xl font-semibold text-gray-900">{{ preview.headers?.length || 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-widest text-gray-400">Rows</p>
                            <p class="mt-1 text-xl font-semibold text-gray-900">{{ preview.total_rows || 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-widest text-emerald-600">Valid</p>
                            <p class="mt-1 text-xl font-semibold text-emerald-800">{{ preview.valid_rows || 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-red-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-widest text-red-500">Invalid</p>
                            <p class="mt-1 text-xl font-semibold text-red-700">{{ preview.invalid_rows || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="draftMeta?.original_name"
                    class="mt-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900"
                >
                    Draft file: <span class="font-semibold">{{ draftMeta.original_name }}</span>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 p-4">
                        <p class="text-sm font-semibold text-gray-900">Missing required columns</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-if="!preview.missing_required_columns?.length"
                                class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            >
                                None
                            </span>
                            <span
                                v-for="column in preview.missing_required_columns || []"
                                :key="`missing-${column}`"
                                class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700"
                            >
                                {{ column }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 p-4">
                        <p class="text-sm font-semibold text-gray-900">Unknown uploaded columns</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-if="!preview.unknown_columns?.length"
                                class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            >
                                None
                            </span>
                            <span
                                v-for="column in preview.unknown_columns || []"
                                :key="`unknown-${column}`"
                                class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            >
                                {{ column }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-gray-100 p-4">
                    <p class="text-sm font-semibold text-gray-900">Detected column mapping</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="mapping in preview.detected_mappings || []"
                            :key="`mapping-${mapping.key}`"
                            class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-gray-900">{{ mapping.label }}</p>
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="mapping.required ? 'bg-red-50 text-red-700' : 'bg-gray-200 text-gray-600'"
                                >
                                    {{ mapping.required ? 'Required' : 'Optional' }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Matched CSV header</p>
                            <p class="mt-1 break-all text-sm text-gray-700">{{ mapping.matched_header }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="preview.preview_rows?.length" class="mt-6 overflow-x-auto rounded-xl border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500">
                                    Row
                                </th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500">
                                    Validation
                                </th>
                                <th
                                    v-for="header in preview.headers"
                                    :key="`head-${header}`"
                                    class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500"
                                >
                                    {{ header }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="row in preview.preview_rows" :key="`row-${row.row_number}`">
                                <td class="whitespace-nowrap px-4 py-3 align-top font-semibold text-gray-900">
                                    {{ row.row_number }}
                                </td>
                                <td class="min-w-[240px] px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="row.valid ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                    >
                                        {{ row.valid ? 'Valid preview row' : 'Needs fixes' }}
                                    </span>
                                    <ul v-if="row.issues?.length" class="mt-3 space-y-2 text-xs text-red-700">
                                        <li v-for="issue in row.issues" :key="`${row.row_number}-${issue.field}-${issue.message}`">
                                            <span class="font-semibold">{{ formatFieldLabel(issue.field) }}:</span> {{ issue.message }}
                                        </li>
                                    </ul>
                                </td>
                                <td
                                    v-for="header in preview.headers"
                                    :key="`row-${row.row_number}-${header}`"
                                    class="max-w-[220px] truncate px-4 py-3 text-gray-700"
                                >
                                    {{ row.raw?.[header] || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-gray-100 bg-gray-50 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Ready for import</p>
                        <p class="mt-1 text-sm text-gray-500">
                            Import stays disabled until the CSV has no missing required columns and no invalid rows.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-gray-300"
                        :class="canImport ? 'bg-[#153B4F] hover:bg-[#0f2b39]' : 'bg-gray-300'"
                        :disabled="importForm.processing || !canImport"
                        @click="submitImport"
                    >
                        {{ importForm.processing ? 'Importing vehicles...' : 'Import Valid Vehicles' }}
                    </button>
                </div>
            </section>

            <Dialog v-model:open="showAllImagesDialog">
                <DialogContent class="max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>All bulk images</DialogTitle>
                    </DialogHeader>

                    <div class="mt-2 max-h-[70vh] overflow-y-auto pr-1">
                        <div class="mb-4 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            Total uploaded images: <span class="font-semibold text-gray-900">{{ bulkImages.length }}</span>
                        </div>

                        <div v-if="!bulkImages.length" class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-4 text-sm text-gray-500">
                            No bulk images uploaded yet.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="image in bulkImages"
                                :key="`dialog-image-${image.id}`"
                                class="flex flex-col gap-4 rounded-2xl border border-gray-100 p-4 sm:flex-row sm:items-start"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        :id="`dialog-bulk-image-${image.id}`"
                                        :checked="selectedBulkImageIds.includes(image.id)"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-[#153B4F] focus:ring-[#153B4F]"
                                        :disabled="isDeletingImage(image.id) || isBulkDeletingImages"
                                        @change="toggleImageSelection(image.id)"
                                    />
                                </div>
                                <img
                                    :src="image.thumbnail_url || image.url"
                                    :alt="image.original_name"
                                    class="h-24 w-24 rounded-xl object-cover"
                                />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ image.original_name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Token</p>
                                    <p class="mt-1 rounded-lg bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">
                                        {{ image.token }}
                                    </p>
                                    <p class="mt-2 text-xs text-gray-500">Uploaded {{ image.created_at }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2 sm:w-[220px] sm:justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="copyToClipboard(image.token)"
                                    >
                                        {{ copiedValue === image.token ? 'Copied Token' : 'Copy Token' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        @click="copyToClipboard(image.url)"
                                    >
                                        {{ copiedValue === image.url ? 'Copied URL' : 'Copy URL' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isDeletingImage(image.id) || isBulkDeletingImages"
                                        @click="deleteImage(image.id)"
                                    >
                                        {{ isDeletingImage(image.id) ? 'Deleting...' : 'Delete' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import {
    VEHICLE_IMAGE_UPLOAD_DETAIL,
    VEHICLE_IMAGE_UPLOAD_HINT,
    validateVehicleImageFiles,
} from '@/utils/vehicleImageValidation';

const props = defineProps({
    templateColumns: { type: Array, default: () => [] },
    requiredColumns: { type: Array, default: () => [] },
    optionalColumns: { type: Array, default: () => [] },
    enumMap: { type: Object, default: () => ({}) },
    aliases: { type: Object, default: () => ({}) },
    availableCategories: { type: Array, default: () => [] },
    vendorLocations: { type: Array, default: () => [] },
    preview: { type: Object, default: null },
    draftMeta: { type: Object, default: null },
    flash: { type: Object, default: () => ({}) },
});

const page = usePage();

const form = useForm({
    csv_file: null,
});

const importForm = useForm({});
const imageUploadForm = useForm({
    images: [],
});
const bulkImages = ref([]);
const copiedValue = ref('');
const bulkImageError = ref('');
const previewSectionRef = ref(null);
const showAllImagesDialog = ref(false);
const deletingImageIds = ref([]);
const isBulkDeletingImages = ref(false);
const selectedBulkImageIds = ref([]);

const scrollToPreview = async () => {
    if (!props.preview || !previewSectionRef.value) {
        return;
    }

    await nextTick();

    previewSectionRef.value.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });

    previewSectionRef.value.focus({ preventScroll: true });
};

const fetchBulkImages = async () => {
    const locale = page.props.locale;
    const cacheBust = `_t=${Date.now()}`;
    const response = await axios.get(route('vendor.bulk-vehicle-images.index', { locale, [cacheBust]: '' }));
    bulkImages.value = (response.data || []).map((image) => ({
        ...image,
        token: `bulkimg_${image.id}`,
    }));
};

const handleFileChange = (event) => {
    form.csv_file = event.target.files?.[0] || null;
};

const handleImageSelection = async (event) => {
    const files = Array.from(event.target.files || []);
    bulkImageError.value = '';

    const validationError = await validateVehicleImageFiles(files);
    if (validationError) {
        imageUploadForm.images = [];
        bulkImageError.value = validationError;
        event.target.value = '';
        return;
    }

    imageUploadForm.images = files;
};

const submitPreview = () => {
    form.post(route('vendor.vehicles.bulk-import.preview', { locale: page.props.locale }), {
        preserveScroll: true,
    });
};

const submitImport = () => {
    importForm.post(route('vendor.vehicles.bulk-import.import', { locale: page.props.locale }), {
        preserveScroll: true,
    });
};

const submitImageUpload = () => {
    imageUploadForm.post(route('vendor.bulk-vehicle-images.store', { locale: page.props.locale }), {
        preserveScroll: true,
        onSuccess: async () => {
            imageUploadForm.reset('images');
            const input = document.getElementById('bulk_vehicle_images');
            if (input) {
                input.value = '';
            }
            await fetchBulkImages();
        },
    });
};

const isDeletingImage = (imageId) => deletingImageIds.value.includes(imageId);

const toggleImageSelection = (imageId) => {
    if (selectedBulkImageIds.value.includes(imageId)) {
        selectedBulkImageIds.value = selectedBulkImageIds.value.filter((id) => id !== imageId);
        return;
    }

    selectedBulkImageIds.value = [...selectedBulkImageIds.value, imageId];
};

const deleteImage = async (imageId) => {
    if (isDeletingImage(imageId) || isBulkDeletingImages.value) {
        return;
    }

    deletingImageIds.value = [...deletingImageIds.value, imageId];

    try {
        await axios.delete(route('vendor.bulk-vehicle-images.destroy', { locale: page.props.locale, image: imageId }));
        bulkImages.value = bulkImages.value.filter((image) => image.id !== imageId);
        selectedBulkImageIds.value = selectedBulkImageIds.value.filter((id) => id !== imageId);
    } finally {
        deletingImageIds.value = deletingImageIds.value.filter((id) => id !== imageId);
    }
};

const deleteSelectedImages = async () => {
    if (!selectedBulkImageIds.value.length || isBulkDeletingImages.value) {
        return;
    }

    isBulkDeletingImages.value = true;

    try {
        await axios.post(route('vendor.bulk-vehicle-images.bulk-destroy', { locale: page.props.locale }), {
            ids: selectedBulkImageIds.value,
        });

        const selectedIds = new Set(selectedBulkImageIds.value);
        bulkImages.value = bulkImages.value.filter((image) => !selectedIds.has(image.id));
        deletingImageIds.value = deletingImageIds.value.filter((id) => !selectedIds.has(id));
        selectedBulkImageIds.value = [];
    } finally {
        isBulkDeletingImages.value = false;
    }
};

const copyToClipboard = async (value) => {
    await navigator.clipboard.writeText(value);
    copiedValue.value = value;
    window.setTimeout(() => {
        if (copiedValue.value === value) {
            copiedValue.value = '';
        }
    }, 1500);
};

const formatFieldLabel = (value) => value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());

const canImport = computed(() => Boolean(
    props.draftMeta?.can_import
    && props.preview
    && (props.preview.missing_required_columns?.length || 0) === 0
    && (props.preview.invalid_rows || 0) === 0
    && (props.preview.total_rows || 0) > 0
));

const selectedImageCount = computed(() => imageUploadForm.images.length);
const imageFieldErrors = computed(() => Object.entries(imageUploadForm.errors)
    .filter(([key]) => key !== 'images')
    .map(([, message]) => message)
);
const visibleBulkImages = computed(() => bulkImages.value.slice(0, 5));
const hasMoreBulkImages = computed(() => bulkImages.value.length > 5);
const sippGuidanceFields = computed(() => [
    {
        key: 'category',
        label: 'Category',
        description: 'Use a real category name, slug, or id from the available categories list below.',
        values: props.availableCategories.map((category) => category.name),
    },
    {
        key: 'body_style',
        label: 'Body Style',
        description: 'Must use one accepted value exactly. Do not use values like compact or luxury here.',
        values: props.enumMap.body_style || [],
    },
    {
        key: 'transmission',
        label: 'Transmission',
        description: 'Must use one accepted value exactly.',
        values: props.enumMap.transmission || [],
    },
    {
        key: 'fuel',
        label: 'Fuel',
        description: 'Must use one accepted fuel value exactly.',
        values: props.enumMap.fuel || [],
    },
    {
        key: 'air_conditioning',
        label: 'Air Conditioning',
        description: 'Use yes or no, or 1 or 0.',
        values: ['yes', 'no', '1', '0'],
    },
    {
        key: 'seating_capacity',
        label: 'Seating Capacity',
        description: 'Use a numeric seat count such as 2, 5, or 7.',
        values: ['2', '4', '5', '7'],
    },
    {
        key: 'number_of_doors',
        label: 'Number of Doors',
        description: 'Use a numeric door count such as 2, 3, 4, or 5.',
        values: ['2', '3', '4', '5'],
    },
]);

onMounted(() => {
    fetchBulkImages().catch((error) => {
        console.error('Failed to load bulk images', error);
    });

    if (props.preview) {
        scrollToPreview();
    }
});

watch(() => props.preview, (preview) => {
    if (preview) {
        scrollToPreview();
    }
});
</script>
