<?php

namespace App\Services;

class YoutubeUrlParser
{
    public function id(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) return null;
        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host']));
        $path = trim($parts['path'] ?? '', '/');
        $id = null;

        if ($host === 'youtu.be') {
            $id = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $segments = explode('/', $path);
            $id = $path === 'watch' ? ($query['v'] ?? null)
                : (in_array($segments[0] ?? '', ['embed', 'shorts', 'live'], true)
                    ? ($segments[1] ?? null) : null);
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }
}
