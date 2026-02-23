<?php

namespace NimblePHP\Email;

use Krzysztofzylka\File\File;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Translation\Translation;
use NimblePHP\Framework\Translation\TranslationProviderInterface;
use NimblePHP\Twig\Twig;

class Module implements ModuleInterface, TranslationProviderInterface
{

    public function getName(): string
    {
        return 'Nimblephp Emails';
    }

    public function register(): void
    {
    }

    /**
     * @return void
     */
    public function registerTranslations(): void
    {
        Translation::getInstance()->addTranslationPath(__DIR__ . '/Lang', Translation::PRIORITY_MODULE);
    }

}