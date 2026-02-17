<template>
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div
            class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center space-y-4 max-w-sm mx-4 border border-blue-100">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-blue-100 rounded-full animate-pulse"></div>
                <div
                    class="absolute top-0 left-0 w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin">
                </div>
            </div>
            <div class="text-center space-y-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ _t('vendorprofilepages', 'loader_updating_text') }}
                </h3>
                <p class="text-sm text-gray-500">{{ _t('vendorprofilepages', 'please_wait_message') }}</p>
            </div>
        </div>
    </div>

    <MyProfileLayout>
        <div class="container mx-auto p-4 space-y-6 sm:p-6">
            <div class="py-6 sm:py-12">
                <div class="mx-auto">
                    <div v-if="Object.keys(formErrors).length"
                        class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold">Please fix the errors below:</p>
                        <ul class="mt-2 list-disc pl-5">
                            <li v-for="(messages, field) in formErrors" :key="field">
                                {{ Array.isArray(messages) ? messages[0] : messages }}
                            </li>
                        </ul>
                    </div>
                    <form @submit.prevent="updateVehicle">
                        <Tabs defaultValue="basic" class="w-full">
                            <TabsList class="grid w-full grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                                <TabsTrigger value="basic" class="whitespace-normal text-xs leading-tight sm:text-sm">
                                    {{ _t('vendorprofilepages', 'tab_basic_information') }}
                                </TabsTrigger>
                                <TabsTrigger value="specifications"
                                    class="whitespace-normal text-xs leading-tight sm:text-sm">
                                    {{ _t('vendorprofilepages', 'tab_specifications') }}
                                </TabsTrigger>
                                <TabsTrigger value="pricing" class="whitespace-normal text-xs leading-tight sm:text-sm">
                                    {{ _t('vendorprofilepages', 'tab_pricing_features') }}
                                </TabsTrigger>
                                <TabsTrigger value="guidelines" class="whitespace-normal text-xs leading-tight sm:text-sm">
                                    {{ _t('vendorprofilepages', 'tab_guidelines_timings') }}
                                </TabsTrigger>
                                <TabsTrigger value="images" class="whitespace-normal text-xs leading-tight sm:text-sm">
                                    {{ _t('vendorprofilepages', 'tab_images') }}
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="basic">
                                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(320px,1fr))]">
                                    <div>
                                        <InputLabel for="category_id">{{ _t('vendorprofilepages',
                                            'label_vehicle_category') }}</InputLabel>
                                        <Select v-model="form.category_id" required>
                                            <SelectTrigger id="category_id">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_category')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages', 'select_label_categories')
                                                    }}</SelectLabel>
                                                    <SelectItem v-for="category in categories" :key="category.id"
                                                        :value="category.id">
                                                        {{ category.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="brand">{{ _t('vendorprofilepages', 'label_brand') }}
                                        </InputLabel>
                                        <Input type="text" v-model="form.brand" id="brand" required />
                                    </div>
                                    <div>
                                        <InputLabel for="model">{{ _t('vendorprofilepages', 'label_model') }}
                                        </InputLabel>
                                        <Input type="text" v-model="form.model" id="model" required />
                                    </div>
                                    <div>
                                        <InputLabel for="color">{{ _t('vendorprofilepages', 'label_color') }}
                                        </InputLabel>
                                        <Select v-model="form.color" required>
                                            <SelectTrigger id="color">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_color')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages', 'select_label_colors') }}
                                                    </SelectLabel>
                                                    <SelectItem v-for="color in colors" :key="color.value"
                                                        :value="color.value">
                                                        {{ color.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="mileage">{{ _t('vendorprofilepages', 'label_mileage') }}
                                        </InputLabel>
                                        <Input type="number" step="0.01" v-model.number="form.mileage" id="mileage"
                                            required />
                                    </div>
                                    <div>
                                        <InputLabel for="transmission">{{ _t('vendorprofilepages',
                                            'label_transmission_select') }}</InputLabel>
                                        <Select v-model="form.transmission" required>
                                            <SelectTrigger id="transmission">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_transmission')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages',
                                                        'select_label_transmission') }}</SelectLabel>
                                                    <SelectItem value="manual">{{ _t('vendorprofilepages',
                                                        'transmission_manual') }}</SelectItem>
                                                    <SelectItem value="automatic">{{ _t('vendorprofilepages',
                                                        'transmission_automatic') }}</SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="fuel">{{ _t('vendorprofilepages', 'label_fuel_select') }}
                                        </InputLabel>
                                        <Select v-model="form.fuel" required>
                                            <SelectTrigger id="fuel">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_fuel')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages', 'select_label_fuel') }}
                                                    </SelectLabel>
                                                    <SelectItem value="petrol">{{ _t('vendorprofilepages',
                                                        'fuel_petrol') }}</SelectItem>
                                                    <SelectItem value="diesel">{{ _t('vendorprofilepages',
                                                        'fuel_diesel') }}</SelectItem>
                                                    <SelectItem value="electric">{{ _t('vendorprofilepages',
                                                        'fuel_electric') }}</SelectItem>
                                                    <SelectItem value="hybrid">{{ _t('vendorprofilepages',
                                                        'fuel_hybrid') }}</SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="status">{{ _t('vendorprofilepages', 'label_status_select') }}
                                        </InputLabel>
                                        <Select v-model="form.status" required>
                                            <SelectTrigger id="status">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_status')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages', 'select_label_status') }}
                                                    </SelectLabel>
                                                    <SelectItem value="available">{{ _t('vendorprofilepages',
                                                        'status_available') }}</SelectItem>
                                                    <SelectItem value="rented">{{ _t('vendorprofilepages',
                                                        'status_rented') }}</SelectItem>
                                                    <SelectItem value="maintenance">{{ _t('vendorprofilepages',
                                                        'maintenance_vehicles_card_title') }}</SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="col-span-2 space-y-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <MapPin class="w-4 h-4 text-blue-600" />
                                            <InputLabel for="location" class="text-gray-700 font-medium">{{
                                                _t('vendorprofilepages', 'label_location') }}</InputLabel>
                                        </div>

                                        <div v-if="form.location"
                                            class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl shadow-sm">
                                            <div class="space-y-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                    <span class="font-medium text-gray-900">{{ _t('vendorprofilepages',
                                                        'current_location_label') }}</span>
                                                </div>
                                                <div class="text-gray-700 font-medium break-words">{{ displayedFullAddress }}</div>
                                                <div class="grid gap-4 text-sm [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-500">{{ _t('vendorprofilepages',
                                                            'label_city') }}:</span>
                                                        <span class="font-medium">{{ form.city }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-500">{{ _t('vendorprofilepages',
                                                            'label_state') }}:</span>
                                                        <span class="font-medium">{{ form.state || 'N/A' }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-500">{{ _t('vendorprofilepages',
                                                            'label_country') }}:</span>
                                                        <span class="font-medium">{{ form.country }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-500">{{ _t('vendorprofilepages',
                                                            'label_coordinates') }}:</span>
                                                        <span class="font-medium text-xs">{{ form.latitude }}, {{
                                                            form.longitude }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <Button type="button" @click="toggleLocationPicker"
                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all duration-200 hover:shadow-md">
                                            <MapPin class="w-4 h-4" />
                                            {{ showLocationPicker ? _t('vendorprofilepages',
                                                'button_hide_location_picker') : _t('vendorprofilepages',
                                                    'button_change_location') }}
                                        </Button>

                                        <transition name="slide-fade"
                                            enter-active-class="transition ease-out duration-200"
                                            enter-from-class="transform opacity-0 -translate-y-4"
                                            enter-to-class="transform opacity-100 translate-y-0"
                                            leave-active-class="transition ease-in duration-150"
                                            leave-from-class="transform opacity-100 translate-y-0"
                                            leave-to-class="transform opacity-0 -translate-y-4">
                                            <div v-show="showLocationPicker"
                                                class="location-picker-container border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                                <LocationPicker :onLocationSelect="handleLocationSelect" />
                                            </div>
                                        </transition>
                                        <div class="mt-4">
                                            <InputLabel for="location_type">{{ _t('vendorprofilepages',
                                                'label_location_type') }}</InputLabel>
                                            <Select v-model="form.location_type" required>
                                                <SelectTrigger id="location_type">
                                                    <SelectValue
                                                        :placeholder="_t('vendorprofilepages', 'placeholder_enter_location_type')" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem value="Downtown">Downtown</SelectItem>
                                                        <SelectItem value="Airport">Airport</SelectItem>
                                                        <SelectItem value="Terminal">Terminal</SelectItem>
                                                        <SelectItem value="Bus Stop">Bus Stop</SelectItem>
                                                        <SelectItem value="Railway Station">Railway Station</SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="specifications">
                                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(320px,1fr))]">
                                    <div>
                                        <InputLabel for="seating_capacity">{{ _t('vendorprofilepages',
                                            'label_seating_capacity') }}</InputLabel>
                                        <Select v-model.number="form.seating_capacity" required>
                                            <SelectTrigger id="seating_capacity">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_seating_capacity')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages',
                                                        'select_label_seating_capacity') }}</SelectLabel>
                                                    <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="number_of_doors">{{ _t('vendorprofilepages',
                                            'label_number_of_doors') }}</InputLabel>
                                        <Select v-model.number="form.number_of_doors" required>
                                            <SelectTrigger id="number_of_doors">
                                                <SelectValue
                                                    :placeholder="_t('vendorprofilepages', 'placeholder_select_number_of_doors')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages',
                                                        'select_label_number_of_doors') }}</SelectLabel>
                                                    <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <InputLabel for="luggage_capacity">{{ _t('vendorprofilepages',
                                            'label_luggage_capacity') }}</InputLabel>
                                        <Input type="number" v-model.number="form.luggage_capacity"
                                            id="luggage_capacity" required />
                                    </div>
                                    <div>
                                        <InputLabel for="horsepower">{{ _t('vendorprofilepages', 'label_horsepower') }}
                                        </InputLabel>
                                        <Input type="number" step="0.01" v-model.number="form.horsepower"
                                            id="horsepower" required />
                                    </div>
                                    <div>
                                        <InputLabel for="co2">{{ _t('vendorprofilepages', 'label_co2_emissions') }}
                                        </InputLabel>
                                        <Input type="text" v-model="form.co2" id="co2" required />
                                    </div>
                                    <div>
                                        <InputLabel for="registration_number">{{ _t('vendorprofilepages',
                                            'label_registration_number') }}</InputLabel>
                                        <Input type="text" v-model="form.registration_number" id="registration_number"
                                            required />
                                    </div>
                                    <div class="relative w-full">
                                        <InputLabel class="text-black" for="registration_country">{{
                                            _t('vendorprofilepages', 'label_registration_country') }}
                                        </InputLabel>

                                        <div class="relative">
                                            <Select v-model="form.registration_country">
                                                <SelectTrigger
                                                    class="w-full p-[1.5rem] border-customLightGrayColor rounded-[12px]">
                                                    <SelectValue
                                                        :placeholder="_t('vendorprofilepages', 'placeholder_select_country')" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectLabel>{{ _t('vendorprofilepages',
                                                            'select_label_countries') }}</SelectLabel>
                                                        <SelectItem v-for="country in countries" :key="country.code"
                                                            :value="country.code">
                                                            {{ country.name }}
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <!-- Dynamic Flag -->
                                            <img v-if="form.registration_country"
                                                :src="getFlagUrl(form.registration_country)"
                                                :alt="_t('vendorprofilepages', 'alt_country_flag')"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 w-[2.1rem] h-[1.5rem] rounded" />
                                        </div>
                                    </div>
                                    <div>
                                        <InputLabel class="text-black" for="registration_date">{{
                                            _t('vendorprofilepages', 'label_registration_date') }}</InputLabel>
                                        <VueDatePicker v-model="form.registration_date" :format="'yyyy-MM-dd'"
                                            auto-apply
                                            :placeholder="_t('vendorprofilepages', 'placeholder_select_registration_date')"
                                            class="w-full" :clearable="false" :max-date="new Date()"
                                            :input-class-name="'w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm text-gray-700'"
                                            @update:modelValue="formatDate" required />

                                    </div>
                                    <div>
                                        <InputLabel for="gross_vehicle_mass">{{ _t('vendorprofilepages',
                                            'label_gross_vehicle_mass') }}</InputLabel>
                                        <Input type="number" v-model.number="form.gross_vehicle_mass"
                                            id="gross_vehicle_mass" />
                                    </div>
                                    <div>
                                        <InputLabel for="vehicle_height">{{ _t('vendorprofilepages',
                                            'label_vehicle_height') }}</InputLabel>
                                        <Input type="number" v-model.number="form.vehicle_height" id="vehicle_height"
                                            step="0.01" />
                                    </div>
                                    <div>
                                        <InputLabel for="phone_number">{{ _t('vendorprofilepages',
                                            'label_phone_number_vehicle') }}</InputLabel>
                                        <Input type="text" v-model="form.phone_number" id="phone_number" required />
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="pricing">
                                <div class="space-y-6">
                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">Pricing</h3>
                                                <p class="text-sm text-gray-500">All prices in <span
                                                        class="inline-currency">{{ currencyCode }}</span></p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(280px,1fr))]">
                                            <div class="col-span-full">
                                                <InputLabel for="price_per_day">{{ _t('vendorprofilepages',
                                                    'label_price_per_day') }}</InputLabel>
                                                <div class="input-with-suffix">
                                                    <Input type="number" v-model.number="form.price_per_day"
                                                        id="price_per_day" min="0" step="0.01"
                                                        class="input-field" />
                                                    <span class="input-suffix">{{ currencyCode }}</span>
                                                </div>
                                            </div>
                                            <div class="col-span-full flex flex-wrap gap-4 sm:gap-6">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" v-model="selectedTypes.week" class="w-auto" />
                                                    {{ _t('vendorprofilepages', 'label_price_per_week') }}
                                                </label>
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" v-model="selectedTypes.month" class="w-auto" />
                                                    {{ _t('vendorprofilepages', 'label_price_per_month') }}
                                                </label>
                                            </div>
                                            <template v-if="selectedTypes.week">
                                                <div>
                                                    <InputLabel for="price_per_week">{{ _t('vendorprofilepages',
                                                        'label_price_per_week') }}</InputLabel>
                                                    <div class="input-with-suffix">
                                                        <Input type="number" v-model.number="form.price_per_week"
                                                            id="price_per_week" min="0" step="0.01"
                                                            class="input-field" />
                                                        <span class="input-suffix">{{ currencyCode }}</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <InputLabel for="weekly_discount">{{ _t('vendorprofilepages',
                                                        'label_weekly_discount') }}</InputLabel>
                                                    <div class="input-with-suffix">
                                                        <Input type="number" v-model.number="form.weekly_discount"
                                                            id="weekly_discount" min="0" max="1000.00" step="0.01"
                                                            class="input-field" />
                                                        <span class="input-suffix">%</span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-if="selectedTypes.month">
                                                <div>
                                                    <InputLabel for="price_per_month">{{ _t('vendorprofilepages',
                                                        'label_price_per_month') }}</InputLabel>
                                                    <div class="input-with-suffix">
                                                        <Input type="number" v-model.number="form.price_per_month"
                                                            id="price_per_month" min="0" step="0.01"
                                                            class="input-field" />
                                                        <span class="input-suffix">{{ currencyCode }}</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <InputLabel for="monthly_discount">{{ _t('vendorprofilepages',
                                                        'label_monthly_discount') }}</InputLabel>
                                                    <div class="input-with-suffix">
                                                        <Input type="number" v-model.number="form.monthly_discount"
                                                            id="monthly_discount" min="0" max="10000.00" step="0.01"
                                                            class="input-field" />
                                                        <span class="input-suffix">%</span>
                                                    </div>
                                                </div>
                                            </template>
                                            <div v-if="selectedTypes.week || selectedTypes.month" class="col-span-full">
                                                <InputLabel class="text-black mb-2">Preferred price type</InputLabel>
                                                <div class="flex flex-wrap gap-2 rounded-lg bg-gray-50 p-2">
                                                    <label
                                                        class="flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition"
                                                        :class="form.preferred_price_type === 'day'
                                                            ? 'bg-[#153B4F] text-white border-[#153B4F]'
                                                            : 'bg-white text-gray-700 border-gray-200'">
                                                        <input type="radio" value="day"
                                                            v-model="form.preferred_price_type" class="hidden" />
                                                        {{ _t('vendorprofilepages', 'label_price_per_day') }}
                                                    </label>
                                                    <label v-if="selectedTypes.week"
                                                        class="flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition"
                                                        :class="form.preferred_price_type === 'week'
                                                            ? 'bg-[#153B4F] text-white border-[#153B4F]'
                                                            : 'bg-white text-gray-700 border-gray-200'">
                                                        <input type="radio" value="week"
                                                            v-model="form.preferred_price_type" class="hidden" />
                                                        {{ _t('vendorprofilepages', 'label_price_per_week') }}
                                                    </label>
                                                    <label v-if="selectedTypes.month"
                                                        class="flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition"
                                                        :class="form.preferred_price_type === 'month'
                                                            ? 'bg-[#153B4F] text-white border-[#153B4F]'
                                                            : 'bg-white text-gray-700 border-gray-200'">
                                                        <input type="radio" value="month"
                                                            v-model="form.preferred_price_type" class="hidden" />
                                                        {{ _t('vendorprofilepages', 'label_price_per_month') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Deposits & Costs</h3>
                                        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(280px,1fr))]">
                                            <div>
                                                <InputLabel for="security_deposit">{{ _t('vendorprofilepages',
                                                    'label_security_deposit') }}</InputLabel>
                                                <div class="input-with-suffix">
                                                    <Input type="number" v-model.number="form.security_deposit"
                                                        id="security_deposit" required min="0" step="0.01"
                                                        class="input-field" />
                                                    <span class="input-suffix">{{ currencyCode }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <InputLabel for="dealer_cost">{{ _t('vendorprofilepages',
                                                    'label_dealer_cost') }}</InputLabel>
                                                <div class="input-with-suffix">
                                                    <Input type="number" v-model.number="form.dealer_cost"
                                                        id="dealer_cost" step="0.01" class="input-field" />
                                                    <span class="input-suffix">{{ currencyCode }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Protection Plans</h3>
                                            <span class="text-xs text-gray-500">Optional</span>
                                        </div>
                                        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(420px,1fr))]">
                                            <div v-for="plan in protectionPlans" :key="plan.key"
                                                class="rounded-[20px] border border-[#153B4F] bg-white p-5 flex flex-col gap-5"
                                                :class="{
                                                    'border-dashed bg-gray-50': !plan.selected,
                                                    'ring-2 ring-green-500': plan.selected
                                                }">
                                                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row">
                                                    <div>
                                                        <span class="text-[1.1rem] font-semibold text-gray-800">{{
                                                            plan.plan_type }}</span>
                                                    </div>
                                                    <div class="flex w-full flex-col items-start gap-2 sm:w-auto sm:items-end">
                                                        <label class="text-xs text-gray-500">Price per day</label>
                                                        <div class="input-with-suffix w-full sm:w-auto">
                                                            <Input type="number" step="0.01" v-model.number="plan.price"
                                                                :min="pricePerDay" :disabled="!plan.selected"
                                                                class="w-full px-2 py-1 border rounded-md text-right sm:w-28"
                                                                :class="!plan.selected ? 'bg-gray-100' : ''" />
                                                            <span class="input-suffix">{{ currencyCode }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="text-xs text-gray-500">Coverage</label>
                                                    <div class="mt-2 space-y-2">
                                                        <input v-for="(feature, index) in plan.features"
                                                            :key="`${plan.key}-feature-${index}`"
                                                            v-model="plan.features[index]" type="text"
                                                            :disabled="!plan.selected"
                                                            class="w-full p-2 border rounded-md"
                                                            :class="!plan.selected ? 'bg-gray-100' : ''"
                                                            :placeholder="`Coverage option ${index + 1}`" />
                                                    </div>
                                                    <p v-if="planErrors[plan.key]" class="text-sm text-red-500 mt-2">
                                                        {{ planErrors[plan.key] }}
                                                    </p>
                                                </div>
                                                <button type="button" @click="togglePlanSelection(plan)"
                                                    class="w-full py-2 rounded-lg font-semibold text-sm transition"
                                                    :class="plan.selected
                                                        ? 'bg-green-600 text-white hover:bg-green-700'
                                                        : 'bg-[#153B4F] text-white hover:bg-[#102c3b]'">
                                                    {{ plan.selected ? 'Selected' : 'Select Plan' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900">Add-ons</h3>
                                            <span class="text-xs text-gray-500">Optional</span>
                                        </div>
                                        <p v-if="!addons.length" class="text-sm text-gray-500 mb-4">
                                            No predefined addons yet. Add a custom addon below.
                                        </p>
                                        <div v-if="addons.length" class="space-y-4">
                                            <div v-for="addon in addons" :key="addon.id"
                                                class="border rounded-lg p-4 flex flex-col gap-4"
                                                :class="{
                                                    'border-dashed bg-gray-50': !isAddonSelected(addon.id),
                                                    'ring-2 ring-green-500': isAddonSelected(addon.id)
                                                }">
                                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                                    <div class="min-w-0 flex items-start gap-3">
                                                        <div>
                                                            <h3 class="font-semibold text-lg">{{ addon.extra_name }}</h3>
                                                            <p class="text-gray-500 text-sm break-words">{{ addon.description }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                                        <div class="flex flex-col items-start">
                                                            <label for="price" class="text-sm text-gray-500">{{
                                                                _t('createvehicle', 'step6_price_per_day_label') }}</label>
                                                            <div class="input-with-suffix">
                                                                <input type="number" v-model="addonPrices[addon.id]"
                                                                    :disabled="!isAddonSelected(addon.id)"
                                                                    class="w-full px-2 py-1 border rounded sm:w-24"
                                                                    :class="!isAddonSelected(addon.id) ? 'bg-gray-100' : ''" />
                                                                <span class="input-suffix">{{ currencyCode }}</span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm text-gray-500 mb-2">{{ _t('createvehicle',
                                                                'step6_quantity_label') }}</p>
                                                            <div class="flex items-center gap-2">
                                                                <button @click="decrementQuantity(addon.id)" type="button"
                                                                    :disabled="!isAddonSelected(addon.id)"
                                                                    class="px-2 py-1 border rounded"
                                                                    :class="!isAddonSelected(addon.id) ? 'opacity-50 cursor-not-allowed' : ''">-</button>
                                                                <span class="px-3 py-1 bg-gray-100 rounded">{{
                                                                    addonQuantities[addon.id] || '00'
                                                                }}</span>
                                                                <button @click="incrementQuantity(addon.id)" type="button"
                                                                    :disabled="!isAddonSelected(addon.id)"
                                                                    class="px-2 py-1 border rounded"
                                                                    :class="!isAddonSelected(addon.id) ? 'opacity-50 cursor-not-allowed' : ''">+</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" @click="toggleAddonSelection(addon.id)"
                                                    class="w-full py-2 rounded-lg font-semibold text-sm transition"
                                                    :class="isAddonSelected(addon.id)
                                                        ? 'bg-green-600 text-white hover:bg-green-700'
                                                        : 'bg-[#153B4F] text-white hover:bg-[#102c3b]'">
                                                    {{ isAddonSelected(addon.id) ? 'Selected' : 'Select Addon' }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-6 border-t border-gray-200 pt-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-semibold text-gray-800">Custom add-ons</h4>
                                                <button type="button" @click="addCustomAddon"
                                                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    Add custom addon
                                                </button>
                                            </div>
                                            <div v-if="customAddons.length" class="space-y-4">
                                                <div v-for="addon in customAddons" :key="addon.id"
                                                    class="rounded-lg border border-gray-200 p-4">
                                                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
                                                    <div>
                                                        <InputLabel>Addon name</InputLabel>
                                                        <Input v-model="addon.extra_name" class="input-field"
                                                            placeholder="e.g. Baby seat" />
                                                    </div>
                                                    <div>
                                                        <InputLabel>Type</InputLabel>
                                                        <Input v-model="addon.extra_type" class="input-field"
                                                            placeholder="e.g. equipment" />
                                                    </div>
                                                    <div>
                                                        <InputLabel>Price</InputLabel>
                                                        <div class="input-with-suffix">
                                                            <Input type="number" v-model.number="addon.price"
                                                                min="0" step="0.01" class="input-field" />
                                                            <span class="input-suffix">{{ currencyCode }}</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <InputLabel>Qty</InputLabel>
                                                        <Input type="number" v-model.number="addon.quantity" min="1"
                                                            class="input-field" />
                                                    </div>
                                                    <div class="[grid-column:1/-1]">
                                                        <InputLabel>Description</InputLabel>
                                                        <Input v-model="addon.description" class="input-field"
                                                            placeholder="Short description" />
                                                    </div>
                                                    <div class="flex items-end justify-start sm:justify-end">
                                                        <button type="button" @click="removeCustomAddon(addon.id)"
                                                            class="text-sm text-red-600 hover:underline">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                            <p v-else class="text-sm text-gray-500">No custom addons added.</p>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[1.2rem] font-medium">{{ _t('vendorprofilepages',
                                                'section_rental_conditions_benefits') }}</span>
                                            <span class="text-xs text-gray-500">Set per period</span>
                                        </div>
                                        <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(460px,1fr))]">
                                            <div class="rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="text-sm font-semibold text-gray-800">Per day</h4>
                                                    <span class="text-xs text-gray-400">Day</span>
                                                </div>
                                                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.limited_km_per_day"
                                                                id="limited_km_per_day" class="w-auto" />
                                                            <InputLabel for="limited_km_per_day" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_limited_km_per_day') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="limited_km_per_day_range"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_km_limit_per_day') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.limited_km_per_day_range"
                                                            id="limited_km_per_day_range" class="input-field"
                                                            :disabled="!form.benefits.limited_km_per_day"
                                                            :class="!form.benefits.limited_km_per_day ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.cancellation_available_per_day"
                                                                id="cancellation_available_per_day" class="w-auto" />
                                                            <InputLabel for="cancellation_available_per_day" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_cancellation_available_per_day') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="cancellation_available_per_day_date"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_cancellation_allowed_until_days') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.cancellation_available_per_day_date"
                                                            id="cancellation_available_per_day_date" min="0"
                                                            class="input-field"
                                                            :disabled="!form.benefits.cancellation_available_per_day"
                                                            :class="!form.benefits.cancellation_available_per_day ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <InputLabel for="price_per_km_per_day" class="mb-0">{{
                                                            _t('vendorprofilepages', 'label_price_per_km_per_day') }}</InputLabel>
                                                        <div class="input-with-suffix">
                                                            <Input type="number"
                                                                v-model.number="form.benefits.price_per_km_per_day"
                                                                id="price_per_km_per_day" min="0" step="0.01"
                                                                class="input-field" />
                                                            <span class="input-suffix">{{ currencyCode }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="text-sm font-semibold text-gray-800">Per week</h4>
                                                    <span class="text-xs text-gray-400">Week</span>
                                                </div>
                                                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.limited_km_per_week"
                                                                id="limited_km_per_week" class="w-auto" />
                                                            <InputLabel for="limited_km_per_week" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_limited_km_per_week') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="limited_km_per_week_range"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_km_limit_per_week') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.limited_km_per_week_range"
                                                            id="limited_km_per_week_range" class="input-field"
                                                            :disabled="!form.benefits.limited_km_per_week"
                                                            :class="!form.benefits.limited_km_per_week ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.cancellation_available_per_week"
                                                                id="cancellation_available_per_week" class="w-auto" />
                                                            <InputLabel for="cancellation_available_per_week" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_cancellation_available_per_week') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="cancellation_available_per_week_date"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_cancellation_allowed_until_weeks') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.cancellation_available_per_week_date"
                                                            id="cancellation_available_per_week_date" min="0"
                                                            class="input-field"
                                                            :disabled="!form.benefits.cancellation_available_per_week"
                                                            :class="!form.benefits.cancellation_available_per_week ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <InputLabel for="price_per_km_per_week" class="mb-0">{{
                                                            _t('vendorprofilepages', 'label_price_per_km_per_week') }}</InputLabel>
                                                        <div class="input-with-suffix">
                                                            <Input type="number"
                                                                v-model.number="form.benefits.price_per_km_per_week"
                                                                id="price_per_km_per_week" min="0" step="0.01"
                                                                class="input-field" />
                                                            <span class="input-suffix">{{ currencyCode }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="text-sm font-semibold text-gray-800">Per month</h4>
                                                    <span class="text-xs text-gray-400">Month</span>
                                                </div>
                                                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.limited_km_per_month"
                                                                id="limited_km_per_month" class="w-auto" />
                                                            <InputLabel for="limited_km_per_month" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_limited_km_per_month') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="limited_km_per_month_range"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_km_limit_per_month') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.limited_km_per_month_range"
                                                            id="limited_km_per_month_range" class="input-field"
                                                            :disabled="!form.benefits.limited_km_per_month"
                                                            :class="!form.benefits.limited_km_per_month ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                v-model="form.benefits.cancellation_available_per_month"
                                                                id="cancellation_available_per_month" class="w-auto" />
                                                            <InputLabel for="cancellation_available_per_month" class="mb-0">{{
                                                                _t('vendorprofilepages', 'label_cancellation_available_per_month') }}
                                                            </InputLabel>
                                                        </div>
                                                        <InputLabel for="cancellation_available_per_month_date"
                                                            class="text-xs text-gray-500 mb-0">{{ _t('vendorprofilepages',
                                                                'label_cancellation_allowed_until_months') }}</InputLabel>
                                                        <Input type="number"
                                                            v-model.number="form.benefits.cancellation_available_per_month_date"
                                                            id="cancellation_available_per_month_date" min="0"
                                                            class="input-field"
                                                            :disabled="!form.benefits.cancellation_available_per_month"
                                                            :class="!form.benefits.cancellation_available_per_month ? 'bg-gray-100' : ''" />
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <InputLabel for="price_per_km_per_month" class="mb-0">{{
                                                            _t('vendorprofilepages', 'label_price_per_km_per_month') }}</InputLabel>
                                                        <div class="input-with-suffix">
                                                            <Input type="number"
                                                                v-model.number="form.benefits.price_per_km_per_month"
                                                                id="price_per_km_per_month" min="0" step="0.01"
                                                                class="input-field" />
                                                            <span class="input-suffix">{{ currencyCode }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
                                            <div>
                                                <InputLabel for="minimum_driver_age">{{ _t('vendorprofilepages',
                                                    'label_minimum_driver_age') }}</InputLabel>
                                                <Input type="number" v-model.number="form.benefits.minimum_driver_age"
                                                    id="minimum_driver_age" min="18" required class="input-field" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <InputLabel for="payment_method">{{ _t('vendorprofilepages',
                                            'label_payment_methods') }}</InputLabel>
                                        <div class="flex flex-wrap items-center gap-4 mt-3 sm:gap-6">
                                            <label class="flex gap-2 items-center">
                                                <input type="checkbox" v-model="form.payment_method" value="credit_card"
                                                    class="w-auto" />
                                                {{ _t('vendorprofilepages', 'payment_method_credit_card') }}
                                            </label>
                                            <label class="flex gap-1 items-center">
                                                <input type="checkbox" v-model="form.payment_method" value="cheque"
                                                    class="w-auto" />
                                                {{ _t('vendorprofilepages', 'payment_method_cheque') }}
                                            </label>
                                            <label class="flex gap-1 items-center">
                                                <input type="checkbox" v-model="form.payment_method" value="bank_wire"
                                                    class="w-auto" />
                                                {{ _t('vendorprofilepages', 'payment_method_bank_wire') }}
                                            </label>
                                            <label class="flex gap-1 items-center">
                                                <input type="checkbox" v-model="form.payment_method"
                                                    value="cryptocurrency" class="w-auto" />
                                                {{ _t('vendorprofilepages', 'payment_method_cryptocurrency') }}
                                            </label>
                                            <label class="flex gap-1 items-center">
                                                <input type="checkbox" v-model="form.payment_method" value="cash"
                                                    class="w-auto" />
                                                {{ _t('vendorprofilepages', 'payment_method_cash') }}
                                            </label>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                                        <InputLabel for="features">{{ _t('vendorprofilepages', 'label_features') }}
                                        </InputLabel>
                                        <div class="flex flex-wrap gap-4 mt-3 sm:gap-6">
                                            <label v-for="feature in availableFeatures" :key="feature.id"
                                                class="flex items-center gap-2">
                                                <input type="checkbox" v-model="form.features" :value="feature.name"
                                                    class="w-auto" />
                                                <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.name"
                                                    class="w-4 h-4 mr-1 inline-block object-contain" />
                                                {{ feature.name }}
                                            </label>
                                        </div>
                                        <div v-if="!availableFeatures.length && form.category_id">
                                            <p class="text-gray-500">{{ _t('vendorprofilepages',
                                                'text_no_features_for_category') }}</p>
                                        </div>
                                        <div v-if="!form.category_id">
                                            <p class="text-gray-500">{{ _t('vendorprofilepages',
                                                'text_select_category_for_features') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="guidelines">
                                <div>
                                    <InputLabel for="guidelines">{{ _t('vendorprofilepages', 'label_guidelines') }}
                                    </InputLabel>
                                    <textarea type="text" v-model="form.guidelines" id="guidelines" required
                                        class="border p-2 rounded-lg w-full" />
                                </div>
                                <div class="time-selector p-6 bg-gray-50 rounded-xl shadow-lg w-full">
                                    <p>{{ _t('vendorprofilepages', 'text_choose_pickup_return_time') }}</p>
                                    <div class="grid gap-10 [grid-template-columns:repeat(auto-fit,minmax(280px,1fr))]">
                                        <div>
                                            <!-- Pickup Times Section -->
                                            <label class="block text-lg font-semibold text-gray-800 mb-2">{{
                                                _t('vendorprofilepages', 'label_pickup_times') }}</label>
                                            <div v-for="(time, index) in form.pickup_times" :key="'pickup-' + index"
                                                class="time-input-group flex items-center mb-3 gap-2">
                                                <input type="time" v-model="form.pickup_times[index]"
                                                    class="time-input max-[768px]:text-[0.75rem] flex-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white shadow-sm" />
                                                <Button type="button" @click="removePickupTime(index)"
                                                    :title="_t('vendorprofilepages', 'title_remove_time')"
                                                    variant="outline" size="sm"
                                                    class="text-red-600 hover:text-red-700 hover:bg-red-50">
                                                    <Trash2 class="w-4 h-4" />
                                                </Button>
                                            </div>

                                            <Button type="button" @click="addPickupTime" class="w-full">
                                                <Plus class="w-4 h-4 mr-2" />
                                                {{ _t('vendorprofilepages', 'button_add_pickup_time') }}
                                            </Button>
                                        </div>
                                        <div>
                                            <!-- Return Times Section -->
                                            <label class="block text-lg font-semibold text-gray-800 mb-2">{{
                                                _t('vendorprofilepages', 'label_return_times') }}</label>
                                            <div v-for="(time, index) in form.return_times" :key="'return-' + index"
                                                class="time-input-group flex items-center mb-3 gap-2">
                                                <input type="time" v-model="form.return_times[index]"
                                                    class="time-input max-[768px]:text-[0.75rem] flex-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white shadow-sm" />
                                                <Button type="button" @click="removeReturnTime(index)"
                                                    :title="_t('vendorprofilepages', 'title_remove_time')"
                                                    variant="outline" size="sm"
                                                    class="text-red-600 hover:text-red-700 hover:bg-red-50">
                                                    <Trash2 class="w-4 h-4" />
                                                </Button>
                                            </div>

                                            <Button type="button" @click="addReturnTime" class="w-full">
                                                <Plus class="w-4 h-4 mr-2" />
                                                {{ _t('vendorprofilepages', 'button_add_return_time') }}
                                            </Button>
                                        </div>
                                    </div>



                                </div>
                            </TabsContent>

                            <TabsContent value="images">
                                <div class="grid gap-6">
                                    <!-- Current Images -->
                                    <div
                                        v-if="props.vehicle && props.vehicle.images && props.vehicle.images.length > 0">
                                        <h3 class="font-medium text-lg mb-3">{{ _t('vendorprofilepages',
                                            'label_current_images') }}</h3>
                                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                                            <div v-for="image in props.vehicle.images" :key="image.id"
                                                class="relative group border rounded-lg overflow-hidden h-48">
                                                <img :src="`${image.image_url}`"
                                                    :alt="_t('vendorprofilepages', 'alt_vehicle_image')"
                                                    class="w-full h-full object-cover" />
                                                <div class="absolute top-0 right-0 p-1 flex gap-1">
                                                    <Button v-if="image.image_type !== 'primary'" type="button"
                                                        @click="setExistingImageAsPrimary(image.id)" size="sm"
                                                        class="bg-white text-blue-600 hover:bg-blue-50 rounded-full p-1 w-8 h-8"
                                                        :title="_t('vendorprofilepages', 'button_set_as_primary')">
                                                        <Star class="w-4 h-4" />
                                                    </Button>
                                                    <Button type="button"
                                                        @click="deleteImage(props.vehicle.id, image.id)" size="sm"
                                                        class="bg-white text-red-600 hover:bg-red-50 rounded-full p-1 w-8 h-8"
                                                        :title="_t('vendorprofilepages', 'title_delete_image')">
                                                        <Trash2 class="w-4 h-4" />
                                                    </Button>
                                                </div>
                                                <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white p-1 text-center text-xs"
                                                    :class="{ 'bg-blue-600/90': image.image_type === 'primary' }">
                                                    {{ image.image_type === 'primary' ? _t('vendorprofilepages',
                                                        'text_image_type_primary') : _t('vendorprofilepages',
                                                            'text_image_type_gallery') }}
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-gray-500 mt-2">
                                            {{ _t('vendorprofilepages', 'text_images_uploaded_count', {
                                                count:
                                                    props.vehicle.images.length
                                            }) }}
                                        </p>
                                    </div>

                                    <!-- Upload New Images -->
                                    <div
                                        v-if="!props.vehicle || !props.vehicle.images || props.vehicle.images.length < 20">
                                        <h3 class="font-medium text-lg mb-3">{{ _t('vendorprofilepages',
                                            'label_upload_new_images') }}</h3>
                                        <div class="border-2 border-dashed border-gray-300 p-6 rounded-lg text-center">
                                            <input type="file" ref="fileInput" multiple @change="handleFileUpload"
                                                accept="image/jpeg,image/png,image/jpg,image/gif" class="hidden" />
                                            <div @click="$refs.fileInput.click()" class="cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor"
                                                    class="w-12 h-12 mx-auto text-gray-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                                </svg>
                                                <p class="mt-2 text-sm text-gray-600">{{ _t('vendorprofilepages',
                                                    'text_click_or_drag_images') }}</p>
                                                <p class="text-xs text-gray-500 mt-1">{{ _t('vendorprofilepages',
                                                    'text_image_format_hint') }}</p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ _t('vendorprofilepages', 'text_files_selected_count', {
                                                        count:
                                                            selectedFiles.length
                                                    }) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-if="selectedFiles.length > 0" class="mt-4">
                                            <h4 class="font-medium text-sm mb-2">{{ _t('vendorprofilepages',
                                                'label_selected_files') }}</h4>
                                            <ul class="text-sm text-gray-600">
                                                <li v-for="(file, index) in selectedFiles" :key="index"
                                                    class="flex justify-between items-center py-1 border-b last:border-b-0">
                                                    <span class="truncate max-w-[200px]">{{ file.name }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <Button type="button" @click="setNewImageAsPrimary(index)"
                                                            size="sm"
                                                            :variant="form.primary_image_index === index ? 'default' : 'secondary'"
                                                            :disabled="form.primary_image_index === index"
                                                            :class="form.primary_image_index === index ? 'cursor-default' : ''">
                                                            <Star class="w-3 h-3 mr-1" />
                                                            {{ form.primary_image_index === index ?
                                                                _t('vendorprofilepages', 'text_image_type_primary') :
                                                                _t('vendorprofilepages', 'button_set_as_primary') }}
                                                        </Button>
                                                        <Button type="button" @click="removeFile(index)"
                                                            variant="destructive" size="sm">
                                                            <Trash2 class="w-3 h-3 mr-1" />
                                                            {{ _t('vendorprofilepages', 'button_remove_file') }}
                                                        </Button>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p v-else class="text-amber-600">
                                        {{ _t('vendorprofilepages', 'text_max_images_reached') }}
                                    </p>
                                </div>
                            </TabsContent>
                        </Tabs>

                        <div class="flex justify-between gap-4 mt-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                            <Button type="submit" :disabled="form.processing" class="flex items-center gap-2 px-6 py-3">
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'update_vehicle_button') }}
                            </Button>
                            <Link :href="route('current-vendor-vehicles.index', { locale: usePage().props.locale })">
                                <Button variant="outline" class="flex items-center gap-2 px-6 py-3">
                                    <X class="w-4 h-4" />
                                    {{ _t('vendorprofilepages', 'cancel_button') }}
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>


<script setup>
import { ref, onMounted, computed, watch, nextTick, getCurrentInstance, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import loader from "../../../../assets/loader.gif";

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import LocationPicker from '@/Components/LocationPicker.vue';
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import predefinedColors from '../../../data/colors.json'
import {
    Edit,
    X,
    Loader2,
    MapPin,
    Save,
    Plus,
    Trash2,
    Eye,
    Star,
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const { props } = usePage();
const fileInput = ref(null);
const selectedFiles = ref([]);
const maxImages = 20;
const isLoading = ref(false);
const allowFormSubmit = ref(true); // Flag to control form submission
const colors = ref(predefinedColors);
const formErrors = ref({});
const currencyCode = computed(() => props.auth?.user?.profile?.currency || props.currency || 'USD');

const selectedTypes = reactive({
    day: true,
    week: false,
    month: false
});

const createCoverageFields = () => Array.from({ length: 5 }, () => '');

const protectionPlans = reactive([
    { key: 'essential', plan_type: 'Essential', price: null, features: createCoverageFields(), selected: false },
    { key: 'premium', plan_type: 'Premium', price: null, features: createCoverageFields(), selected: false },
    { key: 'premium_plus', plan_type: 'Premium Plus', price: null, features: createCoverageFields(), selected: false }
]);

const planErrors = reactive({
    essential: '',
    premium: '',
    premium_plus: ''
});

const addons = ref([]);
const selectedAddons = ref([]);
const addonPrices = ref({});
const addonQuantities = ref({});
const existingAddonSelections = ref({});
const customAddons = ref([]);

const formatDate = (value) => {
    if (!value) {
        form.registration_date = null
        return
    }
    const date = new Date(value)
    form.registration_date = date.toISOString().split('T')[0] // Sets to 'YYYY-MM-DD'
}

watch(isLoading, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const form = useForm({
    category_id: null,
    brand: '',
    model: '',
    color: '',
    mileage: 0,
    transmission: 'manual',
    fuel: 'petrol',
    seating_capacity: 1,
    number_of_doors: 2,
    luggage_capacity: 0,
    horsepower: 0,
    co2: '',
    location: '',
    location_type: '',
    city: '',
    state: '',
    country: '',
    latitude: null,
    longitude: null,
    status: 'available',
    features: [],
    featured: false,
    security_deposit: 0.00,
    payment_method: [],
    guidelines: "",
    price_per_day: 0.00,
    price_per_week: 0.00,
    price_per_month: 0.00,
    weekly_discount: 0.00,
    monthly_discount: 0.00,
    preferred_price_type: 'day',
    registration_number: '',
    registration_country: '',
    registration_date: '',
    gross_vehicle_mass: 0,
    vehicle_height: 0.00,
    dealer_cost: 0.00,
    phone_number: '',
    images: [],
    pickup_times: [],
    return_times: [],
    benefits: {
        limited_km_per_day: false,
        limited_km_per_week: false,
        limited_km_per_month: false,
        limited_km_per_day_range: null,
        limited_km_per_week_range: null,
        limited_km_per_month_range: null,
        cancellation_available_per_day: false,
        cancellation_available_per_week: false,
        cancellation_available_per_month: false,
        cancellation_available_per_day_date: null,
        cancellation_available_per_week_date: null,
        cancellation_available_per_month_date: null,
        price_per_km_per_day: 0.00,
        price_per_km_per_week: 0.00,
        price_per_km_per_month: 0.00,
        minimum_driver_age: 18
    },
    selected_plans: [],
    selected_addons: [],
    addon_prices: {},
    addon_quantities: {},
    custom_addons: [],
    primary_image_index: null, // For new uploads
    existing_primary_image_id: null, // For existing images
    full_vehicle_address: '', // Initialize new field
})

const remainingImageSlots = computed(() => {
    if (!props.vehicle || !props.vehicle.images) return maxImages
    return Math.max(0, maxImages - props.vehicle.images.length)
})

const pricePerDay = computed(() => {
    const value = Number(form.price_per_day);
    return Number.isFinite(value) ? value : 0;
});

const toPrice = (value) => {
    const number = Number(value);
    if (!Number.isFinite(number)) {
        return null;
    }
    return Math.round(number * 100) / 100;
};

const normalizeFeatures = (features) => features
    .map(feature => `${feature}`.trim())
    .filter(Boolean)
    .slice(0, 5);

const isPlanActive = (plan) => plan.selected;

const validateProtectionPlans = () => {
    planErrors.essential = '';
    planErrors.premium = '';
    planErrors.premium_plus = '';

    const minPrice = pricePerDay.value;
    let isValid = true;

    protectionPlans.forEach(plan => {
        if (!isPlanActive(plan)) {
            return;
        }

        const priceValue = Number(plan.price);
        if (!Number.isFinite(priceValue) || priceValue <= 0) {
            planErrors[plan.key] = 'Price is required.';
            isValid = false;
            return;
        }

        if (priceValue < minPrice) {
            planErrors[plan.key] = `Price must be at least ${minPrice} ${currencyCode.value}.`;
            isValid = false;
        }
    });

    return isValid;
};

const togglePlanSelection = (plan) => {
    plan.selected = !plan.selected;

    if (!plan.selected) {
        plan.price = null;
        plan.features = createCoverageFields();
        planErrors[plan.key] = '';
    }
};

const buildSelectedPlans = () => {
    let planId = 1;
    return protectionPlans.reduce((plans, plan) => {
        if (!isPlanActive(plan)) {
            return plans;
        }

        plans.push({
            plan_id: planId,
            plan_type: plan.plan_type,
            plan_value: Number(plan.price),
            plan_description: null,
            features: normalizeFeatures(plan.features)
        });
        planId += 1;
        return plans;
    }, []);
};

const isAddonSelected = (addonId) => selectedAddons.value.includes(addonId);

const toggleAddonSelection = (addonId) => {
    const index = selectedAddons.value.indexOf(addonId);
    if (index >= 0) {
        selectedAddons.value.splice(index, 1);
    } else {
        selectedAddons.value.push(addonId);
        if (!addonQuantities.value[addonId]) {
            addonQuantities.value[addonId] = 1;
        }
    }
};

const incrementQuantity = (addonId) => {
    if (!isAddonSelected(addonId)) {
        return;
    }
    if (!addonQuantities.value[addonId]) {
        addonQuantities.value[addonId] = 1;
    }
    addonQuantities.value[addonId]++;
};

const decrementQuantity = (addonId) => {
    if (!isAddonSelected(addonId)) {
        return;
    }
    if (addonQuantities.value[addonId] > 1) {
        addonQuantities.value[addonId]--;
    }
};

const fetchAddons = async () => {
    try {
        const response = await axios.get('/api/booking-addons');
        addons.value = response.data;
        addons.value.forEach(addon => {
            const existing = existingAddonSelections.value[addon.id];
            addonPrices.value[addon.id] = existing?.price ?? addon.price ?? 0;
            addonQuantities.value[addon.id] = existing?.quantity ?? 1;
        });
        selectedAddons.value = Object.keys(existingAddonSelections.value).map(id => Number(id));
    } catch (error) {
        console.error('Error fetching addons:', error);
    }
};

const addCustomAddon = () => {
    customAddons.value.push({
        id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        extra_name: '',
        extra_type: '',
        description: '',
        price: 0,
        quantity: 1
    });
};

const removeCustomAddon = (addonId) => {
    customAddons.value = customAddons.value.filter(addon => addon.id !== addonId);
};

const buildCustomAddons = () => {
    return customAddons.value
        .map(addon => ({
            extra_name: `${addon.extra_name || ''}`.trim(),
            extra_type: `${addon.extra_type || ''}`.trim() || 'custom',
            description: `${addon.description || ''}`.trim(),
            price: addon.price,
            quantity: addon.quantity || 1
        }))
        .filter(addon => addon.extra_name || addon.description || Number(addon.price) > 0);
};

const validateCustomAddons = (payload) => {
    const errors = [];
    payload.forEach((addon, index) => {
        if (!addon.extra_name) {
            errors.push(`Custom addon ${index + 1}: name is required.`);
        }
        const priceValue = Number(addon.price);
        if (!Number.isFinite(priceValue) || priceValue < 0) {
            errors.push(`Custom addon ${index + 1}: price must be 0 or more.`);
        }
        const quantityValue = Number(addon.quantity);
        if (!Number.isFinite(quantityValue) || quantityValue < 1) {
            errors.push(`Custom addon ${index + 1}: quantity must be at least 1.`);
        }
    });
    return errors;
};

const categories = ref([]);
const availableFeatures = ref([]); // Will hold category-specific features
const addPickupTime = () => {
    form.pickup_times.push(""); // Push empty value for a new time input
};
const addReturnTime = () => {
    form.return_times.push(""); // Push empty value for a new time input
};

const removePickupTime = (index) => {
    form.pickup_times.splice(index, 1);
};
const removeReturnTime = (index) => {
    form.return_times.splice(index, 1);
};


const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/vehicle-categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching vehicle categories:', error)
    }
}

const fetchFeaturesForCategory = async (categoryId) => {
    if (!categoryId) {
        availableFeatures.value = [];
        // form.features = []; // Optionally clear selected features
        return;
    }
    try {
        const response = await axios.get(route('api.categories.features', categoryId));
        availableFeatures.value = response.data.map(feature => ({
            id: feature.id,
            name: feature.feature_name, // Ensure this matches the structure used in the template
            icon_url: feature.icon_url
        }));
    } catch (error) {
        console.error(`Error fetching vehicle features for category ${categoryId}:`, error);
        availableFeatures.value = [];
    }
};


const handleFileUpload = (event) => {
    const newFiles = Array.from(event.target.files);
    const currentImagesCount = props.vehicle?.images?.length || 0;
    const totalAfterAdding = currentImagesCount + selectedFiles.value.length + newFiles.length;

    if (totalAfterAdding > maxImages) {
        toast.error(`Maximum of ${maxImages} images allowed.`, { position: 'top-right' });
        return;
    }

    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    form.images = selectedFiles.value; // This form field is used for submitting NEW files
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    form.images = selectedFiles.value;
    if (form.primary_image_index === index) {
        form.primary_image_index = null; // Reset if primary was removed
    } else if (form.primary_image_index > index) {
        form.primary_image_index--; // Adjust index
    }
};

const setNewImageAsPrimary = (index) => {
    form.primary_image_index = index;
    form.existing_primary_image_id = null; // Clear existing primary if new one is chosen
};

const setExistingImageAsPrimary = (imageId) => {
    form.existing_primary_image_id = imageId;
    form.primary_image_index = null; // Clear new primary if existing one is chosen

    // Update local props.vehicle.images to reflect primary status for UI
    if (props.vehicle && props.vehicle.images) {
        props.vehicle.images.forEach(img => {
            img.image_type = img.id === imageId ? 'primary' : 'gallery';
        });
    }
};

const deleteImage = async (vehicleId, imageId) => {
    try {
        await axios.delete(route('current-vendor-vehicles.deleteImage', { vehicle: vehicleId, image: imageId }));

        // Remove image from local state
        const index = props.vehicle.images.findIndex(img => img.id === imageId);
        if (index !== -1) {
            props.vehicle.images.splice(index, 1);
        }

        toast.success('Image deleted successfully', {
            position: 'top-right',
            timeout: 3000
        });
    } catch (error) {
        console.error('Error deleting image:', error);
        toast.error('Failed to delete image', {
            position: 'top-right',
            timeout: 3000
        });
    }
};

// Handle location selection from LocationPicker
const handleLocationSelect = (locationData) => {
    form.location = locationData.address || locationData.formattedAddress || '';
    form.city = locationData.city || '';
    form.state = locationData.state || '';
    form.country = locationData.country || '';
    const parsedLatitude = Number(locationData.latitude);
    const parsedLongitude = Number(locationData.longitude);
    if (Number.isFinite(parsedLatitude)) {
        form.latitude = parsedLatitude;
    }
    if (Number.isFinite(parsedLongitude)) {
        form.longitude = parsedLongitude;
    }

    showLocationPicker.value = false; // Hide the picker component instance
    allowFormSubmit.value = true;    // Re-allow form submission
};

const ensurePreferredPriceType = () => {
    const allowedTypes = ['day'];
    if (selectedTypes.week) {
        allowedTypes.push('week');
    }
    if (selectedTypes.month) {
        allowedTypes.push('month');
    }
    if (!allowedTypes.includes(form.preferred_price_type)) {
        form.preferred_price_type = allowedTypes[0];
    }
};

watch(() => selectedTypes.week, (isEnabled) => {
    if (isEnabled) {
        if ((form.price_per_week === null || form.price_per_week === '') && form.price_per_day) {
            form.price_per_week = toPrice(Number(form.price_per_day) * 7);
        }
    } else {
        form.price_per_week = null;
        form.weekly_discount = null;
    }
    ensurePreferredPriceType();
});

watch(() => selectedTypes.month, (isEnabled) => {
    if (isEnabled) {
        if ((form.price_per_month === null || form.price_per_month === '') && form.price_per_day) {
            form.price_per_month = toPrice(Number(form.price_per_day) * 30);
        }
    } else {
        form.price_per_month = null;
        form.monthly_discount = null;
    }
    ensurePreferredPriceType();
});

watch(() => form.price_per_day, (newVal) => {
    if (!newVal) {
        return;
    }
    if (selectedTypes.week && (form.price_per_week === null || form.price_per_week === '')) {
        form.price_per_week = toPrice(Number(newVal) * 7);
    }
    if (selectedTypes.month && (form.price_per_month === null || form.price_per_month === '')) {
        form.price_per_month = toPrice(Number(newVal) * 30);
    }
});


// Watch checkbox changes to nullify associated values when unchecked
watch(() => form.benefits.limited_km_per_day, (newValue) => {
    if (!newValue) form.benefits.limited_km_per_day_range = null;
});
watch(() => form.benefits.limited_km_per_week, (newValue) => {
    if (!newValue) form.benefits.limited_km_per_week_range = null;
});
watch(() => form.benefits.limited_km_per_month, (newValue) => {
    if (!newValue) form.benefits.limited_km_per_month_range = null;
});
watch(() => form.benefits.cancellation_available_per_day, (newValue) => {
    if (!newValue) form.benefits.cancellation_available_per_day_date = null;
});
watch(() => form.benefits.cancellation_available_per_week, (newValue) => {
    if (!newValue) form.benefits.cancellation_available_per_week_date = null;
});
watch(() => form.benefits.cancellation_available_per_month, (newValue) => {
    if (!newValue) form.benefits.cancellation_available_per_month_date = null;
});


onMounted(() => {
    fetchCategories(); // Fetch all categories
    if (props.vehicle) {
        form.category_id = props.vehicle.category_id;
        if (form.category_id) {
            fetchFeaturesForCategory(form.category_id); // Fetch features for current category
        }
        form.brand = props.vehicle.brand;
        form.model = props.vehicle.model
        form.color = props.vehicle.color
        form.mileage = props.vehicle.mileage
        form.transmission = props.vehicle.transmission
        form.fuel = props.vehicle.fuel
        form.seating_capacity = props.vehicle.seating_capacity
        form.number_of_doors = props.vehicle.number_of_doors
        form.luggage_capacity = props.vehicle.luggage_capacity
        form.horsepower = props.vehicle.horsepower
        form.co2 = props.vehicle.co2
        form.location = props.vehicle.location || '';
        form.location_type = props.vehicle.location_type || '';
        form.city = props.vehicle.city || '';
        form.state = props.vehicle.state; // Correctly assign null if props.vehicle.state is null
        form.country = props.vehicle.country || '';
        const initialLatitude = Number(props.vehicle.latitude);
        const initialLongitude = Number(props.vehicle.longitude);
        form.latitude = Number.isFinite(initialLatitude) ? initialLatitude : null;
        form.longitude = Number.isFinite(initialLongitude) ? initialLongitude : null;
        form.full_vehicle_address = props.vehicle.full_vehicle_address || ''; // Populate from props
        form.status = props.vehicle.status;
        try {
            // Ensure features are parsed correctly, might be an array of names or empty
            const parsedFeatures = JSON.parse(props.vehicle.features);
            form.features = Array.isArray(parsedFeatures) ? parsedFeatures : [];
        } catch (e) {
            form.features = [];
        }
        form.featured = props.vehicle.featured;
        form.security_deposit = parseFloat(props.vehicle.security_deposit);
        try {
            form.payment_method = JSON.parse(props.vehicle.payment_method)
        } catch (e) {
            form.payment_method = []
        }
        const rawDaily = parseFloat(props.vehicle.price_per_day);
        const rawWeekly = parseFloat(props.vehicle.price_per_week);
        const rawMonthly = parseFloat(props.vehicle.price_per_month);
        const rawWeeklyDiscount = parseFloat(props.vehicle.weekly_discount);
        const rawMonthlyDiscount = parseFloat(props.vehicle.monthly_discount);

        form.price_per_day = Number.isFinite(rawDaily) ? rawDaily : 0.00
        form.price_per_week = Number.isFinite(rawWeekly) ? rawWeekly : null
        form.price_per_month = Number.isFinite(rawMonthly) ? rawMonthly : null
        form.weekly_discount = Number.isFinite(rawWeeklyDiscount) ? rawWeeklyDiscount : null
        form.monthly_discount = Number.isFinite(rawMonthlyDiscount) ? rawMonthlyDiscount : null
        form.preferred_price_type = props.vehicle.preferred_price_type

        selectedTypes.week = Number.isFinite(rawWeekly) && rawWeekly > 0;
        selectedTypes.month = Number.isFinite(rawMonthly) && rawMonthly > 0;
        if (!selectedTypes.week) {
            form.price_per_week = null;
            form.weekly_discount = null;
        }
        if (!selectedTypes.month) {
            form.price_per_month = null;
            form.monthly_discount = null;
        }
        ensurePreferredPriceType();
        form.guidelines = props.vehicle.guidelines;

        // Set existing primary image ID if one exists
        const primaryExistingImage = props.vehicle.images?.find(img => img.image_type === 'primary');
        if (primaryExistingImage) {
            form.existing_primary_image_id = primaryExistingImage.id;
        }


        // Handle benefits
        if (props.vehicle.benefits) {
            form.benefits = {
                limited_km_per_day: !!props.vehicle.benefits.limited_km_per_day,
                limited_km_per_week: !!props.vehicle.benefits.limited_km_per_week,
                limited_km_per_month: !!props.vehicle.benefits.limited_km_per_month,
                limited_km_per_day_range: props.vehicle.benefits.limited_km_per_day_range || null,
                limited_km_per_week_range: props.vehicle.benefits.limited_km_per_week_range || null,
                limited_km_per_month_range: props.vehicle.benefits.limited_km_per_month_range || null,
                cancellation_available_per_day: !!props.vehicle.benefits.cancellation_available_per_day,
                cancellation_available_per_week: !!props.vehicle.benefits.cancellation_available_per_week,
                cancellation_available_per_month: !!props.vehicle.benefits.cancellation_available_per_month,
                cancellation_available_per_day_date: props.vehicle.benefits.cancellation_available_per_day_date || null,
                cancellation_available_per_week_date: props.vehicle.benefits.cancellation_available_per_week_date || null,
                cancellation_available_per_month_date: props.vehicle.benefits.cancellation_available_per_month_date || null,
                price_per_km_per_day: parseFloat(props.vehicle.benefits.price_per_km_per_day) || 0.00,
                price_per_km_per_week: parseFloat(props.vehicle.benefits.price_per_km_per_week) || 0.00,
                price_per_km_per_month: parseFloat(props.vehicle.benefits.price_per_km_per_month) || 0.00,
                minimum_driver_age: props.vehicle.benefits.minimum_driver_age || 18
            };
        }

        if (props.vehicle.latitude && props.vehicle.longitude) {
            handleLocationSelect({
                address: props.vehicle.location,
                city: props.vehicle.city,
                state: props.vehicle.state,
                country: props.vehicle.country,
                latitude: props.vehicle.latitude,
                longitude: props.vehicle.longitude,
            });
        }

        if (props.vehicle.specifications) {
            form.registration_number = props.vehicle.specifications.registration_number
            form.registration_country = props.vehicle.specifications.registration_country
            form.registration_date = props.vehicle.specifications.registration_date
            form.gross_vehicle_mass = props.vehicle.specifications.gross_vehicle_mass
            form.vehicle_height = parseFloat(props.vehicle.specifications.vehicle_height) || 0.00
            form.dealer_cost = parseFloat(props.vehicle.specifications.dealer_cost) || 0.00
            form.phone_number = props.vehicle.specifications.phone_number
        }

        const existingPlans = props.vehicle.vendorPlans || props.vehicle.vendor_plans || [];
        existingPlans.forEach((plan) => {
            const planType = `${plan.plan_type || ''}`.toLowerCase();
            const match = protectionPlans.find(entry => entry.plan_type.toLowerCase() === planType);
            if (!match) {
                return;
            }
            match.selected = true;
            match.price = parseFloat(plan.price) || 0;
            let planFeatures = [];
            if (Array.isArray(plan.features)) {
                planFeatures = plan.features;
            } else if (typeof plan.features === 'string') {
                try {
                    planFeatures = JSON.parse(plan.features || '[]');
                } catch (error) {
                    planFeatures = [];
                }
            }
            match.features = Array.isArray(planFeatures) && planFeatures.length
                ? [...planFeatures, ...createCoverageFields()].slice(0, 5)
                : createCoverageFields();
        });

        const existingAddons = props.vehicle.addons || [];
        existingAddons.forEach((addon) => {
            const addonId = addon.addon_id ?? addon.addon?.id ?? addon.id;
            if (!addonId) {
                return;
            }
            existingAddonSelections.value[addonId] = {
                price: parseFloat(addon.price) || 0,
                quantity: parseInt(addon.quantity, 10) || 1
            };
        });
        if (props.vehicle.pickup_times && typeof props.vehicle.pickup_times === 'string') {
            form.pickup_times = props.vehicle.pickup_times.split(',').filter(Boolean);
        } else if (Array.isArray(props.vehicle.pickup_times)) {
            form.pickup_times = [...props.vehicle.pickup_times];
        } else {
            form.pickup_times = ["09:00"]; // Default time
        }

        // Handle return_times similarly
        if (props.vehicle.return_times && typeof props.vehicle.return_times === 'string') {
            form.return_times = props.vehicle.return_times.split(',').filter(Boolean);
        } else if (Array.isArray(props.vehicle.return_times)) {
            form.return_times = [...props.vehicle.return_times];
        } else {
            form.return_times = ["17:00"]; // Default time
        }
    }

    fetchAddons();

    // Log the form data to the console
    console.log('Form Data:', form);
})

const displayedFullAddress = computed(() => {
    const parts = [form.location, form.city, form.state, form.country];
    return parts.filter(part => part !== null && part !== '').join(', ');
});

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'available': return 'default';
        case 'rented': return 'secondary';
        case 'maintenance': return 'destructive';
        default: return 'outline';
    }
};

