<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Результат транскрипции аудио в текст
 *
 * Value Object для хранения результата работы Whisper STT
 * Immutable по принципу DDD
 */
final readonly class TranscriptionResult implements \JsonSerializable
{
    /**
     * @param string $text Транскрибированный текст
     * @param string $language Определенный язык (ru, en и т.д.)
     * @param float $confidence Уверенность в транскрипции (0.0-1.0)
     * @param int|null $durationMs Длительность аудио в миллисекундах
     */
    public function __construct(
        public string $text,
        public string $language,
        public float $confidence,
        public ?int $durationMs = null
    ) {
        // Валидация уверенности
        if ($this->confidence < 0.0 || $this->confidence > 1.0) {
            throw new \InvalidArgumentException('Confidence must be between 0.0 and 1.0');
        }

        // Валидация языка
        if (!in_array($this->language, ['ru', 'en', 'uk'], true)) {
            throw new \InvalidArgumentException('Unsupported language: ' . $this->language);
        }
    }

    /**
     * Создать из массива (для десериализации)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? '',
            language: $data['language'] ?? 'ru',
            confidence: (float) ($data['confidence'] ?? 0.0),
            durationMs: isset($data['duration_ms']) ? (int) $data['duration_ms'] : null
        );
    }

    /**
     * Проверить, достаточна ли уверенность для использования
     */
    public function isConfident(float $threshold = 0.7): bool
    {
        return $this->confidence >= $threshold;
    }

    /**
     * Получить длительность в секундах
     */
    public function getDurationInSeconds(): ?float
    {
        return $this->durationMs !== null ? $this->durationMs / 1000.0 : null;
    }

    /**
     * Сериализация в JSON
     */
    public function jsonSerialize(): array
    {
        return [
            'text' => $this->text,
            'language' => $this->language,
            'confidence' => $this->confidence,
            'duration_ms' => $this->durationMs,
        ];
    }

    /**
     * Преобразование в строку для логирования
     */
    public function __toString(): string
    {
        return sprintf(
            'TranscriptionResult[text="%s", lang=%s, confidence=%.2f]',
            substr($this->text, 0, 50) . (strlen($this->text) > 50 ? '...' : ''),
            $this->language,
            $this->confidence
        );
    }
}