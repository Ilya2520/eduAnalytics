<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheService
{
    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получает данные из кэша по ключу или вызывает колбэк для их получения.
     */
    public function get(string $key, callable $callback, array $tags = [], int $ttl = 3600): mixed
    {
        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($callback, $tags, $ttl) {
                // Устанавливаем теги
                if (!empty($tags)) {
                    $item->tag($tags);
                }

                // Устанавливаем TTL
                $item->expiresAfter($ttl);

                // Логируем создание кэша
                $this->logger->info('Cache miss, generating new value', [
                    'key' => $item->getKey(),
                    'tags' => $tags,
                    'ttl' => $ttl
                ]);

                return $callback();
            });
        } catch (\Exception $e) {
            $this->logger->error('Cache error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            // Возвращаем результат колбэка без кэширования
            return $callback();
        }
    }

    /**
     * Устаревший метод для обратной совместимости.
     */
    public function fetchFromCache(string $key, string $tag, callable $callback, int $ttl = 3600): mixed
    {
        return $this->get($key, $callback, [$tag], $ttl);
    }

    /**
     * Генерирует ключ кэша на основе класса, префикса и параметров.
     */
    public function generateCacheKey(string $className, string $prefix, array $params = []): string
    {
        $shortClassName = basename(str_replace('\\', '/', $className));

        // Сортируем параметры для консистентности
        ksort($params);

        // Создаем хэш только если есть параметры
        if (!empty($params)) {
            $paramsString = json_encode($params, JSON_THROW_ON_ERROR);
            $hash = hash('xxh3', $paramsString); // Более быстрый хэш

            return sprintf('%s.%s.%s', $shortClassName, $prefix, $hash);
        }

        return sprintf('%s.%s', $shortClassName, $prefix);
    }

    /**
     * Инвалидирует кэш по тегам.
     */
    public function invalidateByTags(array $tags): bool
    {
        try {
            $result = $this->cache->invalidateTags($tags);

            $this->logger->info('Cache invalidated by tags', [
                'tags' => $tags,
                'success' => $result
            ]);

            return $result;
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cache invalidation error', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Инвалидирует кэш по одному тегу.
     * @deprecated Используйте invalidateByTags() вместо этого
     */
    public function invalidateByTag(string $tag): bool
    {
        return $this->invalidateByTags([$tag]);
    }

    /**
     * Удаляет конкретный элемент кэша.
     */
    public function delete(string $key): bool
    {
        try {
            $result = $this->cache->deleteItem($key);

            $this->logger->info('Cache item deleted', [
                'key' => $key,
                'success' => $result
            ]);

            return $result;
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cache delete error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Удаляет несколько элементов кэша.
     */
    public function deleteMultiple(array $keys): bool
    {
        try {
            $result = $this->cache->deleteItems($keys);

            $this->logger->info('Multiple cache items deleted', [
                'keys' => $keys,
                'success' => $result
            ]);

            return $result;
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Multiple cache delete error', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Очищает весь кэш.
     */
    public function clear(): bool
    {
        try {
            $result = $this->cache->clear();

            $this->logger->warning('All cache cleared');

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Cache clear error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Проверяет наличие элемента в кэше.
     */
    public function hasItem(string $key): bool
    {
        try {
            return $this->cache->hasItem($key);
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cache has item check error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
