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
     * @param string               $ruleKey          Rule identifier for the finding.
     * @param string               $severity         Severity label such as BLOCKER or WARNING.
     * @param string               $message          Human-readable description of the finding.
     * @param FindingLocation|null $location         [Optional] Source location metadata.
     * @param string|null          $hint             [Optional] Suggested remediation hint.
     * @param bool                 $autofixAvailable [Optional] Whether an automatic fix is available.
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
    ) {}

    /**
     * Convert the finding to a serializable array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ruleKey' => $this->ruleKey,
            'severity' => $this->severity,
            'message' => $this->message,
            'location' => [
                'file' => $this->location?->file,
                'line' => $this->location?->line,
                'symbol' => $this->location?->symbol,
            ],
            'hint' => $this->hint,
            'autofixAvailable' => $this->autofixAvailable,
        ];
    }
}
