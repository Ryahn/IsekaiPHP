<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - IsekaiPHP</title>
    
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Vite Assets -->
    {!! vite() !!}
    
    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h4><a href="/admin">IsekaiPHP Admin</a></h4>
            </div>
            <ul class="admin-menu">
                @php
                    $currentPath = request()->path();
                    $isDashboard = $currentPath === 'admin' || $currentPath === 'admin/';
                    $isSettings = strpos($currentPath, 'admin/settings') === 0;
                    $isModules = strpos($currentPath, 'admin/modules') === 0;
                @endphp
                <li><a href="/admin" class="{{ $isDashboard ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/admin/settings" class="{{ $isSettings ? 'active' : '' }}"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="/admin/modules" class="{{ $isModules ? 'active' : '' }}"><i class="fas fa-puzzle-piece"></i> Modules</a></li>
                
                @php
                    $menuItems = \IsekaiPHP\Core\AdminMenuRegistry::getMenuItems();
                @endphp
                
                @foreach($menuItems as $item)
                    <li>
                        @php
                            $itemPath = str_replace('/admin/', '', $item['url']);
                            $isItemActive = strpos(request()->path(), $itemPath) === 0;
                        @endphp
                        <a href="{{ $item['url'] }}" class="{{ $isItemActive ? 'active' : '' }}">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                        @if(isset($item['children']) && count($item['children']) > 0)
                            <ul class="admin-submenu">
                                @foreach($item['children'] as $child)
                                    <li><a href="{{ $child['url'] }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="admin-sidebar-footer">
                <a href="/">← Back to Site</a>
                <a href="/logout">Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="admin-content">
            <div class="admin-header">
                <h1>@yield('title', 'Admin Panel')</h1>
            </div>
            <div class="admin-body">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        window.siteUrl = "{{ env('APP_URL', 'http://localhost') }}";
    </script>
    
    @stack('scripts')
</body>
</html>

