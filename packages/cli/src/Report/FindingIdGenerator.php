<?php

/**
 * Deterministic ID generator for report findings.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

/**
 * Generates stable IDs for findings using a fingerprint hash.
 */
final readonly class FindingIdGenerator
{
    private const BASE32_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Absolute project directory for path normalization.
         *
         * @var string
         */
        private string $projectDir,
    ) {
    }

    /**
     * Generate a deterministic finding ID.
     *
     * @param Finding $finding Finding to generate an ID for.
     *
     * @return string Deterministic ID (F-XXXXXXXX).
     */
    public function generate(Finding $finding): string
    {
        $location = $this->normalizeLocation($finding->location);
        $primary = '';
        if (isset($finding->details['primary']) && is_string($finding->details['primary'])) {
            $primary = $finding->details['primary'];
        }
        $parts = [
            $finding->ruleKey,
            $location['file'] ?? '',
            $location['symbol'] ?? '',
            $primary,
        ];

        $fingerprint = implode('|', $parts);
        $hash = sha1($fingerprint, true);
        $bytes = substr($hash, 0, 5);
        $base32 = $this->encodeBase32($bytes);

        return 'F-' . $base32;
    }

    /**
     * Normalize a location into a serializable array.
     *
     * @param FindingLocation|null $location Finding location.
     *
     * @return array{file: string|null, line: int|null, symbol: string|null}
     */
    public function normalizeLocation(?FindingLocation $location): array
    {
        $file = $location?->file;
        $file = $this->normalizePath($file);

        return [
            'file' => $file,
            'line' => $location?->line,
            'symbol' => $location?->symbol,
        ];
    }

    /**
     * Normalize a file path to a relative, forward-slash format.
     *
     * @param string|null $path Absolute or relative path.
     *
     * @return string|null Normalized path.
     */
    public function normalizePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        $projectDir = str_replace('\\', '/', $this->projectDir);

        if (str_starts_with($normalized, $projectDir . '/')) {
            $normalized = substr($normalized, strlen($projectDir) + 1);
        }

        return ltrim($normalized, '/');
    }

    /**
     * Encode 5 bytes into 8 Crockford Base32 characters.
     *
     * @param string $bytes Binary string of length 5.
     *
     * @return string Base32-encoded string.
     */
    private function encodeBase32(string $bytes): string
    {
        $value = 0;
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $value = ($value << 8) | ord($bytes[$i]);
        }

        $output = '';
        for ($i = 0; $i < 8; $i++) {
            $shift = 35 - ($i * 5);
            $index = ($value >> $shift) & 0x1F;
            $output .= self::BASE32_ALPHABET[$index];
        }

        return $output;
    }
}
