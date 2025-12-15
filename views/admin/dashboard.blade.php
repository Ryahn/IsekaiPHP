@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Welcome to the Admin Panel</h3>
            </div>
            <div class="card-body">
                <p>Use the sidebar to navigate to different sections of the admin panel.</p>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Settings</h5>
                                <p>Manage application settings</p>
                                <a href="/admin/settings" class="btn btn-light">Go to Settings</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Modules</h5>
                                <p>Install and manage modules</p>
                                <a href="/admin/modules" class="btn btn-light">Go to Modules</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

