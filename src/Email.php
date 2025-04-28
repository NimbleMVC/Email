<?php

namespace NimblePHP\Email;

use NimblePHP\Email\Config\EmailConfig;
use NimblePHP\Email\Template\TemplateProcessor;
use NimblePHP\Email\Transport\PhpMailTransport;
use NimblePHP\Email\Transport\SmtpTransport;
use NimblePHP\Email\Transport\TransportInterface;
use NimblePHP\Email\Exception\EmailException;
use NimblePHP\Framework\Traits\LogTrait;

class Email
{
    use LogTrait;

    /** @var string|null */
    private ?string $to = null;

    /** @var string|null */
    private ?string $from = null;

    /** @var string|null */
    private ?string $subject = null;

    /** @var string|null */
    private ?string $body = null;

    /** @var string|null */
    private ?string $replyTo = null;

    /** @var array */
    private array $cc = [];

    /** @var array */
    private array $bcc = [];

    /** @var array */
    private array $attachments = [];

    /** @var array */
    private array $embeddedImages = [];

    /** @var bool */
    private bool $isHtml = false;

    /** @var EmailConfig */
    private EmailConfig $config;

    /** @var array */
    private array $customHeaders = [];

    /** @var int */
    private int $connectionTimeout = 30;

    /** @var int */
    private int $timeout = 30;

    /** @var TransportInterface|null */
    private ?TransportInterface $transport = null;

    /** @var TemplateProcessor|null */
    private ?TemplateProcessor $templateProcessor = null;

    /**
     * Construct class
     * @param EmailConfig|null $config Optional email configuration
     * @param TransportInterface|null $transport Optional transport
     * @param TemplateProcessor|null $templateProcessor Optional template processor
     */
    public function __construct(
        ?EmailConfig $config = null,
        ?TransportInterface $transport = null,
        ?TemplateProcessor $templateProcessor = null
    ) {
        $this->config = $config ?? new EmailConfig();
        $this->transport = $transport;
        $this->templateProcessor = $templateProcessor;

        if ($this->config->getFromAddress()) {
            $this->from($this->config->getFromAddress(), $this->config->getFromName());
        }
    }

    /**
     * Sets recipient address
     * @param string $email Recipient email address
     * @param string $name Recipient name (optional)
     * @return $this
     */
    public function to(string $email, string $name = ''): self
    {
        $this->to = $name ? "$name <$email>" : $email;

        return $this;
    }

    /**
     * Sets sender address
     * @param string $email Sender email address
     * @param string $name Sender name (optional)
     * @return $this
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $name ? "$name <$email>" : $email;

        return $this;
    }

    /**
     * Sets email subject
     * @param string $subject Email subject
     * @return $this
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets email body
     * @param string $body Email body content
     * @param bool $isHtml Whether content is HTML format
     * @return $this
     */
    public function body(string $body, bool $isHtml = false): self
    {
        $this->body = $body;
        $this->isHtml = $isHtml;

        return $this;
    }

    /**
     * Sets Reply-To address
     * @param string $email Email address
     * @param string $name Name (optional)
     * @return $this
     */
    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo = $name ? "$name <$email>" : $email;

