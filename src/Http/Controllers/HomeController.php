<?php

namespace IsekaiPHP\Http\Controllers;

use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index(Request $request): Response
    {
        return $this->view('index');
    }
}