const updateVehicle = () => {
    if (!allowFormSubmit.value) {
        // console.warn('Form submission blocked because LocationPicker is active or was just interacted with.');
        return;
    }
    isLoading.value = true;
    formErrors.value = {};

    // Construct full_vehicle_address before getting form.data()
    const addressParts = [form.location, form.city, form.state, form.country];
    form.full_vehicle_address = addressParts.filter(Boolean).join(', ');

    if (!validateProtectionPlans()) {
        isLoading.value = false;
        toast.error('Please complete the selected protection plans.', { position: 'top-right', timeout: 3000 });
        return;
    }

    const customAddonsPayload = buildCustomAddons();
    const customAddonErrors = validateCustomAddons(customAddonsPayload);
    if (customAddonErrors.length) {
        isLoading.value = false;
        formErrors.value = { custom_addons: customAddonErrors };
        toast.error(customAddonErrors[0], { position: 'top-right', timeout: 4000 });
        return;
    }

    form.selected_plans = buildSelectedPlans();
    form.selected_addons = selectedAddons.value;
    form.addon_prices = addonPrices.value;
    form.addon_quantities = addonQuantities.value;
    form.custom_addons = customAddonsPayload;

    // Get a plain JS object of the form data to modify for submission
    let submitData = form.data();

    // Define numeric fields at the top level that should be null if their value is 0
    const numericFieldsToNullifyIfZero = [
        'mileage', 'luggage_capacity', 'horsepower', 'security_deposit',
        'price_per_day', 'price_per_week', 'price_per_month',
        'weekly_discount', 'monthly_discount', 'gross_vehicle_mass',
        'vehicle_height', 'dealer_cost'
    ];

    numericFieldsToNullifyIfZero.forEach(key => {
        if (submitData.hasOwnProperty(key) && Number(submitData[key]) === 0) {
            submitData[key] = null;
        }
    });

    // Define numeric fields within 'benefits' that should be null if their value is 0
    // This also includes fields that are set to null by watch functions if their checkbox is false
    const benefitNumericFieldsToNullifyIfZeroOrAlreadyNull = [
        'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
        'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
        'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month'
    ];

    if (submitData.benefits) {
        benefitNumericFieldsToNullifyIfZeroOrAlreadyNull.forEach(key => {
            // If it's already null (e.g., from a watch function), keep it null.
            // If it's 0 (e.g., from an empty v-model.number input), make it null.
            if (submitData.benefits.hasOwnProperty(key)) {
                if (submitData.benefits[key] === null) {
                    // Already null, do nothing
                } else if (Number(submitData.benefits[key]) === 0) {
                    submitData.benefits[key] = null;
                }
            }
        });
    }

    // Ensure specific top-level fields are numbers if they are not null and not empty strings
    const fieldsToEnsureNumber = [
        'mileage', 'luggage_capacity', 'horsepower', 'security_deposit',
        'price_per_day', 'price_per_week', 'price_per_month',
        'weekly_discount', 'monthly_discount', 'gross_vehicle_mass',
        'vehicle_height', 'dealer_cost', 'seating_capacity', 'number_of_doors',
        'latitude', 'longitude'
    ];

    fieldsToEnsureNumber.forEach(key => {
        if (submitData.hasOwnProperty(key) && submitData[key] !== null && submitData[key] !== '') {
            submitData[key] = Number(submitData[key]);
        }
    });

    // Ensure specific fields within 'benefits' are numbers if they are not null and not empty strings
    if (submitData.benefits) {
        const benefitFieldsToEnsureNumber = [
            'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
            'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
            'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month',
            'minimum_driver_age'
        ];
        benefitFieldsToEnsureNumber.forEach(key => {
            if (submitData.benefits.hasOwnProperty(key) && submitData.benefits[key] !== null && submitData.benefits[key] !== '') {
                submitData.benefits[key] = Number(submitData.benefits[key]);
            }
        });
    }

    let formData = new FormData();

    // Append benefits data
    if (submitData.benefits) {
        Object.keys(submitData.benefits).forEach(key => {
            const value = submitData.benefits[key];
            if (typeof value === 'boolean') {
                formData.append(`benefits[${key}]`, value ? '1' : '0');
            } else {
                // Send empty string for null, otherwise send the value
                formData.append(`benefits[${key}]`, value !== null ? value : '');
            }
        });
    }

    // Append other form data
    for (const key in submitData) {
        // Ensure we only append actual data properties, not nested/grouped fields handled separately
        if (!submitData.hasOwnProperty(key)) {
            continue;
        }
        if (['benefits', 'images', 'selected_plans', 'selected_addons', 'addon_prices', 'addon_quantities', 'custom_addons'].includes(key)) {
            continue;
        }
        const value = submitData[key];
        if (Array.isArray(value)) {
            value.forEach(item => formData.append(`${key}[]`, item !== null ? item : ''));
        } else {
            // Send empty string for null, otherwise send the value
            formData.append(key, value !== null ? value : '');
        }
    }

    if (Array.isArray(submitData.selected_plans)) {
        submitData.selected_plans.forEach((plan, index) => {
            formData.append(`selected_plans[${index}][plan_type]`, plan.plan_type ?? '');
            formData.append(`selected_plans[${index}][plan_value]`, plan.plan_value ?? '');
            if (plan.plan_description) {
                formData.append(`selected_plans[${index}][plan_description]`, plan.plan_description);
            }
            if (Array.isArray(plan.features)) {
                plan.features.forEach((feature, featureIndex) => {
                    formData.append(`selected_plans[${index}][features][${featureIndex}]`, feature);
                });
            }
        });
    }

    if (Array.isArray(submitData.selected_addons)) {
        submitData.selected_addons.forEach(addonId => {
            formData.append('selected_addons[]', addonId);
        });
    }

    if (submitData.addon_prices && typeof submitData.addon_prices === 'object') {
        Object.entries(submitData.addon_prices).forEach(([addonId, price]) => {
            formData.append(`addon_prices[${addonId}]`, price ?? '');
        });
    }

    if (submitData.addon_quantities && typeof submitData.addon_quantities === 'object') {
        Object.entries(submitData.addon_quantities).forEach(([addonId, quantity]) => {
            formData.append(`addon_quantities[${addonId}]`, quantity ?? '');
        });
    }

    if (Array.isArray(submitData.custom_addons)) {
        submitData.custom_addons.forEach((addon, index) => {
            formData.append(`custom_addons[${index}][extra_name]`, addon.extra_name ?? '');
            formData.append(`custom_addons[${index}][extra_type]`, addon.extra_type ?? 'custom');
            formData.append(`custom_addons[${index}][description]`, addon.description ?? '');
            formData.append(`custom_addons[${index}][price]`, addon.price ?? '');
            formData.append(`custom_addons[${index}][quantity]`, addon.quantity ?? 1);
        });
    }

    // Append new image files
    selectedFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
    });

    // For Laravel to treat POST as PUT when FormData is used
    formData.append('_method', 'PUT');

    axios.post(route('current-vendor-vehicles.update', props.vehicle.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
    })
        .then((response) => {
            isLoading.value = false;
            selectedFiles.value = []; // Reset selected files after successful upload
            const updatedVehicle = response?.data?.vehicle;
            if (updatedVehicle && props.vehicle) {
                Object.assign(props.vehicle, updatedVehicle);
                props.vehicle.images = updatedVehicle.images || props.vehicle.images || [];
                props.vehicle.benefits = updatedVehicle.benefits || props.vehicle.benefits || null;
                props.vehicle.specifications = updatedVehicle.specifications || props.vehicle.specifications || null;
                props.vehicle.vendorPlans = updatedVehicle.vendorPlans || updatedVehicle.vendor_plans || [];
                props.vehicle.addons = updatedVehicle.addons || [];
                existingAddonSelections.value = {};
                (props.vehicle.addons || []).forEach((addon) => {
                    const addonId = addon.addon_id ?? addon.addon?.id ?? addon.id;
                    if (!addonId) {
                        return;
                    }
                    existingAddonSelections.value[addonId] = {
                        price: parseFloat(addon.price) || 0,
                        quantity: parseInt(addon.quantity, 10) || 1
                    };
                });
                customAddons.value = [];
                fetchAddons();
            }
            toast.success('Vehicle updated successfully!', { position: 'top-right', timeout: 1000 });
        })
        .catch(error => {
            isLoading.value = false;
            const responseErrors = error?.response?.data?.errors;
            if (responseErrors) {
                formErrors.value = responseErrors;
                const firstError = Object.values(responseErrors)[0];
                const message = Array.isArray(firstError) ? firstError[0] : firstError;
                toast.error(message || 'Please review the highlighted fields.', {
                    position: 'top-right',
                    timeout: 4000
                });
                return;
            }
            toast.error('Something went wrong.', { position: 'top-right', timeout: 3000 });
            // console.error('Error updating vehicle:', error.response ? error.response.data : error);
        });
};

