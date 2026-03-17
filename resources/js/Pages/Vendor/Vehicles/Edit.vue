<template>
    <div v-if="isLoading" class="vln-loading-overlay">
        <div class="vln-spinner"></div>
    </div>

    <Head><title>Edit Vehicle</title></Head>

    <MyProfileLayout>
        <nav class="vln-stepper">
            <div class="vln-stepper-inner">
                <button v-for="(step, i) in stepNames" :key="i" class="vln-step-tab"
                    :class="{ active: currentStep === i, completed: i < highestStepReached && i !== currentStep, clickable: i <= highestStepReached }"
                    @click="goToStep(i)">
                    <span class="vln-step-num">
                        <CheckCircle2 v-if="i < highestStepReached && i !== currentStep" :size="13" />
                        <template v-else>{{ i + 1 }}</template>
                    </span>
                    <span class="vln-step-label">{{ step }}</span>
                </button>
            </div>
        </nav>

        <div v-if="Object.keys(formErrors).length" class="vln-form-errors">
            <AlertCircle :size="16" />
            <div>
                <p class="font-semibold">Please fix the errors below:</p>
                <ul>
                    <li v-for="(messages, field) in formErrors" :key="field">
                        {{ Array.isArray(messages) ? messages[0] : messages }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="vln-layout">
            <div class="vln-form-col" ref="formCol">

                <!-- STEP 0: Vehicle Type & Details -->
                <section v-show="currentStep === 0" class="vln-step" key="step-0">
                    <div class="vln-step-header">
                        <h1>Vehicle type & details</h1>
                        <p>Select your vehicle category and fill in the key specifications.</p>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Car :size="18" /></div>
                            <div><div class="vln-fg-title">Vehicle Category</div><div class="vln-fg-sub">Select one</div></div>
                        </div>
                        <div class="vln-category-grid">
                            <div v-for="cat in categories" :key="cat.id" class="vln-cat-card"
                                :class="{ selected: form.category_id === cat.id }"
                                @click="form.category_id = cat.id">
                                <div class="vln-cat-check"><CheckCircle2 :size="12" /></div>
                                <div class="vln-cat-img-wrap">
                                    <img v-if="cat.image" :src="cat.image" :alt="cat.name" />
                                    <div v-else class="vln-cat-placeholder"><Car :size="24" /></div>
                                </div>
                                <div class="vln-cat-name">{{ cat.name }}</div>
                            </div>
                        </div>
                        <span v-if="errors.category_id" class="vln-error"><AlertCircle :size="13" /> {{ errors.category_id }}</span>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Info :size="18" /></div>
                            <div><div class="vln-fg-title">Basic Information</div><div class="vln-fg-sub">Brand, model, appearance</div></div>
                        </div>
                        <div class="vln-grid vln-grid-3">
                            <div class="vln-field">
                                <label class="vln-label">Brand <span class="req">*</span></label>
                                <input class="vln-input" type="text" v-model="form.brand" placeholder="e.g. BMW" />
                                <span v-if="errors.brand" class="vln-error"><AlertCircle :size="13" /> {{ errors.brand }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Model <span class="req">*</span></label>
                                <input class="vln-input" type="text" v-model="form.model" placeholder="e.g. 3 Series" />
                                <span v-if="errors.model" class="vln-error"><AlertCircle :size="13" /> {{ errors.model }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Color <span class="req">*</span></label>
                                <Select v-model="form.color">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select color" /></SelectTrigger>
                                    <SelectContent><SelectGroup><SelectLabel>Color</SelectLabel>
                                        <SelectItem v-for="c in vehicleColors" :key="c.value" :value="c.value">{{ c.name }}</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                                <span v-if="errors.color" class="vln-error"><AlertCircle :size="13" /> {{ errors.color }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Wrench :size="18" /></div>
                            <div><div class="vln-fg-title">Specifications</div><div class="vln-fg-sub">Performance and capacity</div></div>
                        </div>
                        <div class="vln-grid">
                            <div class="vln-field">
                                <label class="vln-label">Transmission</label>
                                <Select v-model="form.transmission">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem value="manual">Manual</SelectItem>
                                        <SelectItem value="automatic">Automatic</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Fuel Type</label>
                                <Select v-model="form.fuel">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem value="petrol">Petrol</SelectItem>
                                        <SelectItem value="diesel">Diesel</SelectItem>
                                        <SelectItem value="electric">Electric</SelectItem>
                                        <SelectItem value="hybrid">Hybrid</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Seats</label>
                                <Select v-model="form.seating_capacity">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem v-for="n in 8" :key="n" :value="n">{{ n }}</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Doors</label>
                                <Select v-model="form.number_of_doors">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem v-for="n in 8" :key="n" :value="n">{{ n }}</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Mileage</label>
                                <div class="vln-input-suffix">
                                    <input class="vln-input" type="number" v-model="form.mileage" placeholder="0" />
                                    <span class="vln-suffix">km/l</span>
                                </div>
                                <span v-if="errors.mileage" class="vln-error"><AlertCircle :size="13" /> {{ errors.mileage }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Luggage</label>
                                <Select v-model="form.luggage_capacity">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem v-for="n in [0,1,2,3,4,5]" :key="n" :value="n">{{ n }}</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Horsepower</label>
                                <div class="vln-input-suffix">
                                    <input class="vln-input" type="number" v-model="form.horsepower" placeholder="0" />
                                    <span class="vln-suffix">hp</span>
                                </div>
                                <span v-if="errors.horsepower" class="vln-error"><AlertCircle :size="13" /> {{ errors.horsepower }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">CO2 Emissions</label>
                                <div class="vln-input-suffix">
                                    <input class="vln-input" type="text" v-model="form.co2" placeholder="0" />
                                    <span class="vln-suffix">g/km</span>
                                </div>
                                <span v-if="errors.co2" class="vln-error"><AlertCircle :size="13" /> {{ errors.co2 }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Zap :size="18" /></div>
                            <div><div class="vln-fg-title">Status</div></div>
                        </div>
                        <div class="vln-grid">
                            <div class="vln-field">
                                <label class="vln-label">Vehicle Status</label>
                                <Select v-model="form.status">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent><SelectGroup>
                                        <SelectItem value="available">Available</SelectItem>
                                        <SelectItem value="rented">Rented</SelectItem>
                                        <SelectItem value="maintenance">Maintenance</SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Star :size="18" /></div>
                            <div><div class="vln-fg-title">Features</div><div class="vln-fg-sub">Select all that apply to your vehicle</div></div>
                        </div>
                        <div class="vln-feature-grid">
                            <button v-for="feature in allFeatures" :key="feature.name" type="button"
                                class="vln-chip" :class="{ active: form.features.includes(feature.name) }"
                                @click="toggleFeature(feature.name)">
                                <component :is="feature.icon" :size="14" />
                                {{ feature.name }}
                            </button>
                        </div>
                        <div class="vln-custom-feature mt-3">
                            <div class="flex gap-2">
                                <input class="vln-input flex-1" type="text" v-model="customFeatureInput"
                                    placeholder="Add a custom feature..." @keydown.enter.prevent="addCustomFeature" />
                                <button type="button" class="vln-btn-add-feature" @click="addCustomFeature" :disabled="!customFeatureInput.trim()">
                                    <Plus :size="16" /> Add
                                </button>
                            </div>
                        </div>
                        <span v-if="errors.features" class="vln-error"><AlertCircle :size="13" /> {{ errors.features }}</span>
                    </div>
                </section>

                <!-- STEP 1: Registration -->
                <section v-show="currentStep === 1" class="vln-step" key="step-1">
                    <div class="vln-step-header">
                        <h1>Registration & technical specs</h1>
                        <p>Required for insurance and compliance. Never shown publicly.</p>
                    </div>

                    <div class="vln-tip">
                        <Info :size="16" class="shrink-0 mt-0.5" />
                        <p>Have your registration certificate ready -- you'll need the registration number, date, and country.</p>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><FileText :size="18" /></div>
                            <div><div class="vln-fg-title">Registration</div><div class="vln-fg-sub">From your vehicle certificate</div></div>
                        </div>
                        <div class="vln-grid">
                            <div class="vln-field full">
                                <label class="vln-label">Registration Number <span class="req">*</span></label>
                                <input class="vln-input uppercase" type="text" v-model="form.registration_number" placeholder="e.g. AB-123-CD" maxlength="10" />
                                <span v-if="errors.registration_number" class="vln-error"><AlertCircle :size="13" /> {{ errors.registration_number }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Country <span class="req">*</span></label>
                                <Select v-model="form.registration_country">
                                    <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select country" /></SelectTrigger>
                                    <SelectContent><SelectGroup><SelectLabel>Country</SelectLabel>
                                        <SelectItem v-for="c in countries" :key="c.code" :value="c.code">
                                            <div class="flex items-center gap-2">
                                                <img :src="getFlagUrl(c.code)" :alt="c.name" class="w-5 h-3.5 rounded-sm" />
                                                <span>{{ c.name }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectGroup></SelectContent>
                                </Select>
                                <span v-if="errors.registration_country" class="vln-error"><AlertCircle :size="13" /> {{ errors.registration_country }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Registration Date <span class="req">*</span></label>
                                <VueDatePicker v-model="form.registration_date" :format="'yyyy-MM-dd'" auto-apply :clearable="false"
                                    :max-date="new Date()" @update:modelValue="formatDate"
                                    :input-class-name="'vln-input w-full'" />
                                <span v-if="errors.registration_date" class="vln-error"><AlertCircle :size="13" /> {{ errors.registration_date }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Wrench :size="18" /></div>
                            <div><div class="vln-fg-title">Technical Details</div><div class="vln-fg-sub">Weight, dimensions, cost</div></div>
                        </div>
                        <div class="vln-grid">
                            <div class="vln-field">
                                <label class="vln-label">Gross Vehicle Mass</label>
                                <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.gross_vehicle_mass" placeholder="0" /><span class="vln-suffix">kg</span></div>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Vehicle Height</label>
                                <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.vehicle_height" placeholder="0" /><span class="vln-suffix">m</span></div>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Dealer Cost <span class="req">*</span></label>
                                <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.dealer_cost" placeholder="0" /><span class="vln-suffix">{{ currencyCode }}</span></div>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Phone Number <span class="req">*</span></label>
                                <input class="vln-input" type="tel" v-model="form.phone_number" placeholder="+31 6 1234 5678" />
                                <span v-if="errors.phone_number" class="vln-error"><AlertCircle :size="13" /> {{ errors.phone_number }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STEP 2: Location -->
                <section v-show="currentStep === 2" class="vln-step" key="step-2">
                    <div class="vln-step-header">
                        <h1>Where is your vehicle parked?</h1>
                        <p>Your exact address is only shared with confirmed renters.</p>
                    </div>
                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><MapPin :size="18" /></div>
                            <div><div class="vln-fg-title">Parking Address</div><div class="vln-fg-sub">Search or enter manually</div></div>
                        </div>

                        <div v-if="form.location" class="vln-current-location mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="font-medium text-gray-900 text-sm">Current Location</span>
                            </div>
                            <div class="text-gray-700 font-medium text-sm break-words">{{ displayedFullAddress }}</div>
                        </div>

                        <div class="vln-field mb-4">
                            <label class="vln-label">Search Address <span class="req">*</span></label>
                            <LocationPicker :onLocationSelect="handleLocationSelect" />
                            <span v-if="errors.location" class="vln-error"><AlertCircle :size="13" /> {{ errors.location }}</span>
                        </div>
                        <div class="vln-field">
                            <label class="vln-label">Location Type <span class="req">*</span></label>
                            <Select v-model="form.location_type">
                                <SelectTrigger class="vln-select-trigger"><SelectValue placeholder="Select type" /></SelectTrigger>
                                <SelectContent><SelectGroup>
                                    <SelectItem value="Downtown">Downtown</SelectItem>
                                    <SelectItem value="Airport">Airport</SelectItem>
                                    <SelectItem value="Terminal">Terminal</SelectItem>
                                    <SelectItem value="Bus Stop">Bus Stop</SelectItem>
                                    <SelectItem value="Railway Station">Railway Station</SelectItem>
                                </SelectGroup></SelectContent>
                            </Select>
                            <span v-if="errors.location_type" class="vln-error"><AlertCircle :size="13" /> {{ errors.location_type }}</span>
                        </div>
                    </div>
                </section>

                <!-- STEP 3: Pricing & Policies -->
                <section v-show="currentStep === 3" class="vln-step" key="step-3">
                    <div class="vln-step-header">
                        <h1>Set your pricing</h1>
                        <p>Daily rate is required. Add weekly/monthly rates to attract longer bookings.</p>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="vln-pricing-card active">
                            <div class="vln-pc-header">
                                <div class="vln-pc-label"><div class="vln-pc-toggle"></div><span class="vln-pc-period">Daily Rate</span></div>
                                <span class="vln-pc-badge">Required</span>
                            </div>
                            <div class="vln-pc-body">
                                <div class="vln-grid" style="margin-top:0.4rem">
                                    <div class="vln-field">
                                        <label class="vln-label">Price per day <span class="req">*</span></label>
                                        <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.price_per_day" placeholder="0.00" /><span class="vln-suffix">{{ currencyCode }}</span></div>
                                        <span v-if="errors.price_per_day" class="vln-error"><AlertCircle :size="13" /> {{ errors.price_per_day }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="vln-pricing-card" :class="{ active: selectedTypes.week }">
                            <div class="vln-pc-header" @click="selectedTypes.week = !selectedTypes.week">
                                <div class="vln-pc-label"><div class="vln-pc-toggle"></div><span class="vln-pc-period">Weekly Rate</span></div>
                            </div>
                            <div class="vln-pc-body" v-if="selectedTypes.week">
                                <div class="vln-grid" style="margin-top:0.4rem">
                                    <div class="vln-field"><label class="vln-label">Price per week</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.price_per_week" placeholder="0.00" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                                    <div class="vln-field"><label class="vln-label">Weekly discount</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.weekly_discount" placeholder="0" /><span class="vln-suffix">%</span></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="vln-pricing-card" :class="{ active: selectedTypes.month }">
                            <div class="vln-pc-header" @click="selectedTypes.month = !selectedTypes.month">
                                <div class="vln-pc-label"><div class="vln-pc-toggle"></div><span class="vln-pc-period">Monthly Rate</span></div>
                            </div>
                            <div class="vln-pc-body" v-if="selectedTypes.month">
                                <div class="vln-grid" style="margin-top:0.4rem">
                                    <div class="vln-field"><label class="vln-label">Price per month</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.price_per_month" placeholder="0.00" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                                    <div class="vln-field"><label class="vln-label">Monthly discount</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.monthly_discount" placeholder="0" /><span class="vln-suffix">%</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><DollarSign :size="18" /></div>
                            <div><div class="vln-fg-title">Deposit & Terms</div><div class="vln-fg-sub">Security deposit and rental rules</div></div>
                        </div>
                        <div class="vln-grid">
                            <div class="vln-field">
                                <label class="vln-label">Security Deposit <span class="req">*</span></label>
                                <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.security_deposit" placeholder="0.00" /><span class="vln-suffix">{{ currencyCode }}</span></div>
                                <span v-if="errors.security_deposit" class="vln-error"><AlertCircle :size="13" /> {{ errors.security_deposit }}</span>
                            </div>
                            <div class="vln-field">
                                <label class="vln-label">Minimum Driver Age <span class="req">*</span></label>
                                <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.benefits.minimum_driver_age" placeholder="21" /><span class="vln-suffix">yrs</span></div>
                                <span v-if="errors.minimum_driver_age" class="vln-error"><AlertCircle :size="13" /> {{ errors.minimum_driver_age }}</span>
                            </div>
                            <div class="vln-field full">
                                <label class="vln-label">Rental Guidelines</label>
                                <textarea class="vln-textarea" v-model="form.guidelines" placeholder="Rules or instructions for renters..."></textarea>
                            </div>
                            <div class="vln-field full">
                                <label class="vln-label">Terms & Conditions</label>
                                <textarea class="vln-textarea" v-model="form.terms_policy" placeholder="Vendor terms and conditions..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><CalendarDays :size="18" /></div>
                            <div><div class="vln-fg-title">Operating Hours</div><div class="vln-fg-sub">When your vehicle is available for handover</div></div>
                        </div>
                        <div class="flex justify-end mb-3">
                            <button type="button" @click="applyHoursToAllDays" class="vln-link-btn"><Copy :size="14" /> Apply first day to all</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="day in form.operating_hours" :key="day.day"
                                class="vln-hour-row" :class="{ open: day.is_open }">
                                <div class="vln-hour-left">
                                    <button type="button" @click="day.is_open = !day.is_open"
                                        class="vln-toggle" :class="{ on: day.is_open }">
                                        <span class="vln-toggle-dot"></span>
                                    </button>
                                    <span class="vln-hour-day" :class="{ muted: !day.is_open }">{{ day.day_name }}</span>
                                </div>
                                <div v-if="day.is_open" class="vln-hour-right">
                                    <Clock :size="14" class="text-gray-400 shrink-0" />
                                    <input type="time" v-model="day.open_time" class="vln-time-input" />
                                    <span class="text-gray-400 text-xs">to</span>
                                    <input type="time" v-model="day.close_time" class="vln-time-input" />
                                </div>
                                <span v-else class="text-sm text-gray-400 italic ml-auto">Closed</span>
                                <span v-if="day.is_open && day.open_time && day.close_time && day.close_time <= day.open_time" class="vln-error text-xs ml-2">
                                    <AlertCircle :size="12" /> Close must be after open
                                </span>
                            </div>
                        </div>
                        <div v-if="operatingHoursError" class="vln-error mt-3"><AlertCircle :size="14" /> {{ operatingHoursError }}</div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><CreditCard :size="18" /></div>
                            <div><div class="vln-fg-title">Payment Methods</div><div class="vln-fg-sub">Select all you accept</div></div>
                        </div>
                        <div class="vln-payment-grid">
                            <button type="button" v-for="method in paymentOptions" :key="method.value"
                                class="vln-payment-pill" :class="{ active: form.payment_method.includes(method.value) }"
                                @click="togglePaymentMethod(method.value)">
                                <component :is="method.icon" :size="16" /> {{ method.label }}
                            </button>
                        </div>
                        <span v-if="errors.payment_method" class="vln-error"><AlertCircle :size="13" /> {{ errors.payment_method }}</span>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Gauge :size="18" /></div>
                            <div><div class="vln-fg-title">Kilometer Limits</div><div class="vln-fg-sub">Set mileage restrictions per rental period</div></div>
                        </div>
                        <div class="space-y-3">
                            <div class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.limited_km_per_day" class="vln-checkbox" /> Limited KM per day</label>
                                <div v-if="form.benefits.limited_km_per_day" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">KM Limit</label><input class="vln-input" type="number" v-model="form.benefits.limited_km_per_day_range" /></div>
                                    <div class="vln-field"><label class="vln-label">Price per extra KM</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.benefits.price_per_km_per_day" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                                </div>
                            </div>
                            <div v-if="selectedTypes.week" class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.limited_km_per_week" class="vln-checkbox" /> Limited KM per week</label>
                                <div v-if="form.benefits.limited_km_per_week" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">KM Limit</label><input class="vln-input" type="number" v-model="form.benefits.limited_km_per_week_range" /></div>
                                    <div class="vln-field"><label class="vln-label">Price per extra KM</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.benefits.price_per_km_per_week" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                                </div>
                            </div>
                            <div v-if="selectedTypes.month" class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.limited_km_per_month" class="vln-checkbox" /> Limited KM per month</label>
                                <div v-if="form.benefits.limited_km_per_month" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">KM Limit</label><input class="vln-input" type="number" v-model="form.benefits.limited_km_per_month_range" /></div>
                                    <div class="vln-field"><label class="vln-label">Price per extra KM</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model="form.benefits.price_per_km_per_month" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><X :size="18" /></div>
                            <div><div class="vln-fg-title">Cancellation Policy</div><div class="vln-fg-sub">Set notice periods for cancellation</div></div>
                        </div>
                        <div class="space-y-3">
                            <div class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.cancellation_available_per_day" class="vln-checkbox" /> Allow daily cancellation</label>
                                <div v-if="form.benefits.cancellation_available_per_day" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">Days prior notice</label><input class="vln-input" type="number" v-model="form.benefits.cancellation_available_per_day_date" /></div>
                                </div>
                            </div>
                            <div v-if="selectedTypes.week" class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.cancellation_available_per_week" class="vln-checkbox" /> Allow weekly cancellation</label>
                                <div v-if="form.benefits.cancellation_available_per_week" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">Days prior notice</label><input class="vln-input" type="number" v-model="form.benefits.cancellation_available_per_week_date" /></div>
                                </div>
                            </div>
                            <div v-if="selectedTypes.month" class="vln-sub-card">
                                <label class="vln-check-label"><input type="checkbox" v-model="form.benefits.cancellation_available_per_month" class="vln-checkbox" /> Allow monthly cancellation</label>
                                <div v-if="form.benefits.cancellation_available_per_month" class="vln-grid mt-3">
                                    <div class="vln-field"><label class="vln-label">Days prior notice</label><input class="vln-input" type="number" v-model="form.benefits.cancellation_available_per_month_date" /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STEP 4: Protection Plans -->
                <section v-show="currentStep === 4" class="vln-step" key="step-4">
                    <div class="vln-step-header">
                        <h1>Protection plans</h1>
                        <p>Offer coverage options. Plans with protection sell <strong>3x more</strong>.</p>
                    </div>
                    <div class="vln-tip">
                        <Info :size="16" class="shrink-0 mt-0.5" />
                        <p>Prices must be equal to or higher than your daily rental rate ({{ pricePerDay }} {{ currencyCode }}).</p>
                    </div>
                    <div class="vln-plan-grid">
                        <div v-for="plan in protectionPlans" :key="plan.key"
                            class="vln-plan-card" :class="{ selected: plan.selected }">
                            <div class="vln-plan-check" @click="togglePlanSelection(plan)">
                                <CheckCircle2 v-if="plan.selected" :size="14" />
                            </div>
                            <span class="vln-plan-badge" :class="plan.key">{{ plan.plan_type }}</span>
                            <div class="vln-field mt-3">
                                <label class="vln-label">Price per day</label>
                                <div class="vln-input-suffix">
                                    <input class="vln-input" type="number" step="0.01" v-model.number="plan.price"
                                        :disabled="!plan.selected" :class="{ 'bg-gray-50': !plan.selected }" />
                                    <span class="vln-suffix">{{ currencyCode }}</span>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2">
                                <label class="vln-label">Coverage options (max 5)</label>
                                <input v-for="(_, idx) in plan.features" :key="idx" type="text"
                                    v-model="plan.features[idx]" :disabled="!plan.selected"
                                    class="vln-input text-sm" :class="{ 'bg-gray-50': !plan.selected }"
                                    :placeholder="`Coverage option ${idx + 1}`" />
                            </div>
                            <p v-if="planErrors[plan.key]" class="vln-error mt-2"><AlertCircle :size="13" /> {{ planErrors[plan.key] }}</p>
                            <button type="button" @click="togglePlanSelection(plan)"
                                class="vln-plan-btn mt-4" :class="{ selected: plan.selected }">
                                {{ plan.selected ? 'Selected' : 'Select Plan' }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- STEP 5: Add-ons -->
                <section v-show="currentStep === 5" class="vln-step" key="step-5">
                    <div class="vln-step-header">
                        <h1>Optional add-ons</h1>
                        <p>Offer extras like baby seats or GPS devices to boost your earnings.</p>
                    </div>

                    <div v-if="addons.length" class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Package :size="18" /></div>
                            <div><div class="vln-fg-title">Predefined Add-ons</div><div class="vln-fg-sub">Select from available extras</div></div>
                        </div>
                        <div class="space-y-3">
                            <div v-for="addon in addons" :key="addon.id" class="vln-sub-card"
                                :class="{ 'border-cyan-200 bg-cyan-50/30': isAddonSelected(addon.id) }">
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">{{ addon.extra_name }}</div>
                                        <div class="text-xs text-gray-500">{{ addon.description }}</div>
                                    </div>
                                    <button type="button" @click="toggleAddonSelection(addon.id)"
                                        class="vln-plan-btn text-xs px-3 py-1.5" :class="{ selected: isAddonSelected(addon.id) }"
                                        style="width:auto; margin:0; font-size:0.75rem;">
                                        {{ isAddonSelected(addon.id) ? 'Selected' : 'Select' }}
                                    </button>
                                </div>
                                <div v-if="isAddonSelected(addon.id)" class="vln-grid mt-2">
                                    <div class="vln-field">
                                        <label class="vln-label">Price/day</label>
                                        <div class="vln-input-suffix"><input class="vln-input" type="number" v-model="addonPrices[addon.id]" /><span class="vln-suffix">{{ currencyCode }}</span></div>
                                    </div>
                                    <div class="vln-field">
                                        <label class="vln-label">Quantity</label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="decrementQuantity(addon.id)" class="vln-qty-btn">-</button>
                                            <span class="vln-qty-display">{{ addonQuantities[addon.id] || 1 }}</span>
                                            <button type="button" @click="incrementQuantity(addon.id)" class="vln-qty-btn">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Package :size="18" /></div>
                            <div><div class="vln-fg-title">Custom Add-ons</div><div class="vln-fg-sub">Extras your renters can book</div></div>
                        </div>
                        <span v-if="errors.custom_addons" class="vln-error mb-3"><AlertCircle :size="13" /> {{ errors.custom_addons }}</span>
                        <div v-for="(addon, index) in customAddons" :key="addon.id" class="vln-addon-row">
                            <div class="vln-field"><label class="vln-label">Name</label><input class="vln-input" v-model="addon.extra_name" placeholder="e.g. Baby Seat" /></div>
                            <div class="vln-field"><label class="vln-label">Type</label><input class="vln-input" v-model="addon.extra_type" placeholder="e.g. equipment" /></div>
                            <div class="vln-field"><label class="vln-label">Price/day</label><div class="vln-input-suffix"><input class="vln-input" type="number" v-model.number="addon.price" min="0" /><span class="vln-suffix">{{ currencyCode }}</span></div></div>
                            <div class="vln-field"><label class="vln-label">Qty</label><input class="vln-input" type="number" v-model.number="addon.quantity" min="1" /></div>
                            <button type="button" class="vln-addon-remove" @click="removeCustomAddon(addon.id)"><X :size="14" /></button>
                        </div>
                        <button type="button" class="vln-addon-add" @click="addCustomAddon"><Plus :size="16" /> Add an extra</button>
                    </div>
                </section>

                <!-- STEP 6: Photos -->
                <section v-show="currentStep === 6" class="vln-step" key="step-6">
                    <div class="vln-step-header">
                        <h1>Show off your vehicle</h1>
                        <p>Upload high-quality images. The first image is your cover photo.</p>
                    </div>
                    <div class="vln-tip">
                        <Camera :size="16" class="shrink-0 mt-0.5" />
                        <p>Use natural daylight, shoot all angles (front, back, sides, interior, trunk), and clean your car before photographing.</p>
                    </div>

                    <div v-if="props.vehicle && props.vehicle.images && props.vehicle.images.length > 0" class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Camera :size="18" /></div>
                            <div><div class="vln-fg-title">Current Images</div><div class="vln-fg-sub">{{ props.vehicle.images.length }} image{{ props.vehicle.images.length === 1 ? '' : 's' }} uploaded</div></div>
                        </div>
                        <div class="vln-preview-grid">
                            <div v-for="image in props.vehicle.images" :key="image.id" class="vln-preview"
                                :class="{ primary: image.image_type === 'primary' }">
                                <img :src="image.image_url" alt="Vehicle" />
                                <div class="vln-preview-actions">
                                    <button v-if="image.image_type !== 'primary'" type="button"
                                        @click="setExistingImageAsPrimary(image.id)" title="Set as cover"
                                        class="vln-preview-btn">
                                        <Star :size="12" />
                                    </button>
                                    <button type="button" @click="deleteImage(props.vehicle.id, image.id)"
                                        title="Delete" class="vln-preview-btn danger">
                                        <X :size="12" />
                                    </button>
                                </div>
                                <span v-if="image.image_type === 'primary'" class="vln-cover-badge">Cover</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!props.vehicle || !props.vehicle.images || props.vehicle.images.length < 20" class="vln-field-group">
                        <div class="vln-fg-header">
                            <div class="vln-fg-icon"><Upload :size="18" /></div>
                            <div><div class="vln-fg-title">Upload New Images</div><div class="vln-fg-sub">JPG, PNG up to 10MB each</div></div>
                        </div>
                        <div class="vln-upload-zone" @dragenter="onDragEnter" @dragleave="onDragLeave" @dragover="onDragOver" @drop="onDrop">
                            <div class="vln-upload-icon"><Upload :size="22" /></div>
                            <div class="vln-upload-title">Drag & drop or <label for="images-edit" class="vln-upload-browse">browse files</label></div>
                            <div class="vln-upload-hint">JPG, PNG up to 10MB each.</div>
                            <input type="file" id="images-edit" ref="fileInput" @change="handleFileUpload" multiple class="hidden" accept="image/*" />
                        </div>
                        <div v-if="selectedFiles.length" class="vln-preview-grid mt-3">
                            <div v-for="(file, index) in selectedFiles" :key="index" class="vln-preview"
                                :class="{ primary: form.primary_image_index === index }">
                                <img :src="getNewImageUrl(file)" alt="New upload" />
                                <div class="vln-preview-actions">
                                    <button type="button" @click="setNewImageAsPrimary(index)" title="Set as cover"
                                        class="vln-preview-btn" :class="{ active: form.primary_image_index === index }">
                                        <Star :size="12" />
                                    </button>
                                    <button type="button" @click="removeFile(index)" title="Remove" class="vln-preview-btn danger"><X :size="12" /></button>
                                </div>
                                <span v-if="form.primary_image_index === index" class="vln-cover-badge">Cover</span>
                            </div>
                        </div>
                        <span v-if="errors.images" class="vln-error mt-2"><AlertCircle :size="13" /> {{ errors.images }}</span>
                    </div>
                    <p v-else class="text-amber-600 text-sm">Maximum of 20 images reached. Delete an existing image to upload more.</p>
                </section>
            </div>

        </div>

        <div class="vln-action-bar">
            <div class="vln-action-inner">
                <div class="vln-action-info">Step <strong>{{ currentStep + 1 }}</strong> of <strong>{{ stepNames.length }}</strong> &middot; {{ stepNames[currentStep] }}</div>
                <div class="vln-action-buttons">
                    <button v-if="currentStep > 0" class="vln-btn-back" @click="prevStep"><ArrowLeft :size="16" /> Back</button>
                    <button v-if="currentStep < stepNames.length - 1" class="vln-btn-next" @click="nextStep" :disabled="isLoading">
                        Continue <ChevronRight :size="16" />
                    </button>
                    <button v-else class="vln-btn-submit" @click="updateVehicle" :disabled="isLoading">
                        <span v-if="isLoading" class="vln-spinner-sm"></span>
                        <template v-else><CheckCircle2 :size="16" /> Update Vehicle</template>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showErrorDialog" class="vln-dialog-overlay" @click="closeErrorDialog">
            <div class="vln-dialog" @click.stop>
                <AlertCircle :size="24" class="text-red-500" />
                <p>{{ errorMessage }}</p>
                <button @click="closeErrorDialog" class="vln-btn-next mt-3">OK</button>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, getCurrentInstance, onMounted, onUnmounted, reactive, ref, watch } from "vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import LocationPicker from "@/Components/LocationPicker.vue";
import axios from "axios";
import { useToast } from 'vue-toastification';
import Select from "@/Components/ui/select/Select.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import { SelectContent, SelectGroup, SelectLabel, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { usePage } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import vehicleColorsFromJson from '../../../data/colors.json';
import {
    CalendarDays, Clock, Copy, AlertCircle, Car, Wrench, CheckCircle2,
    MapPin, DollarSign, Shield, Package, Camera, ChevronRight, ArrowLeft,
    HelpCircle, X, Upload, CreditCard, Banknote, FileText, Gauge, Star,
    Info, Zap, Plus, Snowflake, Navigation, Bluetooth, Video, KeyRound,
    Usb, Sun, Armchair, ParkingCircle, Gauge as CruiseIcon, Baby, Wifi,
    CircleDot, Lock, Lightbulb, Music, Wind, ThermometerSun, Eye
} from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const page = usePage();
const currencyCode = computed(() => page.props.auth?.user?.profile?.currency || page.props.currency || 'USD');
const toast = useToast();
const customAddons = ref([]);
const stepNames = ['Details', 'Registration', 'Location', 'Pricing', 'Protection', 'Add-ons', 'Photos'];
const formCol = ref(null);

const paymentOptions = [
    { value: 'credit_card', label: 'Credit Card', icon: CreditCard },
    { value: 'bank_wire', label: 'Bank Wire', icon: DollarSign },
    { value: 'cash', label: 'Cash', icon: Banknote },
    { value: 'cheque', label: 'Cheque', icon: FileText },
];

const props = defineProps({
    vehicle: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const defaultOperatingHours = [
    { day: 0, day_name: 'Monday',    is_open: true, open_time: '08:00', close_time: '18:00' },
    { day: 1, day_name: 'Tuesday',   is_open: true, open_time: '08:00', close_time: '18:00' },
    { day: 2, day_name: 'Wednesday', is_open: true, open_time: '08:00', close_time: '18:00' },
    { day: 3, day_name: 'Thursday',  is_open: true, open_time: '08:00', close_time: '18:00' },
    { day: 4, day_name: 'Friday',    is_open: true, open_time: '08:00', close_time: '18:00' },
    { day: 5, day_name: 'Saturday',  is_open: true, open_time: '09:00', close_time: '14:00' },
    { day: 6, day_name: 'Sunday',    is_open: false, open_time: null, close_time: null },
];

const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const form = useForm({
    category_id: null, brand: "", model: "", color: "", mileage: 0,
    transmission: "manual", fuel: "petrol", seating_capacity: 1,
    number_of_doors: 2, luggage_capacity: 0, horsepower: 0, co2: "",
    location: "", location_type: "", latitude: null, longitude: null,
    city: "", state: "", country: "", status: "available",
    features: [], featured: false, security_deposit: 0,
    payment_method: [], guidelines: "", terms_policy: "",
    price_per_day: 0, price_per_week: null, price_per_month: null,
    weekly_discount: null, monthly_discount: null, preferred_price_type: 'day',
    registration_number: "", registration_country: "", registration_date: "",
    gross_vehicle_mass: 0, vehicle_height: 0, dealer_cost: 0, phone_number: "",
    images: [],
    pickup_times: [], return_times: [],
    operating_hours: defaultOperatingHours.map(d => ({ ...d })),
    benefits: {
        limited_km_per_day: false, limited_km_per_week: false, limited_km_per_month: false,
        limited_km_per_day_range: null, limited_km_per_week_range: null, limited_km_per_month_range: null,
        cancellation_available_per_day: false, cancellation_available_per_week: false, cancellation_available_per_month: false,
        cancellation_available_per_day_date: null, cancellation_available_per_week_date: null, cancellation_available_per_month_date: null,
        price_per_km_per_day: 0, price_per_km_per_week: 0, price_per_km_per_month: 0,
        minimum_driver_age: 18
    },
    selected_plans: [], selected_addons: [],
    addon_prices: {}, addon_quantities: {},
    custom_addons: [],
    primary_image_index: null,
    existing_primary_image_id: null,
    full_vehicle_address: '',
});

const selectedTypes = reactive({ day: true, week: false, month: false });

const pricePerDay = computed(() => { const v = Number(form.price_per_day); return Number.isFinite(v) ? v : 0; });
const operatingHoursError = computed(() => {
    if (!form.operating_hours.some(d => d.is_open)) return 'At least one day must be open.';
    for (const day of form.operating_hours) {
        if (day.is_open && day.open_time && day.close_time && day.close_time <= day.open_time) return `${day.day_name}: Close time must be after open time.`;
        if (day.is_open && (!day.open_time || !day.close_time)) return `${day.day_name}: Both open and close times are required.`;
    }
    return null;
});
const displayedFullAddress = computed(() => {
    return [form.location, form.city, form.state, form.country].filter(p => p !== null && p !== '').join(', ');
});

const customFeatureInput = ref('');
const allFeatures = ref([
    { name: 'Air Conditioning', icon: Snowflake },
    { name: 'GPS Navigation', icon: Navigation },
    { name: 'Bluetooth', icon: Bluetooth },
    { name: 'Backup Camera', icon: Video },
    { name: 'Keyless Entry', icon: KeyRound },
    { name: 'USB Charging', icon: Usb },
    { name: 'Sunroof', icon: Sun },
    { name: 'Heated Seats', icon: ThermometerSun },
    { name: 'Parking Sensors', icon: ParkingCircle },
    { name: 'Cruise Control', icon: CruiseIcon },
    { name: 'Baby Seat', icon: Baby },
    { name: 'Wi-Fi Hotspot', icon: Wifi },
    { name: 'Apple CarPlay', icon: Music },
    { name: 'Android Auto', icon: Music },
    { name: 'Blind Spot Monitor', icon: Eye },
    { name: 'Lane Assist', icon: CircleDot },
    { name: 'Central Locking', icon: Lock },
    { name: 'LED Headlights', icon: Lightbulb },
    { name: 'Power Windows', icon: Wind },
    { name: 'All-Wheel Drive', icon: Car },
]);

const addCustomFeature = () => {
    const name = customFeatureInput.value.trim();
    if (!name) return;
    if (allFeatures.value.some(f => f.name.toLowerCase() === name.toLowerCase())) {
        if (!form.features.includes(name)) toggleFeature(name);
    } else {
        allFeatures.value.push({ name, icon: Star });
        form.features.push(name);
    }
    customFeatureInput.value = '';
};

const vehicleColors = ref(vehicleColorsFromJson);
const countries = ref([]);
const isLoading = ref(false);
const currentStep = ref(0);
const highestStepReached = ref(6);
const errorMessage = ref('');
const showErrorDialog = ref(false);
const dragCounter = ref(0);
const formErrors = ref({});
const fileInput = ref(null);
const selectedFiles = ref([]);
const maxImages = 20;

const addons = ref([]);
const selectedAddons = ref([]);
const addonPrices = ref({});
const addonQuantities = ref({});
const existingAddonSelections = ref({});

const createCoverageFields = () => Array.from({ length: 5 }, () => '');
const protectionPlans = reactive([
    { key: 'essential', plan_type: 'Essential', price: null, features: createCoverageFields(), selected: false },
    { key: 'premium', plan_type: 'Premium', price: null, features: createCoverageFields(), selected: false },
    { key: 'premium_plus', plan_type: 'Premium Plus', price: null, features: createCoverageFields(), selected: false },
]);
const planErrors = reactive({ essential: '', premium: '', premium_plus: '' });

const errors = reactive({
    category_id: '', brand: '', model: '', color: '', mileage: '', horsepower: '', co2: '', features: '',
    registration_number: '', registration_country: '', registration_date: '', phone_number: '',
    location: '', location_type: '', latitude: '', longitude: '',
    security_deposit: '', payment_method: '', terms_policy: '', minimum_driver_age: '',
    price_per_day: '', price_per_week: '', price_per_month: '', custom_addons: '', images: '',
    operating_hours: '',
});

const toPrice = (v) => { const n = Number(v); return Number.isFinite(n) ? Math.round(n * 100) / 100 : null; };
const normalizeFeatures = (f) => f.map(x => x.trim()).filter(Boolean).slice(0, 5);
const isPlanActive = (p) => p.selected;

const toggleFeature = (name) => {
    const idx = form.features.indexOf(name);
    idx === -1 ? form.features.push(name) : form.features.splice(idx, 1);
};

const togglePaymentMethod = (value) => {
    const idx = form.payment_method.indexOf(value);
    idx === -1 ? form.payment_method.push(value) : form.payment_method.splice(idx, 1);
};

const applyHoursToAllDays = () => {
    const f = form.operating_hours[0];
    if (!f) return;
    form.operating_hours.forEach((d, i) => { if (i > 0) { d.is_open = f.is_open; d.open_time = f.open_time; d.close_time = f.close_time; } });
};

const ensurePreferredPriceType = () => {
    const types = ['day'];
    if (selectedTypes.week) types.push('week');
    if (selectedTypes.month) types.push('month');
    if (!types.includes(form.preferred_price_type)) form.preferred_price_type = types[0];
};

const validateProtectionPlans = () => {
    planErrors.essential = ''; planErrors.premium = ''; planErrors.premium_plus = '';
    const min = pricePerDay.value; let ok = true;
    protectionPlans.forEach(p => {
        if (!isPlanActive(p)) return;
        const v = Number(p.price);
        if (!Number.isFinite(v) || v <= 0) { planErrors[p.key] = 'Price is required.'; ok = false; return; }
        if (v < min) { planErrors[p.key] = `Price must be at least ${min} ${currencyCode.value}.`; ok = false; }
    });
    return ok;
};

const togglePlanSelection = (plan) => {
    plan.selected = !plan.selected;
    if (!plan.selected) { plan.price = null; plan.features = createCoverageFields(); planErrors[plan.key] = ''; }
};

const buildSelectedPlans = () => {
    let id = 1;
    return protectionPlans.reduce((acc, p) => {
        if (!isPlanActive(p)) return acc;
        acc.push({ plan_id: id, plan_type: p.plan_type, plan_value: Number(p.price), plan_description: null, features: normalizeFeatures(p.features) });
        id++; return acc;
    }, []);
};

const isAddonSelected = (addonId) => selectedAddons.value.includes(addonId);

const toggleAddonSelection = (addonId) => {
    const index = selectedAddons.value.indexOf(addonId);
    if (index >= 0) {
        selectedAddons.value.splice(index, 1);
    } else {
        selectedAddons.value.push(addonId);
        if (!addonQuantities.value[addonId]) addonQuantities.value[addonId] = 1;
    }
};

const incrementQuantity = (addonId) => {
    if (!isAddonSelected(addonId)) return;
    if (!addonQuantities.value[addonId]) addonQuantities.value[addonId] = 1;
    addonQuantities.value[addonId]++;
};

const decrementQuantity = (addonId) => {
    if (!isAddonSelected(addonId)) return;
    if (addonQuantities.value[addonId] > 1) addonQuantities.value[addonId]--;
};

const addCustomAddon = () => {
    customAddons.value.push({ id: `${Date.now()}-${Math.random().toString(16).slice(2)}`, extra_name: '', extra_type: '', description: '', price: 0, quantity: 1 });
};
const removeCustomAddon = (id) => { customAddons.value = customAddons.value.filter(a => a.id !== id); };

const buildCustomAddons = () => {
    return customAddons.value.map(a => ({
        extra_name: `${a.extra_name || ''}`.trim(),
        extra_type: `${a.extra_type || ''}`.trim() || 'custom',
        description: `${a.description || ''}`.trim(),
        price: a.price,
        quantity: a.quantity || 1
    })).filter(a => a.extra_name || a.description || Number(a.price) > 0);
};

const validateCustomAddons = (payload) => {
    const errs = [];
    payload.forEach((addon, index) => {
        if (!addon.extra_name) errs.push(`Custom addon ${index + 1}: name is required.`);
        const pv = Number(addon.price);
        if (!Number.isFinite(pv) || pv < 0) errs.push(`Custom addon ${index + 1}: price must be 0 or more.`);
        const qv = Number(addon.quantity);
        if (!Number.isFinite(qv) || qv < 1) errs.push(`Custom addon ${index + 1}: quantity must be at least 1.`);
    });
    return errs;
};

const fetchCountries = async () => { try { countries.value = await (await fetch('/countries.json')).json(); } catch (e) { console.error(e); } };
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
    } catch (error) { console.error('Error fetching addons:', error); }
};

const handleLocationSelect = (loc) => {
    form.location = loc.address || loc.formattedAddress || '';
    form.latitude = Number.isFinite(Number(loc.latitude)) ? Number(loc.latitude) : null;
    form.longitude = Number.isFinite(Number(loc.longitude)) ? Number(loc.longitude) : null;
    form.city = loc.city || ''; form.state = loc.state || ''; form.country = loc.country || '';
};

const getFlagUrl = (code) => `https://flagcdn.com/w40/${code.toLowerCase()}.png`;
const formatDate = (v) => { if (!v) { form.registration_date = null; return; } form.registration_date = new Date(v).toISOString().split('T')[0]; };

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const VALID_FORMATS = ['image/jpeg', 'image/jpg', 'image/png'];

const handleFileUpload = (e) => {
    const newFiles = Array.from(e.target.files);
    const currentImagesCount = props.vehicle?.images?.length || 0;
    const totalAfterAdding = currentImagesCount + selectedFiles.value.length + newFiles.length;
    if (totalAfterAdding > maxImages) {
        errorMessage.value = `Maximum of ${maxImages} images allowed.`;
        showErrorDialog.value = true;
        return;
    }
    for (const f of newFiles) {
        if (!VALID_FORMATS.includes(f.type)) { errorMessage.value = 'Please upload only JPG or PNG images.'; showErrorDialog.value = true; return; }
        if (f.size > MAX_FILE_SIZE) { errorMessage.value = 'Image size must be under 10MB.'; showErrorDialog.value = true; return; }
    }
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    form.images = selectedFiles.value;
    if (form.primary_image_index === null && selectedFiles.value.length > 0 && !form.existing_primary_image_id) {
        form.primary_image_index = 0;
    }
};

const getNewImageUrl = (file) => URL.createObjectURL(file);

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    form.images = selectedFiles.value;
    if (form.primary_image_index === index) form.primary_image_index = null;
    else if (form.primary_image_index > index) form.primary_image_index--;
};

const setNewImageAsPrimary = (index) => {
    form.primary_image_index = index;
    form.existing_primary_image_id = null;
};

const setExistingImageAsPrimary = (imageId) => {
    form.existing_primary_image_id = imageId;
    form.primary_image_index = null;
    if (props.vehicle && props.vehicle.images) {
        props.vehicle.images.forEach(img => {
            img.image_type = img.id === imageId ? 'primary' : 'gallery';
        });
    }
};

const deleteImage = async (vehicleId, imageId) => {
    try {
        await axios.delete(route('current-vendor-vehicles.deleteImage', { vehicle: vehicleId, image: imageId }));
        const index = props.vehicle.images.findIndex(img => img.id === imageId);
        if (index !== -1) props.vehicle.images.splice(index, 1);
        toast.success('Image deleted successfully', { position: 'top-right', timeout: 3000 });
    } catch (error) {
        console.error('Error deleting image:', error);
        toast.error('Failed to delete image', { position: 'top-right', timeout: 3000 });
    }
};

const closeErrorDialog = () => { showErrorDialog.value = false; errorMessage.value = ''; };
const onDragEnter = (e) => { e.preventDefault(); e.stopPropagation(); dragCounter.value++; };
const onDragLeave = (e) => { e.preventDefault(); e.stopPropagation(); dragCounter.value--; };
const onDragOver = (e) => { e.preventDefault(); e.stopPropagation(); };
const onDrop = (e) => {
    e.preventDefault(); e.stopPropagation(); dragCounter.value = 0;
    const f = Array.from(e.dataTransfer.files);
    if (f.length) {
        const currentImagesCount = props.vehicle?.images?.length || 0;
        const totalAfterAdding = currentImagesCount + selectedFiles.value.length + f.length;
        if (totalAfterAdding > maxImages) {
            errorMessage.value = `Maximum of ${maxImages} images allowed.`;
            showErrorDialog.value = true;
            return;
        }
        for (const file of f) {
            if (!VALID_FORMATS.includes(file.type)) { errorMessage.value = 'Please upload only JPG or PNG images.'; showErrorDialog.value = true; return; }
            if (file.size > MAX_FILE_SIZE) { errorMessage.value = 'Image size must be under 10MB.'; showErrorDialog.value = true; return; }
        }
        selectedFiles.value = [...selectedFiles.value, ...f];
        form.images = selectedFiles.value;
        if (form.primary_image_index === null && selectedFiles.value.length > 0 && !form.existing_primary_image_id) {
            form.primary_image_index = 0;
        }
    }
};

const goToStep = (s) => { if (s <= highestStepReached.value) { currentStep.value = s; window.scrollTo(0, 0); } };

const nextStep = () => {
    let ok = true;
    Object.keys(errors).forEach(k => errors[k] = '');
    switch (currentStep.value) {
        case 0:
            if (!form.category_id) { ok = false; errors.category_id = 'Please select a vehicle category'; }
            if (!form.brand) { ok = false; errors.brand = 'Please enter the vehicle brand'; }
            if (!form.model) { ok = false; errors.model = 'Please enter the vehicle model'; }
            if (!form.color) { ok = false; errors.color = 'Please select a color'; }
            break;
        case 1:
            if (!form.registration_number) { ok = false; errors.registration_number = 'Please enter registration number'; }
            if (!form.registration_country) { ok = false; errors.registration_country = 'Please select country'; }
            if (!form.registration_date) { ok = false; errors.registration_date = 'Please enter registration date'; }
            if (!form.phone_number) { ok = false; errors.phone_number = 'Please enter phone number'; }
            break;
        case 2:
            if (!form.location || form.latitude === null || form.longitude === null) { ok = false; errors.location = 'Please select a valid address'; }
            if (!form.location_type) { ok = false; errors.location_type = 'Please select location type'; }
            break;
        case 3:
            if (!form.price_per_day) { ok = false; errors.price_per_day = 'Please enter daily price'; }
            if (!form.payment_method.length) { ok = false; errors.payment_method = 'Please select at least one payment method'; }
            if (operatingHoursError.value) { ok = false; errors.operating_hours = operatingHoursError.value; }
            break;
        case 4:
            if (!validateProtectionPlans()) ok = false;
            break;
        case 5: {
            const norm = buildCustomAddons();
            const addonErrs = validateCustomAddons(norm);
            if (addonErrs.length) { ok = false; errors.custom_addons = addonErrs[0]; }
            break;
        }
    }
    if (ok && currentStep.value < stepNames.length - 1) {
        currentStep.value++;
        if (currentStep.value > highestStepReached.value) highestStepReached.value = currentStep.value;
        window.scrollTo(0, 0);
    } else if (!ok) {
        const el = document.querySelector('.vln-error');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const prevStep = () => { if (currentStep.value > 0) currentStep.value--; };

const updateVehicle = () => {
    isLoading.value = true;
    formErrors.value = {};

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

    let submitData = form.data();

    const numericFieldsToNullifyIfZero = [
        'mileage', 'luggage_capacity', 'horsepower', 'security_deposit',
        'price_per_day', 'price_per_week', 'price_per_month',
        'weekly_discount', 'monthly_discount', 'gross_vehicle_mass',
        'vehicle_height', 'dealer_cost'
    ];
    numericFieldsToNullifyIfZero.forEach(key => {
        if (submitData.hasOwnProperty(key) && Number(submitData[key]) === 0) submitData[key] = null;
    });

    const benefitNumericFields = [
        'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
        'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
        'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month'
    ];
    if (submitData.benefits) {
        benefitNumericFields.forEach(key => {
            if (submitData.benefits.hasOwnProperty(key)) {
                if (submitData.benefits[key] === null) { /* keep null */ }
                else if (Number(submitData.benefits[key]) === 0) submitData.benefits[key] = null;
            }
        });
    }

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

    if (submitData.benefits) {
        const benefitEnsureNumber = [
            'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
            'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
            'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month', 'minimum_driver_age'
        ];
        benefitEnsureNumber.forEach(key => {
            if (submitData.benefits.hasOwnProperty(key) && submitData.benefits[key] !== null && submitData.benefits[key] !== '') {
                submitData.benefits[key] = Number(submitData.benefits[key]);
            }
        });
    }

    let formData = new FormData();

    if (submitData.benefits) {
        Object.keys(submitData.benefits).forEach(key => {
            const value = submitData.benefits[key];
            if (typeof value === 'boolean') formData.append(`benefits[${key}]`, value ? '1' : '0');
            else formData.append(`benefits[${key}]`, value !== null ? value : '');
        });
    }

    for (const key in submitData) {
        if (!submitData.hasOwnProperty(key)) continue;
        if (['benefits', 'images', 'selected_plans', 'selected_addons', 'addon_prices', 'addon_quantities', 'custom_addons', 'operating_hours'].includes(key)) continue;
        const value = submitData[key];
        if (Array.isArray(value)) {
            value.forEach(item => formData.append(`${key}[]`, item !== null ? item : ''));
        } else {
            formData.append(key, value !== null ? value : '');
        }
    }

    if (Array.isArray(submitData.operating_hours)) {
        submitData.operating_hours.forEach((hours, index) => {
            formData.append(`operating_hours[${index}][day]`, hours.day);
            formData.append(`operating_hours[${index}][is_open]`, hours.is_open ? '1' : '0');
            formData.append(`operating_hours[${index}][open_time]`, hours.is_open && hours.open_time ? hours.open_time : '');
            formData.append(`operating_hours[${index}][close_time]`, hours.is_open && hours.close_time ? hours.close_time : '');
        });
    }

    if (Array.isArray(submitData.selected_plans)) {
        submitData.selected_plans.forEach((plan, index) => {
            formData.append(`selected_plans[${index}][plan_type]`, plan.plan_type ?? '');
            formData.append(`selected_plans[${index}][plan_value]`, plan.plan_value ?? '');
            if (plan.plan_description) formData.append(`selected_plans[${index}][plan_description]`, plan.plan_description);
            if (Array.isArray(plan.features)) {
                plan.features.forEach((feature, fi) => formData.append(`selected_plans[${index}][features][${fi}]`, feature));
            }
        });
    }

    if (Array.isArray(submitData.selected_addons)) {
        submitData.selected_addons.forEach(addonId => formData.append('selected_addons[]', addonId));
    }

    if (submitData.addon_prices && typeof submitData.addon_prices === 'object') {
        Object.entries(submitData.addon_prices).forEach(([addonId, price]) => formData.append(`addon_prices[${addonId}]`, price ?? ''));
    }

    if (submitData.addon_quantities && typeof submitData.addon_quantities === 'object') {
        Object.entries(submitData.addon_quantities).forEach(([addonId, quantity]) => formData.append(`addon_quantities[${addonId}]`, quantity ?? ''));
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

    selectedFiles.value.forEach((file, index) => formData.append(`images[${index}]`, file));

    formData.append('_method', 'PUT');

    axios.post(route('current-vendor-vehicles.update', props.vehicle.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
    })
    .then((response) => {
        isLoading.value = false;
        selectedFiles.value = [];
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
                if (!addonId) return;
                existingAddonSelections.value[addonId] = { price: parseFloat(addon.price) || 0, quantity: parseInt(addon.quantity, 10) || 1 };
            });
            customAddons.value = [];
            fetchAddons();

            if (updatedVehicle.operating_hours || updatedVehicle.operatingHours) {
                const hours = updatedVehicle.operating_hours || updatedVehicle.operatingHours || [];
                initOperatingHours(hours);
            }
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
            toast.error(message || 'Please review the highlighted fields.', { position: 'top-right', timeout: 4000 });
            return;
        }
        toast.error('Something went wrong.', { position: 'top-right', timeout: 3000 });
    });
};

const initOperatingHours = (hoursArray) => {
    if (!hoursArray || !hoursArray.length) return;
    form.operating_hours = defaultOperatingHours.map(def => {
        const match = hoursArray.find(h => h.day_of_week === def.day || h.day === def.day);
        if (match) {
            return {
                day: def.day,
                day_name: def.day_name,
                is_open: !!match.is_open,
                open_time: match.open_time ? match.open_time.substring(0, 5) : null,
                close_time: match.close_time ? match.close_time.substring(0, 5) : null,
            };
        }
        return { ...def };
    });
};

watch(() => selectedTypes.week, (on) => { if (on) { if (!form.price_per_week && form.price_per_day) form.price_per_week = toPrice(Number(form.price_per_day) * 7); } else { form.price_per_week = null; form.weekly_discount = null; } ensurePreferredPriceType(); });
watch(() => selectedTypes.month, (on) => { if (on) { if (!form.price_per_month && form.price_per_day) form.price_per_month = toPrice(Number(form.price_per_day) * 30); } else { form.price_per_month = null; form.monthly_discount = null; } ensurePreferredPriceType(); });
watch(() => form.mileage, (v) => { if (v > 120) form.mileage = 120; });
watch(() => form.vehicle_height, (v) => { if (v > 5) form.vehicle_height = 5; });
watch(() => form.co2, (v) => { if (v > 100) form.co2 = 100; });
watch(() => form.gross_vehicle_mass, (v) => { if (v > 20000) form.gross_vehicle_mass = 20000; });
watch(() => form.price_per_day, (v) => { if (!v) return; if (selectedTypes.week && !form.price_per_week) form.price_per_week = toPrice(Number(v) * 7); if (selectedTypes.month && !form.price_per_month) form.price_per_month = toPrice(Number(v) * 30); });
watch(() => form.registration_number, (v) => { if (v && v.length > 10) form.registration_number = v.slice(0, 10); });
watch(() => form.benefits.limited_km_per_day, (v) => { if (!v) { form.benefits.limited_km_per_day_range = null; form.benefits.price_per_km_per_day = null; } });
watch(() => form.benefits.limited_km_per_week, (v) => { if (!v) { form.benefits.limited_km_per_week_range = null; form.benefits.price_per_km_per_week = null; } });
watch(() => form.benefits.limited_km_per_month, (v) => { if (!v) { form.benefits.limited_km_per_month_range = null; form.benefits.price_per_km_per_month = null; } });
watch(() => form.benefits.cancellation_available_per_day, (v) => { if (!v) form.benefits.cancellation_available_per_day_date = null; });
watch(() => form.benefits.cancellation_available_per_week, (v) => { if (!v) form.benefits.cancellation_available_per_week_date = null; });
watch(() => form.benefits.cancellation_available_per_month, (v) => { if (!v) form.benefits.cancellation_available_per_month_date = null; });
watch(isLoading, (v) => { document.body.style.overflow = v ? 'hidden' : ''; });
watch(customAddons, () => { if (errors.custom_addons) errors.custom_addons = ''; }, { deep: true });

onMounted(() => {
    fetchCountries();

    const v = props.vehicle;
    if (!v) return;

    form.category_id = v.category_id;
    form.brand = v.brand;
    form.model = v.model;
    form.color = v.color;
    form.mileage = v.mileage;
    form.transmission = v.transmission;
    form.fuel = v.fuel;
    form.seating_capacity = v.seating_capacity;
    form.number_of_doors = v.number_of_doors;
    form.luggage_capacity = v.luggage_capacity;
    form.horsepower = v.horsepower;
    form.co2 = v.co2;
    form.location = v.location || '';
    form.location_type = v.location_type || '';
    form.city = v.city || '';
    form.state = v.state;
    form.country = v.country || '';
    const lat = Number(v.latitude);
    const lng = Number(v.longitude);
    form.latitude = Number.isFinite(lat) ? lat : null;
    form.longitude = Number.isFinite(lng) ? lng : null;
    form.full_vehicle_address = v.full_vehicle_address || '';
    form.status = v.status;

    try {
        const parsed = JSON.parse(v.features);
        form.features = Array.isArray(parsed) ? parsed : [];
    } catch { form.features = []; }

    form.features.forEach(name => {
        if (!allFeatures.value.some(f => f.name === name)) {
            allFeatures.value.push({ name, icon: Star });
        }
    });

    form.featured = v.featured;
    form.security_deposit = parseFloat(v.security_deposit);
    try { form.payment_method = JSON.parse(v.payment_method); } catch { form.payment_method = []; }

    const rawDaily = parseFloat(v.price_per_day);
    const rawWeekly = parseFloat(v.price_per_week);
    const rawMonthly = parseFloat(v.price_per_month);
    const rawWeeklyDiscount = parseFloat(v.weekly_discount);
    const rawMonthlyDiscount = parseFloat(v.monthly_discount);

    form.price_per_day = Number.isFinite(rawDaily) ? rawDaily : 0;
    form.price_per_week = Number.isFinite(rawWeekly) ? rawWeekly : null;
    form.price_per_month = Number.isFinite(rawMonthly) ? rawMonthly : null;
    form.weekly_discount = Number.isFinite(rawWeeklyDiscount) ? rawWeeklyDiscount : null;
    form.monthly_discount = Number.isFinite(rawMonthlyDiscount) ? rawMonthlyDiscount : null;
    form.preferred_price_type = v.preferred_price_type || 'day';

    selectedTypes.week = Number.isFinite(rawWeekly) && rawWeekly > 0;
    selectedTypes.month = Number.isFinite(rawMonthly) && rawMonthly > 0;
    if (!selectedTypes.week) { form.price_per_week = null; form.weekly_discount = null; }
    if (!selectedTypes.month) { form.price_per_month = null; form.monthly_discount = null; }
    ensurePreferredPriceType();

    form.guidelines = v.guidelines;
    form.terms_policy = v.terms_policy || '';

    const primaryExistingImage = v.images?.find(img => img.image_type === 'primary');
    if (primaryExistingImage) form.existing_primary_image_id = primaryExistingImage.id;

    if (v.benefits) {
        form.benefits = {
            limited_km_per_day: !!v.benefits.limited_km_per_day,
            limited_km_per_week: !!v.benefits.limited_km_per_week,
            limited_km_per_month: !!v.benefits.limited_km_per_month,
            limited_km_per_day_range: v.benefits.limited_km_per_day_range || null,
            limited_km_per_week_range: v.benefits.limited_km_per_week_range || null,
            limited_km_per_month_range: v.benefits.limited_km_per_month_range || null,
            cancellation_available_per_day: !!v.benefits.cancellation_available_per_day,
            cancellation_available_per_week: !!v.benefits.cancellation_available_per_week,
            cancellation_available_per_month: !!v.benefits.cancellation_available_per_month,
            cancellation_available_per_day_date: v.benefits.cancellation_available_per_day_date || null,
            cancellation_available_per_week_date: v.benefits.cancellation_available_per_week_date || null,
            cancellation_available_per_month_date: v.benefits.cancellation_available_per_month_date || null,
            price_per_km_per_day: parseFloat(v.benefits.price_per_km_per_day) || 0,
            price_per_km_per_week: parseFloat(v.benefits.price_per_km_per_week) || 0,
            price_per_km_per_month: parseFloat(v.benefits.price_per_km_per_month) || 0,
            minimum_driver_age: v.benefits.minimum_driver_age || 18
        };
    }

    if (v.specifications) {
        form.registration_number = v.specifications.registration_number;
        form.registration_country = v.specifications.registration_country;
        form.registration_date = v.specifications.registration_date;
        form.gross_vehicle_mass = v.specifications.gross_vehicle_mass;
        form.vehicle_height = parseFloat(v.specifications.vehicle_height) || 0;
        form.dealer_cost = parseFloat(v.specifications.dealer_cost) || 0;
        form.phone_number = v.specifications.phone_number;
    }

    const existingHours = v.operating_hours || v.operatingHours || [];
    if (existingHours.length) initOperatingHours(existingHours);

    const existingPlans = v.vendorPlans || v.vendor_plans || [];
    existingPlans.forEach((plan) => {
        const planType = `${plan.plan_type || ''}`.toLowerCase();
        const match = protectionPlans.find(entry => entry.plan_type.toLowerCase() === planType);
        if (!match) return;
        match.selected = true;
        match.price = parseFloat(plan.price) || 0;
        let planFeatures = [];
        if (Array.isArray(plan.features)) planFeatures = plan.features;
        else if (typeof plan.features === 'string') { try { planFeatures = JSON.parse(plan.features || '[]'); } catch { planFeatures = []; } }
        match.features = Array.isArray(planFeatures) && planFeatures.length
            ? [...planFeatures, ...createCoverageFields()].slice(0, 5)
            : createCoverageFields();
    });

    const existingAddons = v.addons || [];
    existingAddons.forEach((addon) => {
        const addonId = addon.addon_id ?? addon.addon?.id ?? addon.id;
        if (!addonId) return;
        existingAddonSelections.value[addonId] = { price: parseFloat(addon.price) || 0, quantity: parseInt(addon.quantity, 10) || 1 };
    });

    if (v.pickup_times && typeof v.pickup_times === 'string') form.pickup_times = v.pickup_times.split(',').filter(Boolean);
    else if (Array.isArray(v.pickup_times)) form.pickup_times = [...v.pickup_times];

    if (v.return_times && typeof v.return_times === 'string') form.return_times = v.return_times.split(',').filter(Boolean);
    else if (Array.isArray(v.return_times)) form.return_times = [...v.return_times];

    fetchAddons();
});

onUnmounted(() => { document.body.style.overflow = ''; });
</script>

<style scoped>
/* STEPPER */
.vln-stepper { position: sticky; top: 0; z-index: 99; background: #fff; border-bottom: 1px solid #e2e8f0; overflow-x: auto; scrollbar-width: none; }
.vln-stepper::-webkit-scrollbar { display: none; }
.vln-stepper-inner { padding: 0 1rem; display: flex; width: 100%; }
.vln-step-tab { flex: 1; position: relative; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.8rem 0.5rem; cursor: pointer; border: none; background: none; font: inherit; font-size: 0.78rem; font-weight: 600; color: #94a3b8; white-space: nowrap; transition: color 0.3s; }
.vln-step-tab::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2.5px; border-radius: 2px; background: transparent; transition: background 0.3s; }
.vln-step-tab.active { color: #0891b2; }
.vln-step-tab.active::after { background: #0891b2; }
.vln-step-tab.completed { color: #22c55e; }
.vln-step-tab.completed::after { background: #22c55e; }
.vln-step-tab:not(.clickable) { cursor: not-allowed; opacity: 0.5; }
.vln-step-tab.clickable:hover:not(.active) { color: #334155; }
.vln-step-num { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; border: 1.5px solid #e2e8f0; color: #94a3b8; background: #fff; transition: all 0.3s; }
.vln-step-tab.active .vln-step-num { background: #0891b2; border-color: #0891b2; color: #fff; animation: pulse 2s ease-in-out infinite; }
.vln-step-tab.completed .vln-step-num { background: #22c55e; border-color: #22c55e; color: #fff; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(6,182,212,0.4); } 50% { box-shadow: 0 0 0 8px rgba(6,182,212,0); } }
.vln-step-label { display: inline; }

/* TWO-COL LAYOUT */
.vln-layout { display: grid; grid-template-columns: 1fr; min-height: calc(100vh - 50px); }
.vln-form-col { padding: 2rem 2.5rem 6rem; background: #fff; overflow-y: auto; width: 100%; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
.vln-stat { flex: 1; padding: 0.85rem; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); text-align: center; }

/* STEP CONTENT */
.vln-step { animation: fadeUp 0.35s ease both; }
.vln-step-header { margin-bottom: 1.75rem; }
.vln-step-header h1 { font-size: 1.65rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; color: #0b1b26; }
.vln-step-header p { margin-top: 0.4rem; font-size: 0.92rem; color: #64748b; line-height: 1.6; }
.vln-tip { display: flex; gap: 0.65rem; padding: 0.75rem 1rem; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; margin-bottom: 1rem; color: #92400e; font-size: 0.82rem; line-height: 1.5; }

/* FORM ERRORS */
.vln-form-errors { display: flex; gap: 0.65rem; padding: 0.75rem 1rem; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; margin: 0.75rem 1.5rem; color: #991b1b; font-size: 0.82rem; line-height: 1.5; }
.vln-form-errors ul { margin-top: 0.3rem; padding-left: 1.2rem; list-style: disc; }

/* FIELD GROUPS */
.vln-field-group { border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1rem; transition: border-color 0.2s; }
.vln-field-group:hover { border-color: #cbd5e1; }
.vln-fg-header { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 1rem; }
.vln-fg-icon { width: 34px; height: 34px; border-radius: 8px; background: #ecfeff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #0891b2; }
.vln-fg-title { font-size: 0.95rem; font-weight: 700; color: #0b1b26; }
.vln-fg-sub { font-size: 0.75rem; color: #64748b; }

/* FIELDS */
.vln-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.vln-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.vln-grid .full { grid-column: 1 / -1; }
.vln-field { display: flex; flex-direction: column; gap: 0.25rem; }
.vln-label { font-size: 0.78rem; font-weight: 600; color: #334155; }
.vln-label .req { color: #ef4444; }
.vln-input { width: 100%; height: 42px; padding: 0 0.8rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 0.88rem; color: #0b1b26; background: #fff; outline: none; transition: all 0.2s; }
.vln-input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); }
.vln-input::placeholder { color: #94a3b8; }
.vln-input:disabled { background: #f1f5f9; color: #94a3b8; }
.vln-input-suffix { position: relative; }
.vln-input-suffix .vln-input { padding-right: 3rem; }
.vln-suffix { position: absolute; right: 0.8rem; top: 50%; transform: translateY(-50%); font-size: 0.73rem; font-weight: 600; color: #94a3b8; pointer-events: none; }
.vln-textarea { width: 100%; min-height: 80px; padding: 0.6rem 0.8rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 0.88rem; color: #0b1b26; resize: vertical; outline: none; transition: all 0.2s; }
.vln-textarea:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); }
.vln-select-trigger { height: 42px !important; border: 1.5px solid #e2e8f0 !important; border-radius: 8px !important; padding: 0 0.8rem !important; }
.vln-error { display: flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; color: #ef4444; margin-top: 0.2rem; }

/* CATEGORIES */
.vln-category-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
.vln-cat-card { position: relative; border: 2px solid #e2e8f0; border-radius: 14px; overflow: hidden; cursor: pointer; transition: all 0.3s; background: #fff; text-align: center; }
.vln-cat-card:hover { border-color: #cbd5e1; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.07); }
.vln-cat-card.selected { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.12), 0 8px 20px rgba(6,182,212,0.08); }
.vln-cat-card.selected::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #0891b2, #22d3ee); }
.vln-cat-img-wrap { width: 100%; aspect-ratio: 16/10; overflow: hidden; background: #f8fafc; display: flex; align-items: center; justify-content: center; }
.vln-cat-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.vln-cat-card:hover .vln-cat-img-wrap img { transform: scale(1.05); }
.vln-cat-placeholder { color: #94a3b8; }
.vln-cat-name { padding: 0.6rem 0.5rem; font-size: 0.82rem; font-weight: 700; color: #0b1b26; }
.vln-cat-check { position: absolute; top: 8px; right: 8px; width: 22px; height: 22px; border-radius: 50%; background: #06b6d4; display: none; align-items: center; justify-content: center; color: #fff; box-shadow: 0 2px 8px rgba(6,182,212,0.4); z-index: 2; }
.vln-cat-card.selected .vln-cat-check { display: flex; }

/* CHIPS */
.vln-feature-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.vln-chip { display: flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 100px; font-size: 0.78rem; font-weight: 500; color: #334155; cursor: pointer; transition: all 0.2s; background: #fff; }
.vln-chip:hover { border-color: #06b6d4; color: #0891b2; background: #ecfeff; }
.vln-chip.active { background: #0891b2; border-color: #0891b2; color: #fff; transform: scale(1.02); }
.vln-btn-add-feature { display: flex; align-items: center; gap: 0.3rem; padding: 0 1rem; height: 42px; border: 1.5px solid #0891b2; border-radius: 8px; background: #ecfeff; font: inherit; font-size: 0.82rem; font-weight: 600; color: #0891b2; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.vln-btn-add-feature:hover { background: #0891b2; color: #fff; }
.vln-btn-add-feature:disabled { opacity: 0.4; cursor: not-allowed; }

/* PRICING */
.vln-pricing-card { border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: all 0.25s; }
.vln-pricing-card.active { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.06); }
.vln-pc-header { padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.2s; }
.vln-pc-header:hover { background: #f8fafc; }
.vln-pc-label { display: flex; align-items: center; gap: 0.6rem; }
.vln-pc-toggle { width: 36px; height: 20px; border-radius: 10px; background: #e2e8f0; position: relative; transition: background 0.25s; flex-shrink: 0; }
.vln-pc-toggle::after { content: ''; position: absolute; top: 2.5px; left: 2.5px; width: 15px; height: 15px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.15); transition: transform 0.25s; }
.vln-pricing-card.active .vln-pc-toggle { background: #06b6d4; }
.vln-pricing-card.active .vln-pc-toggle::after { transform: translateX(16px); }
.vln-pc-period { font-weight: 700; font-size: 0.88rem; color: #0b1b26; }
.vln-pc-badge { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: 100px; background: #f0fdf4; color: #22c55e; }
.vln-pc-body { padding: 0 1rem 1rem; }

/* OPERATING HOURS */
.vln-link-btn { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 600; color: #0891b2; background: none; border: none; cursor: pointer; transition: opacity 0.2s; }
.vln-link-btn:hover { opacity: 0.7; }
.vln-hour-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 10px; border: 1px solid #f1f5f9; background: #f8fafc; transition: all 0.2s; flex-wrap: wrap; }
.vln-hour-row.open { background: #fff; border-color: #e2e8f0; }
.vln-hour-left { display: flex; align-items: center; gap: 0.65rem; width: 140px; flex-shrink: 0; }
.vln-hour-right { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
.vln-hour-day { font-size: 0.85rem; font-weight: 600; color: #0b1b26; }
.vln-hour-day.muted { color: #94a3b8; }
.vln-toggle { position: relative; width: 38px; height: 20px; border-radius: 10px; background: #cbd5e1; border: none; cursor: pointer; transition: background 0.25s; flex-shrink: 0; }
.vln-toggle.on { background: #0891b2; }
.vln-toggle-dot { position: absolute; top: 2.5px; left: 2.5px; width: 15px; height: 15px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.15); transition: transform 0.25s; display: block; }
.vln-toggle.on .vln-toggle-dot { transform: translateX(18px); }
.vln-time-input { width: 100px; height: 36px; padding: 0 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 0.82rem; outline: none; transition: all 0.2s; }
.vln-time-input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); }

/* PAYMENT */
.vln-payment-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.vln-payment-pill { display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 0.82rem; font-weight: 500; color: #334155; cursor: pointer; transition: all 0.2s; background: #fff; }
.vln-payment-pill:hover { border-color: #cbd5e1; }
.vln-payment-pill.active { border-color: #06b6d4; background: #ecfeff; color: #0891b2; }

/* SUB CARDS */
.vln-sub-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem; }
.vln-check-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #334155; cursor: pointer; }
.vln-checkbox { border-radius: 4px; border: 1.5px solid #cbd5e1; color: #0891b2; }

/* PLANS */
.vln-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.vln-plan-card { border: 2px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; position: relative; background: #fff; transition: all 0.3s; }
.vln-plan-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.vln-plan-card.selected { border-color: #06b6d4; background: #ecfeff; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); }
.vln-plan-check { position: absolute; top: 10px; right: 10px; width: 24px; height: 24px; border-radius: 50%; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.25s; }
.vln-plan-card.selected .vln-plan-check { background: #06b6d4; border-color: #06b6d4; color: #fff; }
.vln-plan-badge { display: inline-block; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 0.2rem 0.55rem; border-radius: 100px; }
.vln-plan-badge.essential { background: #dbeafe; color: #2563eb; }
.vln-plan-badge.premium { background: #fef3c7; color: #d97706; }
.vln-plan-badge.premium_plus { background: #f0fdf4; color: #22c55e; }
.vln-plan-btn { width: 100%; padding: 0.55rem; border-radius: 8px; font: inherit; font-size: 0.82rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; background: #0e1b26; color: #fff; }
.vln-plan-btn.selected { background: #0891b2; }

/* ADDONS */
.vln-addon-row { display: grid; grid-template-columns: 2fr 1fr 1fr 80px 36px; gap: 0.6rem; align-items: end; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; animation: fadeUp 0.3s ease; }
.vln-addon-remove { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; margin-bottom: 3px; color: #64748b; }
.vln-addon-remove:hover { border-color: #ef4444; background: #fef2f2; color: #ef4444; }
.vln-addon-add { display: flex; align-items: center; justify-content: center; gap: 0.4rem; width: 100%; padding: 0.7rem; border: 2px dashed #e2e8f0; border-radius: 12px; background: none; font: inherit; font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s; margin-top: 0.6rem; }
.vln-addon-add:hover { border-color: #06b6d4; color: #0891b2; background: #ecfeff; }
.vln-qty-btn { width: 30px; height: 30px; border-radius: 6px; border: 1.5px solid #e2e8f0; background: #fff; font: inherit; font-size: 0.9rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.vln-qty-btn:hover { border-color: #0891b2; color: #0891b2; }
.vln-qty-display { padding: 0.2rem 0.6rem; background: #f1f5f9; border-radius: 6px; font-size: 0.85rem; font-weight: 600; min-width: 32px; text-align: center; }

/* UPLOAD */
.vln-upload-zone { border: 2px dashed #e2e8f0; border-radius: 14px; padding: 2rem 1.5rem; text-align: center; cursor: pointer; transition: all 0.25s; background: #f8fafc; }
.vln-upload-zone:hover { border-color: #06b6d4; background: #ecfeff; }
.vln-upload-icon { width: 48px; height: 48px; border-radius: 50%; background: #ecfeff; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; color: #0891b2; }
.vln-upload-title { font-size: 0.92rem; font-weight: 700; color: #0b1b26; }
.vln-upload-browse { color: #0891b2; text-decoration: underline; cursor: pointer; }
.vln-upload-hint { font-size: 0.78rem; color: #64748b; margin-top: 0.2rem; }
.vln-preview-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.6rem; }
.vln-preview { position: relative; aspect-ratio: 4/3; border-radius: 10px; overflow: hidden; border: 2px solid #e2e8f0; }
.vln-preview.primary { border-color: #06b6d4; }
.vln-preview img { width: 100%; height: 100%; object-fit: cover; }
.vln-preview-actions { position: absolute; top: 4px; right: 4px; display: flex; gap: 3px; opacity: 0; transition: opacity 0.2s; }
.vln-preview:hover .vln-preview-actions { opacity: 1; }
.vln-preview-btn { width: 24px; height: 24px; border-radius: 50%; background: rgba(0,0,0,0.5); border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.vln-preview-btn.active { background: #06b6d4; }
.vln-preview-btn.danger:hover { background: #ef4444; }
.vln-cover-badge { position: absolute; bottom: 4px; left: 4px; font-size: 0.6rem; font-weight: 700; background: #06b6d4; color: #fff; padding: 0.15rem 0.4rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; }

/* CURRENT LOCATION */
.vln-current-location { padding: 0.85rem; border-radius: 10px; background: linear-gradient(135deg, #ecfeff, #ede9fe); border: 1px solid #a5f3fc; }

/* ACTION BAR */
.vln-action-bar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 100; background: rgba(255,255,255,0.95); backdrop-filter: blur(16px); border-top: 1px solid #e2e8f0; }
.vln-action-inner { padding: 0.75rem 2rem; display: flex; align-items: center; justify-content: space-between; }
.vln-action-info { font-size: 0.78rem; color: #64748b; }
.vln-action-info strong { color: #0b1b26; }
.vln-action-buttons { display: flex; gap: 0.6rem; }
.vln-btn-back { height: 42px; padding: 0 1.25rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; font: inherit; font-size: 0.85rem; font-weight: 600; color: #334155; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem; }
.vln-btn-back:hover { border-color: #94a3b8; color: #0b1b26; }
.vln-btn-next { height: 42px; padding: 0 1.5rem; border: none; border-radius: 8px; background: #0e1b26; font: inherit; font-size: 0.85rem; font-weight: 700; color: #fff; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; gap: 0.4rem; }
.vln-btn-next:hover { background: #153b4f; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14,27,38,0.2); }
.vln-btn-next:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
.vln-btn-submit { height: 42px; padding: 0 1.5rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #0891b2, #0e7490); font: inherit; font-size: 0.85rem; font-weight: 700; color: #fff; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; gap: 0.4rem; }
.vln-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(8,145,178,0.3); }
.vln-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

/* LOADING */
.vln-loading-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(255,255,255,0.85); display: flex; align-items: center; justify-content: center; }
.vln-spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0891b2; border-radius: 50%; animation: spin 0.7s linear infinite; }
.vln-spinner-sm { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* DIALOG */
.vln-dialog-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; }
.vln-dialog { background: #fff; border-radius: 14px; padding: 2rem; max-width: 360px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.vln-dialog p { font-size: 0.92rem; color: #334155; }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .vln-layout { grid-template-columns: 1fr; }
    .vln-form-col { padding: 1.5rem 1rem 5.5rem; }
}
@media (max-width: 768px) {
    .vln-grid, .vln-grid-3 { grid-template-columns: 1fr; }
    .vln-category-grid { grid-template-columns: 1fr 1fr 1fr; }
    .vln-plan-grid { grid-template-columns: 1fr; }
    .vln-addon-row { grid-template-columns: 1fr; }
    .vln-preview-grid { grid-template-columns: 1fr 1fr; }
    .vln-action-info { display: none; }
    .vln-action-buttons { width: 100%; }
    .vln-btn-back, .vln-btn-next, .vln-btn-submit { flex: 1; justify-content: center; }
    .vln-step-label { display: none; }
    .vln-hour-left { width: auto; }
    .vln-hour-row { gap: 0.5rem; }
    .vln-hour-right { width: 100%; }
    .vln-time-input { flex: 1; min-width: 0; }
}
</style>
