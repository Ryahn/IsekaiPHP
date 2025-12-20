@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Application Settings</h3>
            </div>
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger">
                        <strong>Error loading settings:</strong> {{ $error }}
                    </div>
                @endif
                
                @php
                    $settingsCount = is_array($settings) ? count($settings) : 0;
                    $configGroupsCount = is_array($configGroups ?? null) ? count($configGroups) : 0;
                    $dbSettingsCount = is_array($dbSettings ?? null) ? count($dbSettings) : 0;
                    $settingsKeys = is_array($settings) ? implode(', ', array_keys($settings)) : 'not an array';
                @endphp
                
                @if(empty($settings) || $settingsCount === 0)
                    <div class="alert alert-info">
                        <p><strong>No settings found.</strong></p>
                        <p>Settings from config files will appear here once saved to the database.</p>
                        <p><strong>Debug Info:</strong></p>
                        <ul>
                            <li>Settings count: {{ $settingsCount }}</li>
                            <li>Settings type: {{ gettype($settings ?? null) }}</li>
                            <li>Settings keys: {{ $settingsKeys }}</li>
                            <li>Config groups count: {{ $configGroupsCount }}</li>
                            <li>DB settings count: {{ $dbSettingsCount }}</li>
                        </ul>
                    </div>
                @endif
                
                <form method="POST" action="/admin/settings">
                    @csrf
                    
                    @php
                        // Debug: Log what we have
                        error_log('View: settings type = ' . gettype($settings ?? null));
                        error_log('View: settings count = ' . (is_array($settings) ? count($settings) : 'N/A'));
                        if (is_array($settings)) {
                            error_log('View: settings keys = ' . implode(', ', array_keys($settings)));
                        }
                    @endphp
                    
                    @if(!empty($settings) && is_array($settings) && count($settings) > 0)
                    @foreach($settings as $group => $groupSettings)
                        @php
                            error_log("View: Processing group '{$group}' with " . count($groupSettings ?? []) . ' settings');
                        @endphp
                        <div class="settings-group mb-4">
                            <h4 class="mb-3">{{ ucfirst($group) }} Settings</h4>
                            
                            @foreach($groupSettings as $setting)
                                @if(is_object($setting))
                                    <div class="form-group mb-3">
                                        <label for="setting_{{ $setting->key }}">{{ $setting->key }}</label>
                                        
                                        @php
                                            $settingValue = $setting->getValue();
                                        @endphp
                                        @if($setting->type === 'boolean')
                                            <select name="{{ $setting->key }}" id="setting_{{ $setting->key }}" class="form-control">
                                                <option value="1" {{ $settingValue ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ !$settingValue ? 'selected' : '' }}>No</option>
                                            </select>
                                        @elseif($setting->type === 'json')
                                            <textarea name="{{ $setting->key }}" id="setting_{{ $setting->key }}" class="form-control" rows="4">{{ json_encode($settingValue, JSON_PRETTY_PRINT) }}</textarea>
                                        @else
                                            <input type="text" name="{{ $setting->key }}" id="setting_{{ $setting->key }}" class="form-control" value="{{ $settingValue }}">
                                        @endif
                                        
                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                @elseif(is_array($setting))
                                    <div class="form-group mb-3">
                                        <label for="setting_{{ $setting['key'] }}">{{ $setting['key'] }}</label>
                                        
                                        @if(isset($setting['type']) && $setting['type'] === 'boolean')
                                            <select name="{{ $setting['key'] }}" id="setting_{{ $setting['key'] }}" class="form-control">
                                                <option value="1" {{ $setting['value'] ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ !$setting['value'] ? 'selected' : '' }}>No</option>
                                            </select>
                                        @elseif(isset($setting['type']) && $setting['type'] === 'json')
                                            <textarea name="{{ $setting['key'] }}" id="setting_{{ $setting['key'] }}" class="form-control" rows="4">{{ json_encode($setting['value'], JSON_PRETTY_PRINT) }}</textarea>
                                        @else
                                            <input type="text" name="{{ $setting['key'] }}" id="setting_{{ $setting['key'] }}" class="form-control" value="{{ $setting['value'] ?? '' }}">
                                        @endif
                                        
                                        @if(isset($setting['description']))
                                            <small class="form-text text-muted">{{ $setting['description'] }}</small>
                                        @endif
                                        
                                        @if(isset($setting['from_config']))
                                            <small class="form-text text-info">This setting is currently from config file. Saving will move it to database.</small>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <hr>
                    @endforeach
                    @endif
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

