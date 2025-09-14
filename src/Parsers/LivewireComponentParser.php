<?php

namespace Devdojo\Fuse\Parsers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class LivewireComponentParser
{
    /**
     * Find all Livewire components in the application.
     */
    public function findLivewireComponents()
    {
        $components = [];
        $appPath = app_path();

        // Look for Livewire components in app/Livewire and app/Http/Livewire
        $possibleDirs = [
            $appPath . '/Livewire',
            $appPath . '/Http/Livewire',
        ];

        foreach ($possibleDirs as $dir) {
            if (is_dir($dir)) {
                $components = array_merge($components, $this->findPhpFilesInDirectory($dir));
            }
        }

        return $components;
    }

    /**
     * Find all PHP files in a directory recursively.
     */
    private function findPhpFilesInDirectory($directory)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Parse a Livewire component file to extract public properties and methods.
     */
    public function parseComponent($componentPath)
    {
        $componentData = [
            'publicProperties' => [],
            'publicMethods' => [],
        ];

        $componentClass = $this->getComponentClass($componentPath);
        
        if (!$componentClass) {
            return $componentData;
        }

        try {
            $reflection = new ReflectionClass($componentClass);
            
            // Only process Livewire components
            if (!$this->isLivewireComponent($reflection)) {
                return $componentData;
            }

            // Get public properties
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic()) {
                    $componentData['publicProperties'][] = $property->getName();
                }
            }

            // Get public methods (excluding Livewire's built-in methods)
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isStatic() && !$this->isBuiltInMethod($method->getName())) {
                    $componentData['publicMethods'][] = $method->getName();
                }
            }

        } catch (\Exception $e) {
            // If we can't reflect the class, skip it
        }

        return $componentData;
    }

    /**
     * Get the fully qualified class name from a PHP file.
     */
    public function getComponentClass($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $className = '';
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            return $namespace . '\\' . $className;
        }

        return null;
    }

    /**
     * Check if a class is a Livewire component.
     */
    private function isLivewireComponent(ReflectionClass $reflection)
    {
        // Check if it extends from Livewire Component class
        $parentClass = $reflection->getParentClass();
        while ($parentClass) {
            if ($parentClass->getName() === 'Livewire\\Component') {
                return true;
            }
            $parentClass = $parentClass->getParentClass();
        }

        return false;
    }

    /**
     * Check if a method is a built-in Livewire method that should be ignored.
     */
    private function isBuiltInMethod($methodName)
    {
        $builtInMethods = [
            'mount', 'render', 'hydrate', 'dehydrate', 'boot', 'booted',
            'updating', 'updated', 'updatingFoo', 'updatedFoo',
            '__construct', '__call', '__get', '__set', '__isset', '__unset',
            'dispatchBrowserEvent', 'emit', 'emitTo', 'emitSelf', 'emitUp',
            'redirect', 'redirectRoute', 'redirectAction',
            'validate', 'validateOnly', 'resetValidation', 'resetErrorBag',
            'addError', 'getErrorBag', 'setErrorBag',
            'skipRender', 'forgetComputed', 'getId', 'getName',
            'getComponentClass', 'getFreshInstance', 'getQueryString',
            'getPublicPropertiesDefinedBySubClass', 'getProtectedOrPrivatePropertiesDefinedBySubClass',
            'getDataWithoutPublicProperties', 'getPublicPropertiesExceptComputedOnes',
            'getComputedProperties', 'getComputedPropertyValue',
        ];

        return in_array($methodName, $builtInMethods);
    }
}