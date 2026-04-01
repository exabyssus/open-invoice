<x-filament-widgets::widget>
    <x-filament::section heading="Outstanding">
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 1rem 0;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #f59e0b;">
                {{ $this->getOutstandingAmount() }}
            </div>
            <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 0.5rem;">
                {{ $this->getUnpaidCount() }} unpaid invoices
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
