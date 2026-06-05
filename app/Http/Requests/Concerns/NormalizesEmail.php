<?php

namespace App\Http\Requests\Concerns;

trait NormalizesEmail
{
    /**
     * Lowercase + trim the email before validation, so login lookups and the
     * `unique` check are case-insensitive. Email addresses are not case-sensitive
     * in practice (Postgres string comparison IS), so we normalise everywhere.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
