<?php

/**
 * Project-level Fabryq configuration loader.
 *
 * @package   Fabryq\Cli\Config
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Config;

use Fabryq\Cli\Error\ProjectStateError;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads fabryq.yaml defaults for generators and reports.
 *
 * @phpstan-type ControllerConfig array{
 *   route_prefix: string,
 *   route_name_prefix: string,
 *   default_format: string,
 *   security: array{enabled: bool, attribute: string},
 *   templates: array{enabled: bool, namespace: string},
 *   translations: array{enabled: bool, domain: string}
 * }
 * @phpstan-type ReportsConfig array{
 *   links: array{enabled: bool, scheme: string}
 * }
 * @phpstan-type FabryqConfig array{
 *   controller: ControllerConfig,
 *   reports: ReportsConfig
 * }
 */
final class ProjectConfig
{
    /**
     * @var FabryqConfig
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

        try {
            $parsed = Yaml::parseFile($path);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $exception) {
            throw new ProjectStateError('fabryq.yaml is invalid: ' . $exception->getMessage(), previous: $exception);
        }
        if (!is_array($parsed) || array_is_list($parsed)) {
            throw new ProjectStateError('fabryq.yaml must decode to a mapping.');
        }

        $this->config = self::merge($this->config, $parsed);
    }

    /**
     * @return ControllerConfig
     */
    public function controller(): array
    {
        return $this->config['controller'];
    }

    /**
     * @return ReportsConfig
     */
    public function reports(): array
    {
        return $this->config['reports'];
    }

    /**
     * Default config payload.
     *
     * @return FabryqConfig
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
     * @param FabryqConfig         $defaults
     * @param array<string, mixed> $overrides
     *
     * @return FabryqConfig
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
     * @param ControllerConfig     $defaults
     * @param array<string, mixed> $overrides
     *
     * @return ControllerConfig
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
        }

        /** @var ControllerConfig $defaults */
        return $defaults;
    }

    /**
     * @param ReportsConfig        $defaults
     * @param array<string, mixed> $overrides
     *
     * @return ReportsConfig
     */
    private static function mergeReports(array $defaults, array $overrides): array
    {
        if (isset($overrides['links']) && is_array($overrides['links'])) {
            $defaults['links'] = self::mergeBooleanStringSection($defaults['links'], $overrides['links']);
        }

        /** @var ReportsConfig $defaults */
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

        foreach (['attribute', 'namespace', 'scheme', 'domain'] as $key) {
            if (array_key_exists($key, $defaults) && array_key_exists($key, $overrides) && is_string($overrides[$key])) {
                $defaults[$key] = $overrides[$key];
            }
        }

        return $defaults;
    }
}
