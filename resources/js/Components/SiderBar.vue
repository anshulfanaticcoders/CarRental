<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

// Reactive variables
const activeSection = ref('profile'); 
const activeProfileLink = ref('profile');
const activeBookingLink = ref('completed');

// Methods
const toggleSection = (section) => {
  activeSection.value = activeSection.value === section ? null : section;
};

const setActiveProfileLink = (link) => {
  activeProfileLink.value = link;
};

const setActiveBookingLink = (link) => {
  activeBookingLink.value = link;
};

const greetingMessage = ref(getGreeting());

function getGreeting() {
  const hours = new Date().getHours();
  if (hours < 12) return 'Good Morning';
  if (hours < 18) return 'Good Afternoon';
  return 'Good Evening';
}
</script>

<template>
  <div class="h-full bg-gray-50 p-4 border-r border-gray-200 w-full">
    <!-- User Info -->
    <div class="flex items-center space-x-3">
      <img
        src="https://via.placeholder.com/50"
        alt="User"
        class="w-12 h-12 rounded-full object-cover"
      />
      <div>
        <p class="text-sm text-gray-500">Hello, {{ greetingMessage }}</p>
        <p class="text-lg font-semibold text-gray-800">{{ $page.props.auth.user.first_name }}</p>
      </div>
    </div>

    <!-- Navigation Links -->
    <nav class="mt-6 flex flex-col gap-5">
      <!-- Profile Section -->
      <div>
        <button
          @click="toggleSection('profile')"
          :class="{'bg-[var(--custom-primary)] text-white': activeSection === 'profile'}"
          class="flex items-center justify-between w-full text-left px-4 py-2 text-gray-800 rounded-md"
        >
          <span class="flex items-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
            :stroke="activeSection === 'profile' ? 'white' : 'currentColor'"
            :fill="activeSection === 'profile' ? 'white' : 'none'">
  <g clip-path="url(#clip0_1699_10049)">
    <path d="M3 12C3 13.1819 3.23279 14.3522 3.68508 15.4442C4.13738 16.5361 4.80031 17.5282 5.63604 18.364C6.47177 19.1997 7.46392 19.8626 8.55585 20.3149C9.64778 20.7672 10.8181 21 12 21C13.1819 21 14.3522 20.7672 15.4442 20.3149C16.5361 19.8626 17.5282 19.1997 18.364 18.364C19.1997 17.5282 19.8626 16.5361 20.3149 15.4442C20.7672 14.3522 21 13.1819 21 12C21 10.8181 20.7672 9.64778 20.3149 8.55585C19.8626 7.46392 19.1997 6.47177 18.364 5.63604C17.5282 4.80031 16.5361 4.13738 15.4442 3.68508C14.3522 3.23279 13.1819 3 12 3C10.8181 3 9.64778 3.23279 8.55585 3.68508C7.46392 4.13738 6.47177 4.80031 5.63604 5.63604C4.80031 6.47177 4.13738 7.46392 3.68508 8.55585C3.23279 9.64778 3 10.8181 3 12Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M9 10C9 10.7956 9.31607 11.5587 9.87868 12.1213C10.4413 12.6839 11.2044 13 12 13C12.7956 13 13.5587 12.6839 14.1213 12.1213C14.6839 11.5587 15 10.7956 15 10C15 9.20435 14.6839 8.44129 14.1213 7.87868C13.5587 7.31607 12.7956 7 12 7C11.2044 7 10.4413 7.31607 9.87868 7.87868C9.31607 8.44129 9 9.20435 9 10Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M6.16797 18.849C6.41548 18.0252 6.92194 17.3032 7.61222 16.79C8.30249 16.2768 9.13982 15.9997 9.99997 16H14C14.8612 15.9997 15.6996 16.2774 16.3904 16.7918C17.0811 17.3062 17.5874 18.0298 17.834 18.855" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
  <defs>
    <clipPath id="clip0_1699_10049">
      <rect width="24" height="24" fill="white"/>
    </clipPath>
  </defs>
