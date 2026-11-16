<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

class CompanyTheme
{
    /**
     * @var array<string, array{label: string, primary: string, secondary: string}>
     */
    private const PALETTES = [
        'default' => [
            'label' => 'هم‌ره',
            'primary' => '#087E78',
            'secondary' => '#3976B8',
        ],
        'ocean' => [
            'label' => 'اقیانوس',
            'primary' => '#0F766E',
            'secondary' => '#2563EB',
        ],
        'forest' => [
            'label' => 'جنگل',
            'primary' => '#166534',
            'secondary' => '#CA8A04',
        ],
        'violet' => [
            'label' => 'بنفش',
            'primary' => '#6D28D9',
            'secondary' => '#0891B2',
        ],
        'graphite' => [
            'label' => 'گرافیتی',
            'primary' => '#475569',
            'secondary' => '#0F766E',
        ],
    ];

    private const DENSITIES = ['compact', 'balanced', 'comfortable'];

    /**
     * Resolve the appearance of the current company. No company context means
     * the neutral Hamareh defaults, never another company's saved settings.
     *
     * @return array{palette: string, primary: string, secondary: string, density: string}
     */
    public function current(): array
    {
        $defaults = $this->defaultTheme();
        $companyId = app(CompanyContext::class)->currentCompanyId();

        if ($companyId === null) {
            return $defaults;
        }

        try {
            $stored = app(CompanySettings::class)->get($companyId, 'ui.theme', []);
        } catch (RuntimeException | JsonException) {
            return $defaults;
        }

        if (! is_array($stored)) {
            return $defaults;
        }

        $palette = $stored['palette'] ?? 'default';

        if (! is_string($palette) || (! array_key_exists($palette, self::PALETTES) && $palette !== 'custom')) {
            $palette = 'default';
        }

        $preset = self::PALETTES[$palette] ?? self::PALETTES['default'];
        $primary = $this->normalizeHex($stored['primary'] ?? null) ?? $preset['primary'];
        $secondary = $this->normalizeHex($stored['secondary'] ?? null) ?? $preset['secondary'];
        $density = $stored['density'] ?? 'balanced';

        if (! is_string($density) || ! in_array($density, self::DENSITIES, true)) {
            $density = 'balanced';
        }

        if (
            $palette !== 'custom'
            && ($primary !== $preset['primary'] || $secondary !== $preset['secondary'])
        ) {
            $palette = 'custom';
        }

        return compact('palette', 'primary', 'secondary', 'density');
    }

    /**
     * @return array<string, string>
     */
    public static function paletteOptions(): array
    {
        return array_map(
            fn (array $palette): string => $palette['label'],
            self::PALETTES,
        );
    }

    /**
     * @return array{label: string, primary: string, secondary: string}|null
     */
    public function preset(string $palette): ?array
    {
        return self::PALETTES[$palette] ?? null;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function save(array $settings): void
    {
        $companyId = app(CompanyContext::class)->requireCompanyId();
        $palette = $settings['palette'] ?? null;
        $density = $settings['density'] ?? null;

        if (! is_string($palette) || (! array_key_exists($palette, self::PALETTES) && $palette !== 'custom')) {
            throw ValidationException::withMessages([
                'themePalette' => 'پالت انتخاب‌شده معتبر نیست.',
            ]);
        }

        if (! is_string($density) || ! in_array($density, self::DENSITIES, true)) {
            throw ValidationException::withMessages([
                'themeDensity' => 'تراکم انتخاب‌شده معتبر نیست.',
            ]);
        }

        $primary = $this->normalizeHex($settings['primary'] ?? null);
        $secondary = $this->normalizeHex($settings['secondary'] ?? null);

        if ($primary === null) {
            throw ValidationException::withMessages([
                'themePrimary' => 'رنگ اصلی باید کد HEX شش‌رقمی معتبر باشد.',
            ]);
        }

        if ($secondary === null) {
            throw ValidationException::withMessages([
                'themeSecondary' => 'رنگ ثانویه باید کد HEX شش‌رقمی معتبر باشد.',
            ]);
        }

        if ($palette !== 'custom') {
            $preset = self::PALETTES[$palette];

            if ($primary !== $preset['primary'] || $secondary !== $preset['secondary']) {
                $palette = 'custom';
            }
        }

        app(CompanySettings::class)->setTheme(
            $companyId,
            [
                'palette' => $palette,
                'primary' => $primary,
                'secondary' => $secondary,
                'density' => $density,
            ],
        );
    }

    public function density(): string
    {
        return $this->current()['density'];
    }

    public function styleTag(): string
    {
        $theme = $this->current();

        return '<style id="hm-company-theme">:root{--hm-company-primary:'.$theme['primary'].';--hm-company-secondary:'.$theme['secondary'].'}</style>';
    }

    /**
     * @return array{palette: string, primary: string, secondary: string, density: string}
     */
    private function defaultTheme(): array
    {
        $preset = self::PALETTES['default'];

        return [
            'palette' => 'default',
            'primary' => $preset['primary'],
            'secondary' => $preset['secondary'],
            'density' => 'balanced',
        ];
    }

    private function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value))) {
            return null;
        }

        return strtoupper(trim($value));
    }
}
