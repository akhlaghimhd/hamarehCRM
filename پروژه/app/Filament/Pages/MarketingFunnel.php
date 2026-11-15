<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MarketingFunnel extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'قیف فروش';

    protected static ?string $title = 'قیف فروش';

    protected static ?string $slug = 'marketing-funnel';

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی (جدید)';

    protected static ?int $navigationSort = 5;

    /**
     * @var view-string
     */
    protected string $view = 'filament.pages.marketing-funnel';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user || ! ($user->is_active ?? true)) {
            return false;
        }

        if (method_exists($user, 'isCompanyAdmin') && $user->isCompanyAdmin()) {
            return true;
        }

        return method_exists($user, 'hasPermission')
            && $user->hasPermission('opportunities.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $stages = OpportunityStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $all = Opportunity::query()
            ->with(['customer', 'owner'])
            ->latest('updated_at')
            ->get();

        $opportunities = $all
            ->filter(fn (Opportunity $o) => $o->status !== Opportunity::STATUS_LOST && blank($o->lost_at))
            ->groupBy('stage_id');

        $lost = $all
            ->filter(fn (Opportunity $o) => $o->status === Opportunity::STATUS_LOST || filled($o->lost_at))
            ->take(20)
            ->values();

        $columns = [];
        $totalCount = 0;
        $totalAmount = 0;

        foreach ($stages as $stage) {
            $items = $opportunities->get($stage->id, collect());

            if ($stage->is_lost) {
                $items = $lost;
            }

            $count = $items->count();
            $amount = (float) $items->sum(fn ($o) => (float) ($o->amount ?? 0));
            $totalCount += $count;
            $totalAmount += $amount;

            $columns[] = [
                'id' => $stage->id,
                'code' => $stage->code,
                'name' => $stage->name,
                'description' => $stage->description,
                'is_won' => (bool) $stage->is_won,
                'is_lost' => (bool) $stage->is_lost,
                'count' => $count,
                'amount' => $amount,
                'items' => $items->take(12)->map(fn (Opportunity $o) => [
                    'id' => $o->id,
                    'title' => $o->title,
                    'org' => $o->organization_name ?: $o->customer?->name,
                    'contact' => $o->contact_name,
                    'amount' => $o->amount,
                    'owner' => $o->owner?->name,
                    'next_step' => $o->next_step,
                    'due' => optional($o->next_step_due)?->format('Y/m/d'),
                    'url' => OpportunityResource::getUrl('edit', ['record' => $o]),
                ])->values()->all(),
            ];
        }

        $openCount = Opportunity::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', Opportunity::STATUS_ACTIVE)
                    ->orWhere('status', Opportunity::STATUS_PAUSED);
            })
            ->count();

        $dueSoon = Opportunity::query()
            ->whereNotNull('next_step_due')
            ->whereDate('next_step_due', '<=', now()->addDays(3))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereIn('status', [Opportunity::STATUS_ACTIVE, Opportunity::STATUS_PAUSED]);
            })
            ->count();

        return [
            'columns' => $columns,
            'summary' => [
                'total' => $totalCount,
                'amount' => $totalAmount,
                'open' => $openCount,
                'due_soon' => $dueSoon,
            ],
            'createUrl' => OpportunityResource::getUrl('create'),
            'quickCreateUrl' => OpportunityResource::getUrl('quick-create'),
            'listUrl' => OpportunityResource::getUrl('index'),
        ];
    }
}
