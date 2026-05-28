<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarRail,
} from '@/Components/ui/sidebar';
import {
  LayoutDashboard,
  ChevronRight,
  CarFront,
  User,
  MapPinHouse,
  SquareKanban,
  Activity,
  Notebook,
  DollarSign,
  FileChartColumn,
  Settings,
  BookOpenText,
  Mail,
  User2Icon,
  Star,
  CameraIcon,
  ShieldPlusIcon,
  Target,
  Handshake,
  Megaphone,
  Plug,
  LogOut,
} from 'lucide-vue-next';
import { onMounted, ref, type Component } from 'vue';
import ApplicationLogo from './ApplicationLogo.vue';

interface NavSubItem {
  title: string;
  url: string;
}

interface NavItem {
  title: string;
  url: string;
  icon: Component;
  count?: number;
  items: NavSubItem[];
}

interface NavGroup {
  label: string;
  items: NavItem[];
}

const navGroups: NavGroup[] = [
  {
    label: 'Main',
    items: [
      {
        title: 'Dashboard',
        url: '#',
        icon: LayoutDashboard,
        items: [
          { title: 'Overview', url: '/admin-dashboard' },
          { title: 'Profile Setting', url: '/admin/settings/profile' },
        ],
      },
    ],
  },
  {
    label: 'Management',
    items: [
      {
        title: 'User Management',
        url: '#',
        icon: User,
        items: [
          { title: 'All Users', url: '/users' },
          { title: 'Users Documents', url: '/admin/user-documents' },
        ],
      },
      {
        title: 'Vendor Management',
        url: '#',
        icon: User2Icon,
        items: [
          { title: 'All Vendors', url: '/vendors' },
        ],
      },
      {
        title: 'Car Management',
        url: '#',
        icon: CarFront,
        items: [
          { title: 'All Cars', url: '/vendor-vehicles' },
          { title: 'Categories', url: '/vehicles-categories' },
        ],
      },
      {
        title: 'Locations',
        url: '#',
        icon: MapPinHouse,
        items: [
          { title: 'All Locations', url: '/popular-places' },
        ],
      },
      {
        title: 'Bookings',
        url: '#',
        icon: SquareKanban,
        items: [
          { title: 'All Bookings', url: '/customer-bookings' },
          { title: 'Pending', url: '/customer-bookings/pending' },
          { title: 'Active', url: '/customer-bookings/confirmed' },
          { title: 'Completed', url: '/customer-bookings/completed' },
          { title: 'Cancelled', url: '/customer-bookings/cancelled' },
        ],
      },
      {
        title: 'Provider API',
        url: '#',
        icon: Plug,
        items: [
          { title: 'API Consumers', url: '/api-consumers' },
          { title: 'External Bookings', url: '/external-bookings' },
          { title: 'API Analytics', url: '/admin/api-analytics' },
        ],
      },
    ],
  },
  {
    label: 'Business',
    items: [
      {
        title: 'Affiliates',
        url: '#',
        icon: Handshake,
        items: [
          { title: 'Overview', url: '/admin/affiliate/overview' },
          { title: 'Partners', url: '/admin/affiliate/partners' },
          { title: 'Commissions', url: '/admin/affiliate/commissions' },
          { title: 'Payouts', url: '/admin/affiliate/payouts' },
          { title: 'Settings', url: '/admin/affiliate/business-model' },
        ],
      },
      {
        title: 'Payments',
        url: '#',
        icon: DollarSign,
        items: [
          { title: 'All Payments', url: '/admin/payments' },
        ],
      },
      {
        title: 'Damage Protection',
        url: '#',
        icon: ShieldPlusIcon,
        items: [
          { title: 'Damage Records', url: '/damage-protection-records' },
        ],
      },
      {
        title: 'Reports',
        url: '#',
        icon: FileChartColumn,
        items: [
          { title: 'Analytics', url: '/admin/analytics' },
          { title: 'Users Report', url: '/users-report' },
          { title: 'Vendors Report', url: '/vendors-report' },
          { title: 'Business Report', url: '/business-report' },
        ],
      },
      {
        title: 'Marketing',
        url: '#',
        icon: Megaphone,
        items: [
          { title: 'Offers', url: '/admin/offers' },
        ],
      },
    ],
  },
  {
    label: 'Content',
    items: [
      {
        title: 'Pages',
        url: '#',
        icon: BookOpenText,
        items: [
          { title: 'All Pages', url: '/pages' },
          { title: 'Add New Page', url: '/pages/create' },
        ],
      },
      {
        title: 'Blogs',
        url: '#',
        icon: Notebook,
        items: [
          { title: 'All Blogs', url: '/blogs' },
        ],
      },
      {
        title: 'Media',
        url: '#',
        icon: CameraIcon,
        items: [
          { title: 'All Media', url: '/media' },
        ],
      },
      {
        title: 'Reviews',
        url: '#',
        icon: Star,
        items: [
          { title: 'All Reviews', url: '/admin/reviews' },
        ],
      },
      {
        title: 'Testimonials',
        url: '#',
        icon: User2Icon,
        items: [
          { title: 'All Testimonials', url: '/testimonials' },
        ],
      },
      {
        title: 'SEO Management',
        url: '#',
        icon: Target,
        items: [
          { title: 'SEO Meta Tags', url: '/admin/seo-meta' },
        ],
      },
    ],
  },
  {
    label: 'System',
    items: [
      {
        title: 'Newsletter',
        url: '#',
        icon: Mail,
        items: [
          { title: 'Subscribers', url: '/admin/newsletter-subscribers' },
          { title: 'Campaigns', url: '/admin/newsletter-campaigns' },
        ],
      },
      {
        title: 'Email Logs',
        url: '#',
        icon: Mail,
        items: [
          { title: 'Contact Mails', url: '/contact-us-mails' },
        ],
      },
      {
        title: 'Activity Logs',
        url: '#',
        icon: Activity,
        items: [
          { title: 'All Activities', url: '/activity-logs' },
        ],
      },
      {
        title: 'Settings',
        url: '#',
        icon: Settings,
        items: [
          { title: 'Footer Location', url: '/admin/settings/footer' },
          { title: 'Footer Category', url: '/admin/settings/footer-categories' },
          { title: 'FAQ', url: '/admin/settings/faq' },
          { title: 'Payable Amount', url: '/admin/settings/payable-amount' },
          { title: 'Header/Footer Scripts', url: '/admin/header-footer-scripts' },
          { title: 'Home Page Settings', url: '/admin/settings/homepage' },
        ],
      },
    ],
  },
];

