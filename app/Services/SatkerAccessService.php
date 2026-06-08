<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SatkerAccessService
{
    private const SYSTEM_SATKER_IDS = [
        888881,
        888882,
        999999,
        '888881',
        '888882',
        '999999',
        'admin',
        'Pengawasan',
        'Panev',
        'menpanrb',
    ];

    private const ADMIN_SATKER_IDS = [
        'admin',
        '999999',
        'Pengawasan',
        'Panev',
        'menpanrb',
    ];

    public function currentSatkerId(): string
    {
        return (string) session('id_satker', auth()->user()?->id_satker ?? '');
    }

    public function currentLevel(): int
    {
        return (int) session('id_sakip_level', auth()->user()?->id_sakip_level ?? 0);
    }

    public function currentLogin()
    {
        $idSatker = $this->currentSatkerId();

        return $idSatker !== ''
            ? DB::table('sinori_login')->where('id_satker', $idSatker)->first()
            : null;
    }

    public function isAdmin(?string $idSatker = null, $level = null): bool
    {
        $idSatker = (string) ($idSatker ?? $this->currentSatkerId());
        $level = (int) ($level ?? $this->currentLevel());

        return $level === 99 || in_array($idSatker, self::ADMIN_SATKER_IDS, true);
    }

    public function canSeeAllSatkers(?string $idSatker = null, $level = null): bool
    {
        $idSatker = (string) ($idSatker ?? $this->currentSatkerId());
        $level = (int) ($level ?? $this->currentLevel());

        return in_array($level, [99, 1, 0], true)
            || in_array($idSatker, self::ADMIN_SATKER_IDS, true);
    }

    public function canOpenScopedSatkerPage(?string $idSatker = null, $level = null): bool
    {
        $level = (int) ($level ?? $this->currentLevel());

        return in_array($level, [99, 2, 1, 0], true);
    }

    public function abortUnlessAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }

    public function abortUnlessScopedSatkerPage(): void
    {
        abort_unless($this->canOpenScopedSatkerPage(), 403);
    }

    public function baseSatkerQuery(): Builder
    {
        return DB::table('sinori_login')
            ->whereIn('id_sakip_level', [1, 2, 3, 4])
            ->whereNotIn('id_satker', self::SYSTEM_SATKER_IDS)
            ->where('id_satker', 'not like', 'was%')
            ->where('id_satker', 'not like', '00budi')
            ->where('id_kejati', 'not like', '87');
    }

    public function scopedSatkerQuery(?string $idSatker = null, $level = null, $login = null): Builder
    {
        $idSatker = (string) ($idSatker ?? $this->currentSatkerId());
        $level = (int) ($level ?? $this->currentLevel());
        $login = $login ?? $this->currentLogin();
        $query = $this->baseSatkerQuery();

        if ($this->canSeeAllSatkers($idSatker, $level)) {
            return $query;
        }

        if ($level === 2 && $login?->id_kejati) {
            return $query->where('id_kejati', $login->id_kejati);
        }

        return $query->where('id_satker', $idSatker);
    }

    public function accessibleSatkerIds(?string $idSatker = null, $level = null, $login = null): Collection
    {
        return $this->scopedSatkerQuery($idSatker, $level, $login)
            ->pluck('id_satker')
            ->map(fn ($satker) => (string) $satker)
            ->values();
    }

    public function canAccessSatker(string $targetSatker, ?string $idSatker = null, $level = null, $login = null): bool
    {
        return $this->scopedSatkerQuery($idSatker, $level, $login)
            ->where('id_satker', $targetSatker)
            ->exists();
    }

    public function abortUnlessCanAccessSatker(string $targetSatker): void
    {
        abort_unless($this->canAccessSatker($targetSatker), 403);
    }

    public function satker(string $idSatker)
    {
        return $this->baseSatkerQuery()
            ->where('id_satker', $idSatker)
            ->first(['id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level']);
    }
}
