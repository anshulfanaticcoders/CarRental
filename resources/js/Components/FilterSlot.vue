<script setup>
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

import bulbIcon from '../../assets/bulb.svg';
import percentageIcon from '../../assets/percent.svg';
import travellerIcon from '../../assets/travellerIcon.svg';
import transmitionIcon from '../../assets/transmittionIcon.svg';
import filterIcon from '../../assets/filterIcon.svg';
import carSideIcon from '../../assets/carSideIcon.svg';



</script>

<template>
  <div class="flex justify-between gap-10">

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="bulbIcon" alt="" class="transition-brightness">
          My Ideal Vehicle
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="percentageIcon" alt="" class="transition-brightness">
          Price per day
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="travellerIcon" alt="" class="transition-brightness">
          Travelers
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="carSideIcon" alt="" class="transition-brightness">
          Type of vehicle
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="transmitionIcon" alt="" class="transition-brightness">
          Equipment
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          class="w-full bg-white text-customPrimaryColor text-[1rem] px-5 py-8 rounded-[8px] border-[1px] border-customPrimaryColor  hover:bg-[#153B4F33]">
          <img :src="filterIcon" alt="" class="transition-brightness">
          Preferences
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent class="w-56">
        <DropdownMenuLabel>Appearance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem v-model:checked="showStatusBar">
          Status Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showActivityBar" disabled>
          Activity Bar
        </DropdownMenuCheckboxItem>
        <DropdownMenuCheckboxItem v-model:checked="showPanel">
          Panel
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>


  </div>
</template>