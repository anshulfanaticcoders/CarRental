<template>
  <AdminDashboardLayout>
    <div class="container mx-auto p-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight">Contact Us Submissions</h1>
        <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
          Total: {{ submissions?.total || 0 }}
        </div>
      </div>

      <div v-if="submissions?.data?.length" class="rounded-xl border bg-white">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="whitespace-nowrap px-4 py-3 w-[72px]">Sr No.</TableHead>
                <TableHead class="whitespace-nowrap px-4 py-3">Name</TableHead>
                <TableHead class="whitespace-nowrap px-4 py-3">Email</TableHead>
                <TableHead class="whitespace-nowrap px-4 py-3">Message</TableHead>
                <TableHead class="whitespace-nowrap px-4 py-3">Status</TableHead>
                <TableHead class="whitespace-nowrap px-4 py-3">Submitted At</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(submission, index) in submissions.data" :key="submission.id">
                <TableCell class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-700">
                  {{ (submissions.from || 1) + index }}
                </TableCell>
                <TableCell class="whitespace-nowrap px-4 py-3">
                  <div class="text-sm font-medium text-slate-900">{{ submission.name }}</div>
                </TableCell>
                <TableCell class="whitespace-nowrap px-4 py-3">
                  <div class="text-sm">{{ submission.email }}</div>
                </TableCell>
                <TableCell class="px-4 py-3 max-w-md">
                  <div class="text-sm truncate" :title="submission.message">{{ submission.message }}</div>
                </TableCell>
                <TableCell class="whitespace-nowrap px-4 py-3">
                  <Badge :variant="submission.status === 'read' ? 'default' : 'secondary'" class="capitalize">
                    {{ submission.status }}
                  </Badge>
                </TableCell>
                <TableCell class="whitespace-nowrap px-4 py-3">
                  <div class="text-sm">{{ formatDate(submission.created_at) }}</div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
        <div class="flex justify-end pt-4 pr-2">
          <Pagination :current-page="submissions.current_page"
                      :total-pages="submissions.last_page"
                      @page-change="handlePageChange" />
        </div>
      </div>

      <div v-else class="rounded-xl border bg-card p-12 text-center">
        <div class="flex flex-col items-center space-y-4">
          <Mail class="w-16 h-16 text-muted-foreground" />
          <div class="space-y-2">
            <h3 class="text-xl font-semibold text-foreground">No submissions found</h3>
            <p class="text-muted-foreground">No contact form submissions yet.</p>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Mail } from 'lucide-vue-next';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { router } from "@inertiajs/vue3";

defineProps({
  submissions: Object,
});

const handlePageChange = (page) => {
  router.get(route('contact.mails', { page }), {}, {
    preserveState: true,
    replace: true,
  });
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>
