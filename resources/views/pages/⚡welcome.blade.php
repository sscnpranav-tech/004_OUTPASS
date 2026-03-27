<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\Cadets;
use App\Models\Schedule;
use App\Models\Outpass;
use Carbon\Carbon;
use Mary\Traits\Toast;

new #[Layout('layouts.guest')] // Assuming you have a guest/blank layout for parents
class extends Component {
    use Toast;

    // State Variables
    public int $step = 1;
    public string $searchRollNo = '';
    public ?Cadets $cadet = null;

    // Computed: The verified Cadet's eligible schedules
    #[Computed]
    public function eligibleSchedules()
    {
        if (!$this->cadet) return collect();

        // 6 Sigma Filter: Fetch active schedules and filter strictly by PHP array logic
        return Schedule::where('is_active', true)
            ->orderBy('from_date', 'asc')
            ->get()
            ->filter(function ($schedule) {
                if ($schedule->target_mode === 'classes') {
                    $classes = is_array($schedule->allowed_classes) ? $schedule->allowed_classes : [];
                    return in_array($this->cadet->class, $classes);
                } else {
                    $cadets = is_array($schedule->allowed_cadets) ? $schedule->allowed_cadets : [];
                    // Check against both int and string to guarantee a match
                    return in_array($this->cadet->id, $cadets) || in_array((string)$this->cadet->id, $cadets);
                }
            });
    }

    // Phase 1: Verify Cadet
    public function verifyCadet()
    {
        $this->validate([
            'searchRollNo' => 'required|string|min:1'
        ]);

        $cadet = Cadets::where('rollno', strtoupper(trim($this->searchRollNo)))->first();

        if (!$cadet) {
            $this->error('No cadet found with this Roll Number. Please check and try again.');
            return;
        }

        // Strict Inactive Cadet Trap
        if (!$cadet->is_active) {
            $this->warning(
                'Application Restricted.',
                'Your ward is currently restricted from outpass applications. Please contact the Housemaster for further guidance.',
                position: 'toast-top toast-center',
                timeout: 10000
            );
            return;
        }

        $this->cadet = $cadet;
        $this->step = 2; // Move to Schedule Selection
    }

    // Time-Lock Engine
    public function getWindowStatus(Schedule $schedule): array
    {
        $startDate = Carbon::parse($schedule->from_date);
        $windowOpens = $startDate->copy()->subDays(7)->setTime(9, 0, 0);
        $windowCloses = $startDate->copy()->subDays(1)->setTime(9, 0, 0);
        $now = Carbon::now();
        if ($now->lt($windowOpens)) {
            return [
                'status' => 'upcoming',
                'message' => 'Application opens on ' . $windowOpens->format('d M Y, h:i A'),
                'color' => 'text-warning'
            ];
        }

        if ($now->gt($windowCloses)) {
            return [
                'status' => 'closed',
                'message' => 'Window closed on ' . $windowCloses->format('d M Y, h:i A'),
                'color' => 'text-error'
            ];
        }

        return [
            'status' => 'open',
            'message' => 'Application window closes ' . $windowCloses->format('d M Y, h:i A'),
            'color' => 'text-success'
        ];
    }

    // Final Action: Apply
    public function applyForOutpass($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $window = $this->getWindowStatus($schedule);

        // Security Check: Prevent bypassing UI buttons
        if ($window['status'] !== 'open') {
            $this->error('This application window is currently closed.');
            return;
        }

        // Check if already applied to prevent duplicates
        $exists = Outpass::where('rollno', $this->cadet->rollno)
            ->where('from_date', $schedule->from_date)
            ->where('type', $schedule->type)
            ->exists();

        if ($exists) {
            $this->info('An application for this schedule has already been submitted.');
            return;
        }

        // Create the flattened record matching your migration
        Outpass::create([
            'rollno'    => $this->cadet->rollno,
            'name'      => $this->cadet->name,
            'gender'    => $this->cadet->gender,
            'class'     => $this->cadet->class,
            'house'     => $this->cadet->house,
            'type'      => $schedule->type,
            'from_date' => $schedule->from_date,
            'to_date'   => $schedule->to_date,
            'from_time' => $schedule->from_time,
            'to_time'   => $schedule->to_time,
            'reason'    => $schedule->reason,
            'status'    => 'active'
        ]);

        $this->success('Outpass Application Submitted Successfully!');
        $this->step = 3; // Move to Success Screen
    }

    public function restart()
    {
        $this->reset(['step', 'searchRollNo', 'cadet']);
    }
}; ?>

