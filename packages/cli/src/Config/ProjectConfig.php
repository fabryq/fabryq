<?php

/**
 * Project-level Fabryq configuration loader.
 *
 * @package   Fabryq\Cli\Config
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Loads fabryq.yaml defaults for generators and reports.
 */
final class ProjectConfig
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(string $projectDir)
    {
        $this->config = self::defaults();

        $path = rtrim($projectDir, '/') . '/fabryq.yaml';
        if (!is_file($path)) {
            return;
        }

        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed)) {
            throw new \RuntimeException('fabryq.yaml must decode to a mapping.');
        }

        $this->config = self::merge($this->config, $parsed);
    }

    /**
     * @return array<string, mixed>
     */
    public function controller(): array
    {
        return $this->config['controller'];
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(): array
    {
        return $this->config['reports'];
    }

    /**
     * Default config payload.
     *
     * @return array<string, mixed>
     */
    private static function defaults(): array
    {
        return [
            'controller' => [
                'route_prefix' => '',
                'route_name_prefix' => '',
                'default_format' => 'json',
                'security' => [
                    'enabled' => false,
                    'attribute' => '',
                ],
                'templates' => [
                    'enabled' => false,
                    'namespace' => '',
                ],
                'translations' => [
                    'enabled' => false,
                    'domain' => 'messages',
                ],
            ],
            'reports' => [
                'links' => [
                    'enabled' => true,
                    'scheme' => 'phpstorm',
                ],
            ],
        ];
    }

    /**
     * Merge raw overrides into defaults for known keys.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function merge(array $defaults, array $overrides): array
    {
        if (isset($overrides['controller']) && is_array($overrides['controller'])) {
            $defaults['controller'] = self::mergeController($defaults['controller'], $overrides['controller']);
        }
        if (isset($overrides['reports']) && is_array($overrides['reports'])) {
            $defaults['reports'] = self::mergeReports($defaults['reports'], $overrides['reports']);
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function mergeController(array $defaults, array $overrides): array
    {
        foreach (['route_prefix', 'route_name_prefix', 'default_format'] as $key) {
            if (array_key_exists($key, $overrides) && is_string($overrides[$key])) {
                $defaults[$key] = $overrides[$key];
            }
        }

        if (isset($overrides['security']) && is_array($overrides['security'])) {
            $defaults['security'] = self::mergeBooleanStringSection($defaults['security'], $overrides['security']);
        }
        if (isset($overrides['templates']) && is_array($overrides['templates'])) {
            $defaults['templates'] = self::mergeBooleanStringSection($defaults['templates'], $overrides['templates']);
        }
        if (isset($overrides['translations']) && is_array($overrides['translations'])) {
            $defaults['translations'] = self::mergeBooleanStringSection($defaults['translations'], $overrides['translations']);
            if (array_key_exists('domain', $overrides['translations']) && is_string($overrides['translations']['domain'])) {
                $defaults['translations']['domain'] = $overrides['translations']['domain'];
            }
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function mergeReports(array $defaults, array $overrides): array
    {
        if (isset($overrides['links']) && is_array($overrides['links'])) {
            $defaults['links'] = self::mergeBooleanStringSection($defaults['links'], $overrides['links']);
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function mergeBooleanStringSection(array $defaults, array $overrides): array
    {
        if (array_key_exists('enabled', $overrides) && is_bool($overrides['enabled'])) {
            $defaults['enabled'] = $overrides['enabled'];
        }
        if (array_key_exists('attribute', $overrides) && is_string($overrides['attribute'])) {
            $defaults['attribute'] = $overrides['attribute'];
        }
        if (array_key_exists('namespace', $overrides) && is_string($overrides['namespace'])) {
            $defaults['namespace'] = $overrides['namespace'];
        }
        if (array_key_exists('scheme', $overrides) && is_string($overrides['scheme'])) {
            $defaults['scheme'] = $overrides['scheme'];
        }

        return $defaults;
    }
}
