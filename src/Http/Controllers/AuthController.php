<?php

namespace IsekaiPHP\Http\Controllers;

use IsekaiPHP\Auth\Authentication;
use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class AuthController extends Controller
{
    protected Authentication $auth;

    public function __construct()
    {
        $this->auth = new Authentication();
    }

    /**
     * Show the login form
     */
    public function showLogin(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }

        return $this->view('auth/login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request): Response
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $remember = $request->input('remember', false);

        if ($this->auth->attempt($username, $password, $remember)) {
            return $this->redirect('/');
        }

        return $this->view('auth/login', [
            'error' => 'Invalid credentials',
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return $this->redirect('/login');
    }

    /**
     * Handle API login request
     */
    public function apiLogin(Request $request): Response
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $remember = $request->input('remember', false);

        if ($this->auth->attempt($username, $password, $remember)) {
            return $this->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $this->auth->user()->id,
                        'username' => $this->auth->user()->username,
                        'email' => $this->auth->user()->email,
                    ]
                ]
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Invalid credentials',
            'data' => null
        ], 401);
    }
}

