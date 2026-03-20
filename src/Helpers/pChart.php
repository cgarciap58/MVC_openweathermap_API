<?php

declare(strict_types=1);

final class ChartHelper
{
    private const CACHE_DIRECTORY = __DIR__ . '/../cache/charts';
    private const PUBLIC_PREFIX = 'cache/charts';
    private const PCHART_BOOTSTRAP_FILES = [
        __DIR__ . '/pChart/class/pData.class.php',
        __DIR__ . '/pChart/class/pDraw.class.php',
        __DIR__ . '/pChart/class/pImage.class.php',
    ];

    public static function render(array $config): ?string
    {
        if (($config['dataset']['labels'] ?? []) === [] || ($config['dataset']['series'] ?? []) === []) {
            return null;
        }

        self::ensureCacheDirectory();

        $filename = self::buildFilename($config);
        $absolutePath = self::CACHE_DIRECTORY . '/' . $filename;
        self::cleanupExistingCharts((string) ($config['slug'] ?? 'chart'), $filename);

        if (!file_exists($absolutePath)) {
            if (self::canUsePChart()) {
                self::renderWithPChart($config, $absolutePath);
            } elseif (self::canUseGd()) {
                self::renderWithGdFallback($config, $absolutePath);
            } else {
                return null;
            }
        }
        return is_file($absolutePath) ? self::PUBLIC_PREFIX . '/' . $filename : null;
    }

    private static function ensureCacheDirectory(): void
    {
        if (!is_dir(self::CACHE_DIRECTORY)) {
            mkdir(self::CACHE_DIRECTORY, 0775, true);
        }
    }

    private static function buildFilename(array $config): string
    {
        return ($config['slug'] ?? 'chart') . '.png';
    }

    private static function canUsePChart(): bool
    {
        self::bootstrapPChart();
        return class_exists('pData') && class_exists('pImage') && class_exists('pDraw');
    }

    private static function canUseGd(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecolorallocate')
            && function_exists('imagefill')
            && function_exists('imagepng')
            && function_exists('imagedestroy');
    }


