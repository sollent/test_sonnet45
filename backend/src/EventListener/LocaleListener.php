<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
final class LocaleListener
{
    private const SUPPORTED_LOCALES = ['en', 'ru'];
    private const DEFAULT_LOCALE = 'en';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Priority 1: Query parameter (highest priority)
        if ($request->query->has('locale')) {
            $locale = $request->query->get('locale');
            if (in_array($locale, self::SUPPORTED_LOCALES)) {
                $request->setLocale($locale);
                $request->attributes->set('_locale', $locale);
                return;
            }
        }

        // Priority 2: Accept-Language header
        $acceptLanguage = $request->headers->get('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            $request->setLocale($locale);
            $request->attributes->set('_locale', $locale);
            return;
        }

        // Priority 3: Use default locale
        $request->setLocale(self::DEFAULT_LOCALE);
        $request->attributes->set('_locale', self::DEFAULT_LOCALE);
    }

    /**
     * Parse Accept-Language header and return the best matching locale
     */
    private function parseAcceptLanguage(string $acceptLanguage): string
    {
        // Simple parsing - just get the first supported locale
        $languages = explode(',', $acceptLanguage);
        
        foreach ($languages as $lang) {
            // Extract language code (e.g., "en-US" -> "en")
            $langCode = substr(trim($lang), 0, 2);
            
            if (in_array($langCode, self::SUPPORTED_LOCALES)) {
                return $langCode;
            }
        }
        
        return self::DEFAULT_LOCALE;
    }
}
