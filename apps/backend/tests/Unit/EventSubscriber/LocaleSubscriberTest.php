<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriberTest extends TestCase
{
    private LocaleSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new LocaleSubscriber();
    }

    /** @test */
    public function testGetSubscribedEvents(): void
    {
        // Act
        $events = LocaleSubscriber::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame([['onKernelRequest', 20]], $events[KernelEvents::REQUEST]);
    }

    /** @test */
    public function testSetsLocaleFromAcceptLanguageHeader(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('Accept-Language', 'ru-RU,ru;q=0.9,en-US;q=0.8');
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert
        $this->assertSame('ru', $request->getLocale());
    }

    /** @test */
    public function testFallsBackToDefaultLocale(): void
    {
        // Arrange - Request without Accept-Language header
        $request = new Request();
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert
        $this->assertSame('en', $request->getLocale());
    }

    /** @test */
    public function testSkipsAdminRoutes(): void
    {
        // Arrange - Admin route
        $request = Request::create('/admin/dashboard');
        $request->headers->set('Accept-Language', 'ru-RU');
        $event = $this->createRequestEvent($request);

        // Save original locale
        $originalLocale = $request->getLocale();

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert - Locale should not be changed for admin routes
        $this->assertSame($originalLocale, $request->getLocale());
    }

    /** @test */
    public function testSupportsOnlyConfiguredLocales(): void
    {
        // Arrange - Unsupported locale (fr)
        $request = new Request();
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9');
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert - Should fall back to default 'en'
        $this->assertSame('en', $request->getLocale());
    }

    /** @test */
    public function testParsesQualityValues(): void
    {
        // Arrange - Multiple languages with quality values, 'en' has higher quality
        $request = new Request();
        $request->headers->set('Accept-Language', 'fr;q=0.5,en;q=0.9,ru;q=0.7');
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert - Should choose 'en' (highest quality among supported locales)
        $this->assertSame('en', $request->getLocale());
    }

    /** @test */
    public function testHandlesComplexAcceptLanguageHeader(): void
    {
        // Arrange - Real-world complex header
        $request = new Request();
        $request->headers->set('Accept-Language', 'fr-CH,fr;q=0.9,en;q=0.8,de;q=0.7,*;q=0.5');
        $event = $this->createRequestEvent($request);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert - Should choose 'en' (highest quality among supported: en=0.8)
        $this->assertSame('en', $request->getLocale());
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
