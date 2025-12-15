@extends('admin.layouts.app')

@section('title', 'Install Module')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Install Module</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Install from Git -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Install from Git Repository</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/admin/modules/install/git">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="repo_url">Repository URL</label>
                                        <input type="text" name="repo_url" id="repo_url" class="form-control" placeholder="https://github.com/user/repo.git" required>
                                        <small class="form-text text-muted">HTTPS or SSH Git repository URL</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="branch">Branch/Tag (optional)</label>
                                        <input type="text" name="branch" id="branch" class="form-control" placeholder="main, master, v1.0.0, etc.">
                                        <small class="form-text text-muted">Leave empty for default branch</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Install from Git</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Install from ZIP -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Install from ZIP File</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/admin/modules/install/zip" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label for="zip_file">ZIP File</label>
                                        <input type="file" name="zip_file" id="zip_file" class="form-control" accept=".zip" required>
                                        <small class="form-text text-muted">Upload a ZIP file containing the module</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Install from ZIP</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="/admin/modules" class="btn btn-secondary">← Back to Modules</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

