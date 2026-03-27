<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\Schedule;
use App\Models\Cadets;
use Carbon\Carbon;
use Mary\Traits\Toast;

new #[Layout('layouts.admin')]
class extends Component {
    use Toast;

    // Form Properties (Strictly Typed where applicable)
    public string $type = 'General Outpass';
    public $from_date, $to_date, $from_time, $to_time, $reason;
    public string $target_mode = 'classes'; // Toggle between 'classes' and 'cadets'
    public array $allowed_classes = [];
    public array $allowed_cadets = [];
    public bool $is_active = true;

    // State Properties
    public bool $scheduleModal = false;
    public ?Schedule $selectedSchedule = null;

    // 1. DYNAMIC HEADERS
    public function headers(): array
    {
        return [
            ['key' => 'type', 'label' => 'Type', 'class'=>'w-40'],
            ['key' => 'reason', 'label' => 'Occasion'],
            ['key' => 'timing', 'label' => 'Duration & Timing' ],
            ['key' => 'permitted', 'label' => 'Permitted To'],
            ['key' => 'is_active', 'label' => 'Status', 'class' => 'text-center'],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'text-center'],
        ];
    }

    // 2. COMPUTED DATA SETS
    #[Computed]
    public function schedules()
    {
        return Schedule::latest()->get();
    }

    #[Computed]
    public function availableClasses()
    {
        // Extracts unique class names for the x-choices dropdown
        return Cadets::distinct()->pluck('class')->map(fn($c) => ['id' => $c, 'name' => $c])->toArray();
    }

    #[Computed]
    public function availableCadets()
    {
        // Formats cadets for easy searching: "101 - SANSKAR (XII)"
        return Cadets::where('is_active', true)->orderBy('class')->get()->map(function($cadet) {
            return [
                'id' => (string) $cadet->id, // Cast to string for x-choices strict matching
                'name' => "{$cadet->rollno} - {$cadet->name} ({$cadet->class})"
            ];
        })->toArray();
    }

    // 3. 6 SIGMA SAVE LOGIC
    public function save()
    {
        // Base validation rules
        $rules = [
            'type'        => 'required|string',
            'from_date'   => 'required|date',
            'from_time'   => 'required',
            'to_time'     => 'required',
            'reason'      => 'required|string|min:3',
            'target_mode' => 'required|in:classes,cadets',
            'is_active'   => 'boolean',
        ];

        // Dynamic validation: 'to_date' is only required if NOT a General Outpass
        if ($this->type !== 'General Outpass') {
            $rules['to_date'] = 'required|date|after_or_equal:from_date';
        }

        // Dynamic validation: Check the correct array based on target_mode
        if ($this->target_mode === 'classes') {
            $rules['allowed_classes'] = 'required|array|min:1';
            $this->allowed_cadets = []; // Sanitize: wipe cadets if classes are selected
        } else {
            $rules['allowed_cadets'] = 'required|array|min:1';
            $this->allowed_classes = []; // Sanitize: wipe classes if cadets are selected
        }

        $validated = $this->validate($rules);

        // Sanitize: General Outpass strictly forces the to_date to match from_date in the database
        if ($this->type === 'General Outpass') {
            $validated['to_date'] = $validated['from_date'];
        }

        // Include sanitized arrays explicitly
        $validated['allowed_classes'] = $this->allowed_classes;
        $validated['allowed_cadets'] = $this->allowed_cadets;

        Schedule::updateOrCreate(['id' => $this->selectedSchedule?->id], $validated);

        $this->success('Schedule protocol configured successfully.');
        $this->closeModal();
    }

    // 4. PRECISE EDIT HYDRATION
    public function edit(Schedule $schedule)
    {
        $this->selectedSchedule = $schedule;
        $this->type = $schedule->type;
        $this->from_date = $schedule->from_date;
        $this->to_date = $schedule->to_date;
        $this->from_time = $schedule->from_time;
        $this->to_time = $schedule->to_time;
        $this->reason = $schedule->reason;
        $this->is_active = $schedule->is_active;
        $this->target_mode = $schedule->target_mode ?? 'classes';

        // Strict array coercion to prevent x-choices UI crashes
        $this->allowed_classes = is_array($schedule->allowed_classes) ? $schedule->allowed_classes : [];

        // Ensure cadet IDs are strings for x-choices matching
        $this->allowed_cadets = is_array($schedule->allowed_cadets)
            ? array_map('strval', $schedule->allowed_cadets)
            : [];

        $this->scheduleModal = true;
    }

    public function toggleActive(Schedule $schedule)
    {
        $schedule->update(['is_active' => !$schedule->is_active]);
        $this->success('Schedule status toggled.');
    }

    public function delete(Schedule $schedule)
    {
        $schedule->delete();
        $this->error('Schedule revoked permanently.');
    }

    public function closeModal()
    {
        $this->scheduleModal = false;
        $this->reset(['selectedSchedule', 'from_date', 'to_date', 'from_time', 'to_time', 'reason', 'allowed_classes', 'allowed_cadets']);
        $this->type = 'General Outpass';
        $this->target_mode = 'classes';
        $this->is_active = true;
    }
}; ?>

