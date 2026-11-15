<?php

namespace App\\Filament\\Resources\\TaskResource\\Pages;

use App\\Actions\\Tasks\\SaveTask;
use App\\Filament\\Resources\\TaskResource;
use App\\Models\\Task;
use Filament\\Resources\\Pages\\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordCreation(array $data): Task
    {
        return app(SaveTask::class)->handle($data, auth()->user());
    }
}
