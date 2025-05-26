<template>
    <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e]">
        <div class="flex justify-center flex-col items-center h-full w-full">
            <img :src=loader alt="" class="w-[150px]">
            <p class="text-[white] text-[1.5rem]">{{ _t('vendorprofilepages', 'loader_updating_text') }}</p>
        </div>
    </div>
    <MyProfileLayout>
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">{{ _t('vendorprofilepages', 'edit_vehicle_header') }}</h2>
        </div>
        <div class="py-12">
            <div class="mx-auto">
                <form @submit.prevent="updateVehicle">
                    <Tabs defaultValue="basic" class="w-full">
                        <TabsList class="grid w-full grid-cols-5">
                            <TabsTrigger value="basic">{{ _t('vendorprofilepages', 'tab_basic_information') }}</TabsTrigger>
                            <TabsTrigger value="specifications">{{ _t('vendorprofilepages', 'tab_specifications') }}</TabsTrigger>
                            <TabsTrigger value="pricing">{{ _t('vendorprofilepages', 'tab_pricing_features') }}</TabsTrigger>
                            <TabsTrigger value="guidelines">{{ _t('vendorprofilepages', 'tab_guidelines_timings') }}</TabsTrigger>
                            <TabsTrigger value="images">{{ _t('vendorprofilepages', 'tab_images') }}</TabsTrigger>
                        </TabsList>

                        <TabsContent value="basic">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="category_id">{{ _t('vendorprofilepages', 'label_vehicle_category') }}</InputLabel>
                                    <Select v-model="form.category_id" required>
                                        <SelectTrigger id="category_id">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_category')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_categories') }}</SelectLabel>
                                                <SelectItem v-for="category in categories" :key="category.id"
                                                    :value="category.id">
                                                    {{ category.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="brand">{{ _t('vendorprofilepages', 'label_brand') }}</InputLabel>
                                    <Input type="text" v-model="form.brand" id="brand" required />
                                </div>
                                <div>
                                    <InputLabel for="model">{{ _t('vendorprofilepages', 'label_model') }}</InputLabel>
                                    <Input type="text" v-model="form.model" id="model" required />
                                </div>
                                <div>
                                    <InputLabel for="color">{{ _t('vendorprofilepages', 'label_color') }}</InputLabel>
                                    <Input type="text" v-model="form.color" id="color" required />
                                </div>
                                <div>
                                    <InputLabel for="mileage">{{ _t('vendorprofilepages', 'label_mileage') }}</InputLabel>
                                    <Input type="number" step="0.01" v-model.number="form.mileage" id="mileage"
                                        required />
                                </div>
                                <div>
                                    <InputLabel for="transmission">{{ _t('vendorprofilepages', 'label_transmission_select') }}</InputLabel>
                                    <Select v-model="form.transmission" required>
                                        <SelectTrigger id="transmission">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_transmission')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_transmission') }}</SelectLabel>
                                                <SelectItem value="manual">{{ _t('vendorprofilepages', 'transmission_manual') }}</SelectItem>
                                                <SelectItem value="automatic">{{ _t('vendorprofilepages', 'transmission_automatic') }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="fuel">{{ _t('vendorprofilepages', 'label_fuel_select') }}</InputLabel>
                                    <Select v-model="form.fuel" required>
                                        <SelectTrigger id="fuel">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_fuel')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_fuel') }}</SelectLabel>
                                                <SelectItem value="petrol">{{ _t('vendorprofilepages', 'fuel_petrol') }}</SelectItem>
                                                <SelectItem value="diesel">{{ _t('vendorprofilepages', 'fuel_diesel') }}</SelectItem>
                                                <SelectItem value="electric">{{ _t('vendorprofilepages', 'fuel_electric') }}</SelectItem>
                                                <SelectItem value="hybrid">{{ _t('vendorprofilepages', 'fuel_hybrid') }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="status">{{ _t('vendorprofilepages', 'label_status_select') }}</InputLabel>
                                    <Select v-model="form.status" required>
                                        <SelectTrigger id="status">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_status')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_status') }}</SelectLabel>
                                                <SelectItem value="available">{{ _t('vendorprofilepages', 'status_available') }}</SelectItem>
                                                <SelectItem value="rented">{{ _t('vendorprofilepages', 'status_rented') }}</SelectItem>
                                                <SelectItem value="maintenance">{{ _t('vendorprofilepages', 'maintenance_vehicles_card_title') }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="col-span-2 space-y-3">
                                    <InputLabel for="location" class="text-gray-700 font-medium">{{ _t('vendorprofilepages', 'label_location') }}</InputLabel>

                                    <div v-if="form.location"
                                        class="p-4 bg-gray-50 rounded-lg border border-gray-200 shadow-sm">
                                        <div class="grid grid-cols-2 gap-2 text-[1rem]">
                                            <div class="col-span-2 font-medium text-gray-700">{{ _t('vendorprofilepages', 'current_location_label') }} {{
                                                form.location }}, {{ form.city }}, {{ form.state }}, {{ form.country }}
                                            </div>
                                            <div><span class="text-gray-500">{{ _t('vendorprofilepages', 'label_city') }}</span> {{ form.city }}</div>
                                            <div><span class="text-gray-500">{{ _t('vendorprofilepages', 'label_state') }}</span> {{ form.state }}</div>
                                            <div><span class="text-gray-500">{{ _t('vendorprofilepages', 'label_country') }}</span> {{ form.country }}</div>
                                            <div class=""><span class="text-gray-500">{{ _t('vendorprofilepages', 'label_coordinates') }}</span> {{
                                                form.latitude }}, {{ form.longitude }}</div>
                                        </div>
                                    </div>

                                    <button type="button" @click="toggleLocationPicker"
                                        class="flex items-center justify-center w-full sm:w-auto px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        {{ showLocationPicker ? _t('vendorprofilepages', 'button_hide_location_picker') : _t('vendorprofilepages', 'button_change_location') }}
                                    </button>

                                    <transition name="slide-fade" enter-active-class="transition ease-out duration-200"
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
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="specifications">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="seating_capacity">{{ _t('vendorprofilepages', 'label_seating_capacity') }}</InputLabel>
                                    <Select v-model.number="form.seating_capacity" required>
                                        <SelectTrigger id="seating_capacity">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_seating_capacity')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_seating_capacity') }}</SelectLabel>
                                                <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="number_of_doors">{{ _t('vendorprofilepages', 'label_number_of_doors') }}</InputLabel>
                                    <Select v-model.number="form.number_of_doors" required>
                                        <SelectTrigger id="number_of_doors">
                                            <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_number_of_doors')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{{ _t('vendorprofilepages', 'select_label_number_of_doors') }}</SelectLabel>
                                                <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="luggage_capacity">{{ _t('vendorprofilepages', 'label_luggage_capacity') }}</InputLabel>
                                    <Input type="number" v-model.number="form.luggage_capacity" id="luggage_capacity"
                                        required />
                                </div>
                                <div>
                                    <InputLabel for="horsepower">{{ _t('vendorprofilepages', 'label_horsepower') }}</InputLabel>
                                    <Input type="number" step="0.01" v-model.number="form.horsepower" id="horsepower"
                                        required />
                                </div>
                                <div>
                                    <InputLabel for="co2">{{ _t('vendorprofilepages', 'label_co2_emissions') }}</InputLabel>
                                    <Input type="text" v-model="form.co2" id="co2" required />
                                </div>
                                <div>
                                    <InputLabel for="registration_number">{{ _t('vendorprofilepages', 'label_registration_number') }}</InputLabel>
                                    <Input type="text" v-model="form.registration_number" id="registration_number"
                                        required />
                                </div>
                                <div class="relative w-full">
                                    <InputLabel class="text-black" for="registration_country">{{ _t('vendorprofilepages', 'label_registration_country') }}
                                    </InputLabel>

                                    <div class="relative">
                                        <Select v-model="form.registration_country">
                                            <SelectTrigger
                                                class="w-full p-[1.5rem] border-customLightGrayColor rounded-[12px]">
                                                <SelectValue :placeholder="_t('vendorprofilepages', 'placeholder_select_country')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>{{ _t('vendorprofilepages', 'select_label_countries') }}</SelectLabel>
                                                    <SelectItem v-for="country in countries" :key="country.code"
                                                        :value="country.code">
                                                        {{ country.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>

                                        <!-- Dynamic Flag -->
                                        <img v-if="form.registration_country"
                                            :src="getFlagUrl(form.registration_country)" :alt="_t('vendorprofilepages', 'alt_country_flag')"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-[2.1rem] h-[1.5rem] rounded" />
                                    </div>
                                </div>
                                <div>
                            <InputLabel class="text-black" for="registration_date">{{ _t('vendorprofilepages', 'label_registration_date') }}</InputLabel>
                            <VueDatePicker v-model="form.registration_date" :format="'yyyy-MM-dd'" auto-apply
                                :placeholder="_t('vendorprofilepages', 'placeholder_select_registration_date')" class="w-full"
                                 :clearable="false"
                                :max-date="new Date()"
                                :input-class-name="'w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm text-gray-700'"
                                @update:modelValue="formatDate" required />
                            
                        </div>
                                <div>
                                    <InputLabel for="gross_vehicle_mass">{{ _t('vendorprofilepages', 'label_gross_vehicle_mass') }}</InputLabel>
                                    <Input type="number" v-model.number="form.gross_vehicle_mass"
                                        id="gross_vehicle_mass" required />
                                </div>
                                <div>
                                    <InputLabel for="vehicle_height">{{ _t('vendorprofilepages', 'label_vehicle_height') }}</InputLabel>
                                    <Input type="number" v-model.number="form.vehicle_height" id="vehicle_height"
                                         step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="phone_number">{{ _t('vendorprofilepages', 'label_phone_number_vehicle') }}</InputLabel>
                                    <Input type="text" v-model="form.phone_number" id="phone_number" required />
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="pricing">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="security_deposit">{{ _t('vendorprofilepages', 'label_security_deposit') }}</InputLabel>
                                    <Input type="number" v-model.number="form.security_deposit" id="security_deposit"
                                        required min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="dealer_cost">{{ _t('vendorprofilepages', 'label_dealer_cost') }}</InputLabel>
                                    <Input type="number" v-model.number="form.dealer_cost" id="dealer_cost"
                                        step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_day">{{ _t('vendorprofilepages', 'label_price_per_day') }}</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_day" id="price_per_day"
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_week">{{ _t('vendorprofilepages', 'label_price_per_week') }}</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_week" id="price_per_week"
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_month">{{ _t('vendorprofilepages', 'label_price_per_month') }}</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_month" id="price_per_month"
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="weekly_discount">{{ _t('vendorprofilepages', 'label_weekly_discount') }}</InputLabel>
                                    <Input type="number" v-model.number="form.weekly_discount" id="weekly_discount"
                                        min="0" max="1000.00" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="monthly_discount">{{ _t('vendorprofilepages', 'label_monthly_discount') }}</InputLabel>
                                    <Input type="number" v-model.number="form.monthly_discount" id="monthly_discount"
                                        min="0" max="10000.00" step="0.01" />
                                </div>

                                <!-- New fields -->
                                <div class="flex gap-3 flex-col col-span-2">
                                    <span class="text-[1.2rem] font-medium">{{ _t('vendorprofilepages', 'section_rental_conditions_benefits') }}</span>

                                    <!-- Limited KM Per Day -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_day"
                                            id="limited_km_per_day" class="w-auto" />
                                        <InputLabel for="limited_km_per_day" class="mb-0">{{ _t('vendorprofilepages', 'label_limited_km_per_day') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_day" class="w-[50%]">
                                        <InputLabel for="limited_km_per_day_range">{{ _t('vendorprofilepages', 'label_km_limit_per_day') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_day_range"
                                            id="limited_km_per_day_range" />
                                    </div>

                                    <!-- Limited KM Per Week -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_week"
                                            id="limited_km_per_week" class="w-auto" />
                                        <InputLabel for="limited_km_per_week" class="mb-0">{{ _t('vendorprofilepages', 'label_limited_km_per_week') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_week" class="w-[50%]">
                                        <InputLabel for="limited_km_per_week_range">{{ _t('vendorprofilepages', 'label_km_limit_per_week') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_week_range"
                                            id="limited_km_per_week_range" />
                                    </div>

                                    <!-- Limited KM Per Month -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_month"
                                            id="limited_km_per_month" class="w-auto" />
                                        <InputLabel for="limited_km_per_month" class="mb-0">{{ _t('vendorprofilepages', 'label_limited_km_per_month') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_month" class="w-[50%]">
                                        <InputLabel for="limited_km_per_month_range">{{ _t('vendorprofilepages', 'label_km_limit_per_month') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_month_range"
                                            id="limited_km_per_month_range" />
                                    </div>

                                    <!-- Cancellation Available Per Day -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_day"
                                            id="cancellation_available_per_day" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_day" class="mb-0">{{ _t('vendorprofilepages', 'label_cancellation_available_per_day') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_day" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_day_date">{{ _t('vendorprofilepages', 'label_cancellation_allowed_until_days') }}
                                        </InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_day_date"
                                            id="cancellation_available_per_day_date" min="0" />
                                    </div>

                                    <!-- Cancellation Available Per Week -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_week"
                                            id="cancellation_available_per_week" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_week" class="mb-0">{{ _t('vendorprofilepages', 'label_cancellation_available_per_week') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_week" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_week_date">{{ _t('vendorprofilepages', 'label_cancellation_allowed_until_weeks') }}
                                        </InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_week_date"
                                            id="cancellation_available_per_week_date" min="0" />
                                    </div>

                                    <!-- Cancellation Available Per Month -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_month"
                                            id="cancellation_available_per_month" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_month" class="mb-0">{{ _t('vendorprofilepages', 'label_cancellation_available_per_month') }}
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_month" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_month_date">{{ _t('vendorprofilepages', 'label_cancellation_allowed_until_months') }}
                                        </InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_month_date"
                                            id="cancellation_available_per_month_date" min="0" />
                                    </div>

                                    <!-- Price Per KM -->
                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_day">{{ _t('vendorprofilepages', 'label_price_per_km_per_day') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_day"
                                            id="price_per_km_per_day" min="0" step="0.01" />
                                    </div>

                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_week">{{ _t('vendorprofilepages', 'label_price_per_km_per_week') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_week"
                                            id="price_per_km_per_week" min="0" step="0.01" />
                                    </div>

                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_month">{{ _t('vendorprofilepages', 'label_price_per_km_per_month') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_month"
                                            id="price_per_km_per_month" min="0" step="0.01" />
                                    </div>

                                    <!-- Minimum Driver Age -->
                                    <div class="w-[50%]">
                                        <InputLabel for="minimum_driver_age">{{ _t('vendorprofilepages', 'label_minimum_driver_age') }}</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.minimum_driver_age"
                                            id="minimum_driver_age" min="18" required />
                                    </div>

                                </div>


                                <div class="col-span-2">
                                    <InputLabel for="payment_method">{{ _t('vendorprofilepages', 'label_payment_methods') }}</InputLabel>
                                    <div class="flex items-center gap-10 flex-wrap">
                                        <label class="flex gap-2 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="credit_card"
                                                class="w-auto" />
                                            {{ _t('vendorprofilepages', 'payment_method_credit_card') }}
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cheque"
                                                class="w-auto" />
                                            {{ _t('vendorprofilepages', 'payment_method_cheque') }}
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="bank_wire"
                                                class="w-auto" />
                                            {{ _t('vendorprofilepages', 'payment_method_bank_wire') }}
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cryptocurrency"
                                                class="w-auto" />
                                            {{ _t('vendorprofilepages', 'payment_method_cryptocurrency') }}
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cash"
                                                class="w-auto" />
                                            {{ _t('vendorprofilepages', 'payment_method_cash') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <InputLabel for="features">{{ _t('vendorprofilepages', 'label_features') }}</InputLabel>
                                    <div class="flex gap-10 flex-wrap">
                                        <label v-for="feature in availableFeatures" :key="feature.id"
                                            class="flex items-center text-nowrap gap-2">
                                            <input type="checkbox" v-model="form.features" :value="feature.name"
                                                class="w-auto" />
                                            <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.name" class="w-4 h-4 mr-1 inline-block object-contain"/>
                                            {{ feature.name }}
                                        </label>
                                    </div>
                                    <div v-if="!availableFeatures.length && form.category_id">
                                        <p class="text-gray-500">{{ _t('vendorprofilepages', 'text_no_features_for_category') }}</p>
                                    </div>
                                    <div v-if="!form.category_id">
                                        <p class="text-gray-500">{{ _t('vendorprofilepages', 'text_select_category_for_features') }}</p>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="guidelines">
                            <div>
                                <InputLabel for="guidelines">{{ _t('vendorprofilepages', 'label_guidelines') }}</InputLabel>
                                <textarea type="text" v-model="form.guidelines" id="guidelines" required
                                    class="border p-2 rounded-lg w-full" />
                            </div>
                            <div class="time-selector p-6 bg-gray-50 rounded-xl shadow-lg w-full">
                                <p>{{ _t('vendorprofilepages', 'text_choose_pickup_return_time') }}</p>
                                <div class="grid grid-cols-2 gap-10">
                                    <div>
                                        <!-- Pickup Times Section -->
                                        <label class="block text-lg font-semibold text-gray-800 mb-2">{{ _t('vendorprofilepages', 'label_pickup_times') }}</label>
                                        <div v-for="(time, index) in form.pickup_times" :key="'pickup-' + index"
                                            class="time-input-group flex items-center mb-3">
                                            <input type="time" v-model="form.pickup_times[index]"
                                                class="time-input max-[768px]:text-[0.75rem] w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                            <button type="button" @click="removePickupTime(index)" :title="_t('vendorprofilepages', 'title_remove_time')"
                                                class="ml-1 text-red-600 hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-trash-2">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                                </svg>
                                            </button>
                                        </div>

                                        <button type="button" @click="addPickupTime"
                                            class="w-full py-2 max-[768px]:text-[0.75rem] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                            {{ _t('vendorprofilepages', 'button_add_pickup_time') }}
                                        </button>
                                    </div>
                                    <div>
                                        <!-- Return Times Section -->
                                        <label class="block text-lg font-semibold text-gray-800 mb-2">{{ _t('vendorprofilepages', 'label_return_times') }}</label>
                                        <div v-for="(time, index) in form.return_times" :key="'return-' + index"
                                            class="time-input-group flex items-center mb-3">
                                            <input type="time" v-model="form.return_times[index]"
                                                class="time-input max-[768px]:text-[0.75rem] w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                            <button type="button" @click="removeReturnTime(index)" :title="_t('vendorprofilepages', 'title_remove_time')"
                                                class="ml-1 text-red-600 hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-trash-2">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                                </svg>
                                            </button>
                                        </div>

                                        <button type="button" @click="addReturnTime"
                                            class="w-full py-2 max-[768px]:text-[0.75rem] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                            {{ _t('vendorprofilepages', 'button_add_return_time') }}
                                        </button>
                                    </div>
                                </div>



                            </div>
                        </TabsContent>

                        <TabsContent value="images">
                            <div class="grid gap-6">
                                <!-- Current Images -->
                                <div v-if="props.vehicle && props.vehicle.images && props.vehicle.images.length > 0">
                                    <h3 class="font-medium text-lg mb-3">{{ _t('vendorprofilepages', 'label_current_images') }}</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                                        <div v-for="image in props.vehicle.images" :key="image.id"
                                            class="relative group border rounded-lg overflow-hidden h-48">
                                            <img :src="`${image.image_url}`" :alt="_t('vendorprofilepages', 'alt_vehicle_image')"
                                                class="w-full h-full object-cover" />
                                            <div class="absolute top-0 right-0 p-1 flex gap-1">
                                                <button v-if="image.image_type !== 'primary'" type="button" @click="setExistingImageAsPrimary(image.id)"
                                                    class="bg-white text-blue-600 rounded-full p-1 hover:bg-blue-50 transition-colors" :title="_t('vendorprofilepages', 'button_set_as_primary')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                </button>
                                                <button type="button" @click="deleteImage(props.vehicle.id, image.id)"
                                                    class="bg-white text-red-600 rounded-full p-1 hover:bg-red-50 transition-colors" :title="_t('vendorprofilepages', 'title_delete_image')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-trash-2">
                                                        <path d="M3 6h18"></path>
                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                        <line x1="10" x2="10" y1="11" y2="17"></line>
                                                        <line x1="14" x2="14" y1="11" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div
                                                class="absolute bottom-0 left-0 right-0 bg-black/70 text-white p-1 text-center text-xs"
                                                :class="{'bg-blue-600/90': image.image_type === 'primary'}">
                                                {{ image.image_type === 'primary' ? _t('vendorprofilepages', 'text_image_type_primary') : _t('vendorprofilepages', 'text_image_type_gallery') }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 mt-2">
                                        {{ _t('vendorprofilepages', 'text_images_uploaded_count', {count: props.vehicle.images.length}) }}
                                    </p>
                                </div>

                                <!-- Upload New Images -->
                                <div v-if="!props.vehicle || !props.vehicle.images || props.vehicle.images.length < 20">
                                    <h3 class="font-medium text-lg mb-3">{{ _t('vendorprofilepages', 'label_upload_new_images') }}</h3>
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
                                            <p class="mt-2 text-sm text-gray-600">{{ _t('vendorprofilepages', 'text_click_or_drag_images') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ _t('vendorprofilepages', 'text_image_format_hint') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ _t('vendorprofilepages', 'text_files_selected_count', {count: selectedFiles.length}) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="selectedFiles.length > 0" class="mt-4">
                                        <h4 class="font-medium text-sm mb-2">{{ _t('vendorprofilepages', 'label_selected_files') }}</h4>
                                        <ul class="text-sm text-gray-600">
                                            <li v-for="(file, index) in selectedFiles" :key="index"
                                                class="flex justify-between items-center py-1 border-b last:border-b-0">
                                                <span class="truncate max-w-[200px]">{{ file.name }}</span>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" @click="setNewImageAsPrimary(index)"
                                                        :class="['text-xs px-2 py-1 rounded', form.primary_image_index === index ? 'bg-blue-500 text-white cursor-default' : 'bg-gray-200 hover:bg-gray-300']"
                                                        :disabled="form.primary_image_index === index">
                                                        {{ form.primary_image_index === index ? _t('vendorprofilepages', 'text_image_type_primary') : _t('vendorprofilepages', 'button_set_as_primary') }}
                                                    </button>
                                                    <button type="button" @click="removeFile(index)"
                                                        class="text-red-500 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50">
                                                        {{ _t('vendorprofilepages', 'button_remove_file') }}
                                                    </button>
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

                    <div class="flex justify-between mt-8">
                        <PrimaryButton type="submit" :disabled="form.limited_km && !form.price_per_km"
                            :class="{ 'opacity-50 cursor-not-allowed': form.limited_km && !form.price_per_km }">
                            {{ _t('vendorprofilepages', 'update_vehicle_button') }}
                        </PrimaryButton>
                        <Link href="/current-vendor-vehicles"
                            class="px-4 flex justify-center items-center bg-[#EA3C3C] text-white rounded hover:bg-[#ea3c3ca2]">
                        {{ _t('vendorprofilepages', 'cancel_button') }}
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </MyProfileLayout>
</template>


<script setup>
import { ref, onMounted, computed, watch, nextTick, getCurrentInstance } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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
import LocationPicker from '@/Components/LocationPicker.vue';
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const { props } = usePage();
const fileInput = ref(null);
const selectedFiles = ref([]);
const maxImages = 20;
const isLoading = ref(false);

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
    city: '',
    state: '',
    country: '',
    latitude: 0,
    longitude: 0,
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
    primary_image_index: null, // For new uploads
    existing_primary_image_id: null, // For existing images
    full_vehicle_address: '', // Initialize new field
})

const remainingImageSlots = computed(() => {
    if (!props.vehicle || !props.vehicle.images) return maxImages
    return Math.max(0, maxImages - props.vehicle.images.length)
})

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
    form.location = locationData.address || '';
    form.city = locationData.city || '';
    form.state = locationData.state || '';
    form.country = locationData.country || '';
    form.latitude = parseFloat(locationData.latitude) || 0;
    form.longitude = parseFloat(locationData.longitude) || 0;
};


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
        form.city = props.vehicle.city || '';
        form.state = props.vehicle.state || '';
        form.country = props.vehicle.country || '';
        form.latitude = props.vehicle.latitude ? parseFloat(props.vehicle.latitude) : 0;
        form.longitude = props.vehicle.longitude ? parseFloat(props.vehicle.longitude) : 0;
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
        form.price_per_day = parseFloat(props.vehicle.price_per_day) || 0.00
        form.price_per_week = parseFloat(props.vehicle.price_per_week) || 0.00
        form.price_per_month = parseFloat(props.vehicle.price_per_month) || 0.00
        form.weekly_discount = parseFloat(props.vehicle.weekly_discount) || 0.00
        form.monthly_discount = parseFloat(props.vehicle.monthly_discount) || 0.00
        form.preferred_price_type = props.vehicle.preferred_price_type
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

    // Log the form data to the console
    console.log('Form Data:', form);
})

const updateVehicle = () => {
    isLoading.value = true;

    // Construct full_vehicle_address before getting form.data()
    const addressParts = [form.location, form.city, form.state, form.country];
    form.full_vehicle_address = addressParts.filter(Boolean).join(', ');

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
        // Ensure we only append actual data properties, not 'benefits' (handled above) or 'images' (handled below)
        if (key !== 'benefits' && key !== 'images' && submitData.hasOwnProperty(key)) {
            const value = submitData[key];
            if (Array.isArray(value)) {
                value.forEach(item => formData.append(`${key}[]`, item !== null ? item : ''));
            } else {
                // Send empty string for null, otherwise send the value
                formData.append(key, value !== null ? value : '');
            }
        }
    }

    // Append new image files
    selectedFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
    });

    // For Laravel to treat POST as PUT when FormData is used
    formData.append('_method', 'PUT');

    axios.post(route('current-vendor-vehicles.update', props.vehicle.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
        .then(() => {
            isLoading.value = false;
            selectedFiles.value = []; // Reset selected files after successful upload
            toast.success('Vehicle updated successfully!', { position: 'top-right', timeout: 1000 });
            setTimeout(() => {
                window.location.href = route('current-vendor-vehicles.index');
            }, 1500);
        })
        .catch(error => {
            isLoading.value = false;
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
    showLocationPicker.value = !showLocationPicker.value
    if (showLocationPicker.value) {
        forceMapResize()
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