const currentPath = ref(window.location.pathname);
const expandedMenus = ref<Set<string>>(new Set());

const setActiveMenusBasedOnPath = () => {
  navGroups.forEach((group) => {
    group.items.forEach((menuItem) => {
      const hasActiveSubmenu = menuItem.items.some(item => currentPath.value === item.url);
      if (hasActiveSubmenu) {
        expandedMenus.value.add(menuItem.title);
      }
    });
  });
};

const isMenuOpen = (menuTitle: string) => expandedMenus.value.has(menuTitle);

const toggleMenu = (menuTitle: string) => {
  if (expandedMenus.value.has(menuTitle)) {
    expandedMenus.value.delete(menuTitle);
  } else {
    expandedMenus.value.add(menuTitle);
  }
};

const isMenuActive = (menuItem: NavItem) => {
  return menuItem.items.some(item => currentPath.value === item.url);
};

const isSubmenuActive = (url: string) => currentPath.value === url;

router.on('navigate', () => {
  currentPath.value = window.location.pathname;
  setActiveMenusBasedOnPath();
});

onMounted(() => {
  setActiveMenusBasedOnPath();
});
</script>

<template>
  <Sidebar collapsible="icon" class="bg-[#fbfdff] border-r border-[#dceef6] font-[var(--jakarta-font-family)] shadow-[8px_0_28px_rgba(21,59,79,0.06)]">
      <SidebarContent class="sb-scroll-hide">
        <!-- Brand -->
        <SidebarGroup class="!p-0">
          <Link href="/" class="flex items-center gap-3 px-5 pt-5 pb-4 border-b border-[#dceef6] bg-gradient-to-br from-white via-[#f8fafc] to-[#f0f8fc]">
            <div class="relative w-[38px] h-[38px] rounded-[10px] bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] flex items-center justify-center text-white font-extrabold text-[17px] flex-shrink-0 sb-logo-glow">
              V
            </div>
            <div class="flex flex-col overflow-hidden group-data-[collapsible=icon]:hidden">
              <ApplicationLogo logo-color="#153B4F" class="h-[18px] w-[150px]" />
              <span class="text-[9.5px] font-semibold text-[#0891b2] bg-[#cffafe]/55 border border-[#b0d4e6] px-1.5 rounded mt-1 w-fit uppercase tracking-[0.13em]">Admin Panel</span>
            </div>
          </Link>
        </SidebarGroup>

        <!-- Nav Groups -->
        <SidebarGroup v-for="group in navGroups" :key="group.label" class="!px-3 !py-1">
          <p class="text-[10px] font-bold tracking-[0.12em] uppercase text-slate-400 px-2.5 pt-3 pb-1 group-data-[collapsible=icon]:hidden">
            {{ group.label }}
          </p>
          <SidebarMenu>
            <Collapsible
              v-for="item in group.items"
              :key="item.title"
              as-child
              :open="isMenuOpen(item.title)"
              @update:open="toggleMenu(item.title)"
              class="group/collapsible"
            >
              <SidebarMenuItem class="!py-0 sb-animate-in">
                <!-- Menu Trigger -->
                <CollapsibleTrigger as-child>
                  <SidebarMenuButton
                    :tooltip="item.title"
                    class="relative !rounded-lg !px-2.5 !py-2 !h-auto !text-[13.5px] !font-medium transition-all duration-150"
                    :class="[
                      isMenuActive(item)
                        ? '!bg-gradient-to-r !from-[#f0f8fc] !to-[#ecfeff] !text-[#153b4f] !font-semibold !shadow-[0_0_0_1px_rgba(176,212,230,0.95),0_8px_22px_rgba(21,59,79,0.08)]'
                        : '!text-slate-600 hover:!bg-[#f0f8fc]/75 hover:!text-[#153b4f]'
                    ]"
                  >
                    <!-- Active bar indicator -->
                    <span v-if="isMenuActive(item)" class="sb-active-bar" />

                    <component
                      :is="item.icon"
                      class="!w-[19px] !h-[19px] transition-all duration-150"
                      :class="isMenuActive(item) ? '!text-[#0891b2] drop-shadow-[0_0_5px_rgba(34,211,238,0.32)]' : '!text-slate-400'"
                      :stroke-width="1.7"
                    />
                    <span class="group-data-[collapsible=icon]:hidden">{{ item.title }}</span>

                    <!-- Count badge -->
                    <span
                      v-if="item.count"
                      class="font-mono text-[10px] font-semibold rounded-full px-1.5 py-0.5 ml-auto mr-0.5 leading-none group-data-[collapsible=icon]:hidden transition-all"
                      :class="isMenuActive(item)
                        ? 'bg-[#153b4f] text-white shadow-[0_2px_8px_rgba(21,59,79,0.22)]'
                        : 'bg-[#f1f5f9] text-slate-500'
                      "
                    >
                      {{ item.count }}
                    </span>

                    <ChevronRight
                      class="ml-auto transition-transform duration-300 group-data-[collapsible=icon]:hidden"
                      :class="[
                        isMenuOpen(item.title) ? 'rotate-90 text-[#2d7294]' : 'text-slate-300',
                      ]"
                      :size="16"
                      :stroke-width="2.2"
                    />
                  </SidebarMenuButton>
                </CollapsibleTrigger>

                <!-- Submenu -->
                <CollapsibleContent>
                  <SidebarMenuSub
                    class="!ml-4 !pl-3.5 !border-l-[1.5px] group-data-[collapsible=icon]:hidden"
                    :class="isMenuActive(item) ? 'sb-tree-line-active' : 'sb-tree-line'"
                  >
                    <SidebarMenuSubItem
                      v-for="subItem in item.items"
                      :key="subItem.title"
                    >
                      <SidebarMenuSubButton as-child>
                        <a
                          :href="subItem.url"
                          class="relative !text-[13px] !rounded transition-all duration-150 !py-1.5"
                          :class="[
                            isSubmenuActive(subItem.url)
                              ? '!text-[#153b4f] !font-semibold !bg-[#f0f8fc]'
                              : '!text-slate-400 !font-normal hover:!text-[#153b4f] hover:!bg-[#f8fafc] hover:!pl-4'
                          ]"
                        >
                          <span v-if="isSubmenuActive(subItem.url)" class="sb-active-dot" />
                          <span>{{ subItem.title }}</span>
                        </a>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  </SidebarMenuSub>
                </CollapsibleContent>
              </SidebarMenuItem>
            </Collapsible>
          </SidebarMenu>
        </SidebarGroup>
      </SidebarContent>

      <!-- Footer -->
      <SidebarFooter class="!border-t !border-[#dceef6] !bg-[#fbfdff]">
        <Link
          :href="route('admin.logout')"
          method="post"
          as="button"
          class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-lg text-[13px] font-medium text-red-600 bg-red-50 border border-red-100 transition-all duration-150 hover:bg-red-100 hover:border-red-200 hover:text-red-700 hover:shadow-[0_0_0_3px_rgba(239,68,68,0.08)]"
        >
          <LogOut :size="17" :stroke-width="1.8" />
          <span class="group-data-[collapsible=icon]:hidden">Log out</span>
        </Link>
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
</template>
