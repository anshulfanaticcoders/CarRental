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
  SidebarHeader,
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
  Building,
  Megaphone,
  LogOut,
} from 'lucide-vue-next';
import { onMounted, ref, computed, type Component } from 'vue';
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
          { title: 'Addons', url: '/booking-addons' },
          { title: 'Plans', url: '/admin/plans' },
          { title: 'Features', url: '/features' },
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
    ],
  },
  {
    label: 'Business',
    items: [
      {
        title: 'Business Model',
        url: '#',
        icon: Building,
        items: [
          { title: 'Business Statistics', url: '/admin/affiliate/business-statistics' },
          { title: 'Business Verification', url: '/admin/affiliate/business-verification' },
          { title: 'Payment Tracking', url: '/admin/affiliate/payment-tracking' },
          { title: 'Commission Management', url: '/admin/affiliate/commission-management' },
          { title: 'QR Code Analytics', url: '/admin/affiliate/qr-analytics' },
          { title: 'Business Model Settings', url: '/admin/affiliate/business-model' },
          { title: 'Register Business', url: '/admin/affiliate/business-register' },
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
        title: 'Advertisements',
        url: '#',
        icon: Megaphone,
        items: [
          { title: 'Manage Ads', url: '/admin/advertisements' },
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
  <Sidebar collapsible="icon" class="bg-white border-r border-gray-200 font-[var(--jakarta-font-family)]">
      <SidebarContent class="sb-scroll-hide">
        <!-- Brand -->
        <SidebarGroup class="!p-0">
          <Link href="/" class="flex items-center gap-3 px-5 pt-5 pb-4 border-b border-gray-100">
            <div class="relative w-[38px] h-[38px] rounded-[10px] bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white font-extrabold text-[17px] flex-shrink-0 sb-logo-glow">
              V
            </div>
            <div class="flex flex-col overflow-hidden group-data-[collapsible=icon]:hidden">
              <span class="text-[15px] font-extrabold tracking-tight text-gray-900 leading-tight">Vrooem</span>
              <span class="text-[9.5px] font-mono font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 px-1.5 rounded mt-0.5 w-fit uppercase tracking-wider">Admin Panel</span>
            </div>
          </Link>
        </SidebarGroup>

        <!-- Nav Groups -->
        <SidebarGroup v-for="group in navGroups" :key="group.label" class="!px-3 !py-1">
          <p class="text-[10px] font-bold tracking-[0.1em] uppercase text-gray-400 px-2.5 pt-3 pb-1 group-data-[collapsible=icon]:hidden">
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
                        ? '!bg-gradient-to-r !from-indigo-50 !to-indigo-100/60 !text-indigo-700 !font-semibold !shadow-[0_0_0_1px_rgba(199,210,254,1),0_1px_2px_rgba(0,0,0,0.03)]'
                        : '!text-gray-600 hover:!bg-gray-50 hover:!text-gray-900'
                    ]"
                  >
                    <!-- Active bar indicator -->
                    <span v-if="isMenuActive(item)" class="sb-active-bar" />

                    <component
                      :is="item.icon"
                      class="!w-[19px] !h-[19px] transition-all duration-150"
                      :class="isMenuActive(item) ? '!text-indigo-600 drop-shadow-[0_0_4px_rgba(79,70,229,0.3)]' : '!text-gray-400'"
                      :stroke-width="1.7"
                    />
                    <span class="group-data-[collapsible=icon]:hidden">{{ item.title }}</span>

                    <!-- Count badge -->
                    <span
                      v-if="item.count"
                      class="font-mono text-[10px] font-semibold rounded-full px-1.5 py-0.5 ml-auto mr-0.5 leading-none group-data-[collapsible=icon]:hidden transition-all"
                      :class="isMenuActive(item)
                        ? 'bg-indigo-600 text-white shadow-[0_2px_6px_rgba(79,70,229,0.3)]'
                        : 'bg-gray-100 text-gray-500'
                      "
                    >
                      {{ item.count }}
                    </span>

                    <ChevronRight
                      class="ml-auto transition-transform duration-300 group-data-[collapsible=icon]:hidden"
                      :class="[
                        isMenuOpen(item.title) ? 'rotate-90 text-gray-500' : 'text-gray-300',
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
                              ? '!text-indigo-700 !font-semibold !bg-indigo-50'
                              : '!text-gray-400 !font-normal hover:!text-gray-900 hover:!bg-gray-50 hover:!pl-4'
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
      <SidebarFooter class="!border-t !border-gray-100">
        <Link
          :href="route('admin.logout')"
          method="post"
          as="button"
          class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-lg text-[13px] font-medium text-gray-500 border border-gray-200 transition-all duration-150 hover:bg-red-50 hover:border-red-200/50 hover:text-red-600 hover:shadow-[0_0_0_3px_rgba(239,68,68,0.06)]"
        >
          <LogOut :size="17" :stroke-width="1.8" />
          <span class="group-data-[collapsible=icon]:hidden">Log out</span>
        </Link>
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
</template>
