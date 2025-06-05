<script setup lang=ts>

import axios from "axios";
import { Link,router } from '@inertiajs/vue3';
import logoutIcon from '../../assets/logoutIcon.svg';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
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
  SidebarProvider,
  SidebarRail,
  SidebarTrigger,
} from '@/Components/ui/sidebar'
import {
  AudioWaveform,
  LayoutDashboard,
  ChevronRight,
  Command,
  CarFront,
  User,
  MapPinHouse,
  GalleryVerticalEnd,
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
  Target
} from 'lucide-vue-next'
import { onMounted, ref } from 'vue';
import ApplicationLogo from "./ApplicationLogo.vue";


// This is sample data.
const data = {
  user: {
    name: 'shadcn',
    email: 'm@example.com',
    avatar: '/avatars/shadcn.jpg',
  },
  teams: [
    {
      name: 'Vrooem Car',
      logo: GalleryVerticalEnd,
      plan: 'Car Rental Company',
    },
    {
      name: 'Acme Corp.',
      logo: AudioWaveform,
      plan: 'Startup',
    },
    {
      name: 'Evil Corp.',
      logo: Command,
      plan: 'Free',
    },
  ],
  navMain: [
    {
      title: 'Dashboard',
      url: '#',
      icon: LayoutDashboard,
      isActive: true,
      items: [
        {
          title: 'Overview',
          url: '/admin-dashboard',
        },
      ],
    },
    {
      title: 'User Management',
      url: '#',
      icon: User,
      items: [
        {
          title: 'All Users',
          url: '/users',
        },
        {
          title: 'Users Documents',
          url: '/admin/user-documents',
        },
      ],
    },
    {
      title: 'Vendor Management',
      url: '#',
      icon: User,
      items: [
        {
          title: 'All Vendors',
          url: '/vendors',
        },
      ],
    },
    {
      title: 'Car Management',
      url: '#',
      icon: CarFront,
      items: [
        {
          title: 'All Cars',
          url: '/vendor-vehicles',
        },
        {
          title: 'Categories',
          url: '/vehicles-categories',
        },
        {
          title: 'Addons',
          url: '/booking-addons',
        },
        {
          title: 'Plans',
          url: '/admin/plans',
        },
        {
          title: 'Features',
          url: '/features',
        },
      ],
    },
    {
      title: 'Location Management',
      url: '#',
      icon: MapPinHouse,
      items: [
        {
          title: 'All Locations',
          url: '/popular-places',
        },
      ],
    },
    {
      title: 'Media',
      url: '#',
      icon: CameraIcon,
      items: [
        {
          title: 'All Media',
          url: '/media',
        },
      ],
    },
    {
      title: 'Bookings',
      url: '#',
      icon: SquareKanban,
      items: [
        {
          title: 'All Bookings',
          url: '/customer-bookings',
        },
        {
          title: 'Pending',
          url: '/customer-bookings/pending',
        },
        {
          title: 'Active',
          url: '/customer-bookings/confirmed',
        },
        {
          title: 'Completed',
          url: '/customer-bookings/completed',
        },
        {
          title: 'Cancelled',
          url: '/customer-bookings/cancelled',
        },
      ],
    },
    {
      title: 'Damage Protection',
      url: '#',
      icon: ShieldPlusIcon,
      items: [
        {
          title: 'Damage',
          url: '/damage-protection-records',
        },
      ],
    },
    {
      title: 'Pages',
      url: '#',
      icon: BookOpenText,
      items: [
        {
          title: 'All Pages',
          url: '/pages',
        },
        {
          title: 'Contact Us',
          url: '/admin/contact-us',
        },
      ],
    },
    {
      title: 'Blogs',
      url: '#',
      icon: Notebook,
      items: [
        {
          title: 'All Blogs',
          url: '/blogs',
        },
      ],
    },
    {
      title: 'SEO Management',
      url: '#',
      icon: Target,
      items: [
        {
          title: 'SEO Meta Tags',
          url: '/admin/seo-meta', // Route for SeoMeta index
        },
      ],
    },
    {
      title: 'Schema Management',
      url: '#',
      icon: Target,
      items: [
        {
          title: 'Schemas',
          url: '/admin/schemas', // Route for SeoMeta index
        },
      ],
    },
    {
      title: 'Reviews',
      url: '#',
      icon: Star,
      items: [
        {
          title: 'All Reviews',
          url: '/admin/reviews',
        },
      ],
    },
    {
      title: 'Testimonials',
      url: '#',
      icon: User2Icon,
      items: [
        {
          title: 'All Testimonials',
          url: '/testimonials',
        },
      ],
    },
    
    {
      title: 'Payments',
      url: '#',
      icon: DollarSign,
      items: [
        {
          title: 'All Payments',
          url: '/admin/payments',
        },
      ],
    },
    {
      title: 'Reports',
      url: '#',
      icon: FileChartColumn,
      items: [
        {
          title: 'Users Report',
          url: '/users-report',
        },
        {
          title: 'Vendors Report',
          url: '/vendors-report',
        },
        {
          title: 'Business Report',
          url: '/business-report',
        },
      ],
    },
    {
      title: 'Email Logs',
      url: '#',
      icon: Mail,
      items: [
        {
          title: 'Contact Mails',
          url: '/contact-us-mails',
        },
      ],
    },
    {
      title: 'Activity Logs',
      url: '#',
      icon: Activity,
      items: [
        {
          title: 'All Activities',
          url: '/activity-logs',
        },
      ],
    },
    {
      title: 'Settings',
      url: '#',
      icon: Settings,
      items: [
        {
          title: 'Footer Location',
          url: '/admin/settings/footer',
        },
        {
          title: 'Footer Category',
          url: '/admin/settings/footer-categories',
        },
        {
          title: 'FAQ',
          url: '/admin/settings/faq',
        },
        {
          title: 'Radius',
          url: '/radiuses',
        },
        {
          title: 'Header/Footer Scripts',
          url: '/admin/header-footer-scripts',
        },
      ],
    },
  ],
}

