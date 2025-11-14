<?php

namespace App\Filament\Owner\Resources\Reviews\Pages;

use App\Filament\Owner\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;
}