const countries = ref([]);


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


const showLocationPicker = ref(false);

// Add this method to force map resize
const forceMapResize = () => {
    nextTick(() => {
        setTimeout(() => {
            const mapElement = document.querySelector('.leaflet-container')
            if (mapElement && window.L) {
                // Trigger map resize
                window.dispatchEvent(new Event('resize'))
                // Get the map instance and invalidate its size
                const map = window.L.map(mapElement)
                map.invalidateSize()
                // Reset the view to ensure it's properly centered
                const currentCenter = map.getCenter()
                map.setView(currentCenter, map.getZoom())
            }
        }, 300)
    })
}

// Watch for location picker visibility changes
watch(showLocationPicker, (newVal) => {
    if (newVal) {
        forceMapResize()
    }
})

// Watch for category_id changes to fetch new features
watch(() => form.category_id, (newCategoryId, oldCategoryId) => {
    if (newCategoryId !== oldCategoryId) {
        fetchFeaturesForCategory(newCategoryId);
        // Optionally clear selected features if category changes,
        // or backend should handle features not belonging to the new category.
        // form.features = []; 
    }
});

const toggleLocationPicker = () => {
    showLocationPicker.value = !showLocationPicker.value;
    if (showLocationPicker.value) {
        allowFormSubmit.value = false; // Disallow form submission when picker is shown
        forceMapResize();
    } else {
        allowFormSubmit.value = true; // Re-allow when picker is hidden by this toggle
    }
}

</script>

<style scoped>
select {
    width: 100%;
}

label {
    margin-bottom: 0.5rem;
}

input {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--custom-gray-color);
    border: 1px solid var(--custom-light-gray);
    border-radius: 8px;
}

input[type="checkbox"] {
    width: auto;
    padding: 0;
}

.input-field {
    padding: 0.6rem 0.75rem;
}

.input-with-suffix {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-with-suffix input {
    flex: 1 1 auto;
    min-width: 0;
}

.input-suffix {
    display: inline-flex;
    align-items: center;
    padding: 0.1rem 0.5rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    color: #475569;
    font-weight: 600;
    background: #ffffff;
    font-size: 0.7rem;
    white-space: nowrap;
}

.inline-currency {
    display: inline-flex;
    align-items: center;
    padding: 0.1rem 0.5rem;
    margin-left: 0.25rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    color: #475569;
    font-weight: 600;
    background: #ffffff;
    font-size: 0.7rem;
    white-space: nowrap;
}

select {
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1rem;
    border: 1px solid #2b2b2b4a;
    outline: none;
    border-radius: 8px;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

.slide-fade-enter-to,
.slide-fade-leave-from {
    transform: translateY(0);
    opacity: 1;
}
</style>
