<?php
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Cadets;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

new #[Livewire\Attributes\Layout('layouts.admin')]
class extends Component {
    use Toast, WithFileUploads;

    public $search = '';
    public $csvFile;
    public bool $uploadModal = false;
    public array $importStats = ['new' => 0, 'skipped' => 0];

    // Bulk Upload Logic
    public function importCsv() {
        $this->validate(['csvFile' => 'required|mimes:csv,txt|max:2048']);
        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');

        $header = fgetcsv($file); // Skip header row
        $new = 0; $skipped = 0;

        DB::transaction(function () use ($file, &$new, &$skipped) {
            while (($row = fgetcsv($file)) !== false) {
                // 1. Sanitize and Normalize Data
                $rollno = trim($row[0]);
                $name   = trim($row[1]);
                $class  = trim($row[2]);
                $house  = trim($row[3]);

                // Force lowercase to satisfy the database ENUM check constraint
                $genderInput = strtolower(trim($row[4]));
                $gender = in_array($genderInput, ['male', 'female']) ? $genderInput : 'male';

                // 2. Check for existence to prevent duplicates
                $exists = Cadets::where('rollno', $rollno)->exists();

                if (!$exists) {
                    // 3. Insert using the sanitized $gender variable
                    Cadets::create([
                        'rollno'    => $rollno,
                        'name'      => $name,
                        'class'     => $class,
                        'house'     => $house,
                        'gender'    => $gender, // Use the lowercase variable here
                        'is_active' => true,
                    ]);
                    $new++;
                } else {
                    $skipped++;
                }
            }
        });

        fclose($file);
        $this->importStats = ['new' => $new, 'skipped' => $skipped];
        $this->uploadModal = false;
        $this->success("Import Complete: $new Added, $skipped Skipped.");
    }

    public function downloadSample() {
        $headers = ['rollno', 'name', 'class', 'house', 'gender'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['C-101', 'John Doe', '10th', 'Spartans', 'male']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=cadets_sample.csv",
        ]);
    }

    public function toggleStatus(Cadets $cadet) {
        $cadet->is_active = ($cadet->is_active === 1) ? 0 : 1;
        $cadet->save();
        $this->success("Status updated for $cadet->name");
    }

    public function getActiveProperty() {
        return \App\Models\Cadets::where('is_active', 1)
            ->where('name', 'like', "%{$this->search}%")->latest()->paginate(10);
    }

    public function getInactiveProperty() {
        return \App\Models\Cadets::where('is_active', 0)->latest()->paginate(10);
    }

}; ?>

<div class="p-6 space-y-8">
    {{-- Header with Search and Bulk Action --}}
    <x-header title="Cadet Directory" subtitle="Precision Management System" separator>
        <x-slot:middle class="!justify-end">
            <x-input icon="o-magnifying-glass" placeholder="Search active..." wire:model.live.debounce="search" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Bulk Upload" icon="o-cloud-arrow-up" @click="$wire.uploadModal = true" class="btn-outline btn-primary" />
            <x-button label="Add Cadet" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>


    {{-- <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat title="Total Active" value="{{ count($this->active) }}" icon="o-users" color="text-primary" />
        <x-stat title="Last Import" value="+{{ $importStats['new'] }}" description="Skipped: {{ $importStats['skipped'] }}" icon="o-arrow-path" />
    </div> --}}

    {{-- Main Active Table --}}
    <x-card title="Active Cadets" subtitle="Currently deployed cadets" class="bg-base-200 shadow-lg p-10 rounded-xl" separator>
        <x-table :rows="$this->active" :headers="[
            ['key'=>'rollno','label'=>'Roll'],
            ['key'=>'name','label'=>'Name'],
            ['key'=>'class','label'=>'Class'],
            ['key'=>'house','label'=>'House'],
            ['key'=>'actions','label'=>'Toggle Status','class'=>'text-center']
            ]" striped with-pagination>
            @scope('cell_actions', $cadet)
                <x-button icon="o-power" wire:click="toggleStatus({{ $cadet->id }})" class="btn-sm text-error btn-ghost" tooltip="Deactivate" />
                    {{-- <x-button icon="o-pencil" class="btn-sm btn-ghost text-info" /> --}}

            @endscope
        </x-table>
    </x-card>

    {{-- Inactive Section at Bottom --}}
    <x-card title="Inactive / Gated Cadets" subtitle="Cadets not currently in service" class="bg-base-200 shadow-lg p-10 rounded-xl" separator>
        <x-table :rows="$this->inactive" :headers="[
            ['key'=>'rollno','label'=>'Roll'],
            ['key'=>'name','label'=>'Name'],
            ['key'=>'class','label'=>'Class'],
            ['key'=>'house','label'=>'House'],
            ['key'=>'actions','label'=>'Actions','class'=>'text-center']]" with-pagination>
            @scope('cell_actions', $cadet)
                <x-button label="Restore" icon="o-arrow-up-circle" wire:click="toggleStatus({{ $cadet->id }})" class="btn-sm btn-success btn-outline" />
            @endscope
        </x-table>
        @if(count($this->inactive) == 0)
            <div class="text-center py-4 text-gray-400 italic">No inactive cadets found.</div>
        @endif
    </x-card>

    {{-- Bulk Upload Modal Wizard --}}
    <x-modal wire:model="uploadModal" title="Bulk Cadet Upload" separator>
        <div class="space-y-4">
            <div class="p-4 bg-info/10 border border-info/20 rounded-lg text-sm flex justify-between items-center">
                <span>Please use our standardized CSV format to ensure 6 Sigma accuracy.</span>
                <x-button label="Sample.csv" icon="o-arrow-down-tray" wire:click="downloadSample" class="btn-xs btn-ghost" />
            </div>

            <x-file wire:model="csvFile" label="Select CSV File" accept=".csv" />

            <div wire:loading wire:target="csvFile" class="text-primary text-xs italic">Uploading to server...</div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.uploadModal = false" />
            <x-button label="Start Import" icon="o-check" wire:click="importCsv" class="btn-primary" spinner="importCsv" />
        </x-slot:actions>
    </x-modal>

    <x-toast />
</div>
