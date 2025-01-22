<script setup lang=ts>
import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/Components/ui/avatar'

import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuAction,
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
  BadgeCheck,
  Bell,
  LayoutDashboard,
  ChevronRight,
  ChevronsUpDown,
  Command,
  CreditCard,
  CarFront,
  User,
  MapPinHouse,
  Frame,
  GalleryVerticalEnd,
  LogOut,
  SquareKanban,
  Proportions,
  HeartHandshake,
  Plus,
  Sparkles,
  Settings,
} from 'lucide-vue-next'
import { onMounted, ref } from 'vue'

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
          url: '/register',
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
          url: '#',
        },
        {
          title: 'Add Users',
          url: '#',
        },
        {
          title: 'Blocked Users',
          url: '#',
        },
        {
          title: 'Verifications',
          url: '#',
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
          url: '#',
        },
        {
          title: 'Add Cars',
          url: '#',
        },
        {
          title: 'Categories',
          url: '#',
        },
        {
          title: 'Documents',
          url: '#',
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
          url: '#',
        },
        {
          title: 'Add Locations',
          url: '#',
        },
        {
          title: 'Setting',
          url: '#',
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
          url: '#',
        },
        {
          title: 'Active',
          url: '#',
        },
        {
          title: 'Completed',
          url: '#',
        },
        {
          title: 'Cancelled',
          url: '#',
        },
      ],
    },
    {
      title: 'Reports',
      url: '#',
      icon: Proportions,
      items: [
        {
          title: 'Business',
          url: '#',
        },
        {
          title: 'Users',
          url: '#',
        },
      ],
    },
    {
      title: 'Settings',
      url: '#',
      icon: Settings,
      items: [
        {
          title: 'General',
          url: '#',
        },
        {
          title: 'Security',
          url: '#',
        },
      ],
    },
    {
      title: 'Support',
      url: '#',
      icon: HeartHandshake,
      items: [
        {
          title: 'Tickets',
          url: '#',
        },
        {
          title: 'Logs',
          url: '#',
        },
      ],
    },
  ],
}

const activeTeam = ref(data.teams[0])

function setActiveTeam(team: typeof data.teams[number]) {
  activeTeam.value = team
}


import axios from "axios";

const userCount = ref<number>(0); // Reactive variable for the user count
  const users = ref<Array<Record<string, any>>>([]);
const loading = ref<boolean>(true); // Reactive variable for the loading state

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
});

</script>

<template>

  <Head></Head>
  <SidebarProvider>
    <Sidebar collapsible="icon" class="bg-customPrimaryColor text-white">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <SidebarMenuButton size="lg"
                  class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground text-[2rem]">
                  <div
                    class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                    <component :is="activeTeam.logo" class="size-4" />
                  </div>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{ activeTeam.name }}</span>
                    <span class="truncate text-xs">{{ activeTeam.plan }}</span>
                  </div>
                  <ChevronsUpDown class="ml-auto" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg" align="start"
                side="bottom" :side-offset="4">
                <DropdownMenuLabel class="text-xs text-muted-foreground">
                  Teams
                </DropdownMenuLabel>
                <DropdownMenuItem v-for="(team, index) in data.teams" :key="team.name" class="gap-2 p-2"
                  @click="setActiveTeam(team)">
                  <div class="flex size-6 items-center justify-center rounded-sm border">
                    <component :is="team.logo" class="size-4 shrink-0" />
                  </div>
                  {{ team.name }}
                  <DropdownMenuShortcut>⌘{{ index + 1 }}</DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem class="gap-2 p-2">
                  <div class="flex size-6 items-center justify-center rounded-md border bg-background">
                    <Plus class="size-4" />
                  </div>
                  <div class="font-medium text-muted-foreground">
                    Add team
                  </div>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>Platform</SidebarGroupLabel>
          <SidebarMenu>
            <Collapsible v-for="item in data.navMain" :key="item.title" as-child :default-open="item.isActive"
              class="group/collapsible">
              <SidebarMenuItem>
                <CollapsibleTrigger as-child>
                  <SidebarMenuButton :tooltip="item.title">
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                    <ChevronRight
                      class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
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
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <SidebarMenuButton size="lg"
                  class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                  <Avatar class="h-8 w-8 rounded-lg">
                    <AvatarImage :src="data.user.avatar" :alt="data.user.name" />
                    <AvatarFallback class="rounded-lg">
                      CN
                    </AvatarFallback>
                  </Avatar>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{ data.user.name }}</span>
                    <span class="truncate text-xs">{{ data.user.email }}</span>
                  </div>
                  <ChevronsUpDown class="ml-auto size-4" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg" side="bottom"
                align="end" :side-offset="4">
                <DropdownMenuLabel class="p-0 font-normal">
                  <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar class="h-8 w-8 rounded-lg">
                      <AvatarImage :src="data.user.avatar" :alt="data.user.name" />
                      <AvatarFallback class="rounded-lg">
                        CN
                      </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left text-sm leading-tight">
                      <span class="truncate font-semibold">{{ data.user.name }}</span>
                      <span class="truncate text-xs">{{ data.user.email }}</span>
                    </div>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem>
                    <Sparkles />
                    Upgrade to Pro
                  </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem>
                    <BadgeCheck />
                    Account
                  </DropdownMenuItem>
                  <DropdownMenuItem>
                    <CreditCard />
                    Billing
                  </DropdownMenuItem>
                  <DropdownMenuItem>
                    <Bell />
                    Notifications
                  </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem>
                  <LogOut />
                  Log out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
    
  </SidebarProvider>
</template>