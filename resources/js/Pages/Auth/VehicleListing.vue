<template>
    <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e]">
        <div class="flex justify-center flex-col items-center h-full w-full">
            <img :src=loader alt="" class="w-[200px] max-[768px]:w-[70px]">
            <p class="text-[white] text-[1.5rem] max-[768px]:text-[1rem]">Please do not refresh the page. Wait....</p>
        </div>
    </div>

    <Head title="Vehicle Listing" />
    <div v-if="currentStep === 0" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div
            class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto max-[768px]:gap-10">
            <div class="column h-full w-[50%] flex items-center justify-center max-[768px]:w-full  max-[768px]:h-auto">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden absolute top-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <span class="text-[3rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:px-[1.5rem]">{{
                        _t('createvehicle','step0_create_listing_title') }}</span>
                    <p
                        class="text-customLightGrayColor text-[1.15rem] max-[768px]:text-[0.875rem] max-[768px]:px-[1.5rem] w-[70%] max-[768px]:w-full">
                        {{ _t('createvehicle', 'step0_create_listing_description') }}
                    </p>
                    <div
                        class="buttons flex justify-between gap-[1.5rem] max-[768px]:text-[0.875rem] max-[768px]:px-[1.5rem] w-[30rem]">
                        <PrimaryButton class="w-[40%] max-[768px]:w-fit" type="button" @click="nextStep">{{
                            _t('createvehicle','step0_create_a_listing_button') }}</PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:px-[1.5rem] max-[768px]:py-[2rem] max-[768px]:mt-20">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'step0_temporary_documents_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step0_temporary_documents_description') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="vendorBgimage" alt=""
                    class="absolute bottom-0 left-[-4rem] max-[768px]:top-[-5.5rem] max-[768px]:w-[222px]" />
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-1 -->
    <div v-if="currentStep === 1" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[5rem] max-[768px]:pb-0 max-[768px]:w-full bg-white">
                <div class="flex flex-col gap-10 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[3rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem]">
                        <span class="text-[3rem] font-medium max-[768px]:text-[1.2rem]">{{
                            _t('createvehicle','step1_vehicle_category_title') }}</span>
                        <p
                            class="text-customLightGrayColor text-[1.15rem] max-[768px]:text-[0.875rem] max-[768px]:mt-2">
                            {{ _t('createvehicle', 'step1_vehicle_category_placeholder') }}
                        </p>
                    </div>
                    <!-- Vehicle Category Dropdown -->
                    <div class="grid grid-cols-3 gap-5 max-[768px]:grid-cols-2 max-[768px]:px-[1.5rem]">
                        <div v-for="category in categories" :key="category.id"
                            class="relative flex flex-col justify-center items-center">
                            <input type="radio" :id="category.id" v-model="form.category_id" :value="category.id"
                                class="peer sr-only" />
                            <InputLabel :for="category.id" class="flex flex-col items-center p-4 cursor-pointer rounded-lg border-2 
                            border-gray-200 hover:border-customLightGrayColor 
                            peer-checked:border-customPrimaryColor peer-checked:bg-blue-50 peer-checked:scale-105 transition-transform duration-300 ease-in-out
                                max-[768px]:p-1">
                                <img :src="`${category.image}`" :alt="category.InputLabel"
                                    class="mb-2 w-[200px] h-[150px] max-[768px]:h-[100px] object-cover rounded-lg" />
                                <p class="text-center max-[768px]:text-[0.75rem]">{{ category.name }}</p>
                                <span class="text-[1.5rem] text-center block font-medium text-gray-700">{{
                                    category.InputLabel }}</span>
                            </InputLabel>
                        </div>
                        <span v-if="errors.category_id" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                            errors.category_id }}</span>
                    </div>

                    <div class="mt-[1rem] max-[768px]:px-[1.5rem]">
                        <span class="text-[3rem] font-medium max-[768px]:text-[1.2rem]">{{
                            _t('createvehicle','step1_vehicle_details_title') }}</span>
                        <p
                            class="text-customLightGrayColor text-[1.15rem] max-[768px]:text-[0.875rem] max-[768px]:mt-2">
                            {{ _t('createvehicle', 'step1_vehicle_details_placeholder') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-5 max-[768px]:grid-cols-2 max-[768px]:px-[1.5rem]">
                        <!-- Brand -->
                        <div>
                            <InputLabel for="brand">{{ _t('createvehicle', 'step1_brand_label') }}</InputLabel>
                            <input type="text" v-model="form.brand" id="brand" required
                                :placeholder="_t('createvehicle', 'step1_brand_placeholder')" />
                            <span v-if="errors.brand" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.brand }}</span>
                        </div>

                        <!-- Model -->
                        <div>
                            <InputLabel for="model">{{ _t('createvehicle', 'step1_model_label') }}</InputLabel>
                            <input type="text" v-model="form.model" id="model" required
                                :placeholder="_t('createvehicle', 'step1_model_placeholder')" />
                            <span v-if="errors.model" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.model }}</span>
                        </div>

                        <!-- Color -->
                        <div>
                            <InputLabel for="color">{{ _t('createvehicle', 'step1_color_label') }}</InputLabel>
                            <Select v-model="form.color" id="color" required>
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue
                                        :placeholder="form.color || _t('createvehicle', 'step1_color_placeholder')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_color_label') }}</SelectLabel>
                                        <!-- You might need a new translation key here -->
                                        <SelectItem v-for="colorOption in vehicleColors" :key="colorOption.value"
                                            :value="colorOption.value">
                                            {{ colorOption.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span v-if="errors.color" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.color }}</span>
                        </div>


                        <!-- Mileage -->
                        <div>
                            <InputLabel for="mileage">{{ _t('createvehicle', 'step1_mileage_label') }}</InputLabel>
                            <div class="relative">
                                <input type="number" v-model="form.mileage" id="mileage" required />
                                <span
                                    class="absolute bg-white text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">{{
                                        _t('createvehicle','step1_mileage_unit') }}</span>
                                <span v-if="errors.mileage" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                    errors.mileage }}</span>
                            </div>
                        </div>

                        <!-- Transmission -->
                        <div>
                            <InputLabel for="transmission">{{ _t('createvehicle', 'step1_transmission_label') }}
                            </InputLabel>
                            <Select v-model="form.transmission">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.transmission || 'Select transmission type'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_transmission_select_label') }}
                                        </SelectLabel>
                                        <SelectItem value="manual">{{ _t('createvehicle', 'step1_transmission_manual') }}
                                        </SelectItem>
                                        <SelectItem value="automatic">{{
                                            _t('createvehicle','step1_transmission_automatic') }}</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Fuel -->
                        <div>
                            <InputLabel for="fuel">{{ _t('createvehicle', 'step1_fuel_label') }}</InputLabel>
                            <Select v-model="form.fuel">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.fuel || 'Select fuel type'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_fuel_select_label') }}</SelectLabel>
                                        <SelectItem value="petrol">{{ _t('createvehicle', 'step1_fuel_petrol') }}
                                        </SelectItem>
                                        <SelectItem value="diesel">{{ _t('createvehicle', 'step1_fuel_diesel') }}
                                        </SelectItem>
                                        <SelectItem value="electric">{{ _t('createvehicle', 'step1_fuel_electric') }}
                                        </SelectItem>
                                        <SelectItem value="hybrid">{{ _t('createvehicle', 'step1_fuel_hybrid') }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Seating Capacity -->
                        <div>
                            <InputLabel for="seating_capacity">{{ _t('createvehicle', 'step1_seating_capacity_label') }}
                            </InputLabel>
                            <Select v-model="form.seating_capacity">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.seating_capacity || 'Select seating capacity'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_seating_capacity_select_label') }}
                                        </SelectLabel>
                                        <SelectItem v-for="capacity in [1, 2, 3, 4, 5, 6, 7, 8]" :key="capacity"
                                            :value="capacity">
                                            {{ capacity }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Number of Doors -->
                        <div>
                            <InputLabel for="number_of_doors">{{ _t('createvehicle', 'step1_doors_label') }}</InputLabel>
                            <Select v-model="form.number_of_doors">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.number_of_doors || 'Select number of doors'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_doors_select_label') }}</SelectLabel>
                                        <SelectItem v-for="doors in 8" :key="doors" :value="doors">
                                            {{ doors }}
                                        </SelectItem>

                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Luggage Capacity -->
                        <div>
                            <InputLabel for="luggage_capacity">{{ _t('createvehicle', 'step1_luggage_label') }}
                            </InputLabel>
                            <Select v-model="form.luggage_capacity">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue placeholder="Select luggage capacity" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_luggage_select_label') }}
                                        </SelectLabel>
                                        <SelectItem v-for="capacity in [0, 1, 2, 3, 4, 5]" :key="capacity"
                                            :value="capacity">
                                            {{ capacity }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <span v-if="errors.luggage_capacity"
                                class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{ errors.luggage_capacity
                                }}</span>
                        </div>

                        <!-- Horsepower -->
                        <div>
                            <InputLabel for="horsepower">{{ _t('createvehicle', 'step1_horsepower_label') }}</InputLabel>
                            <div class="relative">
                                <input type="number" v-model="form.horsepower" id="horsepower" required min="0" />
                                <span
                                    class="absolute bg-white text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">hp</span>
                            </div>
                            <span v-if="errors.horsepower" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.horsepower }}</span>
                        </div>

                        <!-- CO2 Emissions -->
                        <div>
                            <InputLabel for="co2">{{ _t('createvehicle', 'step1_co2_label') }}</InputLabel>
                            <div class="relative">
                                <input type="text" v-model="form.co2" id="co2" required />
                                <span
                                    class="absolute bg-white text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">(g/km)</span>
                            </div>
                            <span v-if="errors.co2" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.co2 }}</span>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status">{{ _t('createvehicle', 'step1_status_label') }}</InputLabel>
                            <Select v-model="form.status">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>{{ _t('createvehicle', 'step1_status_select_label') }}</SelectLabel>
                                        <SelectItem value="available">{{ _t('createvehicle', 'step1_status_available') }}
                                        </SelectItem>
                                        <SelectItem value="rented">{{ _t('createvehicle', 'step1_status_rented') }}
                                        </SelectItem>
                                        <SelectItem value="maintenance">{{
                                            _t('createvehicle','step1_status_maintenance') }}</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                        </div>
                    </div>

                    <!-- Car Features -->
                    <div class="max-[768px]:px-[1.5rem]">
                        <div class="mt-8">
                            <span class="text-black mb-2 text-[3rem] font-medium max-[768px]:text-[1.2rem]">{{
                                _t('createvehicle','step1_features_title') }}</span>
                            <p
                                class="text-customLightGrayColor text-[1.15rem] mb-[2rem] max-[768px]:text-[0.875rem] max-[768px]:mt-2">
                                {{ _t('createvehicle', 'step1_features_description') }}
                            </p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-if="!form.category_id" class="col-span-2 md:col-span-4">
                                    <p class="text-gray-500">{{
                                        _t('createvehicle','step1_features_select_category_message') }}</p>
                                </div>
                                <div v-else-if="availableFeatures.length === 0 && form.category_id"
                                    class="col-span-2 md:col-span-4">
                                    <p class="text-gray-500">{{ _t('createvehicle', 'step1_features_no_features_message')
                                        }}</p>
                                </div>
                                <!-- Loop through features if category is selected and features are available -->
                                <div v-else v-for="feature in availableFeatures" :key="feature.id"
                                    class="flex items-center space-x-2 max-[768px]:items-start">
                                    <input type="checkbox" :id="'feature-' + feature.id" :value="feature.name"
                                        v-model="form.features"
                                        class="rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor" />

                                    <InputLabel :for="'feature-' + feature.id"
                                        class="mb-0 flex items-center cursor-pointer mt-[6px] max-[768px]:mt-0">
                                        <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.name"
                                            class="w-4 h-4 mr-1 inline-block object-contain" />
                                        {{ feature.name }}
                                    </InputLabel>
                                </div>
                            </div>

                            <!-- Selected Features Display -->
                            <div v-if="form.features.length > 0" class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ _t('createvehicle', 'step1_features_selected_title') }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="featureName in form.features" :key="featureName"
                                        class="px-3 py-1 text-sm bg-blue-50 text-customPrimaryColor rounded-full">
                                        {{ featureName }}
                                    </span>
                                </div>
                            </div>
                            <span v-if="errors.features" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.features }}</span>
                        </div>
                    </div>
                    <div class="buttons flex justify-between gap-[1.5rem] pb-[4rem] max-[768px]:px-[1.5rem]">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" @click="nextStep">{{
                            _t('createvehicle','next_button') }}</PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column w-[50%] h-full bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center h-full justify-center max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','step1_welcome_message_title') }}</h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'step1_welcome_message_content') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','tip_title') }}</h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step1_tip_registration_certificate') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                    <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
                </div>
            </div>
        </div>
    </div>

    <!-- Step-2 -->
    <div v-if="currentStep === 2" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[5rem] mb-[2rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem] max-[768px]:mb-[1rem]">
                        <span class="text-[1.75rem] font-medium max-[768px]:text-[1.2rem]">{{
                            _t('createvehicle','step2_technical_specifications_title') }}</span>
                        <p class="max-[768px]:text-[0.875rem] max-[768px]:mt-2">{{
                            _t('createvehicle','step2_technical_specifications_description') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-8 max-[768px]:px-[1.5rem] max-[768px]:gap-3 max-[768px]:gap-y-8">
                        <div class="col-span-2">
                            <InputLabel class="text-black mb-0" for="registration_number">{{
                                _t('createvehicle','step2_registration_number_label') }}
                            </InputLabel>
                            <span class="text-[0.675rem] mb-[1rem] inline-block">{{
                                _t('createvehicle','step2_registration_number_tooltip') }}</span>
                            <input class="w-full" type="text" v-model="form.registration_number"
                                id="registration_number" required
                                :placeholder="_t('createvehicle', 'step2_registration_number_placeholder')" />
                            <span v-if="errors.registration_number"
                                class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                    errors.registration_number }}</span>
                        </div>

                        <!-- Registration Country -->
                        <div class="relative w-full">
                            <InputLabel class="text-black" for="registration_country">{{
                                _t('createvehicle','step2_registration_country_label') }}</InputLabel>

                            <div class="relative">
                                <Select v-model="form.registration_country">
                                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                        <SelectValue
                                            :placeholder="_t('createvehicle', 'step2_registration_country_placeholder')" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>{{
                                                _t('createvehicle','step2_registration_country_select_label') }}
                                            </SelectLabel>
                                            <SelectItem v-for="country in countries" :key="country.code"
                                                :value="country.code">
                                                <div class="flex items-center gap-2">
                                                    <img :src="getFlagUrl(country.code)" :alt="`${country.name} flag`"
                                                        class="w-[1.5rem] h-[1rem] rounded-sm" />
                                                    <span>{{ country.name }}</span>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>

                                <!-- Dynamic Flag in Trigger -->
                                <!-- <img v-if="form.registration_country" :src="getFlagUrl(form.registration_country)"
                                    alt="Country Flag"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 w-[2.1rem] h-[1.5rem] rounded" /> -->
                            </div>

                            <span v-if="errors.registration_country"
                                class="text-red-500 max-[768px]:text-[0.75rem] text-sm">
                                {{ errors.registration_country }}
                            </span>
                        </div>


                        <!-- Registration Date -->
                        <div>
                            <InputLabel class="text-black" for="registration_date">{{
                                _t('createvehicle','step2_registration_date_label') }}</InputLabel>
                            <VueDatePicker v-model="form.registration_date" :format="'yyyy-MM-dd'" auto-apply
                                :placeholder="_t('createvehicle', 'step2_registration_date_placeholder')" class="w-full"
                                :class="{ 'dp__error': errors.registration_date }" :clearable="false"
                                :max-date="new Date()"
                                :input-class-name="'w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm text-gray-700'"
                                @update:modelValue="formatDate" required />
                            <span v-if="errors.registration_date"
                                class="text-red-500 max-[768px]:text-[0.75rem] text-sm">
                                {{ errors.registration_date }}
                            </span>
                        </div>

                        <!-- Gross Vehicle Mass -->
                        <div>
                            <InputLabel class="text-black" for="gross_vehicle_mass">{{
                                _t('createvehicle','step2_gross_vehicle_mass_label') }}</InputLabel>
                            <div class="relative">
                                <input class="w-full" type="number" v-model="form.gross_vehicle_mass"
                                    id="gross_vehicle_mass" />
                                <span
                                    class="absolute bg-white text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">{{
                                        _t('createvehicle','step2_gross_vehicle_mass_unit') }}</span>
                            </div>
                        </div>

                        <!-- Vehicle Height -->
                        <div>
                            <InputLabel class="text-black" for="vehicle_height">{{
                                _t('createvehicle','step2_vehicle_height_label') }}</InputLabel>
                            <div class="relative">
                                <input class="w-full" type="number" v-model="form.vehicle_height" id="vehicle_height" />
                                <span
                                    class="absolute bg-white text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">{{
                                        _t('createvehicle','step2_vehicle_height_unit') }}</span>
                            </div>

                        </div>

                        <!-- Dealer Cost -->
                        <div class="col-span-2">
                            <InputLabel class="text-black" for="dealer_cost">{{
                                _t('createvehicle','step2_dealer_cost_label') }}</InputLabel>
                            <input class="" type="number" v-model="form.dealer_cost" id="dealer_cost" required />
                            <span v-if="errors.dealer_cost" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.dealer_cost }}</span>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-span-2">
                            <InputLabel class="text-black mb-0" for="phone_number">{{
                                _t('createvehicle','step2_phone_number_label') }}</InputLabel>
                            <span class="text-[0.675rem] mb-[1rem] inline-block">{{
                                _t('createvehicle','step2_phone_number_tooltip') }}</span>
                            <input class="w-full" type="text" v-model="form.phone_number" id="phone_number" required
                                placeholder="+91" />
                            <span v-if="errors.phone_number" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.phone_number
                                }}</span>
                        </div>
                    </div>
                    <div
                        class="buttons flex justify-between gap-[1.5rem] mt-[2rem] pb-[4rem] max-[768px]:px-[1.5rem] max-[768px]:pb-0">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="button" @click="nextStep">{{
                            _t('createvehicle','next_button') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full max-[768px]:px-[1.5rem] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'data_protection_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step0_temporary_documents_description') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full max-[768px]:px-[1.5rem] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2"> {{
                            _t('createvehicle','information_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step2_information_secure_listing') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full max-[768px]:px-[1.5rem] p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-3 -->
    <div v-if="currentStep === 3" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[5rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem] max-[768px]:mb-1">
                        <span class="text-[1.75rem] font-medium max-[768px]:text-[1.2rem]">{{
                            _t('createvehicle','step3_parking_address_title') }}</span>
                        <p class="max-[768px]:text-[0.875rem] max-[768px]:mt-2">{{
                            _t('createvehicle','step3_parking_address_description') }}</p>
                    </div>
                    <div class="max-[768px]:px-[1.5rem]">
                        <span class="text-[0.875rem] text-black font-medium">{{
                            _t('createvehicle','step3_search_address_label') }}</span>
                        <p class="text-[0.675rem] max-[768px]:mt-2">
                            {{ _t('createvehicle', 'step3_search_address_tooltip') }}
                        </p>
                    </div>
                    <div class="search-container">
                        <LocationPicker :onLocationSelect="selectLocation" />
                    </div>
                    <span v-if="errors.location" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                        errors.location }}</span>
                    <div
                        class="buttons flex justify-between gap-[1.5rem] mt-[2rem] pb-[4rem] max-[768px]:pb-0 max-[768px]:px-[1.5rem]">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="button" @click="nextStep">{{
                            _t('createvehicle','next_button') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'data_protection_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step3_data_protection_address_sharing') }}
                        </p>
                    </div>

                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-4 -->
    <div v-if="currentStep === 4" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[5rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem]">
                        <span class="text-[1.75rem] font-medium max-[768px]:text-[1.2rem]"> {{
                            _t('createvehicle','step4_hire_cost_title') }}</span>
                        <div class="mt-[2rem]">
                            <span class="text-[0.875rem] text-black font-medium">{{
                                _t('createvehicle','step4_basic_daily_rate_label') }}</span>
                            <p class="text-[0.75rem] font-medium mb-[1rem] text-customLightGrayColor">
                                {{ _t('createvehicle', 'step4_basic_daily_rate_tooltip') }}
                            </p>
                        </div>
                    </div>
                    <div class="">
                        <div class="border-[1px] p-8 flex flex-col gap-8 max-[768px]:p-0">
                            <div class="price-section">
                                <h3 class="text-lg font-semibold mb-4">{{
                                    _t('createvehicle','step4_pricing_options_title') }}</h3>

                                <!-- Price Type Selection -->
                                <div class="mb-8 max-[768px]:mb-3">
                                    <InputLabel class="text-black mb-2">{{
                                        _t('createvehicle','step4_preferred_price_types_label') }}</InputLabel>
                                    <div class="flex gap-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.day" class="mr-2" />
                                            {{ _t('createvehicle', 'step4_price_type_daily') }}
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.week" class="mr-2" />
                                            {{ _t('createvehicle', 'step4_price_type_weekly') }}
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.month" class="mr-2" />
                                            {{ _t('createvehicle', 'step4_price_type_monthly') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-10 max-[768px]:grid-cols-1 max-[768px]:gap-2">
                                    <!-- Daily Price Slider -->
                                    <div v-if="selectedTypes.day" class="price-slider  bg-gray-50 p-5 rounded-[12px]">
                                        <label for="price_per_day" class="font-medium">{{
                                            _t('createvehicle','step4_daily_rate_label') }}</label>
                                        <div class="slider-container">
                                            <input type="number" v-model="form.price_per_day" id="price_per_day"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm px-2" />
                                        </div>
                                        <div class="mt-2 flex flex-col items-end gap-1">
                                            <span class="text-sm text-gray-600">{{
                                                _t('createvehicle','step4_daily_rate_recommended') }}</span>
                                        </div>
                                    </div>

                                    <!-- Weekly Price Slider -->
                                    <div v-if="selectedTypes.week" class="price-slider  bg-gray-50 p-5 rounded-[12px]">
                                        <label for="price_per_week" class="font-medium">{{
                                            _t('createvehicle','step4_weekly_rate_label') }}</label>
                                        <div class="slider-container">
                                            <input type="number" v-model="form.price_per_week" id="price_per_week"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm px-2" />
                                        </div>
                                        <div class="mt-2 flex flex-col items-end gap-1">
                                            <span class="text-sm text-gray-600">{{
                                                _t('createvehicle','step4_weekly_rate_recommended') }}</span>
                                        </div>
                                        <div class="mt-2 flex flex-col">
                                            <label for="weekly_discount" class="text-sm font-medium mb-0">{{
                                                _t('createvehicle','step4_weekly_discount_label') }}
                                            </label>
                                            <input type="number" v-model="form.weekly_discount" id="weekly_discount"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm px-2" />
                                        </div>

                                    </div>

                                    <!-- Monthly Price Slider -->
                                    <div v-if="selectedTypes.month" class="price-slider  bg-gray-50 p-5 rounded-[12px]">
                                        <label for="price_per_month" class="font-medium">{{
                                            _t('createvehicle','step4_monthly_rate_label') }}</label>
                                        <div class="slider-container">
                                            <input type="number" v-model="form.price_per_month" id="price_per_month"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm px-2" />
                                        </div>
                                        <div class="mt-2 flex flex-col items-end gap-1">
                                            <span class="text-sm text-gray-600">{{
                                                _t('createvehicle','step4_monthly_rate_recommended') }}</span>
                                        </div>
                                        <div class="mt-2 flex flex-col">
                                            <label for="monthly_discount" class="text-sm font-medium mb-0">{{
                                                _t('createvehicle','step4_monthly_discount_label') }}
                                            </label>
                                            <input type="number" v-model="form.monthly_discount" id="monthly_discount"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm px-2" />
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <span v-if="errors.price_per_day" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                                errors.price_per_day
                                }}</span>
                        </div>
                        <!-- Security Deposit -->
                        <div class="mt-[2rem] max-[768px]:px-[1.5rem]">
                            <InputLabel for="security_deposit" class="text-black mb-0">{{
                                _t('createvehicle','step4_security_deposit_label') }}</InputLabel>
                            <span class="text-[0.75rem] font-medium mb-[1rem] inline-block text-customLightGrayColor">{{
                                _t('createvehicle','step4_security_deposit_tooltip') }}</span>
                            <input type="number" v-model="form.security_deposit" id="security_deposit" required min="0"
                                step="0.01" />
                        </div>
                        <span v-if="errors.security_deposit" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                            errors.security_deposit
                            }}</span>
                    </div>

                    <!-- Guidelines -->
                    <div class="max-[768px]:px-[1.5rem]">
                        <InputLabel for="brand">{{ _t('createvehicle', 'step4_guidelines_label') }}</InputLabel>
                        <textarea type="text" v-model="form.guidelines" id="guidelines" required
                            :placeholder="_t('createvehicle', 'step4_guidelines_placeholder')"
                            class="w-full min-h-[150px]" />
                    </div>

                    <div class="time-selector p-6 bg-gray-50 rounded-xl shadow-lg w-full">
                        <p>{{ _t('createvehicle', 'step4_pickup_return_time_title') }}</p>
                        <div class="grid grid-cols-2 gap-10 max-[768px]:gap-5">
                            <!-- Pickup Times Section -->
                            <div>
                                <label class="block text-lg font-semibold text-gray-800 mb-2">{{
                                    _t('createvehicle','step4_pickup_times_label') }}</label>
                                <div v-for="(time, index) in form.pickup_times" :key="'pickup-' + index"
                                    class="time-input-group flex items-center mb-3">
                                    <input type="time" v-model="form.pickup_times[index]"
                                        @input="normalizeTime('pickup_times', index)"
                                        class="time-input w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                    <button type="button" @click="removePickupTime(index)" title="Remove"
                                        class=" ml-1 text-red-600 hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-trash-2">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            <line x1="10" x2="10" y1="11" y2="17"></line>
                                            <line x1="14" x2="14" y1="11" y2="17"></line>
                                        </svg>
                                    </button>
                                </div>
                                <span v-if="errors.pickup_times" class="text-red-500 text-sm block mb-3 italic">
                                    {{ errors.pickup_times }}
                                </span>
                                <button type="button" @click="addPickupTime"
                                    class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                    {{ _t('createvehicle', 'step4_add_pickup_time_button') }}
                                </button>
                            </div>

                            <div>
                                <!-- Return Times Section -->
                                <label class="block text-lg font-semibold text-gray-800 mb-2">{{
                                    _t('createvehicle','step4_return_times_label') }}</label>
                                <div v-for="(time, index) in form.return_times" :key="'return-' + index"
                                    class="time-input-group flex items-center mb-3">
                                    <input type="time" v-model="form.return_times[index]"
                                        @input="normalizeTime('return_times', index)"
                                        class="time-input w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                    <button type="button" @click="removeReturnTime(index)" title="Remove"
                                        class=" ml-1 text-red-600 hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="lucide lucide-trash-2">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            <line x1="10" x2="10" y1="11" y2="17"></line>
                                            <line x1="14" x2="14" y1="11" y2="17"></line>
                                        </svg>
                                    </button>
                                </div>
                                <span v-if="errors.return_times" class="text-red-500 text-sm block mb-3 italic">
                                    {{ errors.return_times }}
                                </span>
                                <button type="button" @click="addReturnTime"
                                    class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                    {{ _t('createvehicle', 'step4_add_return_time_button') }}
                                </button>
                            </div>
                        </div>


                    </div>

                    <!-- Payment Method -->
                    <div class="mt-[2rem] max-[768px]:px-[1.5rem] max-[768px]:mt-[1rem]">
                        <InputLabel class="text-black text-[1.15rem]">{{
                            _t('createvehicle','step4_payment_methods_title') }}
                        </InputLabel>
                        <span class="text-[0.75rem] font-medium mb-[1rem] inline-block text-customLightGrayColor">
                            {{ _t('createvehicle', 'step4_payment_methods_description') }}
                        </span>
                        <div class="flex gap-[1rem] max-[768px]:flex-wrap">
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="credit_card"
                                    id="credit_card" />
                                <label class="ml-2" for="credit_card">{{
                                    _t('createvehicle','step4_payment_method_credit_card') }}</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="cheque" id="cheque" />
                                <label class="ml-2" for="cheque">{{ _t('createvehicle', 'step4_payment_method_cheque')
                                    }}</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="bank_wire" id="bank_wire" />
                                <label class="ml-2" for="bank_wire">{{
                                    _t('createvehicle','step4_payment_method_bank_wire') }}</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="cryptocurrency"
                                    id="cryptocurrency" />
                                <label class="ml-2" for="cryptocurrency">{{
                                    _t('createvehicle','step4_payment_method_cryptocurrency') }}</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="cash" id="cash" />
                                <label class="ml-2" for="cash">{{ _t('createvehicle', 'step4_payment_method_cash')
                                    }}</label>
                            </InputLabel>
                        </div>
                        <span v-if="errors.payment_method" class="text-red-500 max-[768px]:text-[0.75rem] text-sm">{{
                            errors.payment_method
                            }}</span>

                        <div v-if="paymentMethodsArray.length > 0">
                            <p class="text-[0.85rem] text-green-400">{{
                                _t('createvehicle','step4_selected_payment_methods_label') }}</p>
                            <ul class="mt-2 flex items-center gap-3">
                                <li class="bg-green-200 px-2 rounded-[12px]" v-for="method in paymentMethodsArray"
                                    :key="method">{{ method }}</li>
                            </ul>
                        </div>
                        <div v-else>
                            <p class="text-[0.85rem] text-red-500 max-[768px]:text-[0.75rem]">{{
                                _t('createvehicle','step4_no_payment_methods_selected') }}</p>
                        </div>
                    </div>

                    <div class="max-[768px]:px-[1.5rem]">
                        <!-- Limited Kilometer Section -->
                        <div class="mb-6">
                            <h3 class="font-medium text-lg mb-2">{{
                                _t('createvehicle','step4_kilometer_limitations_title') }}</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Per Day Limitation -->
                                <div v-if="selectedTypes.day" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.limited_km_per_day"
                                            id="limited_km_per_day" />
                                        <InputLabel for="limited_km_per_day" class="!mb-0">{{
                                            _t('createvehicle','step4_limited_km_per_day_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.limited_km_per_day">
                                        <div class="mb-0">
                                            <InputLabel for="limited_km_per_day_range">{{
                                                _t('createvehicle','step4_km_limit_label') }}</InputLabel>
                                            <input type="number" v-model="form.limited_km_per_day_range"
                                                id="limited_km_per_day_range" step="0.01" class="w-full" />
                                            <span v-if="errors.limited_km_per_day_range" class="text-red-500 text-sm">
                                                {{ errors.limited_km_per_day_range }}
                                            </span>
                                        </div>

                                        <div>
                                            <InputLabel for="price_per_km_per_day">{{
                                                _t('createvehicle','step4_price_per_extra_km_label') }}</InputLabel>
                                            <input type="number" v-model="form.price_per_km_per_day"
                                                id="price_per_km_per_day" class="w-full" />
                                            <span v-if="errors.price_per_km_per_day" class="text-red-500 text-sm">
                                                {{ errors.price_per_km_per_day }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Per Week Limitation -->
                                <div v-if="selectedTypes.week" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.limited_km_per_week"
                                            id="limited_km_per_week" />
                                        <InputLabel for="limited_km_per_week" class="!mb-0">{{
                                            _t('createvehicle','step4_limited_km_per_week_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.limited_km_per_week">
                                        <div class="mb-0">
                                            <InputLabel for="limited_km_per_week_range">{{
                                                _t('createvehicle','step4_km_limit_label') }}</InputLabel>
                                            <input type="number" v-model="form.limited_km_per_week_range"
                                                id="limited_km_per_week_range" step="0.01" class="w-full" />
                                            <span v-if="errors.limited_km_per_week_range" class="text-red-500 text-sm">
                                                {{ errors.limited_km_per_week_range }}
                                            </span>
                                        </div>

                                        <div>
                                            <InputLabel for="price_per_km_per_week">{{
                                                _t('createvehicle','step4_price_per_extra_km_label') }}</InputLabel>
                                            <input type="number" v-model="form.price_per_km_per_week"
                                                id="price_per_km_per_week" class="w-full" />
                                            <span v-if="errors.price_per_km_per_week" class="text-red-500 text-sm">
                                                {{ errors.price_per_km_per_week }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Per Month Limitation -->
                                <div v-if="selectedTypes.month" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.limited_km_per_month"
                                            id="limited_km_per_month" />
                                        <InputLabel for="limited_km_per_month" class="!mb-0">{{
                                            _t('createvehicle','step4_limited_km_per_month_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.limited_km_per_month">
                                        <div class="mb-0">
                                            <InputLabel for="limited_km_per_month_range">{{
                                                _t('createvehicle','step4_km_limit_label') }}</InputLabel>
                                            <input type="number" v-model="form.limited_km_per_month_range"
                                                id="limited_km_per_month_range" step="0.01" class="w-full" />
                                            <span v-if="errors.limited_km_per_month_range" class="text-red-500 text-sm">
                                                {{ errors.limited_km_per_month_range }}
                                            </span>
                                        </div>

                                        <div>
                                            <InputLabel for="price_per_km_per_month">{{
                                                _t('createvehicle','step4_price_per_extra_km_label') }}</InputLabel>
                                            <input type="number" v-model="form.price_per_km_per_month"
                                                id="price_per_km_per_month" class="w-full" />
                                            <span v-if="errors.price_per_km_per_month" class="text-red-500 text-sm">
                                                {{ errors.price_per_km_per_month }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cancellation Policy Section -->
                        <div class="mb-6">
                            <h3 class="font-medium text-lg mb-2">{{
                                _t('createvehicle','step4_cancellation_policy_title') }}</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Per Day Cancellation -->
                                <div v-if="selectedTypes.day" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.cancellation_available_per_day"
                                            id="cancellation_available_per_day" />
                                        <InputLabel for="cancellation_available_per_day" class="!mb-0">{{
                                            _t('createvehicle','step4_cancellation_available_per_day_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.cancellation_available_per_day">
                                        <InputLabel for="cancellation_available_per_day_date">{{
                                            _t('createvehicle','step4_days_prior_notice_label') }}
                                        </InputLabel>
                                        <input type="number" v-model="form.cancellation_available_per_day_date"
                                            id="cancellation_available_per_day_date" class="w-full" />
                                        <span v-if="errors.cancellation_available_per_day_date"
                                            class="text-red-500 text-sm">
                                            {{ errors.cancellation_available_per_day_date }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Per Week Cancellation -->
                                <div v-if="selectedTypes.week" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.cancellation_available_per_week"
                                            id="cancellation_available_per_week" />
                                        <InputLabel for="cancellation_available_per_week" class="!mb-0">{{
                                            _t('createvehicle','step4_cancellation_available_per_week_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.cancellation_available_per_week">
                                        <InputLabel for="cancellation_available_per_week_date">{{
                                            _t('createvehicle','step4_days_prior_notice_label') }}
                                        </InputLabel>
                                        <input type="number" v-model="form.cancellation_available_per_week_date"
                                            id="cancellation_available_per_week_date" class="w-full" />
                                        <span v-if="errors.cancellation_available_per_week_date"
                                            class="text-red-500 text-sm">
                                            {{ errors.cancellation_available_per_week_date }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Per Month Cancellation -->
                                <div v-if="selectedTypes.month" class="border rounded p-4">
                                    <div class="flex gap-2 items-center mb-0">
                                        <input type="checkbox" v-model="form.cancellation_available_per_month"
                                            id="cancellation_available_per_month" />
                                        <InputLabel for="cancellation_available_per_month" class="!mb-0">{{
                                            _t('createvehicle','step4_cancellation_available_per_month_label') }}
                                        </InputLabel>
                                    </div>

                                    <div v-if="form.cancellation_available_per_month">
                                        <InputLabel for="cancellation_available_per_month_date">{{
                                            _t('createvehicle','step4_days_prior_notice_label') }}</InputLabel>
                                        <input type="number" v-model="form.cancellation_available_per_month_date"
                                            id="cancellation_available_per_month_date" class="w-full" />
                                        <span v-if="errors.cancellation_available_per_month_date"
                                            class="text-red-500 text-sm">
                                            {{ errors.cancellation_available_per_month_date }}
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Driver Requirements Section -->
                        <div>
                            <h3 class="font-medium text-lg mb-2">{{
                                _t('createvehicle','step4_driver_requirements_title') }}</h3>

                            <div class="max-w-xs">
                                <InputLabel for="minimum_driver_age">{{
                                    _t('createvehicle','step4_minimum_driver_age_label') }}</InputLabel>
                                <input type="number" v-model="form.minimum_driver_age" id="minimum_driver_age"
                                    class="w-full" />
                                <span v-if="errors.minimum_driver_age" class="text-red-500 text-sm">
                                    {{ errors.minimum_driver_age }}
                                </span>
                            </div>
                        </div>
                    </div>




                    <div
                        class="buttons flex justify-between gap-[1.5rem] mt-[2rem] pb-[4rem] max-[768px]:pb-0 max-[768px]:px-[1.5rem]">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="button" @click="nextStep">{{
                            _t('createvehicle','next_button') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px] max-[768px]:w-full">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','tip_title') }}</h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step4_tip_remuneration') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','information_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step2_information_secure_listing') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>


    <!-- Step-5 -->
    <div v-if="currentStep === 5" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />

                    <div class="mt-10 max-[768px]:mt-0 max-[768px]:px-[1.5rem]">
                        <span class="text-[1.7rem] font-medium text-gray-800 max-[768px]:text-[1.2rem]">{{
                            _t('createvehicle','step5_protection_plan_title') }}</span>
                        <p class="text-gray-600 mt-5 max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step5_protection_plan_description') }} <strong>{{
                                _t('createvehicle','step5_protection_plan_description2') }}</strong>.
                            {{ _t('createvehicle', 'step5_protection_plan_description3') }}
                            <strong class="underline text-red-500 max-[768px]:text-[0.75rem]">{{
                                _t('createvehicle','step5_protection_plan_description4') }}</strong>
                        </p>
                    </div>

                    <!-- Protection Plan -->
                    <div class="protection_plan flex gap-10 mt-4 max-[768px]:flex-col max-[768px]:px-[1.5rem]">
                        <div v-for="plan in plans" :key="plan.id" class="cursor-pointer col w-[45%] rounded-[20px] border-[1px] border-[#153B4F] p-5 flex flex-col gap-5 
        hover:bg-[#153B4F0D] transition-transform duration-300 ease-in-out max-[768px]:w-full max-[768px]:gap-2"
                            :class="{
                                'hover:scale-105 scale-105': selectedPlans.some(p => p.id === plan.id),
                                'border-[#153B4F] bg-[#153B4F0D]': selectedPlans.some(p => p.id === plan.id),
                            }">

                            <!-- Edit button at top right -->
                            <div class="flex justify-end">
                                <button class="p-2 rounded-full hover:bg-gray-200" @click.stop="openEditDialog(plan)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-pencil">
                                        <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                        <path d="m15 5 4 4"></path>
                                    </svg>
                                </button>
                            </div>

                            <span class="text-[1.5rem] text-center max-[768px]:text-[1.2rem]" :class="{
                                'text-[#016501]': selectedPlans.some(p => p.id === plan.id),
                            }">
                                {{ plan.plan_type }}
                            </span>

                            <strong class="text-[3rem] font-medium text-center max-[768px]:text-[1.2rem]">
                                {{ plan.plan_value }}
                            </strong>

                            <p class="text-[1.25rem] text-[#2B2B2B] text-center max-[768px]:text-[0.95rem]">
                                {{ plan.plan_description }}
                            </p>

                            <button class="button-primary px-5 py-2 max-[768px]:text-[0.875rem]"
                                @click.stop="togglePlanSelection(plan)" :class="{
                                    'bg-[#016501]': selectedPlans.some(p => p.id === plan.id),
                                }">
                                {{selectedPlans.some(p => p.id === plan.id) ?
                                    _t('createvehicle', 'step5_plan_selected_button') :
                                _t('createvehicle','step5_plan_select_button')}}
                            </button>

                            <div class="checklist features">
                                <ul
                                    class="check-list text-center mt-[1rem] inline-flex flex-col items-center w-full gap-3 max-[768px]:text-[0.95rem]">
                                    <li v-for="(feature, index) in plan.features" :key="index"
                                        class="checklist-item list-disc">
                                        {{ feature }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Plan Dialog -->
                    <Dialog :open="isEditDialogOpen" @update:open="isEditDialogOpen = $event">
                        <DialogContent class="max-h-[90vh] overflow-y-auto p-4">
                            <DialogHeader>
                                <DialogTitle>{{ _t('createvehicle', 'step5_edit_plan_dialog_title') }}</DialogTitle>
                                <DialogDescription>
                                    {{ _t('createvehicle', 'step5_edit_plan_dialog_description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="space-y-2">
                                    <label for="plan-type" class="text-sm font-medium">{{
                                        _t('createvehicle','step5_plan_type_label') }}</label>
                                    <input id="plan-type" v-model="editPlanData.plan_type"
                                        class="w-full p-2 border rounded-md"
                                        :placeholder="_t('createvehicle', 'step5_plan_type_placeholder')" />
                                </div>

                                <div class="space-y-2">
                                    <label for="plan-value" class="text-sm font-medium">{{
                                        _t('createvehicle','step5_plan_value_label') }}</label>
                                    <input id="plan-value" v-model="editPlanData.plan_value" type="number"
                                        class="w-full p-2 border rounded-md"
                                        :placeholder="_t('createvehicle', 'step5_plan_value_placeholder')" />
                                </div>

                                <div class="space-y-2">
                                    <label for="plan-description" class="text-sm font-medium">{{
                                        _t('createvehicle','step5_plan_description_label') }}</label>
                                    <textarea id="plan-description" v-model="editPlanData.plan_description"
                                        class="w-full p-2 border rounded-md"
                                        :placeholder="_t('createvehicle', 'step5_plan_description_placeholder')"
                                        rows="3"></textarea>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-medium">{{
                                            _t('createvehicle','step5_plan_features_label') }}</label>
                                        <button @click="addFeature"
                                            class="text-sm px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            {{ _t('createvehicle', 'step5_add_feature_button') }}
                                        </button>
                                    </div>

                                    <div v-for="(feature, index) in editPlanData.features" :key="index"
                                        class="flex gap-2">
                                        <input v-model="editPlanData.features[index]"
                                            class="w-full p-2 border rounded-md"
                                            :placeholder="_t('createvehicle', 'step5_feature_placeholder')" />
                                        <button @click="removeFeature(index)"
                                            class="p-2 text-red-500 hover:bg-red-50 rounded-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                class="lucide lucide-trash-2">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                <line x1="10" x2="10" y1="11" y2="17"></line>
                                                <line x1="14" x2="14" y1="11" y2="17"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <button @click="isEditDialogOpen = false"
                                    class="px-4 py-2 border rounded-md hover:bg-gray-100">
                                    {{ _t('createvehicle', 'cancel_button') }}
                                </button>
                                <button @click="saveEditedPlan"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 ml-2">
                                    {{ _t('createvehicle', 'save_changes_button') }}
                                </button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <div
                        class="buttons flex justify-between gap-[1.5rem] mt-[2rem] pb-[4rem] max-[768px]:pb-0 max-[768px]:px-[1.5rem]">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="button" @click="nextStep">{{
                            _t('createvehicle','next_button') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','tip_title') }}</h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step4_tip_remuneration') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','information_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step2_information_secure_listing') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>


    <!-- Step-6: Addon Selection -->
    <div v-if="currentStep === 6" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[5rem] mb-[2rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem] max-[768px]:mb-0">
                        <p class="text-[1.75rem] font-medium max-[768px]:text-[1.2rem]">
                            {{ _t('createvehicle', 'step6_addons_title') }}
                        </p>
                        <span class="text-[0.75rem] text-customLightGrayColor font-medium">{{
                            _t('createvehicle','step6_addons_description') }}</span>
                    </div>
                    <div v-for="addon in addons" :key="addon.id"
                        class="flex justify-between gap-10 items-center border rounded-lg p-4 max-[768px]:mx-[1.5rem] max-[768px]:flex-col">
                        <!-- Left Section: Checkbox & Text -->
                        <div class="flex items-start gap-3 w-[55%] max-[768px]:w-full">
                            <input type="checkbox" :value="addon.id" v-model="selectedAddons" class="w-5 h-5 mt-1">
                            <div>
                                <h3 class="font-semibold text-lg max-[768px]:text-[1rem]">{{ addon.extra_name }}</h3>
                                <p class="text-gray-500 text-sm max-[768px]:text-[0.75rem]">{{ addon.description }}</p>
                            </div>
                        </div>

                        <!-- Right Section: Price & Quantity -->
                        <div class="flex  gap-4 items-end">
                            <div class="flex flex-col items-start">
                                <label for="price" class="text-sm text-gray-500">{{
                                    _t('createvehicle','step6_price_per_day_label') }}</label>
                                <input type="number" v-model="addonPrices[addon.id]"
                                    class="w-24 px-2 py-1 border rounded max-[768px]:!py-2" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-2">{{ _t('createvehicle', 'step6_quantity_label') }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <button @click="decrementQuantity(addon.id)"
                                        class="px-2 py-1 border rounded">-</button>
                                    <span class="px-3 py-1 bg-gray-100 rounded">{{ addonQuantities[addon.id] || '00'
                                        }}</span>
                                    <button @click="incrementQuantity(addon.id)"
                                        class="px-2 py-1 border rounded">+</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div
                        class="buttons flex justify-between gap-[1.5rem] mt-[2rem] pb-[4rem] max-[768px]:pb-0 max-[768px]:px-[1.5rem]">
                        <button class="button-secondary w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem]" type="button" @click="nextStep">{{
                            _t('createvehicle','next_button') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','tip_title') }}</h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step4_tip_remuneration') }}
                        </p>
                    </div>
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','information_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step2_information_secure_listing') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-7 -->
    <div v-if="currentStep === 7" class="overflow-x-hidden vehicle-listing h-screen md:overflow-y-hidden relative ">
        <div class="absolute inset-0 flex justify-between max-[768px]:relative max-[768px]:flex-col max-[768px]:h-auto">
            <div
                class="column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white">
                <div class="flex flex-col gap-5 w-[90%] max-[768px]:w-full">
                    <Link href="/" class="max-[768px]:hidden mt-[2rem]">
                    <ApplicationLogo />
                    </Link>
                    <AuthenticatedHeaderLayout class="hidden max-[768px]:block max-[768px]:border-b-0" />
                    <div class="mt-[5rem] mb-[2rem] max-[768px]:mt-0 max-[768px]:px-[1.5rem]">
                        <p class="text-[1.75rem] font-medium max-[768px]:text-[1.2rem]">
                            {{ _t('createvehicle', 'step7_upload_photos_title') }}
                        </p>

                        <span class="text-[0.75rem] text-customLightGrayColor font-medium">{{
                            _t('createvehicle','step7_upload_photos_description') }}</span>
                    </div>
                    <!-- Image Upload Section -->
                    <div class="flex flex-col gap-2 justify-center items-center border-[2px] rounded-[0.5rem] border-customPrimaryColor border-dashed py-10 transition-colors duration-200"
                        @dragenter="onDragEnter" @dragleave="onDragLeave" @dragover="onDragOver" @drop="onDrop">
                        <img :src="uploadIcon" alt="" class="max-[768px]:w-[25px]" />
                        <p class="max-[768px]:hidden">{{ _t('createvehicle', 'step7_drag_drop_text') }}</p>
                        <p class="text-customLightGrayColor font-medium max-[768px]:hidden">{{
                            _t('createvehicle','step7_or_text') }}</p>
                        <label for="images"
                            class="cursor-pointer bg-customPrimaryColor text-white px-4 py-2 rounded hover:bg-opacity-90 transition-colors">
                            {{ _t('createvehicle', 'step7_browse_files_button') }}
                        </label>
                        <input type="file" id="images" @change="handleFileUpload" multiple class="hidden"
                            accept="image/*" />
                        <div v-if="form.images.length"
                            class="image-preview-container mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 w-full px-4">
                            <div v-for="(image, index) in form.images" :key="index" class="image-preview relative group"
                                :class="{ 'border-4 border-blue-500 p-1': form.primary_image_index === index }">
                                <img :src="getImageUrl(image)" alt="Vehicle Image"
                                    class="w-full h-24 object-cover rounded" />
                                <button
                                    class="remove-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                    @click.prevent="removeImage(index)">✖</button>
                                                        <button v-if="form.primary_image_index !== index"
                            @click.prevent="setPrimaryImage(index)"
                            class="absolute bottom-1 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white text-xs px-2 py-1 rounded transition-opacity md:opacity-0 group-hover:md:opacity-100 max-[767px]:opacity-100">
                            {{ _t('createvehicle', 'step7_set_primary_button') }}
                        </button>

                                <span v-if="form.primary_image_index === index"
                                    class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">{{
                                        _t('createvehicle','step7_primary_badge') }}</span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm" :class="form.images.length < 5 ? 'text-red-500' : 'text-green-500'">{{
                            imageCountMessage }}</p>
                    </div>

                    <div
                        class="buttons flex justify-between mt-[2rem] pb-[4rem] max-[768px]:pb-0 max-[768px]:px-[1.5rem]">
                        <button class="button-secondary gap-[1.5rem] w-[15rem] max-[768px]:w-[10rem]" @click="prevStep">
                            {{ _t('createvehicle', 'back_button') }}
                        </button>
                        <PrimaryButton class="w-[15rem] max-[768px]:w-[10rem] disabled:opacity-50" type="button"
                            @click="submit" :disabled="!isImageCountValid">
                            {{ _t('createvehicle', 'submit_button') }}</PrimaryButton>
                    </div>
                </div>
            </div>
            <div
                class="column h-full w-[50%] flex-1 bg-customPrimaryColor relative max-[768px]:w-full max-[768px]:h-auto overflow-hidden">
                <div class="flex flex-col gap-10 items-center justify-center h-full max-[768px]:gap-0">
                    <div
                        class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">{{
                            _t('createvehicle','information_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">
                            {{ _t('createvehicle', 'step2_information_secure_listing') }}
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] max-[768px]:w-full p-[2rem]">
                        <img :src="warningSign" alt="" class="max-[768px]:w-[35px]" />
                        <h4 class="text-[1.5rem] font-medium max-[768px]:text-[1.2rem] max-[768px]:py-2">
                            {{ _t('createvehicle', 'need_help_title') }}
                        </h4>
                        <p class="max-[768px]:text-[0.875rem]">{{ _t('createvehicle', 'contact_us_on') }}</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>


    <!-- Add this right before the closing </div> of your main container -->
    <div v-if="showErrorDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md mx-auto">
            <div class="flex items-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 mr-2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="text-lg font-medium">{{ _t('createvehicle', 'invalid_upload_title') }}</h3>
            </div>
            <p class="mb-4">{{ errorMessage }}</p>
            <div class="flex justify-end">
                <button @click="closeErrorDialog"
                    class="bg-customPrimaryColor text-white px-4 py-2 rounded hover:bg-opacity-90 transition-colors">
                    {{ _t('createvehicle', 'ok_button') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import vendorBgimage from "../../../assets/vendorRegisterbgImage.png";
import warningSign from "../../../assets/WhiteWarningCircle.svg";
import circleImg from "../../../assets/circle.png";
import uploadIcon from "../../../assets/uploadIcon.svg";
import { computed, onMounted, reactive, ref, watch } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import LocationPicker from "@/Components/LocationPicker.vue";
import axios from "axios";
import InputLabel from "@/Components/InputLabel.vue";
// import input from "@/Components/input.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import L from 'leaflet' // Import Leaflet
import 'leaflet/dist/leaflet.css';
import { useToast } from 'vue-toastification'; // Add this import
import Select from "@/Components/ui/select/Select.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import { SelectContent, SelectGroup, SelectLabel, SelectTrigger, SelectValue } from "@/Components/ui/select";
import loader from "../../../assets/loader.gif";
import { usePage } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import vehicleColorsFromJson from '../../data/colors.json';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog'

const toast = useToast(); // Initialize toast
const addons = ref([]);
const selectedAddons = ref([]);
const addonPrices = ref({});
const addonQuantities = ref({});
// Form data
const form = useForm({
    category_id: null,
    brand: "",
    model: "",
    color: "",
    mileage: 0,
    transmission: "manual",
    fuel: "petrol",
    seating_capacity: 1,
    number_of_doors: 2,
    luggage_capacity: 1,
    horsepower: 70,
    co2: "",
    location: "",
    latitude: 'null',
    longitude: 'null',
    city: "",
    state: "",
    country: "",
    status: "available",
    features: [],
    featured: false,
    security_deposit: 0,
    // payment_method: "",
    payment_method: [],
    guidelines: "",
    price_per_day: null,
    price_per_week: null,
    price_per_month: null,
    weekly_discount: null,
    monthly_discount: null,
    preferred_price_type: 'day',
    limited_km: false,
    price_per_km: null,
    cancellation_available: false,

    // vehicle specifications fields
    registration_number: "",
    registration_country: "",
    registration_date: "",
    gross_vehicle_mass: 0,
    vehicle_height: 0,
    dealer_cost: 0,
    phone_number: "",

    // vehicle images
    images: [],
    primary_image_index: null,
    pickup_times: [],
    return_times: [],
    radius: 831867.4340914232,


    // Vehicle Benefit fields
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
    price_per_km_per_day: null,
    price_per_km_per_week: null,
    price_per_km_per_month: null,
    minimum_driver_age: null,

    selected_plans: [],

    selected_addons: selectedAddons.value,
    addon_prices: addonPrices.value,
    addon_quantities: addonQuantities.value,
    full_vehicle_address: null,

});
const props = defineProps({
    vehicle: {
        type: Object,
        required: false, // If the prop is not always passed
        default: null // Provide a default value
    },
});
const selectedTypes = reactive({
    day: true, // Daily is selected by default
    week: false,
    month: false
});


// This is for protection Plans
const plans = ref([]);
const selectedPlans = ref([]);
const isEditDialogOpen = ref(false);
const currentEditingPlan = ref(null);
const vehicleColors = ref(vehicleColorsFromJson);


// Temporary plan data for editing
const editPlanData = ref({
    plan_type: '',
    plan_value: 0,
    plan_description: '',
    features: []
});

const fetchPlans = async () => {
    try {
        const response = await axios.get('/api/plans');
        plans.value = response.data;

    } catch (error) {
        console.error('Error fetching plans:', error);
    }
};

const togglePlanSelection = (plan) => {
    const index = selectedPlans.value.findIndex((p) => p.id === plan.id);

    if (index > -1) {
        // If the plan is already selected, allow deselection
        selectedPlans.value.splice(index, 1);
    } else {
        // Add plan to selected list
        selectedPlans.value.push({ ...plan });
    }
};


const openEditDialog = (plan) => {
    currentEditingPlan.value = plan;
    // Clone the plan data to avoid direct mutation
    editPlanData.value = {
        plan_type: plan.plan_type,
        plan_value: plan.plan_value,
        plan_description: plan.plan_description,
        features: [...plan.features]
    };
    isEditDialogOpen.value = true;
};

const saveEditedPlan = () => {
    if (!currentEditingPlan.value) return;

    // Update the plan in the plans array
    const planIndex = plans.value.findIndex(p => p.id === currentEditingPlan.value.id);
    if (planIndex > -1) {
        plans.value[planIndex] = {
            ...plans.value[planIndex],
            plan_type: editPlanData.value.plan_type,
            plan_value: editPlanData.value.plan_value,
            plan_description: editPlanData.value.plan_description,
            features: editPlanData.value.features
        };
    }

    // Also update the plan if it's in the selectedPlans array
    const selectedPlanIndex = selectedPlans.value.findIndex(p => p.id === currentEditingPlan.value.id);
    if (selectedPlanIndex > -1) {
        selectedPlans.value[selectedPlanIndex] = {
            ...selectedPlans.value[selectedPlanIndex],
            plan_type: editPlanData.value.plan_type,
            plan_value: editPlanData.value.plan_value,
            plan_description: editPlanData.value.plan_description,
            features: editPlanData.value.features
        };
    }

    isEditDialogOpen.value = false;
    currentEditingPlan.value = null;

    toast.success('Plan details updated successfully!', {
        position: 'top-right',
        timeout: 3000,
        closeOnClick: true,
        pauseOnHover: true,
        draggable: true,
    });
};

const addFeature = () => {
    editPlanData.value.features.push('');
};

const removeFeature = (index) => {
    editPlanData.value.features.splice(index, 1);
};


const addPickupTime = () => {
    form.pickup_times.push(""); // Push empty value for a new time input
};

const removePickupTime = (index) => {
    form.pickup_times.splice(index, 1);
};

const addReturnTime = () => {
    form.return_times.push("");
};

const removeReturnTime = (index) => {
    form.return_times.splice(index, 1);
};

const normalizeTime = (field, index) => {
    const time = form[field][index];
    if (time) {
        // Convert to 24-hour format
        const [hours, minutes] = time.split(":");
        const date = new Date();
        date.setHours(parseInt(hours, 10));
        date.setMinutes(parseInt(minutes, 10));
        const normalizedTime = date.toLocaleTimeString("en-US", {
            hour12: false,
            hour: "2-digit",
            minute: "2-digit"
        });
        form[field][index] = normalizedTime; // Update with 24-hour format (e.g., "17:33")
    }
};

// Function to format the date to 'YYYY-MM-DD'
const formatDate = (value) => {
    if (!value) {
        form.registration_date = null
        return
    }
    const date = new Date(value)
    form.registration_date = date.toISOString().split('T')[0] // Sets to 'YYYY-MM-DD'
}

onMounted(() => {
    fetchPlans();
});



// Add addonQuantities ref

const fetchAddons = async () => {
    try {
        const response = await axios.get('/api/booking-addons');
        addons.value = response.data;

        // Prefill addon prices and quantities
        addons.value.forEach(addon => {
            addonPrices.value[addon.id] = addon.price || 0;
            addonQuantities.value[addon.id] = 1;
        });
    } catch (error) {
        console.error('Error fetching addons:', error);
    }
};

const incrementQuantity = (addonId) => {
    if (!addonQuantities.value[addonId]) {
        addonQuantities.value[addonId] = 1;
    }
    addonQuantities.value[addonId]++;
};

const decrementQuantity = (addonId) => {
    if (addonQuantities.value[addonId] > 1) {
        addonQuantities.value[addonId]--;
    }
};

onMounted(() => {
    fetchAddons();
});


// fetching the vehicle categories from the database thorough api
const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await axios.get("/api/vehicle-categories");
        categories.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicle categories:", error);
    }
};
const paymentMethodsArray = computed(() => {
    if (props.vehicle && props.vehicle.payment_method) {
        try {
            return JSON.parse(props.vehicle.payment_method);
        } catch (error) {
            console.error("Error parsing payment methods:", error);
            return [];
        }
    } else if (form.payment_method) {
        return form.payment_method;
    }
    return [];
});


const isLoading = ref(false);
watch(isLoading, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
// Submit form data
const submit = () => {
    isLoading.value = true;
    if (form.images.length < 5) return;
    form.selected_plans = selectedPlans.value;
    form.selected_addons = selectedAddons.value;
    form.addon_prices = addonPrices.value;
    form.addon_quantities = addonQuantities.value;

    // Construct full_vehicle_address
    const addressParts = [form.location, form.city, form.state, form.country];
    form.full_vehicle_address = addressParts.filter(Boolean).join(', ');
    form.post(route("vehicles.store"), {
        onSuccess: () => {
            toast.success('Vehicle Added Successfully', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });

            isLoading.value = false;
        },
        onError: (errors) => {
            toast.error('Something went wrong. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            console.error(errors);
        },
    });
};

// For range slider tip value
const dailyTooltipPosition = computed(() => ({
    left: `${(form.price_per_day / 200) * 100}%`,
}));

const weeklyTooltipPosition = computed(() => ({
    left: `${(form.price_per_week / 1000) * 100}%`,
}));

const monthlyTooltipPosition = computed(() => ({
    left: `${(form.price_per_month / 3000) * 100}%`,
}));


const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB in bytes
const VALID_FORMATS = ['image/jpeg', 'image/jpg', 'image/png'];
const errorMessage = ref('');
const showErrorDialog = ref(false);

// Method to handle file uploads
const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    validateAndAddFiles(files);
};

// Generate image preview URL
const getImageUrl = (image) => {
    return URL.createObjectURL(image);
};

// Remove image from preview
const removeImage = (index) => {
    form.images.splice(index, 1);

    // Adjust primary_image_index if necessary
    if (form.images.length === 0) {
        form.primary_image_index = null;
    } else if (form.primary_image_index === index) {
        // If the removed image was primary, set the first image as new primary
        form.primary_image_index = 0;
    } else if (form.primary_image_index > index) {
        // If an image before the primary was removed, decrement primary_image_index
        form.primary_image_index--;
    }
};

const setPrimaryImage = (index) => {
    form.primary_image_index = index;
};


// Vehicle Features
const availableFeatures = ref([]); // Renamed and will hold category-specific features

const fetchFeaturesForCategory = async (categoryId) => {
    if (!categoryId) {
        availableFeatures.value = [];
        form.features = []; // Clear selected features if no category
        return;
    }
    try {
        // Ensure the route name 'api.categories.features' is correct and returns expected data
        // The controller returns [{id, feature_name, icon_url}, ...]
        const response = await axios.get(route('api.categories.features', categoryId));
        availableFeatures.value = response.data.map(feature => ({
            id: feature.id, // Keep id if needed for keys or future use
            name: feature.feature_name, // Use 'name' to match existing template iteration (feature.name)
            icon_url: feature.icon_url
        }));
        form.features = []; // Clear previously selected features when category changes
    } catch (error) {
        console.error(`Error fetching vehicle features for category ${categoryId}:`, error);
        availableFeatures.value = [];
        form.features = [];
    }
};

// Watch for category_id changes
watch(() => form.category_id, (newCategoryId, oldCategoryId) => {
    if (newCategoryId !== oldCategoryId) {
        fetchFeaturesForCategory(newCategoryId);
    }
}, { immediate: false }); // Set to true if initial fetch needed and category_id might be pre-filled

onMounted(() => {
    fetchCategories();
    // fetchFeatures(); // Old call that fetched all features is removed.
    // If form.category_id could be pre-filled (e.g., when editing), fetch features for it.
    if (props.vehicle && props.vehicle.category_id) { // Assuming vehicle prop might exist for editing
        form.category_id = props.vehicle.category_id; // Ensure form.category_id is set
        fetchFeaturesForCategory(props.vehicle.category_id);
        // If editing, you might also want to pre-select features based on props.vehicle.features
        if (props.vehicle.features && Array.isArray(props.vehicle.features)) {
            // Assuming props.vehicle.features is an array of feature names or objects with a name property
            form.features = props.vehicle.features.map(f => typeof f === 'string' ? f : f.name);
        }
    } else if (form.category_id) { // If form.category_id is set by other means on init
        fetchFeaturesForCategory(form.category_id);
    }
});

const imageCountMessage = computed(() => {
    const count = form.images.length;
    if (count === 0) return "No images uploaded";
    return `${count} image${count === 1 ? '' : 's'} uploaded${count < 5 ? ' (minimum 5 required)' : ''}`;
});

// Add these methods for drag and drop functionality
const dragCounter = ref(0);

const onDragEnter = (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.value++;
    e.currentTarget.classList.add('border-green-500');
};

const onDragLeave = (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.value--;
    if (dragCounter.value === 0) {
        e.currentTarget.classList.remove('border-green-500');
    }
};

const onDragOver = (e) => {
    e.preventDefault();
    e.stopPropagation();
};

const onDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.value = 0;
    e.currentTarget.classList.remove('border-green-500');

    const files = Array.from(e.dataTransfer.files);
    if (files.length) {
        validateAndAddFiles(files);
    }
};

// New validation function
const validateAndAddFiles = (files) => {
    const validFiles = [];
    let hasErrors = false;

    for (const file of files) {
        // Check file format
        if (!VALID_FORMATS.includes(file.type)) {
            errorMessage.value = 'Please upload only JPG, JPEG, or PNG images.';
            hasErrors = true;
            break;
        }

        // Check file size
        if (file.size > MAX_FILE_SIZE) {
            errorMessage.value = 'Image size should be less than 10MB.';
            hasErrors = true;
            break;
        }

        validFiles.push(file);
    }

    if (hasErrors) {
        showErrorDialog.value = true;
    } else if (validFiles.length > 0) {
        form.images = [...form.images, ...validFiles];
        if (form.primary_image_index === null && form.images.length > 0) {
        form.primary_image_index = 0;
    }
    }
};

// Add this to your template section (right before the closing </div> of your main container)
const closeErrorDialog = () => {
    showErrorDialog.value = false;
    errorMessage.value = '';
};

let map = null;
let marker = null // Marker instance
const currentStep = ref(0);

const errors = reactive({
    category_id: '',
    brand: '',
    model: '',
    color: '',
    mileage: '',
    horsepower: '',
    co2: '',
    registration_number: '',
    registration_country: '',
    registration_date: '',
    phone_number: '',
    location: '',
    latitude: '',
    longitude: '',
    security_deposit: '',
    payment_method: '',
    minimum_driver_age: '',
    price_per_day: '',
    price_per_week: '',
    price_per_month: '',
    addon_prices: '',
    images: '',
    pickup_times: '',
    return_times: '',
});

const nextStep = () => {
    let isValid = true;

    // Clear previous errors
    Object.keys(errors).forEach(key => errors[key] = '');

    // Step-specific validation
    switch (currentStep.value) {
        case 1: // Vehicle Category and Details
            if (!form.category_id) {
                isValid = false;
                errors.category_id = 'Please select a vehicle category';
            }
            if (!form.brand) {
                isValid = false;
                errors.brand = 'Please enter the vehicle brand';
            }
            if (!form.model) {
                isValid = false;
                errors.model = 'Please enter the vehicle model';
            }
            if (!form.color) {
                isValid = false;
                errors.color = 'Please enter the vehicle color';
            }
            if (!form.mileage) {
                isValid = false;
                errors.mileage = 'Please enter the vehicle mileage';
            }
            if (!form.horsepower) {
                isValid = false;
                errors.horsepower = 'Please enter the vehicle horsepower';
            }
            if (!form.co2) {
                isValid = false;
                errors.co2 = 'Please enter the vehicle CO2 emissions';
            }
            if (!form.features || form.features.length === 0) {
                isValid = false;
                errors.features = 'Please select at least one vehicle feature';
            }
            break;

        case 2: // Technical Specifications
            if (!form.registration_number) {
                isValid = false;
                errors.registration_number = 'Please enter the registration number';
            }
            if (!form.registration_country) {
                isValid = false;
                errors.registration_country = 'Please select the registration country';
            }
            if (!form.registration_date) {
                isValid = false;
                errors.registration_date = 'Please enter the registration date';
            }
            if (!form.phone_number) {
                isValid = false;
                errors.phone_number = 'Please enter the phone number';
            }
            break;

        case 3: // Location
            if (!form.location || !form.latitude || !form.longitude) {
                isValid = false;
                errors.location = 'Please select a valid location';
            }
            break;

        case 4: // Pricing
            if (!form.security_deposit) {
                isValid = false;
                errors.security_deposit = 'Please enter the security deposit';
            }
            if (!form.payment_method) {
                isValid = false;
                errors.payment_method = 'Please select a payment method';
            }
            if (!form.minimum_driver_age) {
                isValid = false;
                errors.minimum_driver_age = 'Please enter the minimum driver age';
            }
            if (!form.price_per_day && !form.price_per_week && !form.price_per_month) {
                isValid = false;
                errors.price_per_day = 'Please enter at least one pricing option';
            }

            // Kilometer Limit Validation
            if (form.limited_km_per_day && !form.limited_km_per_day_range) {
                isValid = false;
                errors.limited_km_per_day_range = 'Please enter KM limit per day';
            }
            if (form.limited_km_per_day && !form.price_per_km_per_day) {
                isValid = false;
                errors.price_per_km_per_day = 'Please enter price per extra KM per day';
            }

            if (form.limited_km_per_week && !form.limited_km_per_week_range) {
                isValid = false;
                errors.limited_km_per_week_range = 'Please enter KM limit per week';
            }
            if (form.limited_km_per_week && !form.price_per_km_per_week) {
                isValid = false;
                errors.price_per_km_per_week = 'Please enter price per extra KM per week';
            }

            if (form.limited_km_per_month && !form.limited_km_per_month_range) {
                isValid = false;
                errors.limited_km_per_month_range = 'Please enter KM limit per month';
            }
            if (form.limited_km_per_month && !form.price_per_km_per_month) {
                isValid = false;
                errors.price_per_km_per_month = 'Please enter price per extra KM per month';
            }

            // Cancellation Policy Validation
            if (form.cancellation_available_per_day && !form.cancellation_available_per_day_date) {
                isValid = false;
                errors.cancellation_available_per_day_date = 'Please enter required notice days for daily cancellation';
            }
            if (form.cancellation_available_per_week && !form.cancellation_available_per_week_date) {
                isValid = false;
                errors.cancellation_available_per_week_date = 'Please enter required notice days for weekly cancellation';
            }
            if (form.cancellation_available_per_month && !form.cancellation_available_per_month_date) {
                isValid = false;
                errors.cancellation_available_per_month_date = 'Please enter required notice days for monthly cancellation';
            }
            if (!form.pickup_times || form.pickup_times.length < 1) {
                isValid = false;
                errors.pickup_times = 'Please add at least one pickup time';
            }
            if (!form.return_times || form.return_times.length < 1) {
                isValid = false;
                errors.return_times = 'Please add at least one return time';
            }
            break;


        case 5: // Plan Selection
            if (selectedPlans.value.length === 0) {
                isValid = true;
            }
            break;

        case 6: // Addon Selection
            if (selectedAddons.value.length === 0) {
                isValid = true;
            }
            break;

        case 7: // Image Upload
            if (form.images.length < 5) {
                isValid = false;
                errors.images = 'Please upload at least 5 images';
            }
            break;
    }

    // If validation passes, move to next step
    if (isValid) {
        if (currentStep.value < 7) {
            currentStep.value++;
            if (currentStep.value === 3) {
                initializeMap();
            }
        }
    }
};
const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const isImageCountValid = computed(() => form.images.length >= 5);
// leaflet map
const mapform = ref({
    location: '',
    latitude: null,
    longitude: null,
    radius: 831867.4340914232
})
const searchResults = ref([]);

const handleSearchInput = async () => {
    // Ensure the location input is at least 3 characters long
    if (form.location.length < 3) {
        searchResults.value = [];
        return;
    }

    try {
        const response = await axios.get(
            `/api/geocoding/autocomplete?text=${encodeURIComponent(form.location)}`
        );
        searchResults.value = response.data.features; // Store fetched results
    } catch (error) {
        console.error('Error fetching locations:', error);
    }
};

const selectLocation = (location) => {
    form.location = location.address;
    form.latitude = location.latitude;
    form.longitude = location.longitude;
    form.city = location.city;
    form.state = location.state;
    form.country = location.country;
};

const initializeMap = () => {
    // Check if the map already exists and remove it
    if (map) {
        map.remove(); // Remove the existing map instance
    }
    // Initialize the map
    map = L.map("map").setView([20.5937, 78.9629], 5); // Default to India

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
};

const countries = ref([]);
const selectedCountry = ref("");


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

// Get phone code based on selected country
const selectedPhoneCode = computed(() => {
    const country = countries.value.find(c => c.code === form.registration_country);
    return country ? `${country.phone_code} ` : "";
});

// Watch for country changes and update phone number prefix
watch(() => form.registration_country, (newVal) => {
    if (newVal) {
        form.phone_number = selectedPhoneCode.value; // Update phone number with code
    }
});



watch(
    () => form.limited_km_per_day,
    (newValue) => {
        if (!newValue) {
            form.limited_km_per_day_range = null;
            form.price_per_km_per_day = null;
        }
    }
);

watch(
    () => form.limited_km_per_week,
    (newValue) => {
        if (!newValue) {
            form.limited_km_per_week_range = null;
            form.price_per_km_per_week = null;
        }
    }
);

watch(
    () => form.limited_km_per_month,
    (newValue) => {
        if (!newValue) {
            form.limited_km_per_month_range = null;
            form.price_per_km_per_month = null;
        }
    }
);

watch(
    () => form.cancellation_available_per_day,
    (newValue) => {
        if (!newValue) {
            form.cancellation_available_per_day_date = null;
        }
    }
);

watch(
    () => form.cancellation_available_per_week,
    (newValue) => {
        if (!newValue) {
            form.cancellation_available_per_week_date = null;
        }
    }
);

watch(
    () => form.cancellation_available_per_month,
    (newValue) => {
        if (!newValue) {
            form.cancellation_available_per_month_date = null;
        }
    }
);

</script>



<style scoped>
select {
    width: 100%;
}

.vehicle-listing input[type="text"],
.vehicle-listing input[type="number"],
.vehicle-listing input[type="email"] {
    width: 100%;
}

.vehicle-listing label {
    font-size: 0.875rem;
}

.vehicle-listing input,
.vehicle-listing select {
    font-size: 0.875rem;
}

label {
    margin-bottom: 0.5rem;
}

/* .price-slider {
    position: relative;
    width: 100%;
    max-width: 500px;
}

.price-slider input {
    padding: 0.25rem 0;
} */



input[type="range"] {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    appearance: none;
    outline: none;
}

input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    background: white;
    border: 2px solid #11364a;
    border-radius: 50%;
    cursor: pointer;
    margin-top: -0.5rem;
}

input[type="range"]::-webkit-slider-runnable-track {
    background: linear-gradient(to right,
            #11364a 0%,
            #11364a var(--value),
            #e5e7eb var(--value),
            #e5e7eb 100%);
    height: 4px;
    border-radius: 2px;
}

.price-tooltip {
    position: absolute;
    bottom: -90%;
    transform: translateX(-50%);
    background: white;
    color: #153b4f;
    font-weight: 600;
    border: 1px solid #153b4f;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-size: 1rem;
    white-space: nowrap;
}

.price-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
}

.selected-features {
    margin-top: 10px;
}

.selected-features ul {
    list-style-type: none;
    padding: 0;
}

.selected-features li {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.selected-features button {
    margin-left: 10px;
    background-color: red;
    color: white;
    border: none;
    cursor: pointer;
}

@import 'leaflet/dist/leaflet.css';

.marker-pin {
    width: auto;
    min-width: 50px;
    height: 30px;
    background: white;
    border: 2px solid #666;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transform: translate3d(0, 0, 1000px);
}

.marker-pin span {
    color: black;
    font-weight: bold;
    font-size: 12px;
    padding: 0 8px;
}

.custom-div-icon {
    background: none;
    border: none;
}

/* Leaflet pane z-index overrides */
.leaflet-pane.leaflet-marker-pane,
.leaflet-pane.leaflet-popup-pane {
    z-index: 1000 !important;
}

.leaflet-pane.leaflet-tile-pane {
    z-index: 200;
}

.leaflet-pane.leaflet-overlay-pane {
    z-index: 400;
}

.leaflet-marker-icon {
    transform: translate3d(0, 0, 1000px);
}

.leaflet-popup {
    z-index: 1001 !important;
}

/* Hardware acceleration */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-popup {
    will-change: transform;
    transform: translate3d(0, 0, 0);
}

/* Additional styles to ensure markers are always visible */
.leaflet-container {
    z-index: 1;
}

.leaflet-control-container {
    z-index: 2000;
}

#map {
    height: 600px;
    width: 100%;
    margin-top: 1rem;
}

input,
textarea,
select {
    border-radius: 0.75rem;
    border: 1px solid rgba(43, 43, 43, 0.50) !important;
    padding: 0.95rem;
}

.image-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.image-preview {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 5px;
    overflow: hidden;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 5px;
}

.remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 0, 0, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom error styling for the datepicker */
.dp__error .dp__input {
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}


:deep(.dp__input) {
    padding: 1rem 1rem 1rem 2.5rem;
    border-radius: 12px;
    border: 1px solid #2b2b2b99;
    width: 100%;
}

:deep(.dp__menu) {
    border-radius: 0.5rem;
}

::-webkit-scrollbar {
    display: none;
}


@media screen and (max-width:768px) {
    :deep(.dp__input) {
        font-size: 0.75rem;
    }
}
</style>
