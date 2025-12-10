<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

/**
 * Data Transfer Object for shipping label response.
 *
 * Contains the PDF label content and metadata for a Mondial Relay shipment.
 */
readonly class LabelResponse
{
    /**
     * @param string $content PDF content as binary string
     * @param string $contentType MIME type (typically 'application/pdf')
     * @param string $expeditionNumber Related expedition number
     * @param string $format Label format (e.g., 'A4', '10x15', 'A5')
     * @param int $sizeBytes File size in bytes
     * @param \DateTimeInterface|null $expiresAt Optional expiration date for the label URL
     */
    public function __construct(
        public string $content,
        public string $contentType = 'application/pdf',
        public string $expeditionNumber = '',
        public string $format = 'A4',
        public int $sizeBytes = 0,
        public ?\DateTimeInterface $expiresAt = null,
    ) {
    }

    /**
     * Check if the label is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->contentType === 'application/pdf';
    }

    /**
     * Get content as base64 encoded string.
     */
    public function getBase64Content(): string
    {
        return base64_encode($this->content);
    }

    /**
     * Get data URI for embedding in HTML.
     */
    public function getDataUri(): string
    {
        return sprintf(
            'data:%s;base64,%s',
            $this->contentType,
            $this->getBase64Content()
        );
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB'];
        $bytes = $this->sizeBytes;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            ++$unitIndex;
        }

        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }

    /**
     * Check if the label URL has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Save label content to a file.
     *
     * @param string $filepath Path where to save the file
     *
     * @return bool True on success, false on failure
     */
    public function saveToFile(string $filepath): bool
    {
        $directory = dirname($filepath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        return file_put_contents($filepath, $this->content) !== false;
    }

    /**
     * Get suggested filename for the label.
     *
     * @param string|null $prefix Optional filename prefix
     */
    public function getSuggestedFilename(?string $prefix = null): string
    {
        $prefix = $prefix !== null && $prefix !== '' ? $prefix . '_' : '';
        $extension = $this->isPdf() ? 'pdf' : 'bin';

        return sprintf(
            '%slabel_%s_%s.%s',
            $prefix,
            $this->expeditionNumber,
            $this->format,
            $extension
        );
    }

    /**
     * Create response from raw API data.
     *
     * @param string $content Binary content
     * @param string $expeditionNumber Expedition number
     * @param string $contentType MIME type
     * @param string $format Label format
     * @param \DateTimeInterface|null $expiresAt Expiration date
     */
    public static function fromApiResponse(
        string $content,
        string $expeditionNumber,
        string $contentType = 'application/pdf',
        string $format = 'A4',
        ?\DateTimeInterface $expiresAt = null
    ): self {
        return new self(
            content: $content,
            contentType: $contentType,
            expeditionNumber: $expeditionNumber,
            format: $format,
            sizeBytes: strlen($content),
            expiresAt: $expiresAt
        );
    }
}
