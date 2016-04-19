<?php

namespace Flarum\Console\Processor;

use ComponentInstaller\Process\Process;

class AssetProcessor extends Process
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        foreach ($this->packages as $package) {
            // extra may be missing (see spec)
            $extra = isset($package['extra']) ? $package['extra'] : [];
            // Name is required for published libraries (see spec)
            $name = $this->getComponentName($package['name'], $extra);

            if (isset($this->options[$name])) {
                $vendor = $this->getVendorDir($package);
                $this->copyFiles($vendor, $this->options[$name]);
            }
        }

        return true;
    }

    protected function copyFiles($vendor, $files) {
        foreach ($files as $locations) {
            $file = $this->expandFiles($locations);
            if (isset($file['cwd'])) {
                $vendor .= DIRECTORY_SEPARATOR . $file['cwd'];
            }
            $pattern = $vendor . DIRECTORY_SEPARATOR . $file['src'];

            foreach ($this->fs->recursiveGlobFiles($pattern) as $source) {
                $target = str_replace($vendor, $file['dest'], $source);                
    
                $this->fs->ensureDirectoryExists(dirname($target));
                copy($source, $target);
            }
        }
    }

    // Format heavily inspired by grunt's files object format
    // see http://gruntjs.com/configuring-tasks#files-object-format
    private function expandFiles($file) {
        if (count(array_keys($file)) === 1) {
            // Shortcut { src => dest }
            return [
                'src' => key($file), 'dest' => current($file)
            ];
        } else if(isset($file['src'], $file['dest'])) {
            // src and dest already exist
            return $file;
        }

        // Could not expand
        throw new \InvalidArgumentException('Invalid file format: ' . json_encode($file));
    }
}