<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Performance\Helper;

/**
 * CLI progress display for the seeder.
 *
 * Output: [Phase: Messages] Batch 8/16 | 4.000.000/8.000.000 | 45.230 rows/s | ETA: 1:28
 */
class Progress
{
    private string $phase      = '';
    private int    $total      = 0;
    private int    $current    = 0;
    private float  $startTime  = 0.0;
    private float  $lastTime   = 0.0;
    private int    $lastCount  = 0;

    public function start(string $phase, int $total): void
    {
        $this->phase     = $phase;
        $this->total     = $total;
        $this->current   = 0;
        $this->startTime = microtime(true);
        $this->lastTime  = $this->startTime;
        $this->lastCount = 0;

        echo "\n";
        $this->render(0);
    }

    public function update(int $current): void
    {
        $this->current = $current;
        $this->render($current);
    }

    public function finish(): void
    {
        $this->render($this->total);
        $elapsed = microtime(true) - $this->startTime;
        $rps     = $this->total > 0 ? (int) ($this->total / max($elapsed, 0.001)) : 0;
        echo "\n  Done in " . $this->formatDuration((int) $elapsed)
            . " | avg " . number_format($rps, 0, ',', '.') . " rows/s\n";
    }

    public function log(string $msg): void
    {
        echo "  » {$msg}\n";
    }

    // -------------------------------------------------------------------------

    private function render(int $current): void
    {
        $now        = microtime(true);
        $elapsed    = $now - $this->startTime;
        $interval   = $now - $this->lastTime;

        // Current throughput rate (last interval)
        $rps = $interval > 0.1
            ? (int) (($current - $this->lastCount) / $interval)
            : 0;

        if ($interval > 0.5) {
            $this->lastTime  = $now;
            $this->lastCount = $current;
        }

        // Calculate ETA
        $eta = '';
        if ($rps > 0 && $current < $this->total) {
            $remaining = $this->total - $current;
            $etaSec    = (int) ($remaining / $rps);
            $eta       = ' | ETA: ' . $this->formatDuration($etaSec);
        }

        // Percentage
        $pct = $this->total > 0
            ? min(100, (int) ($current / $this->total * 100))
            : 100;

        $line = sprintf(
            "\r  [%-12s] %3d%% | %s / %s rows | %s rows/s%s    ",
            $this->phase,
            $pct,
            number_format($current, 0, ',', '.'),
            number_format($this->total, 0, ',', '.'),
            number_format($rps, 0, ',', '.'),
            $eta
        );

        echo $line;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m < 60) {
            return "{$m}m {$s}s";
        }
        $h = intdiv($m, 60);
        $m = $m % 60;
        return "{$h}h {$m}m";
    }
}
