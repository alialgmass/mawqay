<?php

namespace Modules\Feedback\Services;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rating figures for the dashboards. Only submitted Surveys count towards averages; the response rate is
 * answered over invited-or-answered. Store-scoped figures are cached briefly under the Store prefix.
 */
class FeedbackReport
{
    private const TTL_SECONDS = 60;

    /**
     * @return array{count: int, overall: float|null, product_match: float|null, delivery_speed: float|null}
     */
    public function averages(?int $storeId, CarbonInterface $from, CarbonInterface $until): array
    {
        return $this->remember($storeId, 'averages', [$from, $until], function () use ($storeId, $from, $until): array {
            $row = $this->submitted($storeId, $from, $until)
                ->selectRaw('count(*) as total, avg(overall_rating) as overall, avg(product_match_rating) as product_match, avg(delivery_speed_rating) as delivery_speed')
                ->first();

            return [
                'count' => (int) $row->total,
                'overall' => $row->overall === null ? null : round((float) $row->overall, 2),
                'product_match' => $row->product_match === null ? null : round((float) $row->product_match, 2),
                'delivery_speed' => $row->delivery_speed === null ? null : round((float) $row->delivery_speed, 2),
            ];
        });
    }

    /**
     * Percent of invited Surveys that were answered, or null when none were invited.
     */
    public function responseRate(?int $storeId, CarbonInterface $from, CarbonInterface $until): ?float
    {
        return $this->remember($storeId, 'response-rate', [$from, $until], function () use ($storeId, $from, $until): ?float {
            // A Survey counts in the period it was invited, or answered when it never was (a copied link).
            $query = DB::table('satisfaction_surveys')
                ->whereRaw('coalesce(sent_at, submitted_at) is not null')
                ->whereBetween(DB::raw('coalesce(sent_at, submitted_at)'), [$from, $until])
                ->when($storeId !== null, fn (Builder $q) => $q->where('store_id', $storeId));

            $invited = (int) (clone $query)->count();

            return $invited === 0 ? null : round((int) (clone $query)->whereNotNull('submitted_at')->count() / $invited * 100, 1);
        });
    }

    /**
     * Average overall rating per Store, best first.
     *
     * @return list<array{store: string, average: float, count: int}>
     */
    public function averageByStore(CarbonInterface $from, CarbonInterface $until): array
    {
        return $this->remember(null, 'average-by-store', [$from, $until], function () use ($from, $until): array {
            return $this->submitted(null, $from, $until)
                ->join('stores', 'stores.id', '=', 'satisfaction_surveys.store_id')
                ->selectRaw('stores.name as store, avg(satisfaction_surveys.overall_rating) as average, count(*) as total')
                ->groupBy('stores.id', 'stores.name')
                ->orderByDesc('average')
                ->get()
                ->map(fn ($row): array => ['store' => $row->store, 'average' => round((float) $row->average, 2), 'count' => (int) $row->total])
                ->all();
        });
    }

    /**
     * Average overall rating for each day in the range, null when nobody answered that day.
     *
     * @return array<string, float|null>
     */
    public function trend(int $storeId, CarbonInterface $from, CarbonInterface $until): array
    {
        return $this->remember($storeId, 'trend', [$from, $until], function () use ($storeId, $from, $until): array {
            $days = [];

            for ($day = $from->copy()->startOfDay(); $day->lessThanOrEqualTo($until); $day = $day->addDay()) {
                $days[$day->toDateString()] = null;
            }

            $rows = $this->submitted($storeId, $from, $until)->selectRaw('date(submitted_at) as day, avg(overall_rating) as average')->groupBy('day')->get();

            foreach ($rows as $row) {
                $days[$row->day] = round((float) $row->average, 2);
            }

            return $days;
        });
    }

    private function submitted(?int $storeId, CarbonInterface $from, CarbonInterface $until): Builder
    {
        return DB::table('satisfaction_surveys')
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$from, $until])
            ->when($storeId !== null, fn (Builder $query) => $query->where('satisfaction_surveys.store_id', $storeId));
    }

    /**
     * @template TValue
     *
     * @param  list<mixed>  $arguments
     * @param  Closure(): TValue  $compute
     * @return TValue
     */
    private function remember(?int $storeId, string $name, array $arguments, Closure $compute): mixed
    {
        $prefix = $storeId === null ? 'platform' : "store:{$storeId}";
        $suffix = md5(json_encode(array_map(fn ($value) => $value instanceof CarbonInterface ? $value->toIso8601String() : $value, $arguments)));

        return Cache::remember("{$prefix}:feedback:{$name}:{$suffix}", self::TTL_SECONDS, $compute);
    }
}
