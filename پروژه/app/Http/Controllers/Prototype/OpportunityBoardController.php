<?php

namespace App\Http\Controllers\Prototype;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Support\Fa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityBoardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        return view('prototypes.opportunities.index', [
            'dataUrl' => route('prototype.opportunities.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $this->authorizeAccess($request);
        $query = Opportunity::query()
            ->with(['stage:id,name', 'owner:id,name', 'followupUser:id,name'])
            ->latest('updated_at');

        if (! $user->isCompanyAdmin()) {
            $visibleIds = $user->managedUserIds(includeSelf: true)->all();
            $query->where(fn ($builder) => $builder
                ->whereIn('owner_user_id', $visibleIds)
                ->orWhereIn('followup_user_id', $visibleIds));
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', Fa::toEnglishDigits($search)) ?? '';
            $query->where(function ($builder) use ($search, $digits): void {
                $builder->where('organization_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_name', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%');
                if ($digits !== '') {
                    $builder->orWhere('id', (int) $digits);
                }
            });
        }

        $status = (string) $request->query('status', '');
        if (array_key_exists($status, Opportunity::statusOptions())) {
            $query->where('status', $status);
        }

        $records = $query->paginate(12)->through(fn (Opportunity $record): array => [
            'id' => $record->id,
            'code' => Fa::digits('OPP-'.str_pad((string) $record->id, 3, '0', STR_PAD_LEFT)),
            'title' => $record->title,
            'organization' => $record->organization_name ?: '—',
            'contact' => $record->contact_name ?: '—',
            'mobile' => $record->mobile ?: '—',
            'product' => $record->productInterestLabel(),
            'status' => $record->status,
            'status_label' => Opportunity::statusOptions()[$record->status] ?? $record->status,
            'stage' => $record->stage?->name ?? '—',
            'next_step' => $record->next_step ?: '—',
            'next_step_due' => $record->next_step_due ? Fa::dateTime($record->next_step_due, 'Y/m/d') : '—',
            'owner' => $record->owner?->name ?? '—',
            'followup' => $record->followupUser?->name ?? '—',
            'amount' => $record->amount ? Fa::digits(number_format((float) $record->amount, 0)) : '—',
        ]);

        return response()->json([
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total' => Fa::digits((string) $records->total()),
                'from' => $records->firstItem() ? Fa::digits((string) $records->firstItem()) : '۰',
                'to' => $records->lastItem() ? Fa::digits((string) $records->lastItem()) : '۰',
            ],
        ]);
    }

    private function authorizeAccess(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 403);
        abort_unless($user->currentCompanyId(), 403, 'ابتدا یک شرکت فعال انتخاب کنید.');
        abort_unless($user->hasPermission('opportunities.view'), 403);

        return $user;
    }
}
