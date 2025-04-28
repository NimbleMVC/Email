<?php

namespace NimblePHP\Email\Transport;

use NimblePHP\Email\Exception\EmailException;
use NimblePHP\Framework\Traits\LogTrait;

class PhpMailTransport implements TransportInterface
{

    use LogTrait;

    /** @var int */
    private int $connectionTimeout = 30;

    /** @var int */
    private int $timeout = 30;

    /**
     * Sets connection timeout
     * @param int $seconds Timeout in seconds
     * @return $this
     */
    public function setConnectionTimeout(int $seconds): self
    {
        $this->connectionTimeout = $seconds;

        return $this;
    }

    /**
     * Sets stream timeout
     * @param int $seconds Timeout in seconds
     * @return $this
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Send an email message via PHP's mail() function
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
     * @throws EmailException On error
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
    ): bool {
        $this->log('Sending email via PHP mail()', 'INFO');

        $mailHeaders = [];
        $mailHeaders[] = "From: {$from}";

        foreach ($headers as $name => $value) {
            $mailHeaders[] = "$name: $value";
        }

        if (!empty($cc)) {
            $mailHeaders[] = "Cc: " . implode(", ", $cc);
        }

        if (!empty($bcc)) {
            $mailHeaders[] = "Bcc: " . implode(", ", $bcc);
        }

        $mailHeaders[] = "MIME-Version: 1.0";

        $hasAttachments = !empty($attachments) || !empty($embeddedImages);
        if ($hasAttachments) {
            $boundary = md5(time());
            $mailHeaders[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";

            $message = "--$boundary\r\n";
            $message .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $body . "\r\n\r\n";

            foreach ($embeddedImages as $image) {
                $content = file_get_contents($image['path']);
                $content = chunk_split(base64_encode($content));
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: {$image['mime']}; name=\"" . basename($image['path']) . "\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-ID: <{$image['cid']}>\r\n";
                $message .= "Content-Disposition: inline; filename=\"" . basename($image['path']) . "\"\r\n\r\n";
                $message .= $content . "\r\n\r\n";
            }

            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    $content = file_get_contents($attachment['path']);
                } else {
                    $content = $attachment['content'];
                }

                $content = chunk_split(base64_encode($content));
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: " . ($attachment['mime'] ?? "application/octet-stream") . "; name=\"{$attachment['name']}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
                $message .= $content . "\r\n\r\n";
            }

            $message .= "--$boundary--";
        } else {
            $mailHeaders[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
            $message = $body;
        }

        $headerString = implode("\r\n", $mailHeaders);

        $success = mail($to, $subject, $message, $headerString);

        if (!$success) {
            $this->log('Failed to send email using PHP mail()', 'ERR');
            throw new EmailException("Failed to send email using PHP mail()");
        }

        $this->log('Email successfully sent using PHP mail()', 'INFO');

        return true;
    }

}