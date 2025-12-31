<?php

/**
 * Selection criteria for fix commands.
 *
 * @package Fabryq\Cli\Fix
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Fix;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Represents fix selection filters.
 */
final readonly class FixSelection
{
    /**
     * @param bool        $all       Whether all findings should be selected.
     * @param string|null $file      File path filter.
     * @param string|null $symbol    Symbol filter.
     * @param string|null $findingId Finding ID filter.
     */
    public function __construct(
        public bool $all,
        public ?string $file,
        public ?string $symbol,
        public ?string $findingId,
    ) {}

    /**
     * Build selection from console input.
     *
     * @param InputInterface $input Console input.
     *
     * @return self
     */
    public static function fromInput(InputInterface $input): self
    {
        $all = (bool) $input->getOption('all');
        $file = $input->getOption('file');
        $symbol = $input->getOption('symbol');
        $finding = $input->getOption('finding');

        $file = $file === null || $file === '' ? null : (string) $file;
        $symbol = $symbol === null || $symbol === '' ? null : (string) $symbol;
        $finding = $finding === null || $finding === '' ? null : (string) $finding;

        $set = array_filter([
            'all' => $all ? '1' : null,
            'file' => $file,
            'symbol' => $symbol,
            'finding' => $finding,
        ]);

        if (count($set) > 1) {
            throw new \InvalidArgumentException('Use only one selection flag (--all, --file, --symbol, --finding).');
        }

        if (!$all && $file === null && $symbol === null && $finding === null) {
            $all = true;
        }

        return new self($all, $file, $symbol, $finding);
    }

    /**
     * Check whether a finding matches the selection criteria.
     *
     * @param Finding            $finding     Finding to check.
     * @param FindingIdGenerator $idGenerator ID generator for normalization.
     *
     * @return bool True when the finding matches.
     */
    public function matchesFinding(Finding $finding, FindingIdGenerator $idGenerator): bool
    {
        if ($this->all) {
            return true;
        }

        if ($this->findingId !== null) {
            return $idGenerator->generate($finding) === $this->findingId;
        }

        $location = $idGenerator->normalizeLocation($finding->location);
        if ($this->file !== null) {
            return $location['file'] === $idGenerator->normalizePath($this->file);
        }

        if ($this->symbol !== null) {
            return $location['symbol'] === $this->symbol;
        }

        return true;
    }

    /**
     * Check whether a path matches the selection criteria.
     *
     * @param string             $path        Path to match.
     * @param FindingIdGenerator $idGenerator ID generator for normalization.
     *
     * @return bool True when the path matches.
     */
    public function matchesPath(string $path, FindingIdGenerator $idGenerator): bool
    {
        if ($this->all) {
            return true;
        }

        if ($this->file !== null) {
            return $idGenerator->normalizePath($path) === $idGenerator->normalizePath($this->file);
        }

        return false;
    }

    /**
     * Serialize selection for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'all' => $this->all,
            'file' => $this->file,
            'symbol' => $this->symbol,
            'finding' => $this->findingId,
        ];
    }
}