</svg>
            <span>My Profile</span>
          </span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M5.293 9.293a1 1 0 011.414 0L10 12.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
        <div v-if="activeSection === 'profile'" class="mt-2 space-y-2">
          <Link
            href="profile"
            @click="setActiveProfileLink('profile')"
            :class="{'bg-[var(--custom-light-primary)]': activeProfileLink === 'profile'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Profile
          </Link>
          <Link
            href="travel-documents"
            @click="setActiveProfileLink('travelDocuments')"
            :class="{'bg-[var(--custom-light-primary)]': activeProfileLink === 'travelDocuments'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Travel Documents
          </Link>
          <Link
            href="issued-payments"
            @click="setActiveProfileLink('issuedPayments')"
            :class="{'bg-[var(--custom-light-primary)]': activeProfileLink === 'issuedPayments'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Issued Payments
          </Link>
        </div>
      </div>

      <!-- My Bookings Section -->
      <div class="mt-4">
        <button
          @click="toggleSection('bookings')"
          :class="{'bg-[var(--custom-primary)] text-white': activeSection === 'bookings'}"
          class="flex items-center justify-between w-full text-left px-4 py-2 text-gray-800 rounded-md"
        >
          <span class="flex items-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            :stroke="activeSection === 'profile' ? 'white' : 'currentColor'"
            :fill="activeSection === 'profile' ? 'white' : 'none'">
  <g clip-path="url(#clip0_1869_5413)">
    <path d="M5 17C5 17.5304 5.21071 18.0391 5.58579 18.4142C5.96086 18.7893 6.46957 19 7 19C7.53043 19 8.03914 18.7893 8.41421 18.4142C8.78929 18.0391 9 17.5304 9 17C9 16.4696 8.78929 15.9609 8.41421 15.5858C8.03914 15.2107 7.53043 15 7 15C6.46957 15 5.96086 15.2107 5.58579 15.5858C5.21071 15.9609 5 16.4696 5 17Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M15 17C15 17.5304 15.2107 18.0391 15.5858 18.4142C15.9609 18.7893 16.4696 19 17 19C17.5304 19 18.0391 18.7893 18.4142 18.4142C18.7893 18.0391 19 17.5304 19 17C19 16.4696 18.7893 15.9609 18.4142 15.5858C18.0391 15.2107 17.5304 15 17 15C16.4696 15 15.9609 15.2107 15.5858 15.5858C15.2107 15.9609 15 16.4696 15 17Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M5 17H3V11M3 11L5 6H14L18 11M3 11H18M18 11H19C19.5304 11 20.0391 11.2107 20.4142 11.5858C20.7893 11.9609 21 12.4696 21 13V17H19M15 17H9M12 11V6" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
  <defs>
    <clipPath id="clip0_1869_5413">
      <rect width="24" height="24" fill="white"/>
    </clipPath>
  </defs>
           </svg>
            <span>My Bookings</span>
          </span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M5.293 9.293a1 1 0 011.414 0L10 12.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
        <div v-if="activeSection === 'bookings'" class="mt-2 space-y-2">
          <Link
            href="completed-bookings"
            @click="setActiveBookingLink('completed')"
            :class="{'bg-[var(--custom-light-primary)]': activeBookingLink === 'completed'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Completed
          </Link>
          <Link
            href="confirmed-bookings"
            @click="setActiveBookingLink('confirmed')"
            :class="{'bg-[var(--custom-light-primary)]': activeBookingLink === 'confirmed'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Confirmed
          </Link>
          <Link
            href="pending-bookings"
            @click="setActiveBookingLink('pending')"
            :class="{'bg-[var(--custom-light-primary)]': activeBookingLink === 'pending'}"
            class="block px-6 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Pending
          </Link>
        </div>
      </div>

      <!-- Other Links -->
      <Link href="inbox" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-md"
          @click="toggleSection('inbox')"
          :class="{'bg-[var(--custom-primary)] text-white': activeSection === 'inbox'}" >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <g clip-path="url(#clip0_1869_5423)">
    <path d="M3 7C3 6.46957 3.21071 5.96086 3.58579 5.58579C3.96086 5.21071 4.46957 5 5 5H19C19.5304 5 20.0391 5.21071 20.4142 5.58579C20.7893 5.96086 21 6.46957 21 7V17C21 17.5304 20.7893 18.0391 20.4142 18.4142C20.0391 18.7893 19.5304 19 19 19H5C4.46957 19 3.96086 18.7893 3.58579 18.4142C3.21071 18.0391 3 17.5304 3 17V7Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3 7L12 13L21 7" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
  <defs>
    <clipPath id="clip0_1869_5423">
      <rect width="24" height="24" fill="white"/>
    </clipPath>
  </defs>
        </svg>
        <span class="ml-3">Inbox</span>
      </Link>
      <Link href="favourites" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-md"
          @click="toggleSection('favourites')"
          :class="{'bg-[var(--custom-primary)] text-white': activeSection === 'favourites'}" >
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="20" viewBox="0 0 22 20" fill="none">
  <path d="M11 18.25C11 18.25 1.625 13 1.625 6.62501C1.62519 5.49826 2.01561 4.40635 2.72989 3.53493C3.44416 2.66351 4.4382 2.06636 5.54299 1.84501C6.64778 1.62367 7.79514 1.79179 8.78999 2.32079C9.78484 2.84979 10.5658 3.70702 11 4.74673L11 4.74673C11.4342 3.70702 12.2152 2.84979 13.21 2.32079C14.2049 1.79179 15.3522 1.62367 16.457 1.84501C17.5618 2.06636 18.5558 2.66351 19.2701 3.53493C19.9844 4.40635 20.3748 5.49826 20.375 6.62501C20.375 13 11 18.25 11 18.25Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="ml-3">Favorites</span>
      </Link>
      <Link href="review" class="flex items-center px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-md"
          @click="toggleSection('reviews')"
          :class="{'bg-[var(--custom-primary)] text-white': activeSection === 'reviews'}" >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <g clip-path="url(#clip0_1869_5434)">
    <path d="M12 17V21" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M10 20L14 18" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M10 18L14 20" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M5 17V21" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3 20L7 18" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3 18L7 20" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M19 17V21" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M17 20L21 18" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M17 18L21 20" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M9 6C9 6.79565 9.31607 7.55871 9.87868 8.12132C10.4413 8.68393 11.2044 9 12 9C12.7956 9 13.5587 8.68393 14.1213 8.12132C14.6839 7.55871 15 6.79565 15 6C15 5.20435 14.6839 4.44129 14.1213 3.87868C13.5587 3.31607 12.7956 3 12 3C11.2044 3 10.4413 3.31607 9.87868 3.87868C9.31607 4.44129 9 5.20435 9 6Z" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M7 14C7 13.4696 7.21071 12.9609 7.58579 12.5858C7.96086 12.2107 8.46957 12 9 12H15C15.5304 12 16.0391 12.2107 16.4142 12.5858C16.7893 12.9609 17 13.4696 17 14" stroke="#153B4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </g>
  <defs>
    <clipPath id="clip0_1869_5434">
      <rect width="24" height="24" fill="white"/>
    </clipPath>
  </defs>
        </svg>
        <span class="ml-3">My Reviews</span>
      </Link>
    </nav>
  </div>
</template>

<style scoped></style>