<div class="p-6 space-y-6">
    {{-- HEADER --}}
    <x-header title="Advanced Schedule Matrix" subtitle="Battalion leave and outpass configuration" separator>
        <x-slot:actions>
            <x-button label="Setup Protocol" icon="o-plus" @click="$wire.scheduleModal = true" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- MAIN DATA TABLE --}}
    <x-card shadow>
        <x-table :headers="$this->headers()" :rows="$this->schedules" striped>

            {{-- 1. Color-Coded Types --}}
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
            {{-- Changed :label to value= --}}
            <x-badge value="{{ $schedule->type }}" class="{{ $badgeColor }} font-bold fs-1" />
        @endscope

            {{-- 2. Smart Timing Column --}}
            @scope('cell_timing', $schedule)
                @if($schedule->type === 'General Outpass')
                    <div class="font-bold text-sm">{{ Carbon::parse($schedule->from_date)->format('d M, Y') }}</div>
                    <div class="text-xs text-gray-500">{{ Carbon::parse($schedule->from_time)->format('h:i A') }} to {{ Carbon::parse($schedule->to_time)->format('h:i A') }}</div>
                @else
                    <div class="font-bold text-sm">{{ Carbon::parse($schedule->from_date)->format('d M') }} to {{ Carbon::parse($schedule->to_date)->format('d M, Y') }}</div>
                    <div class="text-xs text-gray-500">{{ Carbon::parse($schedule->from_time)->format('h:i A') }} - {{ Carbon::parse($schedule->to_time)->format('h:i A') }}</div>
                @endif
            @endscope

            {{-- 3. Smart Permissions Column --}}
           @scope('cell_permitted', $schedule)
                <div class="flex flex-wrap gap-1">
                    @if($schedule->target_mode === 'classes' && !empty($schedule->allowed_classes))
                        @foreach($schedule->allowed_classes as $class)
                            {{-- Changed :label to value= --}}
                            <x-badge value="{{ $class }}" class="badge-primary badge-outline badge-sm" />
                        @endforeach
                    @elseif($schedule->target_mode === 'cadets' && !empty($schedule->allowed_cadets))
                        {{-- Changed :label to value= --}}
                        <x-badge value="{{ count($schedule->allowed_cadets) }} Individual Cadet(s)" class="badge-secondary badge-sm" />
                    @else
                        <span class="text-xs text-error italic font-bold">Unassigned</span>
                    @endif
                </div>
            @endscope

            {{-- 4. Status Toggle --}}
            @scope('cell_is_active', $schedule)
                <x-toggle wire:click="toggleActive({{ $schedule->id }})" :checked="$schedule->is_active" class="toggle-success" tight />
            @endscope

            {{-- 5. Actions --}}
            @scope('cell_actions', $schedule)
                <div class="flex justify-end gap-2">
                    <x-button icon="o-pencil" wire:click="edit({{ $schedule->id }})" class="btn-sm btn-ghost text-info" spinner />
                    <x-button icon="o-trash" wire:click="delete({{ $schedule->id }})" wire:confirm="Permanently revoke this protocol?" class="btn-sm btn-ghost text-error" spinner />
                </div>
            @endscope

        </x-table>
    </x-card>

    {{-- CONFIGURATION MODAL --}}
    <x-modal wire:model="scheduleModal" title="{{ $selectedSchedule ? 'Modify Protocol' : 'Establish Protocol' }}" separator class="backdrop-blur">
        <x-form wire:submit="save">

            <div class="grid grid-cols-2 gap-4">
                <x-select
                    label="Protocol Type"
                    :options="[['id'=>'General Outpass','name'=>'General Outpass'],['id'=>'Night Outpass','name'=>'Night Outpass'],['id'=>'Leave','name'=>'Leave'],['id'=>'Medical Leave','name'=>'Medical Leave'],['id'=>'TD','name'=>'TD (Temporary Duty)']]"
                    wire:model.live="type"
                    icon="o-tag"
                />
                <x-input label="Reason/Occasion" wire:model="reason" icon="o-information-circle" />
            </div>

            {{-- Dynamic Date Inputs: Hides 'To Date' if General Outpass --}}
            <div class="grid grid-cols-2 gap-4">
                <x-input label="{{ $type === 'General Outpass' ? 'Event Date' : 'From Date' }}" type="date" wire:model="from_date" />
                @if($type !== 'General Outpass')
                    <x-input label="To Date" type="date" wire:model="to_date" />
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-input label="Time From" type="time" wire:model="from_time" />
                <x-input label="Time To" type="time" wire:model="to_time" />
            </div>

            <hr class="my-4 border-base-300" />

            {{-- Target Mode Toggle --}}
            <div class="mb-2">
                <x-radio
                    label="Target Audience"
                    :options="[['id'=>'classes','name'=>'Entire Classes'],['id'=>'cadets','name'=>'Specific Cadets']]"
                    wire:model.live="target_mode"
                />
            </div>

            {{-- Dynamic Selectors --}}
            <div class="p-4 bg-base-200/50 rounded-lg">
                @if($target_mode === 'classes')
                    <x-choices
                        label="Select Permitted Classes"
                        wire:model="allowed_classes"
                        :options="$this->availableClasses"
                        allow-all compact icon="o-academic-cap"
                    />
                @else
                    <x-choices
                        label="Select Specific Cadets"
                        wire:model="allowed_cadets"
                        :options="$this->availableCadets"
                        searchable compact icon="o-users"
                        hint="Type Roll No or Name to search"
                    />
                @endif
            </div>

            <x-toggle label="Activate Protocol Immediately" wire:model="is_active" class="toggle-success mt-2" />

            <x-slot:actions>
                <x-button label="Discard" wire:click="closeModal" />
                <x-button label="Enact Schedule" type="submit" icon="o-check" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-toast />
</div>
