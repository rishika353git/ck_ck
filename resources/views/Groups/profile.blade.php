@extends('layouts.main')

@section('main-section')
<div class="container-fluid my-4">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Group: {{ $group->Name }}</h2>
        </div>
        <div class="card-body">
            <h4 class="mb-4">Joined Members: <span class="badge bg-info">{{ $users->count() }}</span></h4>
            @if($users->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead class="bg-white">
                            <tr style="border-bottom: 2px solid #dee2e6;">
                                <th style="border-right: 1px solid #dee2e6;">User ID</th>
                                <th style="border-right: 1px solid #dee2e6;">Name</th>
                                <th style="border-right: 1px solid #dee2e6;">Role</th>
                                <th class="text-center" style="border-right: 1px solid #dee2e6;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td style="border-right: 1px solid #dee2e6;">{{ $user->id }}</td>
                                    <td style="border-right: 1px solid #dee2e6;">{{ $user->name }}</td>
                                    <td style="border-right: 1px solid #dee2e6;">
                                        @if($user->user_roll == 1)
                                            <span class="badge bg-success">Student</span>
                                        @elseif($user->user_roll == 2)
                                            <span class="badge bg-warning">Advocate</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $user->user_roll }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center" style="border-right: 1px solid #dee2e6;">
                                        <form action="{{ route('removeUser', ['group' => $group->id, 'user' => $user->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this user?');">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning" role="alert">
                    <strong>No members have joined this group yet.</strong>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('Groups') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Groups
                </a>
            </div>
        </div>
    </div>
</div>
@endsection