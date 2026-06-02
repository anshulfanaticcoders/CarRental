<script setup>
import SiderBar from '@/Components/SiderBar.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import axios from 'axios';
import Dropdown from '@/Components/Dropdown.vue';
import flagEn from '../../assets/flag-en.svg';
import flagFr from '../../assets/flag-fr.svg';
import flagNl from '../../assets/flag-nl.svg';
import flagEs from '../../assets/flag-es.svg';
import flagAr from '../../assets/flag-ar.svg';
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

// Language switcher (mirrors AuthenticatedHeaderLayout)
const currentLocale = computed(() => page.props.locale || 'en');
const availableLocales = {
  en: { name: 'En', flag: flagEn },
  fr: { name: 'Fr', flag: flagFr },
  nl: { name: 'Nl', flag: flagNl },
  es: { name: 'Es', flag: flagEs },
  ar: { name: 'Ar', flag: flagAr },
};

const changeLanguage = async (newLocale) => {
  if (!availableLocales[newLocale] || currentLocale.value === newLocale) return;
  const currentUrl = new URL(window.location.href);
  const pathParts = currentUrl.pathname.split('/');
  pathParts[1] = newLocale;
  const targetUrl = pathParts.join('/') + currentUrl.search;
  try {
    await axios.post(route('language.change'), { locale: newLocale });
  } catch (error) {
    console.error('Error persisting language preference:', error);
  }
  router.visit(targetUrl);
};
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
    <SidebarInset class="profile-inset bg-slate-50 min-w-0">
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
        <div class="flex items-center gap-2 shrink-0">
          <Dropdown align="right" width="48">
            <template #trigger>
              <button type="button" class="lang-trigger" aria-label="Change language">
                <img :src="availableLocales[currentLocale].flag" alt="" aria-hidden="true" class="w-5 h-5 rounded-full">
                <span>{{ availableLocales[currentLocale].name }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </template>
            <template #content>
              <div v-for="(language, code) in availableLocales" :key="code" @click="changeLanguage(code)"
                class="flex items-center w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-[#153B4F] hover:bg-gray-100 transition duration-150 ease-in-out cursor-pointer"
                :class="{ 'bg-gray-500': currentLocale === code }">
                <img :src="language.flag" :alt="language.name + ' Flag'" class="w-5 h-5 mr-2 rounded-full">
                {{ language.name }}
              </div>
            </template>
          </Dropdown>

          <Button as-child class="home-cta text-white whitespace-nowrap h-9 px-4">
            <Link :href="route('welcome', { locale: page.props.locale })">Home</Link>
          </Button>
        </div>
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
  height: 100svh;
  --profile-gutter: 1.5rem;
  overflow: hidden;
}

/* lock the shell: sidebar + topbar stay fixed, only content scrolls */
.profile-inset {
  height: 100svh;
  min-height: 0;
  overflow: hidden;
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
  border-bottom: 1px solid rgba(21, 59, 79, 0.1);
  background: rgba(248, 250, 252, 0.85);
  backdrop-filter: blur(12px) saturate(1.2);
}

.profile-content {
  padding: 1.5rem var(--profile-gutter) 2.5rem;
  width: 100%;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: auto;
}

.profile-content::-webkit-scrollbar {
  width: 9px;
}

.profile-content::-webkit-scrollbar-thumb {
  background: #cbd8e1;
  border-radius: 999px;
  border: 2px solid #f8fafc;
}

.profile-content::-webkit-scrollbar-thumb:hover {
  background: #aebfcb;
}

