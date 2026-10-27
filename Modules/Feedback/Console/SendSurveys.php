<?php

namespace Modules\Feedback\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Events\SurveyInvitationDue;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;
use Modules\Sales\Enums\OrderStatus;
use Modules\Tenancy\Models\Store;
use Throwable;

/**
 * Sends the invitation for Surveys that are due: delivered at least the survey delay ago, not yet sent,
 * and whose Order was not refunded or cancelled. Customers without an email are left to the link a Vendor
 * or Admin can copy. One failure never stops the rest, and a second run sends nothing twice.
 */
class SendSurveys extends Command
{
    protected $signature = 'feedback:send-surveys';

    protected $description = 'Email the satisfaction survey for Orders delivered at least the survey delay ago';

    public function handle(StoreContext $context, PlatformSettings $settings, SurveyLinks $links): int
    {
        $cutoff = now()->subDays($settings->surveyDelayDays());
        $sent = 0;
        $failed = 0;

        foreach (Store::query()->pluck('id') as $storeId) {
            $context->runAs($storeId, function () use ($cutoff, $links, &$sent, &$failed): void {
                $due = Survey::query()
                    ->whereNull('sent_at')
                    ->whereNull('submitted_at')
                    ->whereHas('order', fn ($order) => $order
                        ->where('delivered_at', '<=', $cutoff)
                        ->whereNotIn('status', [OrderStatus::Refunded->value, OrderStatus::Cancelled->value])
                        ->whereHas('customer', fn ($customer) => $customer->whereNotNull('email')))
                    ->get();

                foreach ($due as $survey) {
                    try {
                        $sent += $this->send($survey, $links) ? 1 : 0;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });
        }

        $this->components->info("Sent {$sent} survey invitation(s).".($failed ? " {$failed} failed." : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function send(Survey $survey, SurveyLinks $links): bool
    {
        return DB::transaction(function () use ($survey, $links): bool {
            $locked = Survey::query()->lockForUpdate()->findOrFail($survey->getKey());

            if ($locked->sent_at !== null) {
                return false;
            }

            $locked->update(['sent_at' => now()]);
            SurveyInvitationDue::dispatch($locked, $links->show($locked));

            return true;
        });
    }
}
