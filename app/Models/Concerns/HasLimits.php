<?php

namespace App\Models\Concerns;

use App\Support\Decimal;

/**
 * An accepted range in nullable min / max columns (inclusive; at least one is set),
 * shared by material specification limits and inspection plan characteristics.
 */
trait HasLimits
{
    public function accepts(string $value): bool
    {
        return ($this->min === null || Decimal::compare($value, $this->min) >= 0)
            && ($this->max === null || Decimal::compare($value, $this->max) <= 0);
    }

    /**
     * "0.24 max", "355 min", "470 – 630".
     */
    public function rangeLabel(): string
    {
        return match (true) {
            $this->min !== null && $this->max !== null => Decimal::format($this->min).' – '.Decimal::format($this->max),
            $this->min !== null => __(':value min', ['value' => Decimal::format($this->min)]),
            $this->max !== null => __(':value max', ['value' => Decimal::format($this->max)]),
            default => '—',
        };
    }
}
