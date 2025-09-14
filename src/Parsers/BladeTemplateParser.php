<?php

namespace Devdojo\Fuse\Parsers;

class BladeTemplateParser
{
    /**
     * Parse wire: directives and $wire references from a Blade file.
     */
    public function parseWireReferences($bladeFile)
    {
        if (!file_exists($bladeFile)) {
            return [];
        }

        $content = file_get_contents($bladeFile);
        $lines = explode("\n", $content);
        $references = [];

        foreach ($lines as $lineNumber => $line) {
            $lineReferences = array_merge(
                $this->parseWireDirectives($line, $lineNumber + 1),
                $this->parseWireJavaScript($line, $lineNumber + 1)
            );

            $references = array_merge($references, $lineReferences);
        }

        return $references;
    }

    /**
     * Parse wire: directives from a line.
     */
    private function parseWireDirectives($line, $lineNumber)
    {
        $references = [];

        // Match wire:click, wire:model, wire:change, etc.
        $patterns = [
            '/wire:click\s*=\s*["\']([^"\']+)["\']/' => 'wire:click',
            '/wire:model\s*=\s*["\']([^"\']+)["\']/' => 'wire:model', 
            '/wire:change\s*=\s*["\']([^"\']+)["\']/' => 'wire:change',
            '/wire:keydown\s*=\s*["\']([^"\']+)["\']/' => 'wire:keydown',
            '/wire:keyup\s*=\s*["\']([^"\']+)["\']/' => 'wire:keyup',
            '/wire:submit\s*=\s*["\']([^"\']+)["\']/' => 'wire:submit',
            '/wire:input\s*=\s*["\']([^"\']+)["\']/' => 'wire:input',
            '/wire:blur\s*=\s*["\']([^"\']+)["\']/' => 'wire:blur',
            '/wire:focus\s*=\s*["\']([^"\']+)["\']/' => 'wire:focus',
            '/wire:mouseenter\s*=\s*["\']([^"\']+)["\']/' => 'wire:mouseenter',
            '/wire:mouseleave\s*=\s*["\']([^"\']+)["\']/' => 'wire:mouseleave',
            '/wire:mousedown\s*=\s*["\']([^"\']+)["\']/' => 'wire:mousedown',
            '/wire:mouseup\s*=\s*["\']([^"\']+)["\']/' => 'wire:mouseup',
            '/wire:contextmenu\s*=\s*["\']([^"\']+)["\']/' => 'wire:contextmenu',
            '/wire:touchstart\s*=\s*["\']([^"\']+)["\']/' => 'wire:touchstart',
            '/wire:touchend\s*=\s*["\']([^"\']+)["\']/' => 'wire:touchend',
            '/wire:touchmove\s*=\s*["\']([^"\']+)["\']/' => 'wire:touchmove',
            '/wire:scroll\s*=\s*["\']([^"\']+)["\']/' => 'wire:scroll',
            '/wire:resize\s*=\s*["\']([^"\']+)["\']/' => 'wire:resize',
            '/wire:load\s*=\s*["\']([^"\']+)["\']/' => 'wire:load',
        ];

        foreach ($patterns as $pattern => $directiveType) {
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $target = trim($match[0]);
                    
                    // Skip empty targets
                    if (empty($target)) {
                        continue;
                    }

                    // Parse method calls vs property references
                    $references[] = [
                        'target' => $target,
                        'type' => $directiveType,
                        'line' => $lineNumber,
                    ];
                }
            }
        }

        return $references;
    }

    /**
     * Parse $wire references in JavaScript/Alpine code.
     */
    private function parseWireJavaScript($line, $lineNumber)
    {
        $references = [];
        $processedPositions = [];

        // First, find all method calls and mark their positions to avoid overlap
        if (preg_match_all('/\$wire\.(\w+)\s*\(/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $methodName = trim($matches[1][$index][0]);
                $position = $match[1];
                $length = strlen($match[0]);
                
                if (!empty($methodName)) {
                    $references[] = [
                        'target' => $methodName . '()',
                        'type' => '$wire method call',
                        'line' => $lineNumber,
                    ];
                    
                    // Mark this position range as processed
                    for ($i = $position; $i < $position + $length; $i++) {
                        $processedPositions[$i] = true;
                    }
                }
            }
        }

        // Then, find property accesses, avoiding already processed positions
        if (preg_match_all('/\$wire\.(\w+)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $propertyName = trim($matches[1][$index][0]);
                $position = $match[1];
                
                // Check if this position was already processed as a method call
                $isOverlapping = false;
                for ($i = $position; $i < $position + strlen($match[0]); $i++) {
                    if (isset($processedPositions[$i])) {
                        $isOverlapping = true;
                        break;
                    }
                }
                
                if (!empty($propertyName) && !$isOverlapping) {
                    // Double-check this isn't followed by parentheses (method call)
                    $afterMatch = substr($line, $position + strlen($match[0]));
                    if (!preg_match('/^\s*\(/', $afterMatch)) {
                        $references[] = [
                            'target' => $propertyName,
                            'type' => '$wire property access',
                            'line' => $lineNumber,
                        ];
                    }
                }
            }
        }

        return $references;
    }
}