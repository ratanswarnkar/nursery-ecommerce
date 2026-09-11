<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Audit\AuditLogger;
use App\Services\Order\OrderLifecycleService;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireUnpaidOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-unpaid
                            {--minutes= : Explicit expiry threshold in minutes}
                            {--limit=100 : Maximum number of orders to process in this run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire unpaid abandoned orders and safely release locked inventory';

    /**
     * Execute the console command.
     */
    public function handle(
        OrderLifecycleService $lifecycleService,
        PaymentService $paymentService,
        AuditLogger $auditLogger
    ): int {
        $minutesOption = $this->option('minutes');
        $minutes = $minutesOption !== null && $minutesOption !== ''
            ? (int) $minutesOption
            : config('ecommerce.orders.unpaid_expiry_minutes');

        if ($minutes === null || $minutes <= 0) {
            $this->warn('Unpaid order expiration is not active: no expiry threshold configured.');
            $this->line('Set UNPAID_ORDER_EXPIRY_MINUTES in your environment or pass --minutes=<minutes>.');

            return Command::SUCCESS;
        }

        $limit = max(1, (int) ($this->option('limit') ?: 100));
        $cutoff = now()->subMinutes($minutes);

        // Find candidate orders in PENDING status, unpaid, older than cutoff
        $candidateOrders = Order::query()
            ->where('status', OrderStatus::PENDING)
            ->where('payment_status', '!=', PaymentStatus::PAID)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('paymentTransactions', function ($q) {
                $q->where('status', PaymentStatus::PAID);
            })
            ->limit($limit)
            ->get();

        if ($candidateOrders->isEmpty()) {
            $this->info("No unpaid orders eligible for expiration (threshold: {$minutes} minutes).");

            return Command::SUCCESS;
        }

        $expiredCount = 0;
        $failedCount = 0;

        foreach ($candidateOrders as $candidate) {
            try {
                $processed = DB::transaction(function () use ($candidate, $minutes, $lifecycleService, $paymentService) {
                    /** @var Order|null $lockedOrder */
                    $lockedOrder = Order::where('id', $candidate->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $lockedOrder) {
                        return false;
                    }

                    // Strict state validation inside pessimistic lock
                    if ($lockedOrder->status !== OrderStatus::PENDING || $lockedOrder->payment_status === PaymentStatus::PAID) {
                        return false;
                    }

                    // Check if any payment was captured while waiting for lock
                    $hasPaidTxn = $lockedOrder->paymentTransactions()
                        ->where('status', PaymentStatus::PAID)
                        ->lockForUpdate()
                        ->exists();

                    if ($hasPaidTxn) {
                        return false;
                    }

                    // Expire any pending payment transactions
                    $pendingTxns = $lockedOrder->paymentTransactions()
                        ->where('status', PaymentStatus::PENDING)
                        ->lockForUpdate()
                        ->get();

                    foreach ($pendingTxns as $txn) {
                        $paymentService->expirePayment($txn, "Unpaid order automatically expired after {$minutes} minutes of inactivity.");
                    }

                    // Transition order to CANCELLED and release inventory safely via OrderLifecycleService
                    $lifecycleService->cancelOrder(
                        $lockedOrder,
                        "Unpaid order automatically expired after {$minutes} minutes of inactivity."
                    );

                    return true;
                });

                if ($processed) {
                    $expiredCount++;
                }
            } catch (Throwable $e) {
                $failedCount++;
                $this->error("Failed to expire order #{$candidate->order_number}: {$e->getMessage()}");
            }
        }

        if ($expiredCount > 0) {
            $auditLogger->logSecurityEvent('orders.expired_unpaid_batch', [
                'expired_count' => $expiredCount,
                'threshold_minutes' => $minutes,
                'failed_count' => $failedCount,
            ]);
        }

        $this->info("Successfully expired {$expiredCount} unpaid order(s) (threshold: {$minutes} minutes).");

        return Command::SUCCESS;
    }
}
