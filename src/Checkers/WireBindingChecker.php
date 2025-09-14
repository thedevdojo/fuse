<?php

namespace Devdojo\Fuse\Checkers;

use Devdojo\Fuse\Parsers\LivewireComponentParser;
use Devdojo\Fuse\Parsers\BladeTemplateParser;

class WireBindingChecker
{
    private $componentParser;
    private $bladeParser;

    public function __construct()
    {
        $this->componentParser = new LivewireComponentParser();
        $this->bladeParser = new BladeTemplateParser();
    }

    /**
     * Run the $wire binding validation check.
     */
    public function check()
    {
        $errors = [];

        // Get all Livewire components
        $components = $this->componentParser->findLivewireComponents();

        foreach ($components as $component) {
            // Parse the component to get public properties and methods
            $componentData = $this->componentParser->parseComponent($component);
            
            // Find associated Blade templates
            $bladeFiles = $this->findBladeTemplatesForComponent($component);

            foreach ($bladeFiles as $bladeFile) {
                // Parse wire: directives and $wire references
                $wireReferences = $this->bladeParser->parseWireReferences($bladeFile);

                // Validate each reference
                foreach ($wireReferences as $reference) {
                    $error = $this->validateWireReference($reference, $componentData, $bladeFile);
                    if ($error) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return ['errors' => $errors];
    }

    /**
     * Find Blade templates associated with a Livewire component.
     */
    private function findBladeTemplatesForComponent($componentPath)
    {
        $templates = [];
        
        // Get component class name and namespace
        $componentClass = $this->componentParser->getComponentClass($componentPath);
        
        if (!$componentClass) {
            return $templates;
        }

        // Try to find the view based on component name
        $componentName = $this->getComponentViewName($componentClass);
        
        // Look for blade files in resources/views/livewire
        $viewsPath = base_path('resources/views');
        $livewireViewsPath = $viewsPath . '/livewire';
        
        // Check common locations
        $possiblePaths = [
            $livewireViewsPath . '/' . $componentName . '.blade.php',
            $viewsPath . '/livewire/' . $componentName . '.blade.php',
            $viewsPath . '/' . $componentName . '.blade.php',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && !in_array($path, $templates)) {
                $templates[] = $path;
            }
        }

        return $templates;
    }

    /**
     * Get the view name from component class.
     */
    private function getComponentViewName($componentClass)
    {
        // Convert class name to kebab-case for view name
        $className = class_basename($componentClass);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $className));
    }

    /**
     * Validate a wire reference against component data.
     */
    private function validateWireReference($reference, $componentData, $bladeFile)
    {
        $target = $reference['target'];
        $type = $reference['type'];
        $line = $reference['line'];

        // For wire:submit and wire:click, these are method calls
        if (in_array($type, ['wire:submit', 'wire:click', 'wire:keydown', 'wire:keyup', 'wire:mousedown', 'wire:mouseup', 'wire:contextmenu', 'wire:touchstart', 'wire:touchend', 'wire:touchmove', 'wire:scroll', 'wire:resize', 'wire:load'])) {
            // Remove parentheses if present and treat as method call
            $methodName = str_replace(['(', ')'], '', $target);
            if (!in_array($methodName, $componentData['publicMethods'])) {
                return [
                    'type' => 'Missing Method',
                    'message' => "Method '{$methodName}()' referenced in {$type} does not exist or is not public",
                    'file' => $bladeFile,
                    'line' => $line,
                ];
            }
        }
        // For wire:model and similar, these are property bindings
        elseif (in_array($type, ['wire:model', 'wire:change', 'wire:input', 'wire:blur', 'wire:focus', 'wire:mouseenter', 'wire:mouseleave'])) {
            // Handle nested properties like user.name
            $propertyPath = explode('.', $target);
            $rootProperty = $propertyPath[0];
            
            if (!in_array($rootProperty, $componentData['publicProperties'])) {
                return [
                    'type' => 'Missing Property',
                    'message' => "Property '{$rootProperty}' referenced in {$type} does not exist or is not public",
                    'file' => $bladeFile,
                    'line' => $line,
                ];
            }
        }
        // For $wire references, check if it's a method call or property access
        else {
            // Check if it's a method call (contains parentheses)
            if (preg_match('/^(\w+)\s*\(\)$/', $target, $matches)) {
                $methodName = $matches[1];
                if (!in_array($methodName, $componentData['publicMethods'])) {
                    return [
                        'type' => 'Missing Method',
                        'message' => "Method '{$methodName}()' referenced in {$type} does not exist or is not public",
                        'file' => $bladeFile,
                        'line' => $line,
                    ];
                }
            } else {
                // It's a property reference - handle nested properties
                $propertyPath = explode('.', $target);
                $rootProperty = $propertyPath[0];
                
                if (!in_array($rootProperty, $componentData['publicProperties'])) {
                    return [
                        'type' => 'Missing Property',
                        'message' => "Property '{$rootProperty}' referenced in {$type} does not exist or is not public",
                        'file' => $bladeFile,
                        'line' => $line,
                    ];
                }
            }
        }

        return null;
    }
}