<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Filament\Widgets\PendingInvitationsWidget;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            PendingInvitationsWidget::class,
        ];
    }
}