<div class="min-h-screen bg-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">

        {{-- Header Branding --}}
        <div class="text-center mb-8">
            <x-icon name="o-shield-check" class="w-16 h-16 mx-auto text-primary mb-2" />
            <h2 class="text-3xl font-bold">Cadet Outpass Portal</h2>

            <h1 class="text-gray-500 text-4xl">SAINIK SCHOOL CHANDRAPUR</h1>
        </div>

        {{-- STEP 1: ROLL NO VERIFICATION --}}
        @if($step === 1)
            <x-card shadow class="bg-base-100">
                <x-form wire:submit="verifyCadet">
                    <div class="py-6">
                        <x-input
                            label="Cadet Roll Number"
                            wire:model="searchRollNo"
                            icon="o-identification"
                            placeholder="Roll No..."
                            class="input-lg text-center font-bold tracking-widest"
                            autofocus
                        />
                        <p class="text-xs text-center text-gray-400 mt-2">Enter the assigned institutional Roll Number to view eligible schedules.</p>
                    </div>

                    <x-slot:actions>
                        <x-button label="Verify & Proceed" type="submit" icon="o-arrow-right" class="btn-primary w-full" spinner="verifyCadet" />
                    </x-slot:actions>
                </x-form>
            </x-card>
        @endif

        {{-- STEP 2: SCHEDULE SELECTION --}}
        @if($step === 2)
            <div class="space-y-4">
                {{-- Cadet Identity Badge --}}
                <x-card class="bg-primary text-primary-content">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold">{{ $cadet->name }}</h3>
                            <p class="opacity-80">Roll No: {{ $cadet->rollno }} | Class: {{ $cadet->class }} | {{ $cadet->house }} House</p>
                        </div>
                        <x-button icon="o-arrow-path" class="btn-sm btn-ghost" wire:click="restart" />
                    </div>
                </x-card>

                {{-- Eligible Schedules Loop --}}
                @forelse($this->eligibleSchedules as $schedule)
                    @php $window = $this->getWindowStatus($schedule); @endphp

                    <x-card shadow class="bg-base-100 border-l-4 {{ $window['status'] === 'open' ? 'border-success' : 'border-base-300' }}">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                            {{-- Schedule Details --}}
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <x-badge value="{{ $schedule->type }}" class="badge-neutral font-bold" />
                                    <span class="font-bold text-lg">{{ $schedule->reason }}</span>
                                </div>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <div><x-icon name="o-calendar" class="w-4 h-4 inline" /> {{ \Carbon\Carbon::parse($schedule->from_date)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($schedule->to_date)->format('d M, Y') }}</div>
                                    <div><x-icon name="o-clock" class="w-4 h-4 inline" /> {{ \Carbon\Carbon::parse($schedule->from_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->to_time)->format('h:i A') }}</div>
                                </div>
                            </div>

                            {{-- Dynamic Action Area based on Time Window --}}
                            <div class="text-right w-full md:w-auto p-4 md:p-0 bg-base-200 md:bg-transparent rounded-lg">
                                <p class="text-xs font-bold mb-2 {{ $window['color'] }}">
                                    <x-icon name="o-information-circle" class="w-4 h-4 inline" /> {{ $window['message'] }}
                                </p>

                                @if($window['status'] === 'open')
                                    <x-button
                                        label="Apply Now"
                                        icon="o-paper-airplane"
                                        class="btn-success w-full"
                                        wire:click="applyForOutpass({{ $schedule->id }})"
                                        wire:confirm="Confirm application for {{ $schedule->reason }}?"
                                        spinner
                                    />
                                @elseif($window['status'] === 'upcoming')
                                    <x-button label="Not Yet Open" class="btn-disabled w-full" />
                                @else
                                    <x-button label="Window Closed" class="btn-disabled w-full" />
                                @endif
                            </div>

                        </div>
                    </x-card>
                @empty
                    <x-card shadow class="bg-base-100 text-center py-10">
                        <x-icon name="o-calendar-days" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <h3 class="text-lg font-bold text-gray-500">No Authorized Schedules</h3>
                        <p class="text-sm text-gray-400">There are currently no outpass events scheduled for this cadet's class.</p>
                    </x-card>
                @endforelse
            </div>
        @endif

        {{-- STEP 3: SUCCESS --}}
        @if($step === 3)
            <x-card shadow class="bg-base-100 text-center py-12 border-t-8 border-success">
                <x-icon name="o-check-badge" class="w-24 h-24 mx-auto text-success mb-4" />
                <h2 class="text-2xl font-bold text-success mb-2">Application Secured</h2>
                <p class="text-gray-600 mb-6">The outpass application for <strong>{{ $cadet->name }}</strong> has been successfully registered in the system.</p>
                <x-button label="Return to Home" icon="o-home" class="btn-ghost" wire:click="restart" />
            </x-card>
        @endif

    </div>
    <x-toast />
</div>
