<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const SHARED_USER_FIELDS = [
        'id_satker',
        'id_kejati',
        'id_kejari',
        'id_level',
        'id_hidesatker',
        'satkernama',
        'id_sakip_level',
    ];

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->safeUser($request->user()),
            ],
        ];
    }

    private function safeUser($user): ?array
    {
        if (! $user) {
            return null;
        }

        return collect(self::SHARED_USER_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => $user->{$field} ?? null])
            ->all();
    }
}
