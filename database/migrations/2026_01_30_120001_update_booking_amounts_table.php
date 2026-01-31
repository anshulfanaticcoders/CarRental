<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('booking_amounts')) {
            return;
        }

        Schema::table('booking_amounts', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_amounts', 'booking_currency')) {
                $table->char('booking_currency', 3)->nullable()->after('booking_id');
            }
            if (!Schema::hasColumn('booking_amounts', 'booking_total_amount')) {
                $table->decimal('booking_total_amount', 12, 2)->default(0)->after('booking_currency');
            }
            if (!Schema::hasColumn('booking_amounts', 'booking_paid_amount')) {
                $table->decimal('booking_paid_amount', 12, 2)->default(0)->after('booking_total_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'booking_pending_amount')) {
                $table->decimal('booking_pending_amount', 12, 2)->default(0)->after('booking_paid_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'booking_extra_amount')) {
                $table->decimal('booking_extra_amount', 12, 2)->default(0)->after('booking_pending_amount');
            }

            if (!Schema::hasColumn('booking_amounts', 'admin_currency')) {
                $table->char('admin_currency', 3)->default('EUR')->after('booking_extra_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'admin_total_amount')) {
                $table->decimal('admin_total_amount', 12, 2)->default(0)->after('admin_currency');
            }
            if (!Schema::hasColumn('booking_amounts', 'admin_paid_amount')) {
                $table->decimal('admin_paid_amount', 12, 2)->default(0)->after('admin_total_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'admin_pending_amount')) {
                $table->decimal('admin_pending_amount', 12, 2)->default(0)->after('admin_paid_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'admin_extra_amount')) {
                $table->decimal('admin_extra_amount', 12, 2)->default(0)->after('admin_pending_amount');
            }

            if (!Schema::hasColumn('booking_amounts', 'vendor_currency')) {
                $table->char('vendor_currency', 3)->nullable()->after('admin_extra_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'vendor_total_amount')) {
                $table->decimal('vendor_total_amount', 12, 2)->nullable()->after('vendor_currency');
            }
            if (!Schema::hasColumn('booking_amounts', 'vendor_paid_amount')) {
                $table->decimal('vendor_paid_amount', 12, 2)->nullable()->after('vendor_total_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'vendor_pending_amount')) {
                $table->decimal('vendor_pending_amount', 12, 2)->nullable()->after('vendor_paid_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'vendor_extra_amount')) {
                $table->decimal('vendor_extra_amount', 12, 2)->nullable()->after('vendor_pending_amount');
            }

            if (!Schema::hasColumn('booking_amounts', 'booking_to_admin_rate')) {
                $table->decimal('booking_to_admin_rate', 12, 6)->nullable()->after('vendor_extra_amount');
            }
            if (!Schema::hasColumn('booking_amounts', 'booking_to_vendor_rate')) {
                $table->decimal('booking_to_vendor_rate', 12, 6)->nullable()->after('booking_to_admin_rate');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_amounts')) {
            return;
        }

        Schema::table('booking_amounts', function (Blueprint $table) {
            $columns = [
                'booking_currency',
                'booking_total_amount',
                'booking_paid_amount',
                'booking_pending_amount',
                'booking_extra_amount',
                'admin_currency',
                'admin_total_amount',
                'admin_paid_amount',
                'admin_pending_amount',
                'admin_extra_amount',
                'vendor_currency',
                'vendor_total_amount',
                'vendor_paid_amount',
                'vendor_pending_amount',
                'vendor_extra_amount',
                'booking_to_admin_rate',
                'booking_to_vendor_rate',
            ];

            $drop = array_values(array_filter($columns, static function (string $column) {
                return Schema::hasColumn('booking_amounts', $column);
            }));

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
