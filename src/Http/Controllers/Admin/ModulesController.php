<?php

namespace IsekaiPHP\Http\Controllers\Admin;

use IsekaiPHP\Core\ModuleManager;
use IsekaiPHP\Core\ModuleInstaller;
use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class ModulesController extends Controller
{
    protected ModuleManager $moduleManager;
    protected ModuleInstaller $installer;

    public function __construct(ModuleManager $moduleManager, ModuleInstaller $installer)
    {
        $this->moduleManager = $moduleManager;
        $this->installer = $installer;
    }

    /**
     * Display modules list
     */
    public function index(Request $request): Response
    {
        $modules = $this->moduleManager->getAllModules();

        return $this->view('admin.modules.index', [
            'title' => 'Modules',
            'modules' => $modules,
        ]);
    }

    /**
     * Show module installation form
     */
    public function install(Request $request): Response
    {
        return $this->view('admin.modules.install', [
            'title' => 'Install Module',
        ]);
    }

    /**
     * Install module from Git repository
     */
    public function installFromGit(Request $request): Response
    {
        $this->validate($request, [
            'repo_url' => 'required',
        ]);

        $repoUrl = $request->input('repo_url');
        $branch = $request->input('branch');

        try {
            $modulePath = $this->installer->installFromGit($repoUrl, $branch);
            $moduleName = basename($modulePath);

            // Enable the module by default
            $this->moduleManager->enableModule($moduleName);

            return $this->redirect('/admin/modules');
        } catch (\Exception $e) {
            return $this->redirect('/admin/modules/install');
        }
    }

    /**
     * Install module from ZIP file
     */
    public function installFromZip(Request $request): Response
    {
        $this->validate($request, [
            'zip_file' => 'required',
        ]);

        if (!$request->hasFile('zip_file')) {
            return $this->redirect('/admin/modules/install');
        }

        $file = $request->file('zip_file');

        // Handle Symfony UploadedFile
        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            if (!$file->isValid()) {
                return $this->redirect('/admin/modules/install');
            }

            // Validate file type by extension
            $extension = $file->getClientOriginalExtension();
            if (strtolower($extension) !== 'zip') {
                return $this->redirect('/admin/modules/install');
            }

            // Save uploaded file to temp location
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $file->getClientOriginalName();
            $file->move(sys_get_temp_dir(), basename($tempPath));
        } else {
            // Handle PHP $_FILES array format
            if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
                return $this->redirect('/admin/modules/install');
            }

            // Validate file type by extension
            $filename = $_FILES['zip_file']['name'];
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if (strtolower($extension) !== 'zip') {
                return $this->redirect('/admin/modules/install');
            }

            // Save uploaded file to temp location
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $filename;
            move_uploaded_file($_FILES['zip_file']['tmp_name'], $tempPath);
        }

        try {
            $modulePath = $this->installer->installFromZip($tempPath);
            $moduleName = basename($modulePath);

            // Enable the module by default
            $this->moduleManager->enableModule($moduleName);

            return $this->redirect('/admin/modules');
        } catch (\Exception $e) {
            // Cleanup temp file on error
            @unlink($tempPath);
            return $this->redirect('/admin/modules/install');
        }
    }

    /**
     * Enable a module
     */
    public function enable(Request $request): Response
    {
        // Get module name from route parameter
        $moduleName = $request->attributes->get('moduleName');
        
        try {
            $this->moduleManager->enableModule($moduleName);
            
            if ($request->expectsJson()) {
                return $this->json(['success' => true, 'message' => 'Module enabled']);
            }
            
            return $this->redirect('/admin/modules');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            
            return $this->redirect('/admin/modules');
        }
    }

    /**
     * Disable a module
     */
    public function disable(Request $request): Response
    {
        // Get module name from route parameter
        $moduleName = $request->attributes->get('moduleName');
        
        try {
            $this->moduleManager->disableModule($moduleName);
            
            if ($request->expectsJson()) {
                return $this->json(['success' => true, 'message' => 'Module disabled']);
            }
            
            return $this->redirect('/admin/modules');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            
            return $this->redirect('/admin/modules');
        }
    }

    /**
     * Disable all modules
     */
    public function disableAll(Request $request): Response
    {
        try {
            $this->moduleManager->disableAllModules();
            
            if ($request->expectsJson()) {
                return $this->json(['success' => true, 'message' => 'All modules disabled']);
            }
            
            return $this->redirect('/admin/modules');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            
            return $this->redirect('/admin/modules');
        }
    }
}

