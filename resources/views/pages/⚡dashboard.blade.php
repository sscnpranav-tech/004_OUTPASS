<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\Schedule;
use App\Models\Outpass;
use Carbon\Carbon;

new #[Layout('layouts.admin')]
class extends Component {

    public bool $reportModal = false;
    public ?Schedule $selectedSchedule = null;

    #[Computed]
    public function schedules()
    {
        return Schedule::orderBy('from_date', 'desc')->get();
    }

    #[Computed]
    public function currentOutpasses()
    {
        if (!$this->selectedSchedule) return collect();

        return Outpass::where('type', $this->selectedSchedule->type)
            ->where('from_date', $this->selectedSchedule->from_date)
            ->where('status', 'active')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $data = $this->currentOutpasses;

        return [
            'total'      => $data->count(),
            'boys'       => $data->where('gender', 'male')->count(),
            'girls'      => $data->where('gender', 'female')->count(),
            'house_wise' => $data->where('gender', 'male')->groupBy('house')->map->count()->toArray(),
        ];
    }

    public function openReports(Schedule $schedule)
    {
        $this->selectedSchedule = $schedule;
        $this->reportModal = true;
    }
}; ?>

<div class="p-6 md:p-10 space-y-8 max-w-7xl mx-auto">

    {{-- AESTHETIC HEADER --}}
    <x-header separator progress-indicator>
        <x-slot:title>
            <div class="flex items-center gap-3">
                <x-icon name="o-rocket-launch" class="w-8 h-8 text-primary" />
                <span class="font-black tracking-tight">Command Center</span>
            </div>
        </x-slot:title>
        <x-slot:subtitle>
            <div class="text-gray-500 mt-1">Real-time outpass analytics and official document rendering engine.</div>
        </x-slot:subtitle>
    </x-header>

    {{-- MAIN SCHEDULE LISTING --}}
    <x-card shadow class="border-t-4 border-t-primary rounded-xl overflow-hidden bg-base-100">
        <x-table :rows="$this->schedules" :headers="[['key'=>'type','label'=>'Protocol Type'],['key'=>'reason','label'=>'Occasion / Reason'],['key'=>'from_date','label'=>'Start Date'],['key'=>'actions','label'=>'','class'=>'text-right']]" striped class="w-full">

            @scope('cell_type', $schedule)
                @php
                    $badgeColor = match($schedule->type) {
                        'General Outpass' => 'badge-info',
                        'Night Outpass'   => 'badge-warning',
                        'Leave'           => 'badge-primary',
                        'Medical Leave'   => 'badge-error',
                        'TD'              => 'badge-success',
                        default           => 'badge-neutral',
                    };
                @endphp
                <x-badge value="{{ $schedule->type }}" class="{{ $badgeColor }} font-bold badge-outline shadow-sm" />
            @endscope

            @scope('cell_reason', $schedule)
                <span class="font-bold text-base-content">{{ $schedule->reason }}</span>
            @endscope

            @scope('cell_from_date', $schedule)
                <div class="flex items-center gap-2 text-gray-600">
                    <x-icon name="o-calendar" class="w-4 h-4 opacity-70" />
                    <span class="font-bold">{{ Carbon::parse($schedule->from_date)->format('d M, Y') }}</span>
                </div>
            @endscope

            @scope('actions', $schedule)
                <x-button
                    label="View Analytics"
                    icon="o-presentation-chart-line"
                    wire:click="openReports({{ $schedule->id }})"
                    class="btn-sm btn-primary btn-outline hover:shadow-md transition-all duration-300"
                    spinner
                />

            @endscope

        </x-table>
    </x-card>

    {{-- THE ANALYTICS & DOCUMENT DRAWER (Aesthetic Overhaul) --}}
    <x-drawer wire:model="reportModal" right separator with-close-button class="w-11/12 lg:w-[800px] bg-base-50">
        @if($selectedSchedule)

            {{-- Drawer Hero Section --}}
            <div class="bg-gradient-to-br from-base-200 to-base-300 p-8 rounded-2xl shadow-inner mb-8 border border-base-300">
                <x-badge value="{{ $selectedSchedule->type }}" class="badge-neutral font-bold mb-3 shadow-sm" />
                <h2 class="text-3xl font-black text-base-content tracking-tight leading-tight">{{ $selectedSchedule->reason }}</h2>
                <div class="flex items-center gap-2 mt-3 text-gray-500 font-medium">
                    <x-icon name="o-clock" class="w-5 h-5" />
                    <span>{{ Carbon::parse($selectedSchedule->from_date)->format('l, d F Y') }}</span>
                </div>
            </div>

            @if($this->stats['total'] === 0)
                {{-- Beautiful Empty State --}}
                <div class="flex flex-col items-center justify-center p-12 text-center bg-white rounded-2xl border border-dashed border-gray-300 shadow-sm">
                    <div class="bg-gray-100 p-4 rounded-full mb-4">
                        <x-icon name="o-document-magnifying-glass" class="w-12 h-12 text-gray-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-700">Awaiting Applications</h3>
                    <p class="text-gray-500 mt-2 max-w-sm">Parents have not yet submitted any applications for this specific outpass schedule.</p>
                </div>
            @else

                {{-- Premium Stat Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <x-stat
                        title="Total Authorized"
                        value="{{ $this->stats['total'] }}"
                        icon="o-users"
                        class="bg-gradient-to-br from-primary to-primary-focus text-primary-content rounded-2xl shadow-lg border-none"
                    />
                    <x-stat
                        title="Male Cadets"
                        value="{{ $this->stats['boys'] }}"
                        icon="m-user"
                        class="bg-gradient-to-br from-info to-blue-600 text-white rounded-2xl shadow-md border-none"
                    />
                    <x-stat
                        title="Female Cadets"
                        value="{{ $this->stats['girls'] }}"
                        icon="m-user"
                        class="bg-gradient-to-br from-rose-400 to-rose-600 text-white rounded-2xl shadow-md border-none"
                    />

                </div>

                {{-- Document Generation Zones --}}
                <div class="space-y-6">

                    {{-- Primary Documents Card --}}
                    {{-- Primary Documents Card --}}
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
    <h3 class="text-sm font-black uppercase tracking-widest text-gray-400 mb-4 flex items-center gap-2">
        <x-icon name="o-document-duplicate" class="w-4 h-4" /> Core Documents
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-button
            label="Official Notesheet"
            icon="o-document-text"
            class="btn-primary w-full justify-start shadow-sm hover:shadow-md transition-all"
            link="{{ route('admin.outpass.document', ['schedule_id' => $this->selectedSchedule->id, 'document_type' => 'notesheet']) }}"
            external
        />
        <x-button
            label="Master List (All)"
            icon="o-clipboard-document-list"
            class="btn-outline border-gray-300 w-full justify-start hover:bg-gray-50 transition-all text-gray-700"
            link="{{ route('admin.outpass.document', ['schedule_id' => $this->selectedSchedule->id, 'document_type' => 'master']) }}"
            external
        />
        <x-button
            label="All Girls Roster"
            icon="o-sparkles"
            class="btn-error btn-outline w-full justify-start hover:shadow-md transition-all"
            link="{{ route('admin.outpass.document', ['schedule_id' => $this->selectedSchedule->id, 'document_type' => 'girls']) }}"
            external
        />
        {{-- The newly added Individual Slips Button --}}
        <x-button
            label="Individual Outpass Slips"
            icon="o-identification"
            class="btn-outline btn-accent w-full justify-start hover:shadow-md transition-all"
            link="{{ route('admin.outpass.document', ['schedule_id' => $this->selectedSchedule->id, 'document_type' => 'slips']) }}"
            external
        />
    </div>
</div>

                    {{-- House-wise Breakdown Card --}}
                    <div class="bg-base-200 p-6 rounded-2xl shadow-inner border border-base-300">
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-500 mb-4 flex items-center gap-2">
                            <x-icon name="o-home-modern" class="w-4 h-4" /> House-wise Boys Manifests
                        </h3>

                        @if(empty($this->stats['house_wise']))
                            <div class="text-sm italic text-gray-400">No male applications registered yet.</div>
                        @else
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($this->stats['house_wise'] as $house => $count)
                                    <x-button
                                        label="{{ $house }} ({{ $count }})"
                                        icon="o-arrow-down-tray"
                                        class="btn-info text-white shadow-sm hover:shadow-md transition-all justify-start text-xs sm:text-sm"
                                        link="{{ route('admin.outpass.document', ['schedule_id' => $selectedSchedule->id, 'document_type' => 'house-boys', 'house' => $house]) }}"
                                        external
                                    />
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

            @endif
        @endif
    </x-drawer>
</div>