const userCount = ref<number>(0); // Reactive variable for the user count
const users = ref<Array<Record<string, any>>>([]);
const loading = ref<boolean>(true); // Reactive variable for the loading state
// Track expanded menu items
const expandedMenus = ref<Set<string>>(new Set());

// Get the current route path
const currentPath = ref(window.location.pathname);

// Set active menus based on current path
const setActiveMenusBasedOnPath = () => {
  data.navMain.forEach((menuItem, index) => {
    // Check if any submenu URL matches the current path
    const hasActiveSubmenu = menuItem.items.some(item => currentPath.value === item.url);
    
    if (hasActiveSubmenu) {
      expandedMenus.value.add(menuItem.title);
    }
  });
};

// Check if a menu item should be open (either manually expanded or has active submenu)
const isMenuOpen = (menuTitle: string) => {
  return expandedMenus.value.has(menuTitle);
};

// Toggle menu expansion
const toggleMenu = (menuTitle: string) => {
  if (expandedMenus.value.has(menuTitle)) {
    expandedMenus.value.delete(menuTitle);
  } else {
    expandedMenus.value.add(menuTitle);
  }
};

// Check if a menu item is active (has a submenu matching current path)
const isMenuActive = (menuItem: any) => {
  return menuItem.items.some(item => currentPath.value === item.url);
};

// Check if a submenu item is active
const isSubmenuActive = (url: string) => {
  return currentPath.value === url;
};

// Update current path when route changes
router.on('navigate', () => {
  currentPath.value = window.location.pathname;
  setActiveMenusBasedOnPath();
});

const fetchUserCount = async () => {
  try {
    const response = await axios.get<{ count: number }>("/api/user-count");
    userCount.value = response.data.count; // Update the user count
  } catch (error) {
    console.error("Error fetching user count:", error);
  } finally {
    loading.value = false; // Set loading to false after API call
  }
};

const fetchUsers = async () => {
  try {
    const response = await axios.get<Array<Record<string, any>>>("/api/users");
    users.value = response.data;
  } catch (error) {
    console.error("Error fetching users:", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchUserCount();
  fetchUsers();
  setActiveMenusBasedOnPath();
});

</script>

<template>
  <SidebarProvider>
    <Sidebar collapsible="icon" class="bg-customDarkBlackColor text-white">
      <SidebarContent class="scroll-hide">
        <SidebarGroup>
        <Link href="/" class="mt-4 pb-5">
          <ApplicationLogo logoColor="#FFFFFF" />
        </Link>
          <SidebarMenu>
            <Collapsible v-for="item in data.navMain" :key="item.title" as-child :default-open="item.isActive"
            :open="isMenuOpen(item.title)" 
                @update:open="toggleMenu(item.title)" 
              class="group/collapsible">
              <SidebarMenuItem>
                <CollapsibleTrigger as-child :class="isMenuActive(item) ? 'font-bold' : ''">
                  <SidebarMenuButton :tooltip="item.title">
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                    <ChevronRight
                      class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" 
                      :class="isMenuOpen(item.title) ? 'rotate-90' : ''" />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title"
                    :class="isSubmenuActive(subItem.url) ? ' font-bold' : ''">
                      <SidebarMenuSubButton as-child>
                        <a :href="subItem.url">
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

      <SidebarFooter>
        <Link :href="route('logout')" method="post" as="button" class="text-[#EE1D52] flex items-center gap-1 pl-2 py-3 shadow-sm">
          <img :src=logoutIcon alt="">
          Log out
        </Link>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>

    <SidebarTrigger class="ml-1 mt-[1.5rem]" />

  </SidebarProvider>
</template>

<style scoped>
.scroll-hide {
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none;  /* IE and Edge */

  /* still scrollable */
  overflow: auto;
}

.scroll-hide::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

</style>
