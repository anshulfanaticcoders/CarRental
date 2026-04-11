<template>
    <MyProfileLayout>
        <Head><title>Vendor Locations</title></Head>

        <div class="mx-auto w-full space-y-6 py-4 sm:py-6">
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.errors?.delete" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $page.props.errors.delete }}
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Vendor Locations</h1>
                    <p class="text-sm text-slate-600">Create canonical pickup locations once, then assign multiple vehicles to them.</p>
                </div>
                <Link :href="route('vehicles.create', { locale: page.props.locale })" class="text-sm font-medium text-cyan-700 hover:text-cyan-800">
                    Back to vehicle listing
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-[420px_minmax(0,1fr)]">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">{{ editingId ? 'Edit Location' : 'Add Location' }}</h2>
                        <p class="text-sm text-slate-600">One saved location can serve many vehicles. This prevents duplicate airport or downtown offices.</p>
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                            <input v-model="form.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Menara Airport" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                                <select v-model="form.location_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="airport">Airport</option>
                                    <option value="downtown">Downtown</option>
                                    <option value="terminal">Terminal</option>
                                    <option value="bus stop">Bus Stop</option>
                                    <option value="railway station">Railway Station</option>
                                    <option value="industrial">Industrial</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">IATA</label>
                                <input v-model="form.iata_code" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase" type="text" maxlength="3" placeholder="RAK" />
                                <p v-if="form.errors.iata_code" class="mt-1 text-sm text-red-600">{{ form.errors.iata_code }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                            <input v-model="form.address_line_1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Menara Airport, Terminal 2" />
                            <p v-if="form.errors.address_line_1" class="mt-1 text-sm text-red-600">{{ form.errors.address_line_1 }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">City</label>
                                <input v-model="form.city" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Marrakech" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">State</label>
                                <input v-model="form.state" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Marrakesh-Safi" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                                <input v-model="form.country" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Morocco" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Country Code</label>
                                <input v-model="form.country_code" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase" type="text" maxlength="2" placeholder="MA" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Latitude</label>
                                <input v-model="form.latitude" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="number" step="0.000001" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Longitude</label>
                                <input v-model="form.longitude" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="number" step="0.000001" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input v-model="form.phone" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="+212 ..." />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Pickup Instructions</label>
                            <textarea v-model="form.pickup_instructions" class="min-h-[90px] w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Meet customers at desk 3 in Terminal 2"></textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Dropoff Instructions</label>
                            <textarea v-model="form.dropoff_instructions" class="min-h-[90px] w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Return keys at the arrivals desk"></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700" :disabled="form.processing">
                                {{ editingId ? 'Save Changes' : 'Create Location' }}
                            </button>
                            <button v-if="editingId" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="resetForm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-xl border bg-white shadow-sm">
                    <div class="border-b px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">Saved Locations</h2>
                        <p class="text-sm text-slate-600">These locations drive vehicle grouping, internal APIs, unified locations, and partner feeds.</p>
                    </div>

                    <div v-if="!locations.length" class="p-5 text-sm text-slate-600">
                        No vendor locations exist yet.
                    </div>

                    <div v-else class="divide-y">
                        <div v-for="location in locations" :key="location.id" class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900">{{ location.name }}</h3>
                                    <span class="rounded-full bg-cyan-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">
                                        {{ location.location_type }}
                                    </span>
                                    <span v-if="location.iata_code" class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ location.iata_code }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ location.vehicles_count }} vehicle{{ location.vehicles_count === 1 ? '' : 's' }}
                                    </span>
                                </div>
                                <div class="text-sm text-slate-600">{{ location.address_line_1 }}</div>
                                <div class="text-sm text-slate-600">{{ [location.city, location.state, location.country].filter(Boolean).join(', ') }}</div>
                                <div class="grid gap-1 text-sm text-slate-600 md:grid-cols-2">
                                    <div><span class="font-medium text-slate-700">Phone:</span> {{ location.phone || 'Not set' }}</div>
                                    <div><span class="font-medium text-slate-700">Coords:</span> {{ location.latitude }}, {{ location.longitude }}</div>
                                    <div class="md:col-span-2"><span class="font-medium text-slate-700">Pickup:</span> {{ location.pickup_instructions || 'Not set' }}</div>
                                    <div class="md:col-span-2"><span class="font-medium text-slate-700">Dropoff:</span> {{ location.dropoff_instructions || 'Not set' }}</div>
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="startEditing(location)">
                                    Edit
                                </button>
                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50" @click="remove(location)" :disabled="location.vehicles_count > 0">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    locations: { type: Array, default: () => [] },
});

const page = usePage();
const editingId = ref(null);

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
    form.reset();
    form.clearErrors();
    form.location_type = "airport";
};

const startEditing = (location) => {
    editingId.value = location.id;
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
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (!editingId.value) {
                resetForm();
            }
        },
    };

    if (editingId.value) {
        form.put(route("vendor.locations.update", { locale: page.props.locale, vendor_location: editingId.value }), options);
        return;
    }

    form.post(route("vendor.locations.store", { locale: page.props.locale }), options);
};

const remove = (location) => {
    if (location.vehicles_count > 0) {
        return;
    }

    if (!window.confirm(`Delete ${location.name}?`)) {
        return;
    }

    router.delete(route("vendor.locations.destroy", { locale: page.props.locale, vendor_location: location.id }), {
        preserveScroll: true,
    });
};
</script>
