<?php
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Mary\Traits\Toast;

new #[Layout('layouts.admin')]
class extends Component {
    use Toast;
    public $name, $username, $email, $password, $users, $headers = [];
    public $selected_id;
    public bool $EditModal = false;

    public function mount() {
        $this->refreshUsers();
        $this->headers = [
    ['key' => 'iteration', 'label' => '#', 'class' => 'w-1'],
    ['key' => 'name', 'label' => 'Name'],
    ['key' => 'username', 'label' => 'Username'],
    ['key' => 'email', 'label' => 'Email'],
    ['key' => 'status', 'label' => 'Status', 'class'=>"text-center"], // New Status Column
    ['key' => 'actions', 'label' => 'Actions', 'class'=>"text-center"],
];
    }

    public function refreshUsers() { $this->users = User::all(); }

    public function edit(User $user) {
        $this->selected_id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->password = '';
        $this->EditModal = true;
    }

    public function delete($id) {
        User::find($id)->delete();
        $this->refreshUsers();
        $this->success('User deleted successfully.', position: 'toast-bottom toast-end');
    }

    public function save() {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $this->selected_id,
            'username' => 'required|unique:users,username,' . $this->selected_id,
        ]);
        $user = User::findOrFail($this->selected_id);
        $user->update([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
        ]);
        if ($this->password) { $user->update(['password' => Hash::make($this->password)]); }
        $this->EditModal = false;
        $this->refreshUsers();
        $this->success('User updated successfully.', position: 'toast-bottom toast-end', icon: 'o-check-circle');
    }
    public function toggleStatus(User $user) {
    $user->update(['is_active' => !$user->is_active]);
    $this->refreshUsers();
    $this->success('Status updated to ' . ($user->is_active ? 'Active' : 'Inactive'));
}
}; ?>

<div>
    <x-table :headers="$headers" :rows="$users" striped>
    {{-- Iteration --}}
    @scope('cell_iteration', $user, $loop)
        {{ $loop->iteration }}
    @endscope

    {{-- Status Toggle: Matches 'status' key in headers --}}
    @scope('cell_status', $user)
        <div class="flex justify-center items-center w-full">
            <x-toggle wire:click="toggleStatus({{ $user->id }})" :checked="$user->is_active" class="{{ $user->is_active ? 'toggle-success' : 'toggle-error' }}" tight/>
            <span class="ml-2 text-xs font-semibold {{ $user->is_active ? 'text-success' : 'text-error' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
    @endscope

    {{-- Actions --}}
    @scope('cell_actions', $user)
        <div class="flex justify-center items-center gap-2 w-full">
            <x-button icon="o-pencil" wire:click="edit({{ $user->id }})" spinner class="btn-sm btn-ghost text-info" inline />
            <x-button icon="o-trash" wire:click="delete({{ $user->id }})" spinner class="btn-sm btn-ghost text-error" wire:confirm="Are you sure?" inline />
        </div>
    @endscope
</x-table>

    <x-modal wire:model="EditModal" title="Edit User" subtitle="User Details Can be Updated..." separator class="rounded-xl">
        <x-form wire:submit="save">
            <x-input label="Name" icon="o-user" wire:model="name" />
            <x-input label="Username" icon="o-users" wire:model="username" />
            <x-input label="Email" icon="o-envelope" wire:model="email" />
            <x-input label="Password" type="password" icon="o-key" wire:model="password" placeholder="Leave blank to keep current" />
            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.EditModal = false" />
                <x-button label="Update User" type="submit" icon="o-check" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- The Toast Component (Make sure this is in your layout or here) --}}
    <x-toast />
</div>
