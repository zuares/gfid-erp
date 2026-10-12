<?php

namespace App\Console\Commands;

use App\Models\ServiceIdentity;
use Illuminate\Console\Command;

class RevokeServiceToken extends Command
{
    protected $signature = 'service-token:revoke
        {identity : Explicit service identity slug (currently hermes-agent)}
        {name : Exact token label to revoke}
        {--yes : Skip the interactive confirmation}';

    protected $description = 'Revoke one named service token.';

    public function handle(): int
    {
        $slug = strtolower(trim((string) $this->argument('identity')));
        $name = trim((string) $this->argument('name'));

        if ($slug !== 'hermes-agent' || $name === '') {
            $this->error('Provide the approved identity hermes-agent and an exact token name.');
            return self::FAILURE;
        }

        $identity = ServiceIdentity::query()->where('slug', $slug)->first();
        if (! $identity) {
            $this->error('Service identity not found.');
            return self::FAILURE;
        }

        $tokens = $identity->tokens()->where('name', $name);
        $count = $tokens->count();
        if ($count === 0) {
            $this->info('No matching token found.');
            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm("Revoke {$count} token(s) named {$name}?", false)) {
            $this->info('No token revoked.');
            return self::SUCCESS;
        }

        $tokens->delete();
        $this->info("Revoked {$count} token(s) for {$identity->slug}.");

        return self::SUCCESS;
    }
}
