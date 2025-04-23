<template>
    <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e]">
        <div class="flex justify-center flex-col items-center h-full w-full">
            <img :src=loader alt="" class="w-[150px]">
            <p class="text-[white] text-[1.5rem]">Updating..</p>
        </div>
    </div>
    <MyProfileLayout>
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Edit Vehicle</h2>
        </div>
        <div class="py-12">
            <div class="mx-auto">
                <form @submit.prevent="updateVehicle">
                    <Tabs defaultValue="basic" class="w-full">
                        <TabsList class="grid w-full grid-cols-5">
                            <TabsTrigger value="basic">Basic Information</TabsTrigger>
                            <TabsTrigger value="specifications">Specifications</TabsTrigger>
                            <TabsTrigger value="pricing">Pricing & Features</TabsTrigger>
                            <TabsTrigger value="guidelines">Guidelines & Timings</TabsTrigger>
                            <TabsTrigger value="images">Images</TabsTrigger>
                        </TabsList>

                        <TabsContent value="basic">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="category_id">Vehicle Category:</InputLabel>
                                    <Select v-model="form.category_id" required>
                                        <SelectTrigger id="category_id">
                                            <SelectValue placeholder="Select a category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Categories</SelectLabel>
                                                <SelectItem v-for="category in categories" :key="category.id"
                                                    :value="category.id">
                                                    {{ category.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="brand">Brand:</InputLabel>
                                    <Input type="text" v-model="form.brand" id="brand" required />
                                </div>
                                <div>
                                    <InputLabel for="model">Model:</InputLabel>
                                    <Input type="text" v-model="form.model" id="model" required />
                                </div>
                                <div>
                                    <InputLabel for="color">Color:</InputLabel>
                                    <Input type="text" v-model="form.color" id="color" required />
                                </div>
                                <div>
                                    <InputLabel for="mileage">Mileage:</InputLabel>
                                    <Input type="number" v-model.number="form.mileage" id="mileage" required />
                                </div>
                                <div>
                                    <InputLabel for="transmission">Transmission:</InputLabel>
                                    <Select v-model="form.transmission" required>
                                        <SelectTrigger id="transmission">
                                            <SelectValue placeholder="Select transmission" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Transmission</SelectLabel>
                                                <SelectItem value="manual">Manual</SelectItem>
                                                <SelectItem value="automatic">Automatic</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="fuel">Fuel:</InputLabel>
                                    <Select v-model="form.fuel" required>
                                        <SelectTrigger id="fuel">
                                            <SelectValue placeholder="Select fuel type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Fuel</SelectLabel>
                                                <SelectItem value="petrol">Petrol</SelectItem>
                                                <SelectItem value="diesel">Diesel</SelectItem>
                                                <SelectItem value="electric">Electric</SelectItem>
                                                <SelectItem value="hybrid">Hybrid</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="status">Status:</InputLabel>
                                    <Select v-model="form.status" required>
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Status</SelectLabel>
                                                <SelectItem value="available">Available</SelectItem>
                                                <SelectItem value="rented">Rented</SelectItem>
                                                <SelectItem value="maintenance">Maintenance</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="specifications">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="seating_capacity">Seating Capacity:</InputLabel>
                                    <Select v-model.number="form.seating_capacity" required>
                                        <SelectTrigger id="seating_capacity">
                                            <SelectValue placeholder="Select seating capacity" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Seating Capacity</SelectLabel>
                                                <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="number_of_doors">Number of Doors:</InputLabel>
                                    <Select v-model.number="form.number_of_doors" required>
                                        <SelectTrigger id="number_of_doors">
                                            <SelectValue placeholder="Select number of doors" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Number of Doors</SelectLabel>
                                                <SelectItem v-for="num in 8" :key="num" :value="num">{{ num }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <InputLabel for="luggage_capacity">Luggage Capacity:</InputLabel>
                                    <Input type="number" v-model.number="form.luggage_capacity" id="luggage_capacity"
                                        required />
                                </div>
                                <div>
                                    <InputLabel for="horsepower">Horsepower:</InputLabel>
                                    <Input type="number" v-model.number="form.horsepower" id="horsepower" required />
                                </div>
                                <div>
                                    <InputLabel for="co2">CO2 Emissions:</InputLabel>
                                    <Input type="text" v-model="form.co2" id="co2" required />
                                </div>
                                <div>
                                    <InputLabel for="registration_number">Registration Number:</InputLabel>
                                    <Input type="text" v-model="form.registration_number" id="registration_number"
                                        required />
                                </div>
                                <div class="relative w-full">
                                    <InputLabel class="text-black" for="registration_country">Registration Country:
                                    </InputLabel>

                                    <div class="relative">
                                        <Select v-model="form.registration_country">
                                            <SelectTrigger
                                                class="w-full p-[1.5rem] border-customLightGrayColor rounded-[12px]">
                                                <SelectValue placeholder="Select a country" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Countries</SelectLabel>
                                                    <SelectItem v-for="country in countries" :key="country.code"
                                                        :value="country.code">
                                                        {{ country.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>

                                        <!-- Dynamic Flag -->
                                        <img v-if="form.registration_country"
                                            :src="getFlagUrl(form.registration_country)" alt="Country Flag"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-[2.1rem] h-[1.5rem] rounded" />
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="registration_date">Registration Date:</InputLabel>
                                    <Input type="date" v-model="form.registration_date" id="registration_date"
                                        required />
                                </div>
                                <div>
                                    <InputLabel for="gross_vehicle_mass">Gross Vehicle Mass:</InputLabel>
                                    <Input type="number" v-model.number="form.gross_vehicle_mass"
                                        id="gross_vehicle_mass" required />
                                </div>
                                <div>
                                    <InputLabel for="vehicle_height">Vehicle Height:</InputLabel>
                                    <Input type="number" v-model.number="form.vehicle_height" id="vehicle_height"
                                        required step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="phone_number">Phone Number:</InputLabel>
                                    <Input type="text" v-model="form.phone_number" id="phone_number" required />
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="pricing">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="security_deposit">Security Deposit:</InputLabel>
                                    <Input type="number" v-model.number="form.security_deposit" id="security_deposit"
                                        required min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="dealer_cost">Dealer Cost:</InputLabel>
                                    <Input type="number" v-model.number="form.dealer_cost" id="dealer_cost" required
                                        step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_day">Price Per Day:</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_day" id="price_per_day" required
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_week">Price Per Week:</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_week" id="price_per_week"
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="price_per_month">Price Per Month:</InputLabel>
                                    <Input type="number" v-model.number="form.price_per_month" id="price_per_month"
                                        min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="weekly_discount">Weekly Discount:</InputLabel>
                                    <Input type="number" v-model.number="form.weekly_discount" id="weekly_discount"
                                        min="0" max="1000.00" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="monthly_discount">Monthly Discount:</InputLabel>
                                    <Input type="number" v-model.number="form.monthly_discount" id="monthly_discount"
                                        min="0" max="10000.00" step="0.01" />
                                </div>

                                <!-- New fields -->
                                <div class="flex gap-3 flex-col col-span-2">
                                    <span class="text-[1.2rem] font-medium">Rental Conditions & Benefits</span>

                                    <!-- Limited KM Per Day -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_day"
                                            id="limited_km_per_day" class="w-auto" />
                                        <InputLabel for="limited_km_per_day" class="mb-0">Limited Kilometers Per Day:
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_day" class="w-[50%]">
                                        <InputLabel for="limited_km_per_day_range">KM Limit Per Day:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_day_range"
                                            id="limited_km_per_day_range" />
                                    </div>

                                    <!-- Limited KM Per Week -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_week"
                                            id="limited_km_per_week" class="w-auto" />
                                        <InputLabel for="limited_km_per_week" class="mb-0">Limited Kilometers Per Week:
                                        </InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_week" class="w-[50%]">
                                        <InputLabel for="limited_km_per_week_range">KM Limit Per Week:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_week_range"
                                            id="limited_km_per_week_range" />
                                    </div>

                                    <!-- Limited KM Per Month -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.limited_km_per_month"
                                            id="limited_km_per_month" class="w-auto" />
                                        <InputLabel for="limited_km_per_month" class="mb-0">Limited Kilometers Per Month:</InputLabel>
                                    </div>
                                    <div v-if="form.benefits.limited_km_per_month" class="w-[50%]">
                                        <InputLabel for="limited_km_per_month_range">KM Limit Per Month:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.limited_km_per_month_range"
                                            id="limited_km_per_month_range" />
                                    </div>

                                    <!-- Cancellation Available Per Day -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_day"
                                            id="cancellation_available_per_day" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_day" class="mb-0">Cancellation
                                            Available Per Day:</InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_day" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_day_date">Cancellation Allowed Until
                                            (Days):</InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_day_date"
                                            id="cancellation_available_per_day_date" min="0" />
                                    </div>

                                    <!-- Cancellation Available Per Week -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_week"
                                            id="cancellation_available_per_week" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_week" class="mb-0">Cancellation
                                            Available Per Week:</InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_week" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_week_date">Cancellation Allowed
                                            Until (Weeks):</InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_week_date"
                                            id="cancellation_available_per_week_date" min="0" />
                                    </div>

                                    <!-- Cancellation Available Per Month -->
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.benefits.cancellation_available_per_month"
                                            id="cancellation_available_per_month" class="w-auto" />
                                        <InputLabel for="cancellation_available_per_month" class="mb-0">Cancellation
                                            Available Per Month:</InputLabel>
                                    </div>
                                    <div v-if="form.benefits.cancellation_available_per_month" class="w-[50%]">
                                        <InputLabel for="cancellation_available_per_month_date">Cancellation Allowed
                                            Until (Months):</InputLabel>
                                        <Input type="number"
                                            v-model.number="form.benefits.cancellation_available_per_month_date"
                                            id="cancellation_available_per_month_date" min="0" />
                                    </div>

                                    <!-- Price Per KM -->
                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_day">Price Per KM Per Day:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_day"
                                            id="price_per_km_per_day" min="0" step="0.01" />
                                    </div>

                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_week">Price Per KM Per Week:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_week"
                                            id="price_per_km_per_week" min="0" step="0.01" />
                                    </div>

                                    <div class="w-[50%]">
                                        <InputLabel for="price_per_km_per_month">Price Per KM Per Month:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.price_per_km_per_month"
                                            id="price_per_km_per_month" min="0" step="0.01" />
                                    </div>

                                    <!-- Minimum Driver Age -->
                                    <div class="w-[50%]">
                                        <InputLabel for="minimum_driver_age">Minimum Driver Age:</InputLabel>
                                        <Input type="number" v-model.number="form.benefits.minimum_driver_age"
                                            id="minimum_driver_age" min="18" required />
                                    </div>

                                </div>


                                <div class="col-span-2">
                                    <InputLabel for="payment_method">Payment Methods:</InputLabel>
                                    <div class="flex items-center gap-10 flex-wrap">
                                        <label class="flex gap-2 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="credit_card"
                                                class="w-auto" />
                                            Credit Card
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cheque"
                                                class="w-auto" />
                                            Cheque
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="bank_wire"
                                                class="w-auto" />
                                            Bank Wire
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cryptocurrency"
                                                class="w-auto" />
                                            Cryptocurrency
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cash"
                                                class="w-auto" />
                                            Cash
                                        </label>
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <InputLabel for="features">Features:</InputLabel>
                                    <div class="flex gap-10 flex-wrap">
                                        <label v-for="feature in features" :key="feature.id"
                                            class="flex items-center text-nowrap gap-2">
                                            <input type="checkbox" v-model="form.features" :value="feature.name"
                                                class="w-auto" />
                                            {{ feature.name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="guidelines">
                            <div>
                                <InputLabel for="guidelines">Guidelines:</InputLabel>
                                <textarea type="text" v-model="form.guidelines" id="guidelines" required
                                    class="border p-2 rounded-lg w-full" />
                            </div>
                            <div class="time-selector p-6 bg-gray-50 rounded-xl shadow-lg w-full">
                                <p>Choose Your Pickup and Return Time for the vehicle</p>
                                <div class="grid grid-cols-2 gap-10">
                                    <div>
                                        <!-- Pickup Times Section -->
                                        <label class="block text-lg font-semibold text-gray-800 mb-2">Pickup
                                            Times</label>
                                        <div v-for="(time, index) in form.pickup_times" :key="'pickup-' + index"
                                            class="time-input-group flex items-center mb-3">
                                            <input type="time" v-model="form.pickup_times[index]"
                                                class="time-input max-[768px]:text-[0.75rem] w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                            <button type="button" @click="removePickupTime(index)" title="Remove"
                                                class="ml-1 text-red-600 hover:bg-red-50">
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

                                        <button type="button" @click="addPickupTime"
                                            class="w-full py-2 max-[768px]:text-[0.75rem] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                            + Add Pickup Time
                                        </button>
                                    </div>
                                    <div>
                                        <!-- Return Times Section -->
                                        <label class="block text-lg font-semibold text-gray-800 mb-2">Return
                                            Times</label>
                                        <div v-for="(time, index) in form.return_times" :key="'return-' + index"
                                            class="time-input-group flex items-center mb-3">
                                            <input type="time" v-model="form.return_times[index]"
                                                class="time-input max-[768px]:text-[0.75rem] w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 bg-white shadow-sm" />
                                            <button type="button" @click="removeReturnTime(index)" title="Remove"
                                            class="ml-1 text-red-600 hover:bg-red-50">
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

                                        <button type="button" @click="addReturnTime"
                                            class="w-full py-2 max-[768px]:text-[0.75rem] bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md">
                                            + Add Return Time
                                        </button>
                                    </div>
                                </div>



                            </div>
                        </TabsContent>

                        <TabsContent value="images">
                            <div class="grid gap-6">
                                <!-- Current Images -->
                                <div v-if="props.vehicle && props.vehicle.images && props.vehicle.images.length > 0">
                                    <h3 class="font-medium text-lg mb-3">Current Images</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                                        <div v-for="image in props.vehicle.images" :key="image.id"
                                            class="relative group border rounded-lg overflow-hidden h-48">
                                            <img :src="`${image.image_url}`" alt="Vehicle image"
                                                class="w-full h-full object-cover" />
                                            <div class="absolute top-0 right-0 p-1">
                                                <button type="button" @click="deleteImage(props.vehicle.id, image.id)"
                                                    class="bg-white text-red-600 rounded-full p-1 hover:bg-red-50 transition-colors">
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
                                            <div
                                                class="absolute bottom-0 left-0 right-0 bg-black/70 text-white p-1 text-center text-xs">
                                                {{ image.image_type === 'primary' ? 'Primary' : 'Gallery' }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 mt-2">
                                        {{ props.vehicle.images.length }}/20 images uploaded
                                    </p>
                                </div>

                                <!-- Upload New Images -->
                                <div v-if="!props.vehicle || !props.vehicle.images || props.vehicle.images.length < 20">
                                    <h3 class="font-medium text-lg mb-3">Upload New Images</h3>
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
                                            <p class="mt-2 text-sm text-gray-600">Click to select images or drag and
                                                drop</p>
                                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF up to 2MB</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ selectedFiles.length }} file(s) selected
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="selectedFiles.length > 0" class="mt-4">
                                        <h4 class="font-medium text-sm mb-2">Selected Files:</h4>
                                        <ul class="text-sm text-gray-600">
                                            <li v-for="(file, index) in selectedFiles" :key="index"
                                                class="flex justify-between items-center py-1">
                                                <span>{{ file.name }}</span>
                                                <button type="button" @click="removeFile(index)"
                                                    class="text-red-500 hover:text-red-700">
                                                    Remove
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <p v-else class="text-amber-600">
                                    Maximum number of images (5) reached. Delete some images to upload new ones.
                                </p>
                            </div>
                        </TabsContent>
                    </Tabs>

                    <div class="flex justify-between mt-8">
                        <PrimaryButton type="submit" :disabled="form.limited_km && !form.price_per_km"
                            :class="{ 'opacity-50 cursor-not-allowed': form.limited_km && !form.price_per_km }">
                            Update Vehicle
                        </PrimaryButton>
                        <Link href="/current-vendor-vehicles"
                            class="px-4 flex justify-center items-center bg-[#EA3C3C] text-white rounded hover:bg-[#ea3c3ca2]">
                        Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </MyProfileLayout>
</template>


<script setup>
import { ref, onMounted, computed, watch } from 'vue';
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

const toast = useToast();
const { props } = usePage();
const fileInput = ref(null);
const selectedFiles = ref([]);
const maxImages = 20;
const isLoading = ref(false);


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
    }
})

const remainingImageSlots = computed(() => {
    if (!props.vehicle || !props.vehicle.images) return maxImages
    return Math.max(0, maxImages - props.vehicle.images.length)
})

const categories = ref([]);
const features = ref([]);
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


const fetchCategoriesAndFeatures = async () => {
    try {
        const response = await axios.get('/api/vehicle-categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching vehicle categories:', error)
    }

    features.value = props.features
}

const handleFileUpload = (event) => {
    const newFiles = Array.from(event.target.files)

    // Check if adding new files would exceed the limit
    const currentImagesCount = props.vehicle?.images?.length || 0
    const totalAfterAdding = currentImagesCount + selectedFiles.value.length + newFiles.length

    if (totalAfterAdding > maxImages) {
        toast.error(`You can only upload a maximum of ${maxImages} images.`, {
            position: 'top-right',
            timeout: 3000
        })
        return
    }

    // Add new files to the selected files array
    selectedFiles.value = [...selectedFiles.value, ...newFiles]

    // Update the form's images field with the selected files
    form.images = selectedFiles.value
}

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
    form.images = selectedFiles.value
}

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
    fetchCategoriesAndFeatures()
    if (props.vehicle) {
        form.category_id = props.vehicle.category_id
        form.brand = props.vehicle.brand
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
        form.location = props.vehicle.location
        form.latitude = parseFloat(props.vehicle.latitude)
        form.longitude = parseFloat(props.vehicle.longitude)
        form.status = props.vehicle.status
        try {
            form.features = JSON.parse(props.vehicle.features)
        } catch (e) {
            form.features = []
        }
        form.featured = props.vehicle.featured
        form.security_deposit = parseFloat(props.vehicle.security_deposit)
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
        form.guidelines = props.vehicle.guidelines
        form


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
    // Convert numeric fields to numbers
    const benefitsData = {
        ...form.benefits,
        limited_km_per_day_range: Number(form.benefits.limited_km_per_day_range),
        limited_km_per_week_range: Number(form.benefits.limited_km_per_week_range),
        limited_km_per_month_range: Number(form.benefits.limited_km_per_month_range),
        cancellation_available_per_day_date: Number(form.benefits.cancellation_available_per_day_date),
        cancellation_available_per_week_date: Number(form.benefits.cancellation_available_per_week_date),
        cancellation_available_per_month_date: Number(form.benefits.cancellation_available_per_month_date),
        price_per_km_per_day: Number(form.benefits.price_per_km_per_day),
        price_per_km_per_week: Number(form.benefits.price_per_km_per_week),
        price_per_km_per_month: Number(form.benefits.price_per_km_per_month),
        minimum_driver_age: Number(form.benefits.minimum_driver_age),
    };

    let formData = new FormData();

    // Append benefits data with proper number types
    Object.keys(benefitsData).forEach(key => {
        const value = benefitsData[key];
        // Handle boolean values properly
        if (typeof value === 'boolean') {
            formData.append(`benefits[${key}]`, value ? '1' : '0');
        } else {
            formData.append(`benefits[${key}]`, value !== null ? value : '');
        }
    });

    // Append other form data
    for (const key in form) {
        if (key !== 'benefits') {
            if (Array.isArray(form[key])) {
                form[key].forEach(value => formData.append(`${key}[]`, value));
            } else {
                formData.append(key, form[key]);
            }
        }
    }

    axios.post(route('current-vendor-vehicles.update', props.vehicle.id), formData, {
        method: 'POST',
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PUT' } // Laravel treats this as PUT
    })
        .then(() => {
            isLoading.value = false;
            // Reset selected files after upload
            selectedFiles.value = [];

            toast.success('Vehicle updated successfully!', { position: 'top-right', timeout: 1000 });

            setTimeout(() => {
                window.location.href = route('current-vendor-vehicles.index');
            }, 1500);
        })
        .catch(error => {
            isLoading.value = false;
            toast.error('Something went wrong.', { position: 'top-right', timeout: 3000 });
            // console.error(error);
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
</style>