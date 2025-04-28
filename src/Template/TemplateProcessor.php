<?php

namespace NimblePHP\Email\Template;

use NimblePHP\Email\Exception\EmailException;

class TemplateProcessor
{

    /**
     * Default template placeholder format
     *
     * @var string
     */
    private string $placeholderFormat = '{{%s}}';

    /**
     * Set custom placeholder format
     * @param string $format Format string with %s placeholder
     * @return $this
     */
    public function setPlaceholderFormat(string $format): self
    {
        $this->placeholderFormat = $format;

        return $this;
    }

    /**
     * Process template from file
     * @param string $templatePath Path to template file
     * @param array $variables Variables to replace in template
     * @return string Processed template content
     * @throws EmailException If template file doesn't exist
     */
    public function processFile(string $templatePath, array $variables = []): string
    {
        if (!file_exists($templatePath)) {
            throw new EmailException("Template file $templatePath does not exist");
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new EmailException("Failed to read template file $templatePath");
        }

        return $this->processContent($content, $variables);
    }

    /**
     * Process template from content string
     * @param string $content Template content
     * @param array $variables Variables to replace in template
     * @return string Processed template content
     */
    public function processContent(string $content, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = sprintf($this->placeholderFormat, $key);
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Check if file exists and is readable
     * @param string $templatePath Path to template file
     * @return bool True if file exists and is readable
     */
    public function templateExists(string $templatePath): bool
    {
        return file_exists($templatePath) && is_readable($templatePath);
    }

}