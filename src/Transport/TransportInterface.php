<?php

namespace NimblePHP\Email\Transport;

use NimblePHP\Email\Exception\EmailException;

interface TransportInterface
{
    /**
     * Send an email message
     * @param string $from From address
     * @param string $to To address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $headers Additional headers
     * @param array $attachments File attachments
     * @param array $embeddedImages Embedded images
     * @param bool $isHtml Whether the email is HTML format
     * @param array $cc CC recipients
     * @param array $bcc BCC recipients
     * @return bool Success status
     * @throws EmailException On send error
     */
    public function send(
        string $from,
        string $to,
        string $subject,
        string $body,
        array $headers = [],
        array $attachments = [],
        array $embeddedImages = [],
        bool $isHtml = false,
        array $cc = [],
        array $bcc = []
    ): bool;

    /**
     * Sets connection timeout
     * @param int $seconds Timeout in seconds
     * @return self
     */
    public function setConnectionTimeout(int $seconds): self;

    /**
     * Sets stream timeout
     * @param int $seconds Timeout in seconds
     * @return self
     */
    public function setTimeout(int $seconds): self;
}