        return $this;
    }

    /**
     * Adds CC recipient
     * @param string $email Email address
     * @param string $name Name (optional)
     * @return $this
     */
    public function cc(string $email, string $name = ''): self
    {
        $this->cc[] = $name ? "$name <$email>" : $email;

        return $this;
    }

    /**
     * Adds BCC recipient
     * @param string $email Email address
     * @param string $name Name (optional)
     * @return $this
     */
    public function bcc(string $email, string $name = ''): self
    {
        $this->bcc[] = $name ? "$name <$email>" : $email;

        return $this;
    }

    /**
     * Adds multiple recipients
     * @param array $recipients Array of email addresses or [email => name] pairs
     * @return $this
     */
    public function addRecipients(array $recipients): self
    {
        foreach ($recipients as $key => $value) {
            if (is_numeric($key)) {
                $this->to($value);
            } else {
                $this->to($key, $value);
            }
        }

        return $this;
    }

    /**
     * Adds multiple CC recipients
     * @param array $recipients Array of email addresses or [email => name] pairs
     * @return $this
     */
    public function addCc(array $recipients): self
    {
        foreach ($recipients as $key => $value) {
            if (is_numeric($key)) {
                $this->cc($value);
            } else {
                $this->cc($key, $value);
            }
        }

        return $this;
    }

    /**
     * Adds multiple BCC recipients
     * @param array $recipients Array of email addresses or [email => name] pairs
     * @return $this
     */
    public function addBcc(array $recipients): self
    {
        foreach ($recipients as $key => $value) {
            if (is_numeric($key)) {
                $this->bcc($value);
            } else {
                $this->bcc($key, $value);
            }
        }

        return $this;
    }

    /**
     * Adds custom header to email
     * @param string $name Header name
     * @param string $value Header value
     * @return $this
     */
    public function addHeader(string $name, string $value): self
    {
        $this->customHeaders[$name] = $value;

        return $this;
    }

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
     * Adds attachment to email
     * @param string $path File path
     * @param string $name Attachment name (optional)
     * @return $this
     * @throws \Exception If file doesn't exist
     */
    public function attachment(string $path, string $name = ''): self
    {
        if (!file_exists($path)) {
            throw new EmailException("File $path does not exist");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path)
        ];

        return $this;
    }

    /**
     * Adds attachment from string content
     * @param string $content File content
     * @param string $name Attachment name
     * @param string $mimeType MIME type (default: application/octet-stream)
     * @return $this
     */
    public function attachmentFromString(string $content, string $name, string $mimeType = 'application/octet-stream'): self
    {
        $this->attachments[] = [
            'content' => $content,
            'name' => $name,
            'mime' => $mimeType
        ];

        return $this;
    }

    /**
     * Embeds image into HTML email
     * @param string $path Image file path
     * @param string $cid Content ID (reference in HTML as cid:ID)
     * @return $this
     * @throws EmailException If file doesn't exist
     */
    public function embedImage(string $path, string $cid): self
    {
        if (!file_exists($path)) {
            throw new EmailException("Image file $path does not exist");
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        $this->embeddedImages[] = [
            'path' => $path,
            'cid' => $cid,
            'mime' => $mime
        ];

        return $this;
    }

    /**
     * Sets OAuth2 token for authentication
     * @param string $token OAuth2 token
     * @return $this
     */
    public function setOAuthToken(string $token): self
    {
        $this->config->setOAuthToken($token);

        return $this;
    }

    /**
     * Set SMTP configuration directly
     * @param array $config SMTP configuration options
     * @return $this
     */
    public function setSmtpConfig(array $config): self
    {
        $this->config->setConfig($config);

        return $this;
    }

    /**
     * Set transport to use for sending emails
     * @param TransportInterface $transport
     * @return $this
     */
    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Set template processor
     * @param TemplateProcessor $templateProcessor
     * @return $this
     */
    public function setTemplateProcessor(TemplateProcessor $templateProcessor): self
    {
        $this->templateProcessor = $templateProcessor;

        return $this;
    }

    /**
     * Get template processor (creates one if not set)
     * @return TemplateProcessor
     */
    private function getTemplateProcessor(): TemplateProcessor
    {
        if ($this->templateProcessor === null) {
            $this->templateProcessor = new TemplateProcessor();
        }

        return $this->templateProcessor;
    }

    /**
     * Creates a new email from a template file
     * @param string $templatePath Path to template file
     * @param array $variables Variables to replace in template
     * @param bool $isHtml Whether template is HTML format
     * @return $this
     * @throws EmailException If template file doesn't exist
     */
    public function template(string $templatePath, array $variables = [], bool $isHtml = true): self
    {
        $content = $this->getTemplateProcessor()->processFile($templatePath, $variables);
        $this->body($content, $isHtml);

        return $this;
    }

    /**
     * Creates a new email from a template string
     * @param string $templateContent Template content
     * @param array $variables Variables to replace in template
     * @param bool $isHtml Whether template is HTML format
     * @return $this
     */
    public function templateFromString(string $templateContent, array $variables = [], bool $isHtml = true): self
    {
        $content = $this->getTemplateProcessor()->processContent($templateContent, $variables);
        $this->body($content, $isHtml);

        return $this;
    }

    /**
     * Get the appropriate transport for sending email
     * @return TransportInterface
     */
    private function getTransport(): TransportInterface
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        if ($this->config->isAuthEnabled()) {
            $transport = new SmtpTransport($this->config);
        } else {
            $transport = new PhpMailTransport();
        }

        $transport->setConnectionTimeout($this->connectionTimeout);
        $transport->setTimeout($this->timeout);

        return $transport;
    }

    /**
     * Sends email message
     * @return bool Operation result (true = success, false = error)
     * @throws EmailException If required fields are missing
     */
    public function send(): bool
    {
        $this->log('Sending email', 'INFO');

        if (empty($this->to)) {
            $this->log('Email recipient is missing', 'ERR');
            throw new EmailException('Recipient address is required');
        }

        if (empty($this->from)) {
            $this->log('Email sender is missing', 'ERR');
            throw new EmailException('Sender address is required');
        }

        if (empty($this->subject)) {
            $this->log('Email subject is missing', 'ERR');
            throw new EmailException('Email subject is required');
        }

        if (empty($this->body)) {
            $this->log('Email body is missing', 'ERR');
            throw new EmailException('Email body is required');
        }

        $headers = [];

        if (!empty($this->replyTo)) {
            $headers['Reply-To'] = $this->replyTo;
        }

        foreach ($this->customHeaders as $name => $value) {
            $headers[$name] = $value;
        }

        $transport = $this->getTransport();

        return $transport->send(
            $this->from,
            $this->to,
            $this->subject,
            $this->body,
            $headers,
            $this->attachments,
            $this->embeddedImages,
            $this->isHtml,
            $this->cc,
            $this->bcc
        );
    }
}