<template>
    <MyProfileLayout>
        <Head><title>Vendor Locations</title></Head>
        <Toaster class="pointer-events-auto" position="bottom-right" rich-colors />

        <div class="space-y-6">
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.errors?.delete" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $page.props.errors.delete }}
            </div>

            <div class="vr-phead">
                <div>
                    <span class="vr-eyebrow"><MapPin /> {{ tt('vendorprofilepages', 'fleet_eyebrow', 'Fleet') }}</span>
                    <h2>{{ tt('vendorprofilepages', 'vendor_locations_header', 'Manage Vehicle Locations') }}</h2>
                    <p class="vr-sub">Create canonical pickup locations once, then assign multiple vehicles to them.</p>
                </div>
                <div class="vr-phead-actions">
                    <Link :href="route('vehicles.create', { locale: page.props.locale })" class="vr-btn vr-btn-ghost">
                        <ArrowLeft class="w-4 h-4" /> Back to vehicle listing
                    </Link>
                </div>
            </div>

            <div class="space-y-6">
                <div v-if="shouldShowForm" ref="formSection" class="vr-panel">
                    <div class="vr-panel-head">
                        <h3><MapPin /> {{ editingId ? 'Edit Location' : 'Add Location' }}</h3>
                    </div>
                    <div class="vr-panel-body">
                        <p class="vr-hint" style="margin-bottom:16px">One saved location can serve many vehicles. This prevents duplicate airport or downtown offices.</p>

                        <div v-if="editingLocationName" class="mb-4 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                            <div class="text-sm font-semibold text-cyan-900">Editing: {{ editingLocationName }}</div>
                            <div class="mt-1 text-xs text-cyan-800">Update the form below, then click <span class="font-semibold">Save Changes</span>. Use Cancel if you want to go back to creating a new location.</div>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <VendorLocationFormFields :form="form" :errors="form.errors" />

                            <div class="vr-form-actions" style="justify-content:flex-start">
                                <button type="submit" class="vr-btn vr-btn-pri" :disabled="form.processing">
                                    {{ editingId ? 'Save Changes' : 'Create Location' }}
                                </button>
                                <button v-if="editingId || hasLocations" type="button" class="vr-btn vr-btn-ghost" @click="resetForm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="vr-panel">
                    <div class="vr-panel-head">
                        <h3><MapPin /> Saved Locations</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <div v-if="locationCards.length" class="vr-pill">
                                Showing {{ locations.from }}-{{ locations.to }} of {{ locations.total }}
                            </div>
                            <button v-if="hasLocations && !shouldShowForm" type="button" class="vr-btn vr-btn-pri vr-btn-sm" @click="openCreateForm">
                                <Plus class="w-4 h-4" /> Add New Location
                            </button>
                        </div>
                    </div>

                    <div v-if="!locationCards.length" class="vr-empty">
                        <div class="e-ic"><MapPin /></div>
                        <h4>No vendor locations yet</h4>
                        <p>Create your first pickup location above.</p>
                    </div>

                    <div v-else class="space-y-5 p-5">
                        <div class="grid gap-4 xl:grid-cols-2">
                            <article
                                v-for="location in locationCards"
                                :key="location.id"
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-cyan-200 hover:shadow-md"
                            >
                                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-base font-semibold text-slate-900">{{ location.name }}</h3>
                                            <p class="mt-1 text-sm text-slate-600">{{ location.address_line_1 }}</p>
                                            <p class="text-sm text-slate-500">{{ [location.city, location.state, location.country].filter(Boolean).join(', ') }}</p>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">
                                                {{ location.location_type }}
                                            </span>
                                            <span v-if="location.iata_code" class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ location.iata_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-4 px-5 py-4 md:grid-cols-2">
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Operational</div>
                                        <div class="mt-2 space-y-2 text-sm text-slate-700">
                                            <div><span class="font-medium text-slate-900">Vehicles:</span> {{ location.vehicles_count }}</div>
                                            <div><span class="font-medium text-slate-900">Phone:</span> {{ location.phone || 'Not set' }}</div>
                                            <div><span class="font-medium text-slate-900">Coords:</span> {{ location.latitude }}, {{ location.longitude }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Instructions</div>
                                        <div class="mt-2 space-y-2 text-sm text-slate-700">
                                            <div><span class="font-medium text-slate-900">Pickup:</span> {{ location.pickup_instructions || 'Not set' }}</div>
                                            <div><span class="font-medium text-slate-900">Dropoff:</span> {{ location.dropoff_instructions || 'Not set' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
                                    <div class="text-xs text-slate-500">
                                        {{ location.display_name }}
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="startEditing(location)">
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                            @click="openDeleteDialog(location)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="flex justify-center border-t border-slate-100 pt-4">
                            <Pagination
                                :current-page="locations.current_page"
                                :total-pages="locations.last_page"
                                @page-change="goToPage"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AlertDialog :open="Boolean(deleteTarget)" @update:open="handleDeleteDialogChange">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ deleteTarget?.vehicles_count > 0 ? 'This location has linked vehicles' : 'Delete location?' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="deleteTarget?.vehicles_count > 0">
                            <span class="font-semibold text-slate-900">{{ deleteTarget?.name }}</span> has
                            <span class="font-semibold text-slate-900">{{ deleteTarget?.vehicles_count }}</span>
                            linked vehicle{{ deleteTarget?.vehicles_count === 1 ? '' : 's' }}.
                            Reassign those vehicles to another location, or delete this location together with all linked vehicles.
                        </template>
                        <template v-else>
                            This will permanently remove
                            <span class="font-semibold text-slate-900">{{ deleteTarget?.name }}</span>.
                            No vehicles are linked to this office.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deleteTarget = null">Cancel</AlertDialogCancel>
                    <button
                        v-if="deleteTarget?.vehicles_count > 0"
                        type="button"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        @click="goToLinkedVehicles"
                    >
                        Reassign vehicles
                    </button>
                    <AlertDialogAction
                        class="bg-red-600 text-white hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        {{ deleteTarget?.vehicles_count > 0 ? 'Delete location and vehicles' : 'Delete location' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import VendorLocationFormFields from "@/Components/VendorLocationFormFields.vue";
import Pagination from "@/Components/ReusableComponents/Pagination.vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref, getCurrentInstance } from "vue";
import { toast } from "vue-sonner";
import { Toaster } from "@/Components/ui/sonner";
import { MapPin, ArrowLeft, Plus } from "lucide-vue-next";

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

const props = defineProps({
    locations: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        }),
    },
});

