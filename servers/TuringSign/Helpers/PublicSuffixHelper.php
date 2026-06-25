<?php
declare(strict_types=1);

namespace ModulesGarden\TuringSign\Helpers;

final class PublicSuffixHelper
{
    private const PSL_URL   = 'https://publicsuffix.org/list/public_suffix_list.dat';
    private const CACHE_TTL = 86400;

    public static function isSubdomain(string $domain): bool
    {
        $domain = self::normalizeDomain($domain);

        if($domain === '')
        {
            return false;
        }

        $parts = explode('.', $domain);

        if(count($parts) < 2)
        {
            return false;
        }

        $suffix = self::getPublicSuffix($domain);

        if($suffix === null)
        {
            return count($parts) > 2;
        }

        $suffixParts = substr_count($suffix, '.') + 1;

        return count($parts) > ($suffixParts + 1);
    }

    public static function getPublicSuffix(string $domain): ?string
    {
        $domain = self::normalizeDomain($domain);

        if($domain === '')
        {
            return null;
        }

        $rules      = self::loadRules();
        $labels     = explode('.', $domain);
        $labelCount = count($labels);
        $matches    = [];

        for($i = 0; $i < $labelCount; $i++)
        {
            $candidate = implode('.', array_slice($labels, $i));

            if(isset($rules['exceptions'][$candidate]))
            {
                return implode('.', array_slice($labels, $i + 1));
            }

            if(isset($rules['rules'][$candidate]))
            {
                $matches[] = $candidate;
            }

            if($i > 0)
            {
                $wildcardBase = implode('.', array_slice($labels, $i + 1));

                if($wildcardBase !== '' && isset($rules['wildcards'][$wildcardBase]))
                {
                    $matches[] = $candidate;
                }
            }
        }

        if(empty($matches))
        {
            return end($labels);
        }

        usort($matches, static function(string $a, string $b): int {
            return substr_count($b, '.') <=> substr_count($a, '.');
        });

        return $matches[0];
    }

    public static function findBadUtf8($value, $path = '')
    {
        if(is_array($value))
        {
            foreach($value as $k => $v)
            {
                $result = self::findBadUtf8($v, $path === '' ? (string)$k : $path . '.' . $k);

                if($result !== null)
                {
                    return $result;
                }
            }

            return null;
        }

        if(is_string($value) && !mb_check_encoding($value, 'UTF-8'))
        {
            return [
                'path'   => $path,
                'hex'    => bin2hex($value),
                'base64' => base64_encode($value),
                'length' => strlen($value),
            ];
        }

        return null;
    }


    public static function refreshCache(): bool
    {
        $content = self::downloadPsl();

        if($content === null || trim($content) === '')
        {
            return false;
        }

        $parsed = self::parsePsl($content);

        if(empty($parsed['rules']) && empty($parsed['wildcards']) && empty($parsed['exceptions']))
        {
            return false;
        }

        $payload = [
            'updated_at' => time(),
            'source'     => self::PSL_URL,
            'rules'      => array_keys($parsed['rules']),
            'wildcards'  => array_keys($parsed['wildcards']),
            'exceptions' => array_keys($parsed['exceptions']),
        ];

        return file_put_contents(self::getCacheFilePath(), json_encode($payload, JSON_UNESCAPED_SLASHES)) !== false;
    }

    private static function loadRules(): array
    {
        $cacheFile = self::getCacheFilePath();

        if(is_file($cacheFile))
        {
            $json = file_get_contents($cacheFile);
            $data = json_decode((string)$json, true);

            if(is_array($data) && isset($data['updated_at'], $data['rules'], $data['wildcards'], $data['exceptions']))
            {
                if((time() - (int)$data['updated_at']) < self::CACHE_TTL)
                {
                    return [
                        'rules'      => array_fill_keys($data['rules'], true),
                        'wildcards'  => array_fill_keys($data['wildcards'], true),
                        'exceptions' => array_fill_keys($data['exceptions'], true),
                    ];
                }
            }
        }

        if(self::refreshCache())
        {
            $json = file_get_contents($cacheFile);
            $data = json_decode((string)$json, true);

            if(is_array($data) && isset($data['rules'], $data['wildcards'], $data['exceptions']))
            {
                return [
                    'rules'      => array_fill_keys($data['rules'], true),
                    'wildcards'  => array_fill_keys($data['wildcards'], true),
                    'exceptions' => array_fill_keys($data['exceptions'], true),
                ];
            }
        }

        if(is_file($cacheFile))
        {
            $json = file_get_contents($cacheFile);
            $data = json_decode((string)$json, true);

            if(is_array($data) && isset($data['rules'], $data['wildcards'], $data['exceptions']))
            {
                return [
                    'rules'      => array_fill_keys($data['rules'], true),
                    'wildcards'  => array_fill_keys($data['wildcards'], true),
                    'exceptions' => array_fill_keys($data['exceptions'], true),
                ];
            }
        }

        return [
            'rules'      => [],
            'wildcards'  => [],
            'exceptions' => [],
        ];
    }

    private static function parsePsl(string $content): array
    {
        $rules      = [];
        $wildcards  = [];
        $exceptions = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line)
        {
            $line = trim($line);

            if($line === '' || str_starts_with($line, '//'))
            {
                continue;
            }

            if(str_starts_with($line, '!'))
            {
                $exceptions[substr($line, 1)] = true;
                continue;
            }

            if(str_starts_with($line, '*.'))
            {
                $wildcards[substr($line, 2)] = true;
                continue;
            }

            $rules[$line] = true;
        }

        return [
            'rules'      => $rules,
            'wildcards'  => $wildcards,
            'exceptions' => $exceptions,
        ];
    }

    private static function downloadPsl(): ?string
    {
        $context = stream_context_create([
                                             'http' => [
                                                 'timeout'       => 15,
                                                 'ignore_errors' => true,
                                                 'user_agent'    => 'TuringSign PublicSuffixHelper/1.0',
                                             ],
                                             'ssl'  => [
                                                 'verify_peer'      => true,
                                                 'verify_peer_name' => true,
                                             ],
                                         ]);

        $content = @file_get_contents(self::PSL_URL, false, $context);

        if($content === false)
        {
            return null;
        }

        return $content;
    }

    private static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('~^https?://~', '', $domain);
        $domain = preg_replace('~/.*$~', '', $domain);
        $domain = trim($domain, '.');

        if(str_starts_with($domain, '*.'))
        {
            $domain = substr($domain, 2);
        }

        return $domain;
    }

    private static function getCacheFilePath(): string
    {
        return dirname(__DIR__) . '/Storage/public_suffix_cache.json';
    }
}
