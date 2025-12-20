<?php

namespace IsekaiPHP\Http\Controllers\Admin;

use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index(Request $request): Response
    {
        return $this->view('admin.dashboard', [
            'title' => 'Admin Dashboard',
        ]);
    }
}