.lang-trigger {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 38px;
  padding: 0 12px;
  border-radius: 11px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 0.84rem;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: background 0.3s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.3s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.lang-trigger:hover {
  background: #f0f8fc;
  border-color: #153b4f;
  color: #153b4f;
}

.home-cta {
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  border-radius: 12px;
  box-shadow: 0 6px 16px rgba(21, 59, 79, 0.24);
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.home-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(21, 59, 79, 0.3);
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
  overflow-x: auto;
}

.profile-content thead {
  background: #f8fafc;
}

.profile-content th {
  padding: 12px 18px;
  text-align: left;
  font-size: 0.7rem;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 700;
  white-space: nowrap;
  vertical-align: middle;
}

.profile-content td {
  padding: 13px 18px;
  border-top: 1px solid #eef2f6;
  font-size: 0.86rem;
  color: #0f172a;
  vertical-align: middle;
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

.profile-shell .bg-sidebar {
  background-color: #ffffff !important;
}

.profile-shell .text-sidebar-foreground {
  color: #0f172a !important;
}

/* active leaf/sub-link sits on a dark teal fill — force white text + icon
   (beats the .text-sidebar-foreground !important above via higher specificity) */
.profile-shell [data-sidebar='menu-sub-button'][data-active='true'],
.profile-shell [data-sidebar='menu-sub-button'][data-active='true'] span,
.profile-shell [data-sidebar='menu-button'][data-active='true']:not(.profile-group-button),
.profile-shell [data-sidebar='menu-button'][data-active='true']:not(.profile-group-button) span {
  color: #ffffff !important;
}

.profile-shell [data-sidebar='menu-button'][data-active='true']:not(.profile-group-button) .nav-icon {
  color: #ffffff !important;
}

/* active leaf (quick link): bold teal fill. Global because tooltip-wrapped
   buttons don't receive the component's scoped data-v attribute. */
.profile-shell [data-sidebar='menu-button'][data-active='true']:not(.profile-group-button) {
  background: linear-gradient(135deg, #153b4f, #1c4d66) !important;
  box-shadow: 0 8px 18px rgba(21, 59, 79, 0.3) !important;
}

/* active sub-link: bold teal fill */
.profile-shell [data-sidebar='menu-sub-button'][data-active='true'] {
  background: linear-gradient(135deg, #153b4f, #1c4d66) !important;
  box-shadow: 0 6px 14px rgba(21, 59, 79, 0.26) !important;
}

/* parent dropdown that is open / holds the active route: clear teal active state */
.profile-shell [data-sidebar='menu-button'][data-active='true'].profile-group-button,
.profile-shell [data-sidebar='menu-button'][data-active='true'].profile-group-button span {
  color: #153b4f !important;
}

.profile-shell [data-sidebar='menu-button'][data-active='true'].profile-group-button {
  background: rgba(21, 59, 79, 0.1) !important;
  box-shadow: inset 3px 0 0 #153b4f !important;
}

.profile-shell [data-sidebar='menu-button'][data-active='true'].profile-group-button .nav-icon,
.profile-shell [data-sidebar='menu-button'][data-active='true'].profile-group-button .nav-chevron {
  color: #153b4f !important;
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
  /* padding: 0.55rem 0.8rem; */
  font-size: 0.95rem;
  background: #f8fafc;
  transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
}

.profile-content input:not([type='checkbox']):not([type='radio'])::placeholder,
.profile-content textarea::placeholder {
  color: #94a3b8;
}

.profile-content input:not([type='checkbox']):not([type='radio']):focus,
.profile-content select:focus,
.profile-content textarea:focus {
  outline: none;
  border-color: #153b4f;
  background: #ffffff;
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

/* =====================================================================
   VROOEM DASHBOARD DESIGN SYSTEM  (vr- prefixed, shared across pages)
   Mirrors public/design-previews/dashboard-redesign-all-in-one-v1.html
   ===================================================================== */
.profile-shell {
  --vr-brand: #153b4f;
  --vr-brand-2: #1c4d66;
  --vr-cyan: #22d3ee;
  --vr-cyan-dark: #0891b2;
  --vr-ink: #0f172a;
  --vr-strong: #334155;
  --vr-muted: #64748b;
  --vr-faint: #94a3b8;
  --vr-line: #e2e8f0;
  --vr-line-soft: #eef2f6;
  --vr-soft: #f8fafc;
  --vr-danger: #e11d48;
  --vr-tint: rgba(21, 59, 79, 0.10);
  --vr-tint-soft: rgba(21, 59, 79, 0.06);
  --vr-ease: cubic-bezier(0.22, 1, 0.36, 1);
  --vr-sh-sm: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
  --vr-sh-md: 0 4px 12px rgba(21, 59, 79, 0.08), 0 2px 4px rgba(21, 59, 79, 0.04);
}

/* page header */
.vr-phead { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
.vr-phead h2 { line-height: 1.2; }
.vr-phead .vr-eyebrow { margin-bottom: 6px; }
.vr-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: var(--vr-cyan-dark); margin-bottom: 8px; }
.vr-eyebrow svg { width: 13px; height: 13px; }
.vr-phead h2 { font-size: 1.6rem; font-weight: 800; color: var(--vr-ink); }
.vr-phead .vr-sub { color: var(--vr-muted); font-size: 0.9rem; margin-top: 4px; max-width: 640px; }
.vr-phead-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* buttons */
.vr-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 11px; border: 1px solid transparent; cursor: pointer; font-weight: 600; font-size: 0.84rem; line-height: 1; transition: transform 0.3s var(--vr-ease), box-shadow 0.3s var(--vr-ease), background 0.3s var(--vr-ease), border-color 0.3s var(--vr-ease); white-space: nowrap; }
.vr-btn svg { width: 16px; height: 16px; }
.vr-btn-pri { background: linear-gradient(135deg, var(--vr-brand), var(--vr-brand-2)); color: #fff; box-shadow: 0 6px 16px rgba(21, 59, 79, 0.24); }
.vr-btn-pri:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(21, 59, 79, 0.3); }
.vr-btn-ghost { background: #fff; color: var(--vr-strong); border-color: var(--vr-line); }
.vr-btn-ghost:hover { border-color: var(--vr-brand); background: #f0f8fc; color: var(--vr-brand); transform: translateY(-1px); }
.vr-btn-danger { background: #fff; color: var(--vr-danger); border-color: #fecdd3; }
.vr-btn-danger:hover { background: #fff1f2; transform: translateY(-1px); }
.vr-btn-sm { padding: 7px 12px; font-size: 0.8rem; }

/* toolbar / search / filters */
.vr-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.vr-search { flex: 1; min-width: 220px; display: flex; align-items: center; gap: 9px; background: #fff; border: 1px solid var(--vr-line); border-radius: 12px; padding: 10px 14px; transition: border-color 0.25s var(--vr-ease), box-shadow 0.25s var(--vr-ease); }
.vr-search:focus-within { border-color: var(--vr-brand); box-shadow: 0 0 0 3px rgba(21, 59, 79, 0.1); }
.vr-search svg { width: 17px; height: 17px; color: var(--vr-faint); }
.vr-search input { border: 0 !important; outline: 0; flex: 1; font-size: 0.88rem; color: var(--vr-ink); background: transparent; box-shadow: none !important; padding: 0 !important; }

/* stat cards */
.vr-stat-grid { display: grid; gap: 14px; margin-bottom: 20px; }
.vr-stat-grid.c2 { grid-template-columns: repeat(2, 1fr); }
.vr-stat-grid.c3 { grid-template-columns: repeat(3, 1fr); }
.vr-stat-grid.c4 { grid-template-columns: repeat(4, 1fr); }
.vr-stat { background: #fff; border: 1px solid var(--vr-line); border-radius: 16px; padding: 16px; box-shadow: var(--vr-sh-sm); transition: transform 0.35s var(--vr-ease), box-shadow 0.35s var(--vr-ease); }
.vr-stat:hover { transform: translateY(-3px); box-shadow: var(--vr-sh-md); }
.vr-stat .vr-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; margin-bottom: 12px; }
.vr-stat .vr-ic svg { width: 19px; height: 19px; }
.vr-stat .vr-v { font-weight: 800; font-size: 1.5rem; color: var(--vr-ink); }
.vr-stat .vr-l { font-size: 0.78rem; color: var(--vr-muted); margin-top: 2px; }
.vr-ic-teal { background: var(--vr-tint); color: var(--vr-brand); }
.vr-ic-cyan { background: #ecfeff; color: var(--vr-cyan-dark); }
.vr-ic-green { background: #ecfdf5; color: #059669; }
.vr-ic-amber { background: #fffbeb; color: #d97706; }
.vr-ic-rose { background: #fff1f2; color: var(--vr-danger); }
.vr-ic-violet { background: #f5f3ff; color: #7c3aed; }
.vr-ic-blue { background: #eff6ff; color: #2563eb; }

/* panels */
.vr-panel { background: #fff; border: 1px solid var(--vr-line); border-radius: 18px; box-shadow: var(--vr-sh-sm); overflow: hidden; margin-bottom: 20px; }
.vr-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--vr-line-soft); flex-wrap: wrap; }
.vr-panel-head h3 { font-size: 1rem; font-weight: 700; color: var(--vr-ink); display: flex; align-items: center; gap: 9px; margin: 0; }
.vr-panel-head h3 svg { width: 17px; height: 17px; color: var(--vr-brand); }
.vr-panel-body { padding: 18px; }
.vr-panel table { border: 0 !important; border-radius: 0 !important; background: transparent !important; }
.vr-panel > .overflow-x-auto { border-radius: 18px; }
.vr-pill { font-size: 0.7rem; font-weight: 600; color: var(--vr-cyan-dark); background: rgba(8, 145, 178, 0.08); border: 1px solid rgba(8, 145, 178, 0.18); padding: 4px 10px; border-radius: 999px; }

/* chips */
.vr-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 0.72rem; font-weight: 600; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
.vr-chip svg { width: 13px; height: 13px; }
.vr-chip.ok { color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; }
.vr-chip.warn { color: #b45309; background: #fffbeb; border: 1px solid #fde68a; }
.vr-chip.info { color: var(--vr-cyan-dark); background: #ecfeff; border: 1px solid #a5f3fc; }
.vr-chip.blue { color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; }
.vr-chip.bad { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
.vr-chip.mut { color: var(--vr-muted); background: #f1f5f9; border: 1px solid #e2e8f0; }
.vr-vbadge { display: inline-block; font-weight: 600; font-size: 0.76rem; color: var(--vr-brand); background: var(--vr-tint-soft); border: 1px solid var(--vr-tint); padding: 3px 9px; border-radius: 8px; }

/* avatars / cells */
.vr-ava { width: 30px; height: 30px; border-radius: 9px; background: var(--vr-tint); color: var(--vr-brand); display: inline-grid; place-items: center; font-weight: 700; font-size: 0.7rem; }
.vr-cust { display: inline-flex; align-items: center; gap: 9px; }
.vr-num { text-align: right; font-variant-numeric: tabular-nums; }
.profile-content .cell-strong { font-weight: 600; color: var(--vr-ink); }
.profile-content .vr-mut { color: var(--vr-muted); font-size: 0.78rem; }

/* icon action buttons */
.vr-iact { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--vr-line); background: #fff; display: inline-grid; place-items: center; cursor: pointer; color: var(--vr-muted); transition: all 0.25s var(--vr-ease); }
.vr-iact svg { width: 15px; height: 15px; }
.vr-iact:hover { border-color: var(--vr-brand); color: var(--vr-brand); background: #f0f8fc; }
.vr-iact.danger:hover { border-color: #fecdd3; color: var(--vr-danger); background: #fff1f2; }
.vr-act-row { display: inline-flex; gap: 6px; }

/* tabs */
.vr-tabs { display: inline-flex; gap: 4px; background: #fff; border: 1px solid var(--vr-line); border-radius: 12px; padding: 4px; margin-bottom: 18px; flex-wrap: wrap; }
.vr-tab { border: 0; background: transparent; font-weight: 600; font-size: 0.82rem; color: var(--vr-muted); padding: 8px 14px; border-radius: 9px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: background 0.25s var(--vr-ease), color 0.25s var(--vr-ease); }
.vr-tab .cnt { font-size: 0.7rem; background: var(--vr-soft); border-radius: 999px; padding: 1px 7px; color: var(--vr-muted); }
.vr-tab.on { background: var(--vr-tint); color: var(--vr-brand); }
.vr-tab.on .cnt { background: #fff; color: var(--vr-brand); }

/* empty state */
.vr-empty { text-align: center; padding: 48px 20px; }
.vr-empty .e-ic { width: 60px; height: 60px; border-radius: 18px; background: var(--vr-tint-soft); color: var(--vr-brand); display: inline-grid; place-items: center; margin-bottom: 16px; }
.vr-empty .e-ic svg { width: 28px; height: 28px; }
.vr-empty h4 { font-size: 1.05rem; font-weight: 700; color: var(--vr-ink); margin-bottom: 4px; }
.vr-empty p { font-size: 0.86rem; color: var(--vr-muted); margin-bottom: 18px; }

/* forms */
.vr-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.vr-field { display: flex; flex-direction: column; gap: 7px; }
.vr-field.full { grid-column: 1 / -1; }
.vr-field label { font-size: 0.8rem; font-weight: 600; color: var(--vr-strong); }
.vr-field .req { color: var(--vr-danger); }
.vr-form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--vr-line-soft); }
.vr-hint { font-size: 0.76rem; color: var(--vr-muted); }
.vr-filebox { border: 1.5px dashed var(--vr-line); border-radius: 12px; padding: 22px; text-align: center; background: var(--vr-soft); transition: border-color 0.25s var(--vr-ease), background 0.25s var(--vr-ease); cursor: pointer; }
.vr-filebox:hover { border-color: var(--vr-brand); background: #f0f8fc; }

/* pagination */
.vr-pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--vr-line-soft); flex-wrap: wrap; }
.vr-pager .info { font-size: 0.8rem; color: var(--vr-muted); }

/* booking & vehicle cards */
.vr-card-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.vr-bk-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.vr-card { background: #fff; border: 1px solid var(--vr-line); border-radius: 18px; overflow: hidden; box-shadow: var(--vr-sh-sm); transition: transform 0.35s var(--vr-ease), box-shadow 0.35s var(--vr-ease); }
.vr-card:hover { transform: translateY(-3px); box-shadow: var(--vr-sh-md); }

@media (max-width: 1100px) {
  .vr-stat-grid.c4 { grid-template-columns: repeat(2, 1fr); }
  .vr-card-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 820px) {
  .vr-form-grid, .vr-bk-grid { grid-template-columns: 1fr; }
  .vr-stat-grid.c2, .vr-stat-grid.c3, .vr-stat-grid.c4 { grid-template-columns: 1fr; }
  .vr-card-grid { grid-template-columns: 1fr; }
}
</style>
