<?php

namespace App\\Filament\\Resources\\TaskResource\\Pages;

use App\\Actions\\Tasks\\SaveTask;
use App\\Filament\\Resources\\TaskResource;
use App\\Models\\Task;
use Filament\\Resources\\Pages\\EditRecord;
use Illuminate\\Database\\Eloquent\\Model;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Task
    {
        return app(SaveTask::class)->handle($data, auth()->user(), $record);
    }
}
