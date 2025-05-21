<template>
  <AdminDashboardLayout>
    <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
      <div class="flex items-center justify-between mt-[2rem]">
        <span class="text-[1.5rem] font-semibold">Contact Us Submissions</span>
        <!-- Placeholder for search or other actions -->
      </div>

      <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>#</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Message</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Submitted At</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(submission, index) in submissions.data" :key="submission.id" class="hover:bg-gray-100">
              <TableCell>{{ (submissions.current_page - 1) * submissions.per_page + index + 1 }}</TableCell>
              <TableCell>{{ submission.name }}</TableCell>
              <TableCell>{{ submission.email }}</TableCell>
              <TableCell class="max-w-xs truncate" :title="submission.message">{{ submission.message }}</TableCell>
              <TableCell>
                <Badge :variant="submission.status === 'read' ? 'default' : 'secondary'">
                  {{ submission.status }}
                </Badge>
              </TableCell>
              <TableCell>{{ formatDate(submission.created_at) }}</TableCell>
            </TableRow>
            <TableRow v-if="!submissions.data || submissions.data.length === 0">
              <TableCell colspan="6" class="text-center">No contact submissions found.</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <!-- Pagination -->
        <div v-if="submissions.data && submissions.data.length > 0" class="mt-4 flex justify-end">
          <Pagination :currentPage="submissions.current_page"
                      :totalPages="submissions.last_page"
                      @page-change="handlePageChange" />
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import { defineProps } from 'vue';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import Pagination from "@/Pages/AdminDashboardPages/Vendors/Pagination.vue"; // Reusing Pagination component
import { router } from "@inertiajs/vue3";

const props = defineProps({
  submissions: Object, // Expecting a paginator object
});

const handlePageChange = (page) => {
  router.get(route('contact.mails', { page: page }), {}, {
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

<style scoped>
/* Styles from Vendors/Index.vue for table font sizes */
table th {
  font-size: 0.95rem;
}
table td {
  font-size: 0.875rem;
}
.max-w-xs {
  max-width: 20rem; /* Adjust as needed for message column */
}
</style>
