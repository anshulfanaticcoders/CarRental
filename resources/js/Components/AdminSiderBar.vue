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
  useSidebar,
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

declare const route: (name: string, params?: unknown, absolute?: boolean) => string;

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
const { state: sidebarState } = useSidebar();

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

const getPrimaryMenuUrl = (menuItem: NavItem) => {
  return menuItem.items.find(item => currentPath.value === item.url)?.url
    || menuItem.items[0]?.url
    || (menuItem.url !== '#' ? menuItem.url : '');
};

const announceAdminRouteLoading = () => {
  window.dispatchEvent(new CustomEvent('admin-route-loading:start'));
};

const visitAdminRouteWithLoader = (url: string) => {
  announceAdminRouteLoading();
  window.setTimeout(() => router.visit(url), 50);
};

const visitCollapsedMenu = (event: MouseEvent, menuItem: NavItem) => {
  if (sidebarState.value !== 'collapsed') {
    return;
  }

  const targetUrl = getPrimaryMenuUrl(menuItem);
  if (!targetUrl) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();
  event.stopImmediatePropagation();
  visitAdminRouteWithLoader(targetUrl);
};

const visitSubmenu = (event: MouseEvent, url: string) => {
  const clickButton = event.button ?? 0;

  if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || clickButton !== 0) {
    return;
  }

  event.preventDefault();

  if (currentPath.value === url) {
    return;
  }

  visitAdminRouteWithLoader(url);
};

router.on('navigate', () => {
  currentPath.value = window.location.pathname;
  setActiveMenusBasedOnPath();
});

onMounted(() => {
  setActiveMenusBasedOnPath();
});
</script>

