<?php

namespace IsekaiPHP\Core;

/**
 * Service for installing modules from Git repositories or ZIP files
 */
class ModuleInstaller
{
    protected string $modulesPath;

    public function __construct(string $basePath)
    {
        $this->modulesPath = $basePath . '/modules';

        // Ensure modules directory exists
        if (! is_dir($this->modulesPath)) {
            mkdir($this->modulesPath, 0755, true);
        }
    }

    /**
     * Install a module from a Git repository
     *
     * @param string $repoUrl Git repository URL (HTTPS or SSH)
     * @param string|null $branch Branch or tag to checkout (default: main/master)
     * @param string|null $moduleName Custom module name (default: extracted from repo URL)
     * @return string Path to installed module
     * @throws \Exception
     */
    public function installFromGit(string $repoUrl, ?string $branch = null, ?string $moduleName = null): string
    {
        // Validate URL
        if (! $this->isValidGitUrl($repoUrl)) {
            throw new \Exception('Invalid Git repository URL');
        }

        // Extract module name from URL if not provided
        if ($moduleName === null) {
            $moduleName = $this->extractModuleNameFromUrl($repoUrl);
        }

        $modulePath = $this->modulesPath . '/' . $moduleName;
        $tempPath = $modulePath . '.tmp.' . uniqid();

        try {
            // Clone the repository to a temp directory
            $this->cloneRepository($repoUrl, $tempPath, $branch);

            // Validate the module has module.json
            if (! file_exists($tempPath . '/module.json')) {
                throw new \Exception('Module does not contain module.json file');
            }

            // Validate module.json structure
            $manifest = json_decode(file_get_contents($tempPath . '/module.json'), true);
            if (! $manifest || ! isset($manifest['name'])) {
                throw new \Exception('Invalid module.json file');
            }

            // Ensure module name matches
            if ($manifest['name'] !== $moduleName) {
                // Optionally rename directory to match module name
                // For now, we'll use the provided name or extracted name
            }

            // Remove old module if it exists
            if (is_dir($modulePath)) {
                $this->removeDirectory($modulePath);
            }

            // Move temp directory to final location
            rename($tempPath, $modulePath);

            // Install dependencies
            $this->installDependencies($modulePath);

            return $modulePath;
        } catch (\Exception $e) {
            // Cleanup on failure
            if (is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Install a module from a ZIP file
     *
     * @param string $zipPath Path to ZIP file
     * @param string|null $moduleName Custom module name
     * @return string Path to installed module
     * @throws \Exception
     */
    public function installFromZip(string $zipPath, ?string $moduleName = null): string
    {
        if (! file_exists($zipPath)) {
            throw new \Exception('ZIP file does not exist');
        }

        // Validate ZIP file
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Invalid ZIP file or cannot open ZIP file');
        }

        // Extract module name from ZIP filename or first directory if not provided
        if ($moduleName === null) {
            // Try to get module name from ZIP filename
            $zipName = basename($zipPath, '.zip');
            $moduleName = preg_replace('/[^a-z0-9_-]/i', '', $zipName);

            // If ZIP has a single root directory, use that name
            if ($zip->numFiles > 0) {
                $firstEntry = $zip->getNameIndex(0);
                if ($firstEntry && strpos($firstEntry, '/') !== false) {
                    $rootDir = explode('/', $firstEntry)[0];
                    if (! empty($rootDir)) {
                        $moduleName = $rootDir;
                    }
                }
            }
        }

        $modulePath = $this->modulesPath . '/' . $moduleName;
        $tempPath = sys_get_temp_dir() . '/module_' . uniqid();

        try {
            // Get entries before extracting
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entries[] = $zip->getNameIndex($i);
            }

            // Extract ZIP to temp directory
            $zip->extractTo($tempPath);
            $zip->close();

            // If ZIP has a single root directory, adjust path
            $extractedPath = $tempPath;
            if (! empty($entries)) {
                $firstEntry = $entries[0];
                if (strpos($firstEntry, '/') !== false) {
                    $rootDir = explode('/', $firstEntry)[0];
                    $potentialRoot = $tempPath . '/' . $rootDir;
                    if (is_dir($potentialRoot)) {
                        $extractedPath = $potentialRoot;
                    }
                }
            }

            // Validate the module has module.json
            if (! file_exists($extractedPath . '/module.json')) {
                throw new \Exception('Module does not contain module.json file');
            }

            // Validate module.json structure
            $manifest = json_decode(file_get_contents($extractedPath . '/module.json'), true);
            if (! $manifest || ! isset($manifest['name'])) {
                throw new \Exception('Invalid module.json file');
            }

            // Remove old module if it exists
            if (is_dir($modulePath)) {
                $this->removeDirectory($modulePath);
            }

            // Move extracted directory to final location
            rename($extractedPath, $modulePath);

            // Cleanup temp directory
            if ($extractedPath !== $tempPath && is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }

            // Install dependencies
            $this->installDependencies($modulePath);

            // Remove ZIP file after successful installation
            @unlink($zipPath);

            return $modulePath;
        } catch (\Exception $e) {
            // Cleanup on failure
            if (is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Validate that a module path contains a valid module
     *
     * @param string $modulePath
     * @return bool
     */
    public function validateModule(string $modulePath): bool
    {
        if (! is_dir($modulePath)) {
            return false;
        }

        $moduleJsonPath = $modulePath . '/module.json';
        if (! file_exists($moduleJsonPath)) {
            return false;
        }

        $manifest = json_decode(file_get_contents($moduleJsonPath), true);
        if (! $manifest || ! isset($manifest['name'])) {
            return false;
        }

        return true;
    }

    /**
     * Install Composer dependencies for a module
     *
     * @param string $modulePath
     * @return void
     * @throws \Exception
     */
    public function installDependencies(string $modulePath): void
    {
        $composerJsonPath = $modulePath . '/composer.json';
        if (! file_exists($composerJsonPath)) {
            return; // No dependencies to install
        }

        $command = sprintf(
            'cd %s && composer install --no-interaction --quiet 2>&1',
            escapeshellarg($modulePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $error = implode("\n", $output);

            throw new \Exception('Failed to install module dependencies: ' . $error);
        }
    }

    /**
     * Clone a Git repository
     *
     * @param string $repoUrl
     * @param string $targetPath
     * @param string|null $branch
     * @return void
     * @throws \Exception
     */
    protected function cloneRepository(string $repoUrl, string $targetPath, ?string $branch = null): void
    {
        // Check if git is available
        exec('which git', $output, $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Git is not installed or not available in PATH');
        }

        // Build clone command
        $branchArg = $branch ? ' -b ' . escapeshellarg($branch) : '';
        $command = sprintf(
            'git clone%s %s %s 2>&1',
            $branchArg,
            escapeshellarg($repoUrl),
            escapeshellarg($targetPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $error = implode("\n", $output);

            throw new \Exception('Failed to clone repository: ' . $error);
        }
    }

    /**
     * Validate Git URL format
     *
     * @param string $url
     * @return bool
     */
    protected function isValidGitUrl(string $url): bool
    {
        // Check for HTTPS
        if (preg_match('/^https?:\/\/.*\.git$/', $url)) {
            return true;
        }

        // Check for SSH format
        if (preg_match('/^(git@|ssh:\/\/git@)[\w\.-]+:.*\.git$/', $url)) {
            return true;
        }

        // Check for GitHub/GitLab shorthand
        if (preg_match('/^[\w\.-]+\/[\w\.-]+$/', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Extract module name from Git URL
     *
     * @param string $url
     * @return string
     */
    protected function extractModuleNameFromUrl(string $url): string
    {
        // Remove .git extension
        $url = preg_replace('/\.git$/', '', $url);

        // Extract from HTTPS/SSH URL
        if (preg_match('/\/([\w\.-]+)$/', $url, $matches)) {
            return $matches[1];
        }

        // Extract from shorthand format (user/repo)
        if (preg_match('/^[\w\.-]+\/([\w\.-]+)$/', $url, $matches)) {
            return $matches[1];
        }

        // Fallback: use sanitized version of URL
        return preg_replace('/[^a-z0-9_-]/i', '', basename($url));
    }

    /**
     * Remove directory recursively
     *
     * @param string $dir
     * @return void
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