    private static function bootstrapPChart(): void
    {
        foreach (self::PCHART_BOOTSTRAP_FILES as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    private static function cleanupExistingCharts(string $slug, string $currentFilename): void
    {
        $pattern = self::CACHE_DIRECTORY . '/' . $slug . '*.png';
        foreach (glob($pattern) ?: [] as $existingChart) {
            if (basename($existingChart) !== $currentFilename) {
                @unlink($existingChart);
            }
        }
    }

    private static function renderWithPChart(array $config, string $absolutePath): void
    {
        $labels = $config['dataset']['labels'];
        $series = $config['dataset']['series'];
        $width = (int) ($config['width'] ?? 720);
        $height = (int) ($config['height'] ?? 360);

        $data = new pData();
        $data->addPoints($labels, 'Labels');
        $data->setAbscissa('Labels');

        foreach ($series as $serie) {
            $data->addPoints($serie['values'], $serie['label']);
        }

        $image = new pImage($width, $height, $data);
        $image->drawFilledRectangle(0, 0, $width, $height, ['R' => 248, 'G' => 250, 'B' => 252]);
        $image->setFontProperties(['FontName' => __DIR__ . '/fonts/DejaVuSans.ttf', 'FontSize' => 10]);
        $image->setGraphArea(60, 40, $width - 40, $height - 60);
        $image->drawScale(['CycleBackground' => true, 'GridR' => 215, 'GridG' => 223, 'GridB' => 230]);

        if (($config['type'] ?? 'line') === 'bar') {
            $image->drawBarChart(['DisplayValues' => true, 'Rounded' => true]);
        } else {
            $image->drawLineChart(['DisplayValues' => true, 'DisplayColor' => DISPLAY_AUTO]);
            $image->drawPlotChart(['PlotBorder' => true, 'PlotSize' => 4]);
        }

        $image->drawLegend($width - 180, 20, ['Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL]);
        $image->render($absolutePath);
    }

    private static function renderWithGdFallback(array $config, string $absolutePath): void
    {
        $width = (int) ($config['width'] ?? 720);
        $height = (int) ($config['height'] ?? 360);
        $paddingLeft = 60;
        $paddingRight = 30;
        $paddingTop = 40;
        $paddingBottom = 60;
        $labels = $config['dataset']['labels'];
        $series = $config['dataset']['series'];
        $type = $config['type'] ?? 'line';

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return;
        }

        $white = imagecolorallocate($image, 248, 250, 252);
        $axis = imagecolorallocate($image, 100, 116, 139);
        $grid = imagecolorallocate($image, 226, 232, 240);
        $text = imagecolorallocate($image, 15, 23, 42);
        $palette = [
            imagecolorallocate($image, 59, 130, 246),
            imagecolorallocate($image, 16, 185, 129),
            imagecolorallocate($image, 249, 115, 22),
            imagecolorallocate($image, 168, 85, 247),
        ];

        imagefill($image, 0, 0, $white);

        $allValues = [];
        foreach ($series as $serie) {
            foreach ($serie['values'] as $value) {
                $allValues[] = (float) $value;
            }
        }

        $minValue = $allValues !== [] ? floor(min($allValues)) : 0.0;
        $maxValue = $allValues !== [] ? ceil(max($allValues)) : 1.0;
        if ($minValue === $maxValue) {
            $maxValue += 1;
        }

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $gridLines = 5;

        for ($line = 0; $line <= $gridLines; $line++) {
            $y = $paddingTop + (int) round(($plotHeight / $gridLines) * $line);
            imageline($image, $paddingLeft, $y, $width - $paddingRight, $y, $grid);
            $value = $maxValue - (($maxValue - $minValue) / $gridLines) * $line;
            imagestring($image, 2, 8, $y - 7, (string) round($value, 1), $axis);
        }

        imageline($image, $paddingLeft, $paddingTop, $paddingLeft, $height - $paddingBottom, $axis);
        imageline($image, $paddingLeft, $height - $paddingBottom, $width - $paddingRight, $height - $paddingBottom, $axis);

        $pointCount = max(count($labels), 1);
        $baseXStep = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : $plotWidth;

        if ($type === 'bar') {
            $groupWidth = $plotWidth / $pointCount;
            $barWidth = max((int) floor(($groupWidth * 0.7) / max(count($series), 1)), 12);

            foreach ($labels as $index => $label) {
                $groupX = $paddingLeft + (int) round($index * $groupWidth);
                imagestringup($image, 2, $groupX + 18, $height - 8, (string) $label, $text);
            }

            foreach ($series as $seriesIndex => $serie) {
                foreach ($serie['values'] as $index => $rawValue) {
                    $value = (float) $rawValue;
                    $x1 = $paddingLeft + (int) round($index * $groupWidth) + 10 + ($seriesIndex * $barWidth);
                    $x2 = $x1 + $barWidth - 4;
                    $y1 = self::mapValueToY($value, $minValue, $maxValue, $paddingTop, $plotHeight);
                    imagefilledrectangle($image, $x1, $y1, $x2, $height - $paddingBottom - 1, $palette[$seriesIndex % count($palette)]);
                    imagestring($image, 2, $x1, $y1 - 15, (string) round($value, 1), $text);
                }
            }
        } else {
            foreach ($labels as $index => $label) {
                $x = $paddingLeft + (int) round($baseXStep * $index);
                imagestringup($image, 2, $x + 4, $height - 8, (string) $label, $text);
            }

            foreach ($series as $seriesIndex => $serie) {
                $color = $palette[$seriesIndex % count($palette)];
                $previous = null;

                foreach ($serie['values'] as $index => $rawValue) {
                    $value = (float) $rawValue;
                    $x = $paddingLeft + (int) round($baseXStep * $index);
                    $y = self::mapValueToY($value, $minValue, $maxValue, $paddingTop, $plotHeight);

                    if ($previous !== null) {
                        imageline($image, $previous['x'], $previous['y'], $x, $y, $color);
                    }

                    imagefilledellipse($image, $x, $y, 8, 8, $color);
                    imagestring($image, 2, $x - 8, $y - 18, (string) round($value, 1), $text);
                    $previous = ['x' => $x, 'y' => $y];
                }
            }
        }

        imagestring($image, 5, 16, 12, (string) ($config['title'] ?? 'Gráfico meteorológico'), $text);
        foreach ($series as $seriesIndex => $serie) {
            $legendY = 18 + ($seriesIndex * 18);
            imagefilledrectangle($image, $width - 165, $legendY, $width - 150, $legendY + 10, $palette[$seriesIndex % count($palette)]);
            imagestring($image, 3, $width - 145, $legendY - 1, (string) $serie['label'], $text);
        }

        imagepng($image, $absolutePath);
        imagedestroy($image);
    }

    private static function mapValueToY(float $value, float $minValue, float $maxValue, int $paddingTop, int $plotHeight): int
    {
        $normalized = ($value - $minValue) / ($maxValue - $minValue);
        return $paddingTop + (int) round($plotHeight - ($normalized * $plotHeight));
    }
}