<template>
  <Sidebar collapsible="icon" class="admin-sidebar-dark border-r border-[rgba(176,212,230,0.13)] font-[var(--jakarta-font-family)] shadow-[18px_0_58px_rgba(0,0,0,0.26)]">
      <SidebarContent class="sb-scroll-hide">
        <!-- Brand -->
        <SidebarGroup class="!p-0">
          <Link href="/" class="flex h-16 items-center gap-3 px-5 border-b border-[rgba(176,212,230,0.13)] bg-[linear-gradient(180deg,rgba(8,24,34,0.98),rgba(7,19,28,0.97))] transition-[gap,padding] duration-300 ease-out group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:gap-0 group-data-[collapsible=icon]:px-0">
            <div class="relative w-[38px] h-[38px] rounded-[10px] bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] flex items-center justify-center text-white font-extrabold text-[17px] flex-shrink-0 sb-logo-glow group-data-[collapsible=icon]:!h-9 group-data-[collapsible=icon]:!w-9 group-data-[collapsible=icon]:!rounded-[11px]">
              V
            </div>
            <div class="admin-sidebar-brand-copy flex flex-col overflow-hidden">
              <ApplicationLogo logo-color="#FFFFFF" class="h-[18px] w-[150px]" />
              <span class="text-[9.5px] font-semibold text-[#67e8f9] bg-[rgba(34,211,238,0.08)] border border-[rgba(34,211,238,0.2)] px-1.5 rounded mt-1 w-fit uppercase tracking-[0.13em]">Admin Panel</span>
            </div>
          </Link>
        </SidebarGroup>

        <!-- Nav Groups -->
        <SidebarGroup v-for="group in navGroups" :key="group.label" class="!px-3 !py-1">
          <p class="admin-sidebar-group-label text-[10px] font-bold tracking-[0.12em] uppercase text-[#667e90] px-2.5 pt-3 pb-1">
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
                    @click.capture="visitCollapsedMenu($event, item)"
                    class="relative !rounded-[12px] !px-2.5 !py-2 !h-auto !text-[13.5px] !font-medium transition-[color,background-color,border-color,box-shadow,transform] duration-200"
                    :class="[
                      isMenuActive(item)
                        ? '!bg-[linear-gradient(135deg,rgba(21,59,79,0.92),rgba(34,211,238,0.14))] !text-white !font-semibold !border !border-[rgba(34,211,238,0.3)] !shadow-[inset_0_1px_0_rgba(255,255,255,0.08),0_12px_28px_rgba(34,211,238,0.08)]'
                        : '!text-[#b8c7d2] hover:!bg-[rgba(21,59,79,0.46)] hover:!text-white hover:!shadow-[inset_0_0_0_1px_rgba(176,212,230,0.08)] hover:!-translate-y-px active:!translate-y-0 active:!scale-[0.99]'
                    ]"
                  >
                    <!-- Active bar indicator -->
                    <span v-if="isMenuActive(item)" class="sb-active-bar" />

                    <component
                      :is="item.icon"
                      class="!w-[19px] !h-[19px] transition-[color,filter,transform] duration-200"
                      :class="isMenuActive(item) ? '!text-[#67e8f9] drop-shadow-[0_0_7px_rgba(34,211,238,0.34)]' : '!text-[#8da3b4]'"
                      :stroke-width="1.7"
                    />
                    <span class="admin-sidebar-label">{{ item.title }}</span>

                    <!-- Count badge -->
                    <span
                      v-if="item.count"
                      class="admin-sidebar-badge font-mono text-[10px] font-semibold rounded-full px-1.5 py-0.5 ml-auto mr-0.5 leading-none transition-[color,background-color,box-shadow,transform] duration-200"
                      :class="isMenuActive(item)
                        ? 'bg-[#22d3ee] text-[#07131c] shadow-[0_4px_14px_rgba(34,211,238,0.28)]'
                        : 'bg-[rgba(176,212,230,0.1)] text-[#8da3b4]'
                      "
                    >
                      {{ item.count }}
                    </span>

                    <ChevronRight
                      class="admin-sidebar-chevron ml-auto transition-transform duration-300"
                      :class="[
                        isMenuOpen(item.title) ? 'rotate-90 text-[#67e8f9]' : 'text-[#5f7484]',
                      ]"
                      :size="16"
                      :stroke-width="2.2"
                    />
                  </SidebarMenuButton>
                </CollapsibleTrigger>

                <!-- Submenu -->
                <CollapsibleContent class="admin-sidebar-submenu-content">
                  <SidebarMenuSub
                    class="!ml-4 !pl-3.5 !border-l-[1.5px] group-data-[collapsible=icon]:hidden"
                    :class="isMenuActive(item) ? 'sb-tree-line-active' : 'sb-tree-line'"
                  >
                    <SidebarMenuSubItem
                      v-for="subItem in item.items"
                      :key="subItem.title"
                    >
                      <SidebarMenuSubButton
                        as-child
                        :is-active="isSubmenuActive(subItem.url)"
                        class="admin-sidebar-submenu-link !h-7 !text-[13px] !rounded-md !py-1.5 transition-[color,background-color,padding,transform] duration-150 focus-visible:!ring-2 focus-visible:!ring-[#22d3ee]/45"
                        :class="isSubmenuActive(subItem.url)
                          ? '!bg-[rgba(34,211,238,0.08)] !font-semibold !text-[#67e8f9]'
                          : '!font-normal !text-[#8da3b4] hover:!bg-[rgba(21,59,79,0.36)] hover:!pl-4 hover:!text-white'"
                      >
                        <a
                          :href="subItem.url"
                          @click="visitSubmenu($event, subItem.url)"
                        >
                          <span v-if="isSubmenuActive(subItem.url)" class="sb-active-dot" />
                          <span class="truncate">{{ subItem.title }}</span>
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
      <SidebarFooter class="!border-t !border-[rgba(176,212,230,0.13)] !bg-[rgba(7,19,28,0.96)] group-data-[collapsible=icon]:!items-center group-data-[collapsible=icon]:!px-1 group-data-[collapsible=icon]:!py-2">
        <Link
          :href="route('admin.logout')"
          method="post"
          as="button"
          class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-[12px] text-[13px] font-medium text-red-300 bg-[rgba(239,68,68,0.08)] border border-[rgba(239,68,68,0.16)] transition-[color,background-color,border-color,box-shadow,transform] duration-200 hover:bg-[rgba(239,68,68,0.13)] hover:border-[rgba(239,68,68,0.3)] hover:text-red-200 hover:shadow-[0_0_0_4px_rgba(239,68,68,0.08)] hover:-translate-y-px active:translate-y-0 active:scale-[0.98] group-data-[collapsible=icon]:!h-10 group-data-[collapsible=icon]:!w-10 group-data-[collapsible=icon]:!justify-center group-data-[collapsible=icon]:!gap-0 group-data-[collapsible=icon]:!px-0 group-data-[collapsible=icon]:!py-0"
        >
          <LogOut :size="17" :stroke-width="1.8" class="shrink-0" />
          <span class="admin-sidebar-label">Log out</span>
        </Link>
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
</template>
