<x-filament::page>

    {{-- Banner --}}
    <div class="flex justify-center mb-6">
        <img
            src="{{ asset('images/banner_talentIA.png') }}"
            alt="TalentIA"
            class="h-36 w-auto rounded-xl shadow-lg object-contain"
        />
    </div>

    {{-- Widgets --}}
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />

</x-filament::page>