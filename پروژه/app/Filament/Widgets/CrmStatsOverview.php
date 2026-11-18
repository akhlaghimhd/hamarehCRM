<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\Order;
use App\Models\Task;
use App\Support\Fa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CrmStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $userId = Auth::id();

        $openStages = OpportunityStage::query()
            ->where('is_active', true)
            ->where('is_won', false)
            ->where('is_lost', false)
            ->orderBy('sort_order')
            ->get();

        $activeOpps = Opportunity::query()
            ->whereIn('status', [
                Opportunity::STATUS_ACTIVE,
                Opportunity::STATUS_NURTURING,
                Opportunity::STATUS_PAUSED,
            ])
            ->count();

        $stageParts = $openStages->map(function (OpportunityStage $stage) {
            $count = Opportunity::query()
                ->where('stage_id', $stage->id)
                ->whereIn('status', [
                    Opportunity::STATUS_ACTIVE,
                    Opportunity::STATUS_NURTURING,
                    Opportunity::STATUS_PAUSED,
                ])
                ->count();

            return $stage->name.': '.Fa::digits($count);
        })->take(6)->implode(' · ');

        $myOpenTasks = Task::query()
            ->whereIn('status', [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS])
            ->when($userId, fn ($q) => $q->where('assignee_user_id', $userId))
            ->count();

        $overdueTasks = Task::query()
            ->whereIn('status', [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->when($userId, fn ($q) => $q->where('assignee_user_id', $userId))
            ->count();

        $openOrders = Order::query()
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_FULFILLMENT])
            ->count();

        return [
            Stat::make('فرصت‌های باز', Fa::digits($activeOpps))
                ->description($stageParts !== '' ? $stageParts : 'مرحله‌ای تعریف نشده')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary'),
            Stat::make('کارهای باز من', Fa::digits($myOpenTasks))
                ->description($overdueTasks > 0 ? 'سررسید گذشته: '.Fa::digits($overdueTasks) : 'بدون سررسید گذشته')
                ->descriptionIcon($overdueTasks > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),
            Stat::make('سفارش‌های باز', Fa::digits($openOrders))
                ->description('تأیید شده یا در حال انجام')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
        ];
    }
}
