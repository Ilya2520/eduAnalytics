<?php

declare(strict_types=1);

namespace App\Service;

final class CacheKeyBuilder
{
    public function build(string $className, string $methodName, array $params = []): string
    {
        $short = basename(str_replace('\\', '/', $className));
        ksort($params);
        $normalized = array_map(static function ($v) {
            if ($v instanceof \DateTimeInterface) {
                return $v->format(DATE_ATOM);
            }
            if (is_bool($v)) {
                return $v ? '1' : '0';
            }
            if (is_array($v)) {
                return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            }
            return (string) $v;
        }, $params);

        $payload = $short . ':' . $methodName . ':' . implode('|', array_map(
            static fn ($k, $v) => $k . '=' . $v,
            array_keys($normalized),
            array_values($normalized)
        ));

        $hash = hash('xxh3', $payload);
        return sprintf('%s.%s.%s', $short, $methodName, $hash);
    }
} 