<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber to set locale from Accept-Language header for API requests
 */
final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['en', 'ru'];

    private const DEFAULT_LOCALE = 'en';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip for admin routes (they have their own locale handling)
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        // Try to get locale from Accept-Language header
        $acceptLanguage = $request->headers->get('Accept-Language');

        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            $request->setLocale($locale);
        } else {
            $request->setLocale(self::DEFAULT_LOCALE);
        }
    }

    /**
     * Parse Accept-Language header and return best matching locale
     */
    private function parseAcceptLanguage(string $acceptLanguage): string
    {
        // Parse Accept-Language header (e.g., "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7")
        $languages = [];

        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $code = strtolower(explode('-', $parts[0])[0]);
            $quality = 1.0;

            if (isset($parts[1]) && str_starts_with($parts[1], 'q=')) {
                $quality = (float) substr($parts[1], 2);
            }

            $languages[$code] = $quality;
        }

        // Sort by quality
        arsort($languages);

        // Find first supported locale
        foreach (array_keys($languages) as $code) {
            if (in_array($code, self::SUPPORTED_LOCALES, true)) {
                return $code;
            }
        }

        return self::DEFAULT_LOCALE;
    }
}