const page = usePage();
const editingId = ref(null);
const editingLocationName = ref("");
const showForm = ref(false);
const formSection = ref(null);
const deleteTarget = ref(null);
const locationCards = computed(() => props.locations?.data || []);
const hasLocations = computed(() => Number(props.locations?.total || 0) > 0);
const shouldShowForm = computed(() => showForm.value || editingId.value !== null || !hasLocations.value);
const vendorLocationsIndexUrl = computed(() => `/${page.props.locale}/vendor-locations`);
const vendorLocationUrl = (locationId) => `${vendorLocationsIndexUrl.value}/${locationId}`;
const vendorLocationWithVehiclesDeleteUrl = (locationId) => `${vendorLocationUrl(locationId)}/with-vehicles`;
const vendorVehiclesIndexUrl = computed(() => `/${page.props.locale}/current-vendor-vehicles`);

const form = useForm({
    name: "",
    code: "",
    address_line_1: "",
    address_line_2: "",
    city: "",
    state: "",
    country: "",
    country_code: "",
    latitude: "",
    longitude: "",
    location_type: "airport",
    iata_code: "",
    phone: "",
    pickup_instructions: "",
    dropoff_instructions: "",
});

const resetForm = () => {
    editingId.value = null;
    editingLocationName.value = "";
    showForm.value = false;
    form.reset();
    form.clearErrors();
    form.location_type = "airport";
};

const scrollToForm = async () => {
    await nextTick();
    formSection.value?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const startEditing = async (location) => {
    showForm.value = true;
    editingId.value = location.id;
    editingLocationName.value = location.name || "";
    form.name = location.name || "";
    form.code = location.code || "";
    form.address_line_1 = location.address_line_1 || "";
    form.address_line_2 = location.address_line_2 || "";
    form.city = location.city || "";
    form.state = location.state || "";
    form.country = location.country || "";
    form.country_code = location.country_code || "";
    form.latitude = location.latitude ?? "";
    form.longitude = location.longitude ?? "";
    form.location_type = location.location_type || "airport";
    form.iata_code = location.iata_code || "";
    form.phone = location.phone || "";
    form.pickup_instructions = location.pickup_instructions || "";
    form.dropoff_instructions = location.dropoff_instructions || "";
    form.clearErrors();
    scrollToForm();
};

const openCreateForm = async () => {
    resetForm();
    showForm.value = true;
    await scrollToForm();
};

const submit = () => {
    const isEditing = Boolean(editingId.value);
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            toast.success(isEditing ? "Location updated successfully" : "Location created successfully");
        },
        onError: () => {
            toast.error(isEditing ? "Failed to update location" : "Failed to create location");
        },
    };

    if (isEditing) {
        form.put(vendorLocationUrl(editingId.value), options);
        return;
    }

    form.post(vendorLocationsIndexUrl.value, options);
};

const openDeleteDialog = (location) => {
    deleteTarget.value = location;
};

const handleDeleteDialogChange = (isOpen) => {
    if (!isOpen) {
        deleteTarget.value = null;
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    const withVehicles = deleteTarget.value.vehicles_count > 0;
    const deleteUrl = withVehicles
        ? vendorLocationWithVehiclesDeleteUrl(deleteTarget.value.id)
        : vendorLocationUrl(deleteTarget.value.id);

    router.delete(deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(withVehicles ? "Location and linked vehicles deleted" : "Location deleted successfully");
        },
        onError: (errors) => {
            toast.error(errors?.delete || "Failed to delete location");
        },
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
};

const goToLinkedVehicles = () => {
    if (!deleteTarget.value) {
        return;
    }

    router.get(vendorVehiclesIndexUrl.value, {
        vendor_location_id: deleteTarget.value.id,
    });

    deleteTarget.value = null;
};

const goToPage = (pageNumber) => {
    router.get(vendorLocationsIndexUrl.value, { page: pageNumber }, {
        preserveScroll: true,
        preserveState: true,
        only: ['locations'],
    });
};
</script>
