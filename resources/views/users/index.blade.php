@extends('layouts.app')

@section('title', 'Users & Roles')
@section('heading', 'Users & Roles')
@section('subheading', 'Admin, Manager, Staff, and Customer accounts')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('users.create') }}" class="btn-primary">Add user</a>
</div>

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="font-medium">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge bg-teal-400/15 text-teal-300 capitalize">{{ $user->role }}</span></td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('users.edit', $user) }}" class="text-teal-300">Edit</a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete user?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
