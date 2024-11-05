<?php
declare(strict_types=1);

namespace AttributeRouter\Service;

class LocaleService
{
    private string $defaultLocale;
    private array $locales;

    public function setDefaultLocale(string $defaultLocale): static
    {
        $this->defaultLocale = $defaultLocale;
        return $this;
    }

    public function setLocales(array $locales): static
    {
        $this->locales = $locales;
        return $this;
    }


    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }
}