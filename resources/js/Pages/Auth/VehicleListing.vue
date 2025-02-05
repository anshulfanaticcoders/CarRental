<template>

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
                        <div v-for="category in categories" :key="category.id" class="relative flex flex-col justify-center items-center">
                            <input type="radio" :id="category.id" v-model="form.category_id" :value="category.id"
                                class="peer sr-only" />
                            <InputLabel :for="category.id"
                                class="flex flex-col items-center p-4 cursor-pointer rounded-lg border-2 border-gray-200 hover:border-customPrimaryColor transition-colors peer-checked:border-customPrimaryColor peer-checked:bg-blue-50">
                                <img :src="`/storage/${category.image}`" :alt="category.InputLabel" class="p-2 w-[200px] h-[150px] object-cover" />
                                <p class="text-center">{{ category.name }}</p>
                                <span class="text-[1.5rem] text-center block font-medium text-gray-700">{{
                                    category.InputLabel }}</span>
                            </InputLabel>
                        </div>
                    </div>

                    <div class="mt-[3rem]">
                        <span class="text-[3rem] font-medium">Vehicle Details</span>
                        <p class="text-customLightGrayColor text-[1.15rem]">
                            Please provide vehicle details .
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-5">
                        <!-- Brand -->
                        <div>
                            <InputLabel for="brand">Brand:</InputLabel>
                            <TextInput type="text" v-model="form.brand" id="brand" required />
                        </div>

                        <!-- Model -->
                        <div>
                            <InputLabel for="model">Model:</InputLabel>
                            <TextInput type="text" v-model="form.model" id="model" required />
                        </div>

                        <!-- Color -->
                        <div>
                            <InputLabel for="color">Color:</InputLabel>
                            <TextInput type="text" v-model="form.color" id="color" required />
                        </div>

                        <!-- Mileage -->
                        <div>
                            <InputLabel for="mileage">Mileage:</InputLabel>
                            <TextInput type="number" v-model="form.mileage" id="mileage" required />
                        </div>

                        <!-- Transmission -->
                        <div>
                            <InputLabel for="transmission">Transmission:</InputLabel>
                            <select v-model="form.transmission" id="transmission" required>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                        </div>

                        <!-- Fuel -->
                        <div>
                            <InputLabel for="fuel">Fuel:</InputLabel>
                            <select v-model="form.fuel" id="fuel" required>
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>

                        <!-- Seating Capacity -->
                        <div>
                            <InputLabel for="seating_capacity">Seating Capacity:</InputLabel>
                            <select v-model="form.seating_capacity" id="seating_capacity" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                            </select>
                        </div>

                        <!-- Number of Doors -->
                        <div>
                            <InputLabel for="number_of_doors">Number of Doors:</InputLabel>
                            <select v-model="form.number_of_doors" id="number_of_doors" required>
                                <option value="2">2</option>
                                <option value="4">4</option>
                                <option value="6">6</option>
                            </select>
                        </div>

                        <!-- Luggage Capacity -->
                        <div>
                            <InputLabel for="luggage_capacity">Luggage Capacity:</InputLabel>
                            <select v-model="form.luggage_capacity" id="luggage_capacity" required>
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>

                        <!-- Horsepower -->
                        <div>
                            <InputLabel for="horsepower">Horsepower:</InputLabel>
                            <TextInput type="number" v-model="form.horsepower" id="horsepower" required min="0" />
                        </div>

                        <!-- CO2 Emissions -->
                        <div>
                            <InputLabel for="co2">CO2 Emissions:</InputLabel>
                            <TextInput type="text" v-model="form.co2" id="co2" required />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status">Status:</InputLabel>
                            <select v-model="form.status" id="status" required>
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
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

                                    <InputLabel :for="'feature-' + feature.id" class="mb-0 flex items-center">
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
                            <TextInput class="w-full" type="text" v-model="form.registration_number"
                                id="registration_number" required />
                        </div>

                        <!-- Registration Country -->
                        <div>
                            <InputLabel class="text-black" for="registration_country">Registration Country:</InputLabel>
                            <TextInput class="w-full" type="text" v-model="form.registration_country"
                                id="registration_country" required />
                        </div>

                        <!-- Registration Date -->
                        <div>
                            <InputLabel class="text-black" for="registration_date">Registration Date:</InputLabel>
                            <TextInput class="w-full" type="date" v-model="form.registration_date"
                                id="registration_date" required />
                        </div>

                        <!-- Gross Vehicle Mass -->
                        <div>
                            <InputLabel class="text-black" for="gross_vehicle_mass">Gross Vehicle Mass:</InputLabel>
                            <TextInput class="w-full" type="number" v-model="form.gross_vehicle_mass"
                                id="gross_vehicle_mass" required />
                        </div>

                        <!-- Vehicle Height -->
                        <div>
                            <InputLabel class="text-black" for="vehicle_height">Vehicle Height:</InputLabel>
                            <TextInput class="w-full" type="number" v-model="form.vehicle_height" id="vehicle_height"
                                required />
                        </div>

                        <!-- Dealer Cost -->
                        <div class="col-span-2">
                            <InputLabel class="text-black" for="dealer_cost">Dealer Cost:</InputLabel>
                            <TextInput class="" type="number" v-model="form.dealer_cost" id="dealer_cost" required />
                        </div>

                        <!-- Phone Number -->
                        <div class="col-span-2">
                            <InputLabel class="text-black mb-0" for="phone_number">Phone Number:</InputLabel>
                            <span class="text-[0.675rem] mb-[1rem] inline-block">Indicate the telephone number on which
                                you wish
                                to receive your requests</span>
                            <TextInput class="w-full" type="text" v-model="form.phone_number" id="phone_number"
                                required />
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
                    <div class="mt-[5rem] mb-[2rem]">
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
                        <div class="border-[1px] h-[20rem] flex justify-center items-center">
                            <div class="price-slider">
                                <label for="price_per_day">Price Per Day:</label>
                                <div class="slider-container">
                                    <input type="range" v-model="form.price_per_day" id="price_per_day" min="0" max="70"
                                        step="1" class="p-1" />
                                    <div class="price-tooltip" :style="tooltipPosition">
                                        €{{ form.price_per_day }}/day
                                    </div>
                                </div>
                                <div class="mt-[5rem] flex flex-col items-end gap-2">
                                    <span class="font-medium">Ideal daily rate: between 67 and
                                        €74!</span>
                                    <p class="text-[0.675rem]">
                                        Keeping this rate will give you a better
                                        chance of renting your vehicle.
                                    </p>
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
                            <TextInput type="number" v-model="form.security_deposit" id="security_deposit" required
                                min="0" step="0.01" />
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-[2rem]">
                        <InputLabel class="text-black">Payment method(s) for the security
                            deposit</InputLabel>
                        <span class="text-[0.75rem] font-medium mb-[1rem] inline-block text-customLightGrayColor">Select
                            the payment method(s) you accept (the
                            “Credit Card” mode means that you have your own
                            payment terminal)</span>
                        <div class="flex gap-[2rem]">
                            <InputLabel>
                                <input type="radio" v-model="form.payment_method" value="credit_card" />
                                Credit Card
                            </InputLabel>
                            <InputLabel>
                                <input type="radio" v-model="form.payment_method" value="cheque" />
                                Cheque
                            </InputLabel>
                            <InputLabel>
                                <input type="radio" v-model="form.payment_method" value="cash" />
                                Cash
                            </InputLabel>
                            <InputLabel>
                                <input type="radio" v-model="form.payment_method" value="bank_wire" />
                                Bank Wire
                            </InputLabel>
                            <InputLabel>
                                <input type="radio" v-model="form.payment_method" value="other" />
                                Other
                            </InputLabel>
                        </div>
                    </div>

                    <!-- Featured -->
                    <div class="flex gap-[0.5rem] items-center">
                        <InputLabel class="mb-0" for="featured">Featured:</InputLabel>
                        <input type="checkbox" v-model="form.featured" id="featured" />
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
                        <p v-if="form.images.length < 5" class="text-red-500 mt-2">
            Please select at least 5 images.
          </p>
                    </div>

                    <div class="buttons flex justify-between mt-[2rem] pb-[4rem]">
                        <button class="button-secondary w-[40%]" @click="prevStep">
                            Back
                        </button>
                        <PrimaryButton class="w-[40%]" type="button" @click="submit"
                        :disabled="form.images.length < 5">Submit</PrimaryButton>
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
import { computed, onMounted, ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import LocationPicker from "@/Components/LocationPicker.vue";
import axios from "axios";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import L from 'leaflet' // Import Leaflet

import { useToast } from 'vue-toastification'; // Add this import
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
    horsepower: 0,
    co2: "",
    location: "",
    latitude:'null',
    longitude:'null',
    status: "available",
    features: [],
    featured: false,
    security_deposit: 0,
    payment_method: "",
    price_per_day: 0,

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
});

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

// Submit form data
const submit = () => {
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
const tooltipPosition = computed(() => ({
    left: `${(form.price_per_day / 70) * 100}%`,
}));

// Method to handle file uploads
const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    form.images = files; // Store the selected files in the form data
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
        form.price_per_day <= 0 ||
        !form.security_deposit ||
        !form.payment_method
      ) {
        isValid = false;
        alert('Please fill in all pricing details');
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
    bottom: 100;
    transform: translateX(-50%);
    background: white;
    color: #153b4f;
    font-weight: 600;
    border: 1px solid #153b4f;
    padding: 1rem 4rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-size: 1rem;
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
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  transform: translate3d(0,0,1000px);
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
  transform: translate3d(0,0,1000px);
}

.leaflet-popup {
  z-index: 1001 !important;
}

/* Hardware acceleration */
.leaflet-marker-icon,
.leaflet-marker-shadow,
.leaflet-popup {
  will-change: transform;
  transform: translate3d(0,0,0);
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
input,textarea,select {
    border-radius: 0.75rem;
    border: 1px solid rgba(43, 43, 43, 0.50)!important;
    padding: 1rem;
}
</style>
