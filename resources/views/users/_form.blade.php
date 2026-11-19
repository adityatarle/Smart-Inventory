<form method="POST" action="{{ $action }}" class="card max-w-xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div>
        <label class="label">Name</label>
        <input name="name" class="input" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div>
        <label class="label">Email</label>
        <input type="email" name="email" class="input" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div>
        <label class="label">Role</label>
        <select name="role" class="input" required>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? 'customer') === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">{{ $user ? 'New password (optional)' : 'Password' }}</label>
        <input type="password" name="password" class="input" {{ $user ? '' : 'required' }}>
    </div>
    <div class="flex gap-3">
        <button class="btn-primary">Save</button>
        <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
