<template>

    <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e]">
        <div class="flex justify-center flex-col items-center h-full w-full">
            <img :src=loader alt="" class="w-[200px]">
            <p class="text-[white] text-[1.5rem]">Please do not refresh the page. Wait....</p>
        </div>
    </div>

    <Head title="Vehicle Listing" />
    <div v-if="currentStep === 0" class="overflow-x-hidden">
        <div class="flex justify-between min-h-[100vh]">
            <div class="column min-h-full w-[50%] flex items-center justify-center">
                <div class="flex flex-col gap-5 w-[50%]">
                    <Link href="/">
                    <ApplicationLogo />
                    </Link>
                    <span class="text-[3rem] font-medium">Create Car Listing</span>
                    <p class="text-customLightGrayColor text-[1.15rem]">
                        Create your listing in a few minutes to receive rental
                        requests! All you need is a photo, a rate, and an
                        address and our team will contact you and offer you a
                        personalized appointment. Also, make sure you have the
                        vehicle's registration certificate nearby.
                    </p>
                    <div class="buttons flex justify-between">
                        <PrimaryButton class="w-[40%]" type="button" @click="nextStep">Create a Listing</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column min-h-full w-[50%] flex-1 bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Temporary documents
                        </h4>
                        <p>
                            You can submit your ad with temporary documents
                            (order form, temporary registration certificate,
                            crossed out vehicle registration document and
                            transfer certificate) while waiting to receive your
                            final vehicle registration document.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="vendorBgimage" alt="" class="absolute bottom-0 left-[-4rem]" />
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-1 -->
    <div v-if="currentStep === 1" class="overflow-x-hidden">
        <div class="flex justify-between h-[100vh]">
            <div class="column overflow-y-auto w-[50%] flex justify-center pb-[5rem]">
                <div class="flex flex-col gap-10 w-[70%]">
                    <Link class="w-[5rem] mt-[2rem]" href="/">
                    <ApplicationLogo />
                    </Link>
                    <div class="mt-[3rem]">
                        <span class="text-[3rem] font-medium">Vehicle Category</span>
                        <p class="text-customLightGrayColor text-[1.15rem]">
                            Please provide vehicle category.
                        </p>
                    </div>
                    <!-- Vehicle Category Dropdown -->
                    <div class="grid grid-cols-3 gap-5">
                        <div v-for="category in categories" :key="category.id"
                            class="relative flex flex-col justify-center items-center">
                            <input type="radio" :id="category.id" v-model="form.category_id" :value="category.id"
                                class="peer sr-only" />
                            <InputLabel :for="category.id"
                                class="flex flex-col items-center p-4 cursor-pointer rounded-lg border-2 border-gray-200 hover:border-customPrimaryColor transition-colors peer-checked:border-customPrimaryColor peer-checked:bg-blue-50">
                                <img :src="`${category.image}`" :alt="category.InputLabel"
                                    class="p-2 w-[200px] h-[150px] object-cover" />
                                <p class="text-center">{{ category.name }}</p>
                                <span class="text-[1.5rem] text-center block font-medium text-gray-700">{{
                                    category.InputLabel }}</span>
                            </InputLabel>
                        </div>
                    </div>

                    <div class="mt-[1rem]">
                        <span class="text-[3rem] font-medium">Vehicle Details</span>
                        <p class="text-customLightGrayColor text-[1.15rem]">
                            Please provide vehicle details .
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-5">
                        <!-- Brand -->
                        <div>
                            <InputLabel for="brand">Brand:</InputLabel>
                            <input type="text" v-model="form.brand" id="brand" required
                                placeholder="Enter vehicle brand" />
                        </div>

                        <!-- Model -->
                        <div>
                            <InputLabel for="model">Model:</InputLabel>
                            <input type="text" v-model="form.model" id="model" required
                                placeholder="Enter vehicle model" />
                        </div>

                        <!-- Color -->
                        <div>
                            <InputLabel for="color">Color:</InputLabel>
                            <input type="text" v-model="form.color" id="color" required
                                placeholder="Enter vehicle color" />
                        </div>

                        <!-- Mileage -->
                        <div>
                            <InputLabel for="mileage">Mileage:</InputLabel>
                            <div class="relative">
                                <input type="number" v-model="form.mileage" id="mileage" required />
                                <span
                                    class="absolute text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">(km/d)</span>
                            </div>
                        </div>

                        <!-- Transmission -->
                        <div>
                            <InputLabel for="transmission">Transmission:</InputLabel>
                            <Select v-model="form.transmission">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.transmission || 'Select transmission type'" />
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


                        <!-- Fuel -->
                        <div>
                            <InputLabel for="fuel">Fuel:</InputLabel>
                            <Select v-model="form.fuel">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.fuel || 'Select fuel type'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Fuel Type</SelectLabel>
                                        <SelectItem value="petrol">Petrol</SelectItem>
                                        <SelectItem value="diesel">Diesel</SelectItem>
                                        <SelectItem value="electric">Electric</SelectItem>
                                        <SelectItem value="hybrid">Hybrid</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Seating Capacity -->
                        <div>
                            <InputLabel for="seating_capacity">Seating Capacity:</InputLabel>
                            <Select v-model="form.seating_capacity">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.seating_capacity || 'Select seating capacity'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Seating Capacity</SelectLabel>
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
                            <InputLabel for="number_of_doors">Number of Doors:</InputLabel>
                            <Select v-model="form.number_of_doors">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue :placeholder="form.number_of_doors || 'Select number of doors'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Number of Doors</SelectLabel>
                                        <SelectItem v-for="doors in 8" :key="doors" :value="doors">
                                            {{ doors }}
                                        </SelectItem>

                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>


                        <!-- Luggage Capacity -->
                        <div>
                            <InputLabel for="luggage_capacity">Luggage Capacity:</InputLabel>
                            <Select v-model="form.luggage_capacity">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                    <SelectValue placeholder="Select luggage capacity" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Luggage Capacity</SelectLabel>
                                        <SelectItem v-for="capacity in [0, 1, 2, 3, 4, 5]" :key="capacity"
                                            :value="capacity">
                                            {{ capacity }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                        </div>

                        <!-- Horsepower -->
                        <div>
                            <InputLabel for="horsepower">Horsepower:</InputLabel>
                            <div class="relative">
                                <input type="number" v-model="form.horsepower" id="horsepower" required min="0" />
                                <span
                                    class="absolute text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">hp</span>
                            </div>
                        </div>

                        <!-- CO2 Emissions -->
                        <div>
                            <InputLabel for="co2">CO2 Emissions:</InputLabel>
                            <div class="relative">
                                <input type="text" v-model="form.co2" id="co2" required />
                                <span
                                    class="absolute text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">(g/km)</span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status">Status:</InputLabel>
                            <Select v-model="form.status">
                                <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
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

                    <!-- Car Features -->
                    <div>
                        <div class="mt-8">
                            <InputLabel class="text-black mb-2">Vehicle Features</InputLabel>
                            <p class="text-customLightGrayColor text-sm mb-4">
                                Select all the features available in your
                                vehicle
                            </p>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="feature in features" :key="feature.id" class="flex items-center space-x-2">
                                    <input type="checkbox" :id="'feature-' + feature.id" :value="feature.name"
                                        v-model="form.features"
                                        class="rounded border-gray-300 text-customPrimaryColor focus:ring-customPrimaryColor" />

                                    <InputLabel :for="'feature-' + feature.id"
                                        class="mb-0 flex items-center cursor-pointer">
                                        {{ feature.name }}
                                    </InputLabel>
                                </div>
                            </div>

                            <!-- Selected Features Display -->
                            <div v-if="form.features.length > 0" class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    Selected Features:
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="featureName in form.features" :key="featureName"
                                        class="px-3 py-1 text-sm bg-blue-50 text-customPrimaryColor rounded-full">
                                        {{ featureName }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="buttons flex justify-between pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" @click="nextStep">Next</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column w-[50%] bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center h-full justify-center">
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Welcome!</h4>
                        <p>Placing an ad on Vrooem 100% free.</p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Tip</h4>
                        <p>
                            To save time while creating your ad, make sure to
                            have your registration certificate handy.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                    <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
                </div>
            </div>
        </div>
    </div>

    <!-- Step-2 -->
    <div v-if="currentStep === 2" class="overflow-x-hidden">
        <div class="flex justify-between h-[100vh]">
            <div class="column overflow-y-auto w-[50%] flex justify-center pb-[4rem]">
                <div class="flex flex-col gap-5 w-[60%]">
                    <Link class="w-[5rem] mt-[2rem]" href="/">
                    <ApplicationLogo />
                    </Link>
                    <div class="mt-[5rem] mb-[2rem]">
                        <span class="text-[1.75rem] font-medium">Key Technical Specifications of Your Vehicle</span>
                        <p>This information is needed to list your vehicle.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-8">
                        <div class="col-span-2">
                            <InputLabel class="text-black mb-0" for="registration_number">Registration Number:
                            </InputLabel>
                            <span class="text-[0.675rem] mb-[1rem] inline-block">As mentioned on your vehicle's
                                registration
                                certificate.</span>
                            <input class="w-full" type="text" v-model="form.registration_number"
                                id="registration_number" required
                                placeholder="Enter your vehicle registration number" />
                        </div>

                        <!-- Registration Country -->
                        <div class="relative w-full">
                            <InputLabel class="text-black" for="registration_country">Registration Country:</InputLabel>

                            <div class="relative">
                                <Select v-model="form.registration_country">
                                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
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
                                <img v-if="form.registration_country" :src="getFlagUrl(form.registration_country)"
                                    alt="Country Flag"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 w-[2.1rem] h-[1.5rem] rounded" />
                            </div>
                        </div>


                        <!-- Registration Date -->
                        <div>
                            <InputLabel class="text-black" for="registration_date">Registration Date:</InputLabel>
                            <input class="w-full" type="date" v-model="form.registration_date" id="registration_date"
                                required />
                        </div>

                        <!-- Gross Vehicle Mass -->
                        <div>
                            <InputLabel class="text-black" for="gross_vehicle_mass">Gross Vehicle Mass:</InputLabel>
                            <div class="relative">
                                <input class="w-full" type="number" v-model="form.gross_vehicle_mass"
                                    id="gross_vehicle_mass" required />
                                <span
                                    class="absolute text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">in
                                    (lb's)</span>
                            </div>
                        </div>

                        <!-- Vehicle Height -->
                        <div>
                            <InputLabel class="text-black" for="vehicle_height">Vehicle Height:</InputLabel>
                            <div class="relative">
                                <input class="w-full" type="number" v-model="form.vehicle_height" id="vehicle_height"
                                    required />
                                <span
                                    class="absolute text-[0.875rem] top-[50%] right-3 translate-y-[-50%] text-customLightGrayColor font-medium">in
                                    m(meters)</span>
                            </div>

                        </div>

                        <!-- Dealer Cost -->
                        <div class="col-span-2">
                            <InputLabel class="text-black" for="dealer_cost">Dealer Cost:</InputLabel>
                            <input class="" type="number" v-model="form.dealer_cost" id="dealer_cost" required />
                        </div>

                        <!-- Phone Number -->
                        <div class="col-span-2">
                            <InputLabel class="text-black mb-0" for="phone_number">Phone Number:</InputLabel>
                            <span class="text-[0.675rem] mb-[1rem] inline-block">Indicate the telephone number on which
                                you wish
                                to receive your requests</span>
                            <input class="w-full" type="text" v-model="form.phone_number" id="phone_number" required
                                placeholder="+91" />
                        </div>
                    </div>
                    <div class="buttons flex justify-between mt-[2rem] pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" type="button" @click="nextStep">Next</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column min-h-full w-[50%] flex-1 bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Data protection
                        </h4>
                        <p>
                            Temporary documents Your vehicle's licence plate and
                            value remain strictly confidential and secure.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Information</h4>
                        <p>
                            All this information is necessary so that we can
                            secure your listing.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] px-[2rem]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-3 -->
    <div v-if="currentStep === 3" class="overflow-x-hidden">
        <div class="flex justify-between h-[100vh]">
            <div class="column overflow-y-auto w-[50%] flex justify-center pb-[4rem]">
                <div class="flex flex-col gap-5 w-[60%]">
                    <Link class="w-[5rem] mt-[2rem]" href="/">
                    <ApplicationLogo />
                    </Link>
                    <div class="mt-[5rem] mb-[2rem]">
                        <span class="text-[1.75rem] font-medium">Parking Address of the Vehicle</span>
                        <p>This allows renters to search by location</p>
                    </div>
                    <div>
                        <span class="text-[0.875rem] text-black font-medium">Search your address</span>
                        <p class="text-[0.675rem]">
                            If you can't find your address, please indicate the
                            closest address that can be geolocated.
                        </p>
                    </div>
                    <div class="search-container">
                        <!-- <div>
                            <InputLabel for="location">Location:</InputLabel>
                            <input
                               v-model="form.location"
                               type="text"
                               @input="handleSearchInput"
                               placeholder="Search location"
                               class="w-full p-2 border rounded"
                             />
                        </div>
                        <div v-if="searchResults.length" class="search-results">
                          <div
                            v-for="result in searchResults"
                            :key="result.id"
                            @click="selectLocation(result)"
                            class="p-2 hover:bg-gray-100 cursor-pointer"
                          >
                            {{ result.properties?.label || 'Unknown Location' }}  
                          </div>
                        </div> -->
                        <!-- <div id="map" class="w-full h-64 mt-4"></div> -->
                        <LocationPicker :onLocationSelect="selectLocation" />
                    </div>

                    <div class="buttons flex justify-between mt-[2rem] pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" type="button" @click="nextStep">Next</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column min-h-full w-[50%] flex-1 bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Data protection
                        </h4>
                        <p>
                            Your exact address will never be shared on our site.
                            It will be sent directly to the renter after
                            confirmation of a booking.
                        </p>
                    </div>

                    <div class="col text-customPrimaryColor-foreground w-[70%] px-[2rem]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-4 -->
    <div v-if="currentStep === 4" class="overflow-x-hidden">
        <div class="flex justify-between h-[100vh]">
            <div class="column overflow-y-auto w-[50%] flex justify-center pb-[4rem]">
                <div class="flex flex-col gap-5 w-[60%]">
                    <Link class="w-[5rem] mt-[2rem]" href="/">
                    <ApplicationLogo />
                    </Link>
                    <div class="mt-[5rem]">
                        <span class="text-[1.75rem] font-medium">Hire Cost of Your Vehicle</span>
                        <div class="mt-[2rem]">
                            <span class="text-[0.875rem] text-black font-medium">Basic daily rate</span>
                            <p class="text-[0.75rem] font-medium mb-[1rem] text-customLightGrayColor">
                                Once your listing is created, you can modify the
                                daily rate prices according to the season
                            </p>
                        </div>
                    </div>
                    <div class="">
                        <div class="border-[1px] p-8 flex flex-col gap-8">
                            <div class="price-section">
                                <h3 class="text-lg font-semibold mb-4">Select Your Pricing Options</h3>

                                <!-- Price Type Selection -->
                                <div class="mb-8">
                                    <InputLabel class="text-black mb-2">Preferred Price Types:</InputLabel>
                                    <div class="flex gap-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.day" class="mr-2" />
                                            Daily
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.week" class="mr-2" />
                                            Weekly
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="selectedTypes.month" class="mr-2" />
                                            Monthly
                                        </label>
                                    </div>
                                </div>

                                <!-- Daily Price Slider -->
                                <div v-if="selectedTypes.day" class="price-slider mb-8">
                                    <label for="price_per_day" class="font-medium">Daily Rate:</label>
                                    <div class="slider-container">
                                        <input type="range" v-model="form.price_per_day" id="price_per_day" min="0"
                                            max="200" step="1" class="p-1" />
                                        <div class="price-tooltip" :style="dailyTooltipPosition">
                                            €{{ form.price_per_day }}/day
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-col items-end gap-1">
                                        <span class="text-sm text-gray-600">Recommended: €67-€74/day</span>
                                    </div>
                                </div>

                                <!-- Weekly Price Slider -->
                                <div v-if="selectedTypes.week" class="price-slider mb-8">
                                    <label for="price_per_week" class="font-medium">Weekly Rate:</label>
                                    <div class="slider-container">
                                        <input type="range" v-model="form.price_per_week" id="price_per_week" min="0"
                                            max="1000" step="10" class="p-1" />
                                        <div class="price-tooltip" :style="weeklyTooltipPosition">
                                            €{{ form.price_per_week }}/week
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-col items-end gap-1">
                                        <span class="text-sm text-gray-600">Recommended: €400-€450/week</span>
                                    </div>
                                    <div class="mt-2 flex flex-col justify-end items-end">
                                        <label for="weekly_discount" class="text-sm font-medium mb-0">Weekly Discount
                                            (%):</label>
                                        <input type="number" v-model="form.weekly_discount" id="weekly_discount"
                                            class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm p-1 text-center" />
                                    </div>

                                </div>

                                <!-- Monthly Price Slider -->
                                <div v-if="selectedTypes.month" class="price-slider mb-8">
                                    <label for="price_per_month" class="font-medium">Monthly Rate:</label>
                                    <div class="slider-container">
                                        <input type="range" v-model="form.price_per_month" id="price_per_month" min="0"
                                            max="3000" step="50" class="p-1" />
                                        <div class="price-tooltip" :style="monthlyTooltipPosition">
                                            €{{ form.price_per_month }}/month
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-col items-end gap-1">
                                        <span class="text-sm text-gray-600">Recommended: €1500-€1800/month</span>
                                    </div>
                                    <div class="mt-2 flex flex-col justify-end items-end">
                                        <label for="monthly_discount" class="text-sm font-medium mb-0">Monthly Discount
                                            (%):</label>
                                        <input type="number" v-model="form.monthly_discount" id="monthly_discount"
                                            class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm p-1 text-center" />
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- Security Deposit -->
                        <div class="mt-[2rem]">
                            <InputLabel for="security_deposit" class="text-black mb-0">Guarantee Deposit:</InputLabel>
                            <span
                                class="text-[0.75rem] font-medium mb-[1rem] inline-block text-customLightGrayColor">This
                                is the deposit that the renter must give
                                you no later than the first day of the rental
                                period.</span>
                            <input type="number" v-model="form.security_deposit" id="security_deposit" required min="0"
                                step="0.01" />
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-[2rem]">
                        <InputLabel class="text-black text-[1.15rem]">Payment methods for the security deposit
                        </InputLabel>
                        <span class="text-[0.75rem] font-medium mb-[1rem] inline-block text-customLightGrayColor">
                            Select the payment method(s) you accept
                        </span>
                        <div class="flex gap-[1rem]">
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="credit_card"
                                    id="credit_card" />
                                <label class="ml-2" for="credit_card">Credit Card</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="cheque" id="cheque" />
                                <label class="ml-2" for="cheque">Cheque</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="bank_wire" id="bank_wire" />
                                <label class="ml-2" for="bank_wire">Bank Wire</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="cryptocurrency"
                                    id="cryptocurrency" />
                                <label class="ml-2" for="cryptocurrency">Cryptocurrency</label>
                            </InputLabel>
                            <InputLabel>
                                <input type="checkbox" v-model="form.payment_method" value="other" id="other" />
                                <label class="ml-2" for="other">Other</label>
                            </InputLabel>
                        </div>

                        <div v-if="paymentMethodsArray.length > 0">
                            <p class="text-[0.85rem] text-green-400">Selected Payment Methods:</p>
                            <ul class="mt-2 flex items-center gap-3">
                                <li class="bg-green-200 px-2 rounded-[12px]" v-for="method in paymentMethodsArray"
                                    :key="method">{{ method }}</li>
                            </ul>
                        </div>
                        <div v-else>
                            <p class="text-[0.85rem] text-red-500">No payment methods selected yet</p>
                        </div>
                    </div>


                    <!-- Unlimited Kilometers and Featured
                    <div class="flex items items-center gap-5">
                        <div class="flex gap-[0.5rem] items-center">
                            <input type="checkbox" v-model="form.limited_km" id="limited_km" />
                            <InputLabel for="limited_km" class="mb-0">Limited Kilometer</InputLabel>
                        </div>

                        <div class="flex gap-[0.5rem] items-center">
                            <input type="checkbox" v-model="form.cancellation_available" id="cancellation_available" />
                            <InputLabel for="cancellation_available" class="mb-0">Cancellation Available</InputLabel>
                        </div>

                    </div>
                    <div v-if="form.limited_km">
                        <InputLabel for="price_per_km">Price per Kilometer</InputLabel>
                        <input type="number" v-model="form.price_per_km" id="price_per_km" />
                    </div> -->


                    <div>
    <!-- Limited Kilometer Section -->
    <div class="mb-6">
        <h3 class="font-medium text-lg mb-2">Kilometer Limitations</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Per Day Limitation -->
            <div v-if="selectedTypes.day" class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.limited_km_per_day" id="limited_km_per_day" />
                    <InputLabel for="limited_km_per_day" class="mb-0">Limited Kilometer Per Day</InputLabel>
                </div>
                
                <div v-if="form.limited_km_per_day">
                    <div class="mb-3">
                        <InputLabel for="limited_km_per_day_range">KM Limit</InputLabel>
                        <input type="number" v-model="form.limited_km_per_day_range" id="limited_km_per_day_range" class="w-full" />
                    </div>
                    
                    <div>
                        <InputLabel for="price_per_km_per_day">Price Per Extra KM</InputLabel>
                        <input type="number" v-model="form.price_per_km_per_day" id="price_per_km_per_day" class="w-full" />
                    </div>
                </div>
            </div>
            
            <!-- Per Week Limitation -->
            <div v-if="selectedTypes.week" class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.limited_km_per_week" id="limited_km_per_week" />
                    <InputLabel for="limited_km_per_week" class="mb-0">Limited Kilometer Per Week</InputLabel>
                </div>
                
                <div v-if="form.limited_km_per_week">
                    <div class="mb-3">
                        <InputLabel for="limited_km_per_week_range">KM Limit</InputLabel>
                        <input type="number" v-model="form.limited_km_per_week_range" id="limited_km_per_week_range" class="w-full" />
                    </div>
                    
                    <div>
                        <InputLabel for="price_per_km_per_week">Price Per Extra KM</InputLabel>
                        <input type="number" v-model="form.price_per_km_per_week" id="price_per_km_per_week" class="w-full" />
                    </div>
                </div>
            </div>
            
            <!-- Per Month Limitation -->
            <div v-if="selectedTypes.month" class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.limited_km_per_month" id="limited_km_per_month" />
                    <InputLabel for="limited_km_per_month" class="mb-0">Limited Kilometer Per Month</InputLabel>
                </div>
                
                <div v-if="form.limited_km_per_month">
                    <div class="mb-3">
                        <InputLabel for="limited_km_per_month_range">KM Limit</InputLabel>
                        <input type="number" v-model="form.limited_km_per_month_range" id="limited_km_per_month_range" class="w-full" />
                    </div>
                    
                    <div>
                        <InputLabel for="price_per_km_per_month">Price Per Extra KM</InputLabel>
                        <input type="number" v-model="form.price_per_km_per_month" id="price_per_km_per_month" class="w-full" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancellation Policy Section -->
    <div class="mb-6">
        <h3 class="font-medium text-lg mb-2">Cancellation Policy</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Per Day Cancellation -->
            <div class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.cancellation_available_per_day" id="cancellation_available_per_day" />
                    <InputLabel for="cancellation_available_per_day" class="mb-0">Cancellation Available Per Day</InputLabel>
                </div>
                
                <div v-if="form.cancellation_available_per_day">
                    <InputLabel for="cancellation_available_per_day_date">Days Prior Notice Required</InputLabel>
                    <input type="number" v-model="form.cancellation_available_per_day_date" id="cancellation_available_per_day_date" class="w-full" />
                </div>
            </div>
            
            <!-- Per Week Cancellation -->
            <div class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.cancellation_available_per_week" id="cancellation_available_per_week" />
                    <InputLabel for="cancellation_available_per_week" class="mb-0">Cancellation Available Per Week</InputLabel>
                </div>
                
                <div v-if="form.cancellation_available_per_week">
                    <InputLabel for="cancellation_available_per_week_date">Days Prior Notice Required</InputLabel>
                    <input type="number" v-model="form.cancellation_available_per_week_date" id="cancellation_available_per_week_date" class="w-full" />
                </div>
            </div>
            
            <!-- Per Month Cancellation -->
            <div class="border rounded p-4">
                <div class="flex gap-2 items-center mb-3">
                    <input type="checkbox" v-model="form.cancellation_available_per_month" id="cancellation_available_per_month" />
                    <InputLabel for="cancellation_available_per_month" class="mb-0">Cancellation Available Per Month</InputLabel>
                </div>
                
                <div v-if="form.cancellation_available_per_month">
                    <InputLabel for="cancellation_available_per_month_date">Days Prior Notice Required</InputLabel>
                    <input type="number" v-model="form.cancellation_available_per_month_date" id="cancellation_available_per_month_date" class="w-full" />
                </div>
            </div>
        </div>
    </div>
    
    <!-- Driver Requirements Section -->
    <div>
        <h3 class="font-medium text-lg mb-2">Driver Requirements</h3>
        
        <div class="max-w-xs">
            <InputLabel for="minimum_driver_age">Minimum Driver Age</InputLabel>
            <input type="number" v-model="form.minimum_driver_age" id="minimum_driver_age" class="w-full" />
        </div>
    </div>
</div>



                    <div class="buttons flex justify-between mt-[2rem] pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" type="button" @click="nextStep">Next</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column min-h-full w-[50%] flex-1 bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Tip</h4>
                        <p>
                            The rate indicated corresponds to the daily rate of
                            the hire. Please note your remuneration will
                            correspond to 85% of the rate applied for the hire.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Information</h4>
                        <p>
                            All this information is necessary so that we can
                            secure your listing.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] px-[2rem]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
            </div>
        </div>
    </div>

    <!-- Step-5 -->
    <div v-if="currentStep === 5" class="overflow-x-hidden">
        <div class="flex justify-between h-[100vh]">
            <div class="column overflow-y-auto w-[50%] flex justify-center pb-[4rem]">
                <div class="flex flex-col gap-5 w-[60%]">
                    <Link class="w-[5rem] mt-[2rem]" href="/">
                    <ApplicationLogo />
                    </Link>
                    <div class="mt-[5rem] mb-[2rem]">
                        <p class="text-[1.75rem] font-medium">
                            Upload Vehicle Photos
                        </p>

                        <span class="text-[0.75rem] text-customLightGrayColor font-medium">Upload at least 5 photos of
                            your vehicle</span>
                    </div>
                    <div
                        class="flex flex-col gap-2 justify-center items-center border-[2px] rounded-[0.5rem] border-customPrimaryColor border-dashed py-10">
                        <img :src="uploadIcon" alt="" />
                        <p>Drag & Drop to Upload Photos</p>
                        <p class="text-customLightGrayColor font-medium">or</p>
                        <input type="file" id="images" @change="handleFileUpload" multiple />
                        <div v-if="form.images.length" class="image-preview-container">
                            <div v-for="(image, index) in form.images" :key="index" class="image-preview">
                                <img :src="getImageUrl(image)" alt="Vehicle Image" />
                                <button class="remove-btn" @click="removeImage(index)">✖</button>
                            </div>
                        </div>
                    </div>

                    <div class="buttons flex justify-between mt-[2rem] pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" type="button" @click="submit" :disabled="form.images.length < 5">
                            Submit</PrimaryButton>
                    </div>
                </div>
            </div>
            <div class="column min-h-full w-[50%] flex-1 bg-customPrimaryColor relative">
                <div class="flex flex-col gap-10 items-center justify-center h-full">
                    <div class="col text-customPrimaryColor-foreground w-[70%] p-[2rem] border-b-[2px]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">Information</h4>
                        <p>
                            All this information is necessary so that we can
                            secure your listing.
                        </p>
                    </div>
                    <div class="col text-customPrimaryColor-foreground w-[70%] px-[2rem]">
                        <img :src="warningSign" alt="" />
                        <h4 class="text-[1.5rem] font-medium">
                            Need some help?
                        </h4>
                        <p>Contact us on: +91 524555552</p>
                    </div>
                </div>
                <img :src="circleImg" alt="" class="absolute top-[-30%] right-[-15%]" />
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
const toast = useToast(); // Initialize toast
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
    luggage_capacity: 0,
    horsepower: 70,
    co2: "",
    location: "",
    latitude: 'null',
    longitude: 'null',
    status: "available",
    features: [],
    featured: false,
    security_deposit: 0,
    // payment_method: "",
    payment_method: [],
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
})
// fetching the vehicle categories from the database thorough api
const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await axios.get("/api/vehicle-categories");
        categories.value = response.data; // Store the fetched categories
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
    form.post(route("vehicles.store"), {
        onSuccess: () => {
            toast.success('Vendor registration completed successfully! Wait for confimation', {
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

// Method to handle file uploads
const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    form.images = [...form.images, ...files]; // Append new images instead of resetting
};

// Generate image preview URL
const getImageUrl = (image) => {
    return URL.createObjectURL(image);
};

// Remove image from preview
const removeImage = (index) => {
    form.images.splice(index, 1);
};
// Vehicle Features
const features = ref([]);

const fetchFeatures = async () => {
    try {
        const response = await axios.get("/api/vehicle-features");
        features.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicle features:", error);
    }
};

onMounted(() => {
    fetchCategories();
    fetchFeatures();
});
let map = null;
let marker = null // Marker instance
const currentStep = ref(0);

const nextStep = () => {
    let isValid = true;

    // Step-specific validation
    switch (currentStep.value) {
        case 1: // Vehicle Category and Details
            if (!form.category_id) {
                isValid = false;
                alert('Please select a vehicle category');
            } else if (
                !form.brand ||
                !form.model ||
                !form.color ||
                !form.mileage ||
                !form.horsepower ||
                !form.co2
            ) {
                isValid = false;
                alert('Please fill in all vehicle details');
            }
            break;

        case 2: // Technical Specifications
            if (
                !form.registration_number ||
                !form.registration_country ||
                !form.registration_date ||
                !form.gross_vehicle_mass ||
                !form.vehicle_height ||
                !form.dealer_cost ||
                !form.phone_number
            ) {
                isValid = false;
                alert('Please fill in all technical specification details');
            }
            break;

        case 3: // Location
            if (!form.location || !form.latitude || !form.longitude) {
                isValid = false;
                alert('Please select a valid location');
            }
            break;

        case 4: // Pricing
            if (
                !form.preferred_price_type ||
                (form.preferred_price_type === 'day' && form.price_per_day <= 0) ||
                (form.preferred_price_type === 'week' && form.price_per_week <= 0) ||
                (form.preferred_price_type === 'month' && form.price_per_month <= 0) ||
                !form.security_deposit ||
                !form.payment_method
            ) {
                isValid = false;
                alert('Please fill in all required pricing details');
            }
            break;

        case 5: // Image Upload
            if (form.images.length < 5) {
                isValid = false;
                alert('Please upload at least 5 images');
            }
            break;
    }

    // If validation passes, move to next step
    if (isValid) {
        if (currentStep.value < 5) {
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
        searchResults.value = []; // Clear previous results if input is too short
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

</script>



<style>
select {
    width: 100%;
}

label {
    margin-bottom: 0.5rem;
}

.price-slider {
    position: relative;
    width: 100%;
    max-width: 500px;
}

.slider-container {
    position: relative;
    padding-top: 30px;
}

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
</style>
