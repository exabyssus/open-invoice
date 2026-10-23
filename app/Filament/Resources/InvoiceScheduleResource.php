<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceScheduleResource\Pages;
use App\Models\Client;
use App\Models\InvoiceSchedule;
use App\ScheduleFrequency;
use App\Services\RunInvoiceSchedule;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceScheduleResource extends Resource
{
    protected static ?string $model = InvoiceSchedule::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $yearly = fn (Get $get) => $get('frequency') === ScheduleFrequency::YEARLY->value;

        return $schema
            ->columns(2)
            ->schema([
                Select::make('client_id')
                    ->label(__('resources.invoice.client'))
                    ->options(fn () => Client::query()->pluck('title', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('frequency')
                    ->label(__('resources.schedule.frequency'))
                    ->options(ScheduleFrequency::asOptions())
                    ->default(ScheduleFrequency::MONTHLY->value)
                    ->live()
                    ->required(),
                Select::make('month')
                    ->label(__('resources.schedule.month'))
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m, 1)->translatedFormat('F')]))
                    ->visible($yearly)
                    ->required($yearly),
                TextInput::make('day')
                    ->label(__('resources.schedule.day'))
                    ->helperText(__('resources.schedule.day_hint'))
                    ->numeric()->integer()->minValue(1)->maxValue(31)
                    ->required(),
                TextInput::make('due_days')
                    ->label(__('resources.schedule.due_days'))
                    ->helperText(__('resources.schedule.due_days_hint'))
                    ->numeric()->integer()->minValue(0)->maxValue(365),
                TextInput::make('comment')
                    ->label(__('resources.invoice.comment')),
                Toggle::make('auto_send')
                    ->label(__('resources.schedule.auto_send'))
                    ->helperText(__('resources.schedule.auto_send_hint'))
                    ->rules([fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                        if ($value && ! Client::find($get('client_id'))?->email) {
                            $fail(__('resources.schedule.auto_send_needs_email'));
                        }
                    }]),
                Toggle::make('is_active')
                    ->label(__('resources.schedule.is_active'))
                    ->default(true),
                InvoiceResource::linesRepeater()->minItems(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('next_run_on')
            ->columns([
                Tables\Columns\TextColumn::make('client.title')->label(__('resources.invoice.client')),
                Tables\Columns\TextColumn::make('frequency')
                    ->label(__('resources.schedule.frequency'))
                    ->state(fn (InvoiceSchedule $s) => $s->frequency === ScheduleFrequency::YEARLY
                        ? __('resources.schedule.yearly_on', ['day' => $s->day, 'month' => Carbon::create(null, $s->month, 1)->translatedFormat('F')])
                        : __('resources.schedule.monthly_on', ['day' => $s->day])),
                Tables\Columns\TextColumn::make('next_run_on')
                    ->label(__('resources.schedule.next_run_on'))
                    ->date('Y.m.d'),
                Tables\Columns\IconColumn::make('auto_send')
                    ->label(__('resources.schedule.auto_send'))
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\ToggleColumn::make('is_active')->label(__('resources.schedule.is_active')),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('runNow')
                    ->icon('heroicon-o-play')
                    ->label(__('resources.schedule.run_now'))
                    ->requiresConfirmation()
                    ->modalDescription(fn (InvoiceSchedule $record) => $record->auto_send ? __('resources.schedule.run_now_sends') : null)
                    ->action(function (InvoiceSchedule $record) {
                        $invoice = app(RunInvoiceSchedule::class)($record, advance: false);

                        Notification::make()
                            ->title($invoice ? __('resources.schedule.created', ['number' => $invoice->invoice_number]) : __('resources.schedule.nothing_made'))
                            ->{$invoice ? 'success' : 'warning'}()
                            ->send();
                    }),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceSchedules::route('/'),
            'create' => Pages\CreateInvoiceSchedule::route('/create'),
            'edit' => Pages\EditInvoiceSchedule::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('pages.schedules.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages.schedules.plural');
    }
}
