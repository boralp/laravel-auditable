<?php

namespace Boralp\Auditable\Traits;

use Boralp\Auditable\Models\AuditLog;
use Boralp\Auditable\Models\UserAgent;

trait Auditable
{
    public static function bootAuditable()
    {
        static::creating(function ($model) {
            if ($model->isFillable('creator_id') && auth()->id()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if ($model->isFillable('updater_id') && auth()->id()) {
                $model->updated_by = auth()->id();
            }
        });

        static::created(function ($model) {
            $model->storeAuditLog('created');
        });

        static::updated(function ($model) {
            $model->storeAuditLog('updated', [
                'before' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                'after' => $model->getChanges(),
            ]);
        });
    }

    protected function storeAuditLog($action, $changes = null)
    {
        try {
            $uaId = null;
            if (config('auditable.track_user_agent', true)) {
                $uaString = $this->sanitizeUserAgent(request()->userAgent());
                $uaHash = hash('xxh128', $uaString);

                $uaId = UserAgent::firstOrCreate(
                    ['hash' => $uaHash], [
                        'raw' => $uaString,
                        'device_category' => $this->detectDevice($uaString),
                        'os_name' => $this->detectOS($uaString),
                        'browser_name' => $this->detectBrowser($uaString),
                    ]
                )->id;
            }

            AuditLog::create([
                'auditable_type' => get_class($this),
                'auditable_id' => $this->id,
                'user_id' => config('auditable.track_user_id') ? auth()->id() : null,
                'action' => $action,
                'ip' => config('auditable.track_ip') ? request()->ip() : null,
                'user_agent_id' => $uaId,
                'changes' => config('auditable.track_changes') ? $changes : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Audit log failed: '.$e->getMessage());
        }
    }

    /**
     * Sanitize user agent string
     */
    private function sanitizeUserAgent(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return mb_substr(trim($userAgent), 0, 1024);
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    private function detectBrowser(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        // Order matters - check more specific patterns first
        $browsers = [
            // Microsoft Edge (check before Chrome as it contains "Chrome")
            '/Edg\//i' => 'Edge',
            '/Edge\//i' => 'Edge Legacy',

            // Opera (check before Chrome as newer versions contain "Chrome")
            '/OPR\//i' => 'Opera',
            '/Opera/i' => 'Opera',

            // Chrome-based browsers (check before Chrome)
            '/Brave\//i' => 'Brave',
            '/Vivaldi/i' => 'Vivaldi',
            '/YaBrowser/i' => 'Yandex Browser',
            '/SamsungBrowser/i' => 'Samsung Browser',

            // Chrome (check after Chrome-based browsers)
            '/Chrome/i' => 'Chrome',
            '/CriOS/i' => 'Chrome', // Chrome on iOS

            // Firefox
            '/Firefox/i' => 'Firefox',
            '/FxiOS/i' => 'Firefox', // Firefox on iOS

            // Safari (check after other WebKit browsers)
            '/Safari/i' => 'Safari',

            // Internet Explorer
            '/MSIE/i' => 'Internet Explorer',
            '/Trident/i' => 'Internet Explorer',

            // Mobile browsers
            '/UCBrowser/i' => 'UC Browser',
            '/MiuiBrowser/i' => 'MIUI Browser',
            '/DuckDuckGo/i' => 'DuckDuckGo Browser',

            // Bots and crawlers
            '/Googlebot/i' => 'Googlebot',
            '/Bingbot/i' => 'Bingbot',
            '/facebookexternalhit/i' => 'Facebook Bot',
            '/Twitterbot/i' => 'Twitter Bot',
            '/LinkedInBot/i' => 'LinkedIn Bot',
            '/WhatsApp/i' => 'WhatsApp',
            '/bot/i' => 'Bot',
            '/crawl/i' => 'Crawler',
        ];

        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    private function detectOS(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $operatingSystems = [
            // Windows (simplified)
            '/Windows NT/i' => 'Windows',
            '/Windows/i' => 'Windows',

            // macOS
            '/Mac OS X/i' => 'macOS',
            '/Macintosh/i' => 'macOS',

            // iOS
            '/iPhone OS/i' => 'iOS',
            '/OS.*like Mac OS X/i' => 'iOS', // iPad/iPhone
            '/iPad/i' => 'iOS',

            // Android
            '/Android/i' => 'Android',

            // Linux
            '/Linux/i' => 'Linux',
            '/X11/i' => 'Linux',

            // Other mobile OS
            '/BlackBerry/i' => 'BlackBerry',
            '/Windows Phone/i' => 'Windows Phone',
            '/Windows Mobile/i' => 'Windows Mobile',
            '/webOS/i' => 'webOS',
            '/Palm/i' => 'Palm OS',
            '/Symbian/i' => 'Symbian',

            // Gaming consoles
            '/PlayStation/i' => 'PlayStation',
            '/Xbox/i' => 'Xbox',
            '/Nintendo/i' => 'Nintendo',

            // Smart TV OS
            '/Tizen/i' => 'Tizen',
            '/Smart-TV/i' => 'Smart TV',

            // Unix-like
            '/FreeBSD/i' => 'FreeBSD',
            '/SunOS/i' => 'Solaris',
        ];

        foreach ($operatingSystems as $pattern => $name) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    private function detectDevice(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        // Bot patterns - check first as bots can mimic other devices
        $botPatterns = [
            '/bot/i',
            '/crawl/i',
            '/slurp/i',
            '/spider/i',
            '/mediapartners/i',
            '/facebookexternalhit/i',
            '/WhatsApp/i',
            '/Googlebot/i',
            '/Bingbot/i',
            '/YandexBot/i',
            '/Applebot/i',
            '/LinkedInBot/i',
            '/Twitterbot/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'bot';
            }
        }

        // Tablet patterns - check before mobile since tablets can contain "Mobile"
        $tabletPatterns = [
            '/iPad/i',
            '/Tablet/i',
            '/Nexus 7/i',
            '/Nexus 9/i',
            '/Nexus 10/i',
            '/KFAPWI/i', // Kindle Fire
            '/KFTT/i',   // Kindle Fire HD
            '/KFJWI/i',  // Kindle Fire HD 8.9
            '/KFOT/i',   // Kindle Fire HDX 7
            '/PlayBook/i', // BlackBerry PlayBook
            '/Galaxy Tab/i',
            '/SM-T/i',   // Samsung Galaxy Tab series
            '/Xoom/i',   // Motorola Xoom
        ];

        foreach ($tabletPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'tablet';
            }
        }

        // Special case for Android - check if it's NOT mobile (likely tablet)
        if (preg_match('/Android/i', $userAgent) && ! preg_match('/Mobile/i', $userAgent)) {
            return 'tablet';
        }

        // Mobile patterns
        $mobilePatterns = [
            '/Mobile/i',
            '/Android.*Mobile/i', // Android mobile specifically
            '/iPhone/i',
            '/iPod/i',
            '/BlackBerry/i',
            '/Windows Phone/i',
            '/Windows Mobile/i',
            '/Opera Mini/i',
            '/Opera Mobi/i',
            '/IEMobile/i',
            '/Mobile Safari/i',
            '/Nokia/i',
            '/webOS/i',
            '/Palm/i',
            '/Symbian/i',
        ];

        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'mobile';
            }
        }

        // Smart TV / Console patterns (optional - you might want these as separate categories)
        $tvPatterns = [
            '/Smart-TV/i',
            '/SmartTV/i',
            '/GoogleTV/i',
            '/Apple TV/i',
            '/NetCast/i',
            '/PlayStation/i',
            '/Xbox/i',
            '/Nintendo/i',
        ];

        foreach ($tvPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'tv'; // or you could return 'desktop' if you don't want a separate category
            }
        }

        // Default fallback
        return 'desktop';
    }
}
