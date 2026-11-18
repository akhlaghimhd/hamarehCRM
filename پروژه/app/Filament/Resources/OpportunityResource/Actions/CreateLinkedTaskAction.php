<?php

namespace App\Filament\Resources\OpportunityResource\Actions;

use App\Filament\Actions\CreateLinkedTaskAction as SharedCreateLinkedTaskAction;
use App\Models\Opportunity;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class CreateLinkedTaskAction
{
    public static function make(Page $page): Action
    {
        return SharedCreateLinkedTaskAction::make(
            $page,
            Opportunity::class,
            modalHeading: 'ایجاد کار وابسته به این فرصت',
            subjectDefault: fn (Opportunity $record) => 'پیگیری: '.($record->title ?: $record->organization_name),
            idempotencyPrefix: 'opp',
            actionName: 'createTask',
        );
    }
}
