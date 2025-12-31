<?php

/**
 * Report finding emitted by analysis or verification tools.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

/**
 * Immutable representation of a single finding.
 */
final readonly class Finding
{
    /**
     * Structured details used for fingerprinting and rendering.
     *
     * @var array<string, mixed>
     */
    public array $details;

    /**
     * @param string               $ruleKey          Rule identifier for the finding.
     * @param string               $severity         Severity label such as BLOCKER or WARNING.
     * @param string               $message          Human-readable description of the finding.
     * @param FindingLocation|null $location         [Optional] Source location metadata.
     * @param array<string, mixed> $details          [Optional] Structured detail payload (must include primary).
     * @param string|null          $hint             [Optional] Suggested remediation hint.
     * @param bool                 $autofixAvailable [Optional] Whether an automatic fix is available.
     * @param string|null          $autofixFixer     [Optional] Fixer key when an autofix is available.
     */
    public function __construct(
        /**
         * Rule identifier for the finding.
         *
         * @var string
         */
        public string           $ruleKey,
        /**
         * Severity level for the finding.
         *
         * @var string
         */
        public string           $severity,
        /**
         * Human-readable message describing the issue.
         *
         * @var string
         */
        public string           $message,
        /**
         * Source location details, if known.
         *
         * @var FindingLocation|null
         */
        public ?FindingLocation $location = null,
        /**
         * Structured details argument.
         * Note: Not promoted to allow modification before assignment.
         *
         * @var array<string, mixed>
         */
        array                   $details = [],
        /**
         * Optional hint for resolving the finding.
         *
         * @var string|null
         */
        public ?string          $hint = null,
        /**
         * Whether an automated fix is available.
         *
         * @var bool
         */
        public bool             $autofixAvailable = false,
        /**
         * Fixer key used for autofix when available.
         *
         * @var string|null
         */
        public ?string          $autofixFixer = null,
    ) {
        // Bearbeite das lokale Array '$details', bevor es der readonly-Eigenschaft zugewiesen wird
        if (!array_key_exists('primary', $details)) {
            $details['primary'] = $message;
        }
        $this->details = $details;
    }

    /**
     * Convert the finding to a serializable array.
     *
     * @return array<string, mixed>
     */
    public function toArray(FindingIdGenerator $idGenerator): array
    {
        $location = $idGenerator->normalizeLocation($this->location);
        $autofix = ['available' => $this->autofixAvailable];
        if ($this->autofixAvailable && $this->autofixFixer !== null) {
            $autofix['fixer'] = $this->autofixFixer;
        }

        return [
            'id' => $idGenerator->generate($this),
            'ruleKey' => $this->ruleKey,
            'severity' => $this->severity,
            'message' => $this->message,
            'location' => $location,
            'details' => $this->details,
            'autofix' => $autofix,
        ];
    }
}