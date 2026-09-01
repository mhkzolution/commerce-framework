<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Models\Register;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

final class PosRegisterResolver
{
    private const SESSION_KEY = 'commerce.pos.register_uuid';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function resolve(Request $request): Register
    {
        if ($request->filled('register')) {
            $register = Register::query()
                ->where('uuid', $request->string('register')->toString())
                ->where('is_active', true)
                ->first();

            if ($register !== null) {
                $this->session->put(self::SESSION_KEY, $register->uuid);

                return $register;
            }
        }

        $storedUuid = $this->session->get(self::SESSION_KEY);

        if (is_string($storedUuid) && $storedUuid !== '') {
            $register = Register::query()
                ->where('uuid', $storedUuid)
                ->where('is_active', true)
                ->first();

            if ($register !== null) {
                return $register;
            }
        }

        $register = Register::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        if ($register === null) {
            throw new DomainException('No active POS register found. Create a register in admin first.');
        }

        $this->session->put(self::SESSION_KEY, $register->uuid);

        return $register;
    }
}
