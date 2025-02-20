<template>
    <MyProfileLayout>
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Edit Vehicle</h2>
        </div>
        <div class="py-12">
            <div class="mx-auto">
                <form @submit.prevent="updateVehicle">
                    <Tabs defaultValue="basic" class="w-full">
                        <TabsList class="grid w-full grid-cols-3">
                            <TabsTrigger value="basic">Basic Information</TabsTrigger>
                            <TabsTrigger value="specifications">Specifications</TabsTrigger>
                            <TabsTrigger value="pricing">Pricing & Features</TabsTrigger>
                        </TabsList>

                        <TabsContent value="basic">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="category_id">Vehicle Category:</InputLabel>
                                    <select v-model.number="form.category_id" id="category_id" required>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="brand">Brand:</InputLabel>
                                    <TextInput type="text" v-model="form.brand" id="brand" required />
                                </div>
                                <div>
                                    <InputLabel for="model">Model:</InputLabel>
                                    <TextInput type="text" v-model="form.model" id="model" required />
                                </div>
                                <div>
                                    <InputLabel for="color">Color:</InputLabel>
                                    <TextInput type="text" v-model="form.color" id="color" required />
                                </div>
                                <div>
                                    <InputLabel for="mileage">Mileage:</InputLabel>
                                    <TextInput type="number" v-model.number="form.mileage" id="mileage" required />
                                </div>
                                <div>
                                    <InputLabel for="transmission">Transmission:</InputLabel>
                                    <select v-model="form.transmission" id="transmission" required>
                                        <option value="manual">Manual</option>
                                        <option value="automatic">Automatic</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="fuel">Fuel:</InputLabel>
                                    <select v-model="form.fuel" id="fuel" required>
                                        <option value="petrol">Petrol</option>
                                        <option value="diesel">Diesel</option>
                                        <option value="electric">Electric</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="status">Status:</InputLabel>
                                    <select v-model="form.status" id="status" required>
                                        <option value="available">Available</option>
                                        <option value="rented">Rented</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="specifications">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="seating_capacity">Seating Capacity:</InputLabel>
                                    <select v-model.number="form.seating_capacity" id="seating_capacity" required>
                                        <option v-for="num in 8" :key="num" :value="num">{{ num }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="number_of_doors">Number of Doors:</InputLabel>
                                    <select v-model.number="form.number_of_doors" id="number_of_doors" required>
                                        <option value="2">2</option>
                                        <option value="4">4</option>
                                        <option value="6">6</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="luggage_capacity">Luggage Capacity:</InputLabel>
                                    <TextInput type="number" v-model.number="form.luggage_capacity" id="luggage_capacity" required />
                                </div>
                                <div>
                                    <InputLabel for="horsepower">Horsepower:</InputLabel>
                                    <TextInput type="number" v-model.number="form.horsepower" id="horsepower" required />
                                </div>
                                <div>
                                    <InputLabel for="co2">CO2 Emissions:</InputLabel>
                                    <TextInput type="text" v-model="form.co2" id="co2" required />
                                </div>
                                <div>
                                    <InputLabel for="registration_number">Registration Number:</InputLabel>
                                    <TextInput type="text" v-model="form.registration_number" id="registration_number" required />
                                </div>
                                <div>
                                    <InputLabel for="registration_country">Registration Country:</InputLabel>
                                    <TextInput type="text" v-model="form.registration_country" id="registration_country" required />
                                </div>
                                <div>
                                    <InputLabel for="registration_date">Registration Date:</InputLabel>
                                    <TextInput type="date" v-model="form.registration_date" id="registration_date" required />
                                </div>
                                <div>
                                    <InputLabel for="gross_vehicle_mass">Gross Vehicle Mass:</InputLabel>
                                    <TextInput type="number" v-model.number="form.gross_vehicle_mass" id="gross_vehicle_mass" required />
                                </div>
                                <div>
                                    <InputLabel for="vehicle_height">Vehicle Height:</InputLabel>
                                    <TextInput type="number" v-model.number="form.vehicle_height" id="vehicle_height" required step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="phone_number">Phone Number:</InputLabel>
                                    <TextInput type="text" v-model="form.phone_number" id="phone_number" required />
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="pricing">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="security_deposit">Security Deposit:</InputLabel>
                                    <TextInput type="number" v-model.number="form.security_deposit" id="security_deposit" required min="0" step="0.01" />
                                </div>
                                <div>
                                    <InputLabel for="dealer_cost">Dealer Cost:</InputLabel>
                                    <TextInput type="number" v-model.number="form.dealer_cost" id="dealer_cost" required step="0.01" />
                                </div>
                                <div v-if="form.price_per_day !== ''">
                                    <InputLabel for="price_per_day">Price Per Day:</InputLabel>
                                    <TextInput type="number" v-model.number="form.price_per_day" id="price_per_day" required min="0" step="0.01" />
                                </div>
                                <div v-if="form.price_per_week !== ''">
                                    <InputLabel for="price_per_week">Price Per Week:</InputLabel>
                                    <TextInput type="number" v-model.number="form.price_per_week" id="price_per_week" min="0" step="0.01" />
                                </div>
                                <div v-if="form.price_per_month !== ''">
                                    <InputLabel for="price_per_month">Price Per Month:</InputLabel>
                                    <TextInput type="number" v-model.number="form.price_per_month" id="price_per_month" min="0" step="0.01" />
                                </div>
                                <div v-if="form.weekly_discount !== ''">
                                    <InputLabel for="weekly_discount">Weekly Discount:</InputLabel>
                                    <TextInput type="number" v-model.number="form.weekly_discount" id="weekly_discount" min="0" max="1000.00" step="0.01" />
                                </div>
                                <div v-if="form.monthly_discount !== ''">
                                    <InputLabel for="monthly_discount">Monthly Discount:</InputLabel>
                                    <TextInput type="number" v-model.number="form.monthly_discount" id="monthly_discount" min="0" max="10000.00" step="0.01" />
                                </div>
                                <div class="col-span-2">
                                    <InputLabel for="payment_method">Payment Methods:</InputLabel>
                                    <div class="flex  items-center gap-10">
                                        <label class="flex gap-2 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="credit_card" />
                                            Credit Card
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cheque" />
                                            Cheque
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="bank_wire" />
                                            Bank Wire
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="cryptocurrency" />
                                            Cryptocurrency
                                        </label>
                                        <label class="flex gap-1 items-center text-nowrap">
                                            <input type="checkbox" v-model="form.payment_method" value="other" />
                                            Other
                                        </label>
                                    </div>
                                </div>
                                <!-- <div>
                                    <InputLabel for="featured">Featured:</InputLabel>
                                    <input type="checkbox" v-model="form.featured" id="featured" />
                                </div> -->
                                <div class="col-span-2">
                                    <InputLabel for="features">Features:</InputLabel>
                                    <div class="flex gap-10">
                                        <label v-for="feature in features" :key="feature.id" class="flex items-center text-nowrap gap-2">
                                            <input type="checkbox" v-model="form.features" :value="feature.name" />
                                            {{ feature.name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>

                    <div class="flex justify-between mt-8">
                        <PrimaryButton type="submit">Update Vehicle</PrimaryButton>
                        <Link href="/current-vendor-vehicles" class="px-4 flex justify-center items-center bg-[#EA3C3C] text-white rounded hover:bg-[#ea3c3ca2]">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </MyProfileLayout>
</template>


<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import { usePage } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs'
const toast = useToast()
const { props } = usePage()

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
    phone_number: ''
})

const categories = ref([])
const features = ref([])

const fetchCategoriesAndFeatures = async () => {
    try {
        const response = await axios.get('/api/vehicle-categories')
        categories.value = response.data
    } catch (error) {
        console.error('Error fetching vehicle categories:', error)
    }

    features.value = props.features
}

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
        if (props.vehicle.specifications) {
            form.registration_number = props.vehicle.specifications.registration_number
            form.registration_country = props.vehicle.specifications.registration_country
            form.registration_date = props.vehicle.specifications.registration_date
            form.gross_vehicle_mass = props.vehicle.specifications.gross_vehicle_mass
            form.vehicle_height = parseFloat(props.vehicle.specifications.vehicle_height) || 0.00
            form.dealer_cost = parseFloat(props.vehicle.specifications.dealer_cost) || 0.00
            form.phone_number = props.vehicle.specifications.phone_number
        }
    }
})

const updateVehicle = () => {
    form.put(route('current-vendor-vehicles.update', props.vehicle.id), {
        onSuccess: () => {
            toast.success('Vehicle updated successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true
            })
            form.reset()
        },
        onError: (errors) => {
            toast.error('Something went wrong. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true
            })
            console.error(errors)
        }
    })
}
</script>

<style>
select {
    width: 100%;
}

label {
    margin-bottom: 0.5rem;
}
input{
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--custom-gray-color);
    border: 1px solid var(--custom-light-gray);
    border-radius: 8px;
}
select{
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    padding: 1rem;
    border: 1px solid #2b2b2b4a;
    outline: none;
    border-radius: 8px;
}
</style>