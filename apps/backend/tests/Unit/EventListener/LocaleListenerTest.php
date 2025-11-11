<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\LocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    private LocaleListener $listener;

    protected function setUp(): void
    {
        $this->listener = new LocaleListener();
    }

    /** @test */
    public function testSetsLocaleFromQueryParameter(): void
    {
        // Arrange
        $request = new Request(['locale' => 'ru']);
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertSame('ru', $request->getLocale());
        $this->assertSame('ru', $request->attributes->get('_locale'));
    }

    /** @test */
    public function testSetsLocaleFromAcceptLanguageHeader(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('Accept-Language', 'ru-RU,ru;q=0.9,en-US;q=0.8');
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertSame('ru', $request->getLocale());
        $this->assertSame('ru', $request->attributes->get('_locale'));
    }

    /** @test */
    public function testFallsBackToDefaultLocale(): void
    {
        // Arrange - Request without locale query param or Accept-Language header
        $request = new Request();
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert
        $this->assertSame('en', $request->getLocale());
        $this->assertSame('en', $request->attributes->get('_locale'));
    }

    /** @test */
    public function testSupportsOnlyConfiguredLocales(): void
    {
        // Arrange - Request with unsupported locale (fr)
        $request = new Request();
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9');
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert - Should fall back to default 'en'
        $this->assertSame('en', $request->getLocale());
        $this->assertSame('en', $request->attributes->get('_locale'));
    }

    /** @test */
    public function testQueryParameterHasHigherPriorityThanAcceptLanguage(): void
    {
        // Arrange - Both query param and Accept-Language header present
        $request = new Request(['locale' => 'en']);
        $request->headers->set('Accept-Language', 'ru-RU');
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert - Query param should win
        $this->assertSame('en', $request->getLocale());
        $this->assertSame('en', $request->attributes->get('_locale'));
    }

    /** @test */
    public function testIgnoresUnsupportedLocaleInQueryParameter(): void
    {
        // Arrange - Query param with unsupported locale, Accept-Language with supported
        $request = new Request(['locale' => 'fr']);
        $request->headers->set('Accept-Language', 'ru-RU');
        $event = $this->createRequestEvent($request);

        // Act
        ($this->listener)($event);

        // Assert - Should use Accept-Language (second priority)
        $this->assertSame('ru', $request->getLocale());
        $this->assertSame('ru', $request->attributes->get('_locale'));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
