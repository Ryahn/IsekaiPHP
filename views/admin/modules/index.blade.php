@extends('admin.layouts.app')

@section('title', 'Modules')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Installed Modules</h3>
                <a href="/admin/modules/install" class="btn btn-primary">Install Module</a>
            </div>
            <div class="card-body">
                @if(count($modules) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Display Name</th>
                                    <th>Version</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module)
                                    <tr>
                                        <td><strong>{{ $module['name'] }}</strong></td>
                                        <td>{{ $module['display_name'] }}</td>
                                        <td>{{ $module['version'] }}</td>
                                        <td>{{ $module['description'] ?? '-' }}</td>
                                        <td>
                                            @if($module['enabled'])
                                                <span class="badge bg-success">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($module['enabled'])
                                                <form method="POST" action="/admin/modules/{{ $module['name'] }}/disable" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to disable this module?')">Disable</button>
                                                </form>
                                            @else
                                                <form method="POST" action="/admin/modules/{{ $module['name'] }}/enable" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Enable</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <form method="POST" action="/admin/modules/disable-all" onsubmit="return confirm('Are you sure you want to disable ALL modules? This action cannot be undone easily.')">
                            @csrf
                            <button type="submit" class="btn btn-danger">Disable All Modules</button>
                        </form>
                    </div>
                @else
                    <p>No modules installed. <a href="/admin/modules/install">Install a module</a> to get started.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

