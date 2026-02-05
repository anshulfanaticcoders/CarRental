<script setup>
import SiderBar from '@/Components/SiderBar.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import {
  Sidebar,
  SidebarInset,
  SidebarProvider,
  SidebarSeparator,
  SidebarTrigger,
} from '@/Components/ui/sidebar';
import { Button } from '@/Components/ui/button';

const page = usePage();

const titleMap = {
  'Profile/Edit': 'My Profile',
  'Profile/Bookings/AllBookings': 'Bookings',
  'Profile/IssuedPayments': 'Issued Payments',
  'Profile/Favourites': 'Favorites',
  'Profile/Inbox': 'Inbox',
  'Profile/Review': 'Reviews',
  'Profile/Review/Index': 'Reviews',
  'Profile/TravelDocuments': 'Travel Documents',
  'Profile/Documents/Index': 'Documents',
  'Profile/Documents/Create': 'Add Document',
  'Profile/Documents/Edit': 'Edit Document',
  'Vendor/Overview/Index': 'Vendor Overview',
  'Vendor/Vehicles/Index': 'Vehicles',
  'Vendor/Vehicles/Edit': 'Edit Vehicle',
  'Vendor/VendorVehicles': 'Vehicles',
  'Vendor/Bookings/Index': 'Bookings',
  'Vendor/Payments/Index': 'Payments',
  'Vendor/Plan/Index': 'Plans',
  'Vendor/Plan/Edit': 'Edit Plan',
  'Vendor/Documents/Index': 'Documents',
  'Vendor/Documents/Edit': 'Edit Document',
  'Vendor/Status/Index': 'Verification Status',
  'Vendor/BlockingDates/Index': 'Date Blocking',
  'Vendor/DamageProtection/Index': 'Damage Protection',
  'Vendor/Review/Index': 'Reviews',
  'Vendor/VendorApproved': 'Vendor Status',
  'Vendor/VendorPending': 'Vendor Status',
  'Vendor/VendorRejected': 'Vendor Status',
  Dashboard: 'Dashboard',
  'Messages/Show': 'Messages',
};

const componentName = computed(() => page.component || '');

const formatTitle = (name) => {
  if (!name) return 'Profile';
  const parts = name.split('/');
  const last = parts[parts.length - 1] || '';
  const clean = last.replace(/([a-z])([A-Z])/g, '$1 $2');
  return clean === 'Index' ? (parts[parts.length - 2] || 'Profile') : clean;
};

const pageTitle = computed(() => titleMap[componentName.value] || formatTitle(componentName.value));
const pageSection = computed(() => (componentName.value.startsWith('Vendor') ? 'Vendor' : 'Profile'));
</script>

<template>
  <Head>
    <meta name="robots" content="noindex, nofollow">
    <title>Profile</title>
  </Head>
  <SidebarProvider class="profile-shell !bg-slate-50 text-slate-900">
    <Sidebar variant="inset" collapsible="icon" class="bg-white border-r border-slate-200/70">
      <SiderBar />
    </Sidebar>
    <SidebarInset class="bg-slate-50">
      <div class="profile-topbar">
        <div class="flex items-center gap-3 min-w-0">
          <SidebarTrigger class="!text-slate-700" />
          <SidebarSeparator class="h-5" />
          <Breadcrumb class="hidden sm:block max-w-full">
            <BreadcrumbList class="truncate">
              <BreadcrumbItem>
                <BreadcrumbLink :href="route('profile.edit', { locale: page.props.locale })">Account</BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbLink href="#">{{ pageSection }}</BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbPage>{{ pageTitle }}</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
          <div class="sm:hidden text-sm font-semibold text-slate-700">{{ pageTitle }}</div>
        </div>
        <Button as-child class="bg-customPrimaryColor text-white hover:bg-[#153b4fef] shrink-0 whitespace-nowrap h-9 px-4">
          <Link :href="route('welcome', { locale: page.props.locale })">Home</Link>
        </Button>
      </div>
      <main class="profile-content">
        <div class="profile-page">
          <slot />
        </div>
      </main>
    </SidebarInset>
  </SidebarProvider>
</template>

<style>
.profile-shell {
  min-height: 100svh;
  --profile-gutter: 1.5rem;
  overflow-x: hidden;
}

.profile-topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 0.9rem var(--profile-gutter);
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  background: rgba(248, 250, 252, 0.92);
  backdrop-filter: blur(10px);
}

.profile-content {
  padding: 1.5rem var(--profile-gutter) 2.5rem;
  width: 100%;
  overflow-x: hidden;
}

.profile-content .container {
  max-width: 100% !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.profile-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.profile-content table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
}

.profile-content thead {
  background: #f8fafc;
}

.profile-content th {
  padding: 12px 16px;
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 600;
}

.profile-content td {
  padding: 12px 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 0.9rem;
  color: #0f172a;
}

.profile-content tr:hover td {
  background: #f8fafc;
}

.profile-shell [data-sidebar='sidebar'] {
  background: #ffffff;
  color: #0f172a;
}

.profile-shell [data-sidebar='sidebar'][data-mobile='true'] {
  background: #ffffff;
  box-shadow: 0 28px 60px rgba(15, 23, 42, 0.25);
}

.bg-sidebar {
  background-color: #ffffff !important;
}

.text-sidebar-foreground {
  color: #0f172a !important;
}

.profile-shell [data-sidebar='menu-button'] {
  color: #334155;
}

.profile-shell [data-sidebar='menu-button'] span {
  transition: opacity 200ms ease, transform 200ms ease;
}

.profile-shell [data-state='collapsed'] [data-sidebar='menu-button'] span {
  opacity: 0;
  transform: translateX(-6px);
}



.profile-content h1,
.profile-content h2,
.profile-content h3,
.profile-content h4 {
  color: #0f172a;
  letter-spacing: -0.01em;
}

.profile-content h4 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.profile-content input:not([type='checkbox']):not([type='radio']),
.profile-content select,
.profile-content textarea {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.55rem 0.8rem;
  font-size: 0.95rem;
  background: #ffffff;
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

.profile-content input:not([type='checkbox']):not([type='radio']):focus,
.profile-content select:focus,
.profile-content textarea:focus {
  outline: none;
  border-color: #153b4f;
  box-shadow: 0 0 0 3px rgba(21, 59, 79, 0.15);
}

@media (max-width: 640px) {
  .profile-topbar {
    padding: 0.75rem 1rem;
  }

  .profile-content {
    padding: 1rem 1rem 2rem;
  }

  .profile-shell {
    --profile-gutter: 1rem;
  }
}

@media (min-width: 768px) and (max-width: 1024px) {
  .profile-shell {
    --profile-gutter: 1.25rem;
  }
}
</style>
