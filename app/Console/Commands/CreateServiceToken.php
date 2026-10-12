<?php

namespace App\Console\Commands;

use App\Models\ServiceIdentity;
use Illuminate\Console\Command;

class CreateServiceToken extends Command
{
    protected $signature = 'service-token:create
        {identity : Explicit service identity slug (currently hermes-agent)}
        {name : Unique token label used later for revocation}
        {--days=90 : Token lifetime in days (1 to 365)}
        {--yes : Skip the interactive confirmation}';

    protected $description = 'Create a revocable, least-privilege token for an approved service identity.';

    public function handle(): int
    {
        $slug = strtolower(trim((string) $this->argument('identity')));
        $name = trim((string) $this->argument('name'));
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);

        if ($slug !== 'hermes-agent') {
            $this->error('Only the explicitly approved identity hermes-agent is enabled in this phase.');
            return self::FAILURE;
        }

        if ($name === '' || strlen($name) > 120) {
            $this->error('Token name must be between 1 and 120 characters.');
            return self::FAILURE;
        }

        if ($days === false || $days < 1 || $days > 365) {
            $this->error('Token lifetime must be between 1 and 365 days.');
            return self::FAILURE;
        }

        $identity = ServiceIdentity::query()->where('slug', $slug)->first();
        $identityExists = $identity !== null;
        $identity ??= new ServiceIdentity([
            'slug' => $slug,
            'name' => 'Hermes Agent',
            'is_active' => true,
        ]);

        if (! $identity->is_active) {
            $this->error('Service identity is inactive.');
            return self::FAILURE;
        }

        $abilities = $identity->allowedAbilities();
        if ($abilities !== ['mcp:inventory:read']) {
            $this->error('Identity does not have an approved ability set.');
            return self::FAILURE;
        }

        if ($identityExists && $identity->tokens()->where('name', $name)->exists()) {
            $this->error('A token with this name already exists for the identity. Use a new name for rotation.');
            return self::FAILURE;
        }

        $this->line('Identity: '.$identity->slug);
        $this->line('Abilities: '.implode(', ', $abilities));
        $this->line('Expires: '.now()->addDays($days)->toIso8601String());

        if (! $this->option('yes') && ! $this->confirm('Create this service token?', false)) {
            $this->info('No token created.');
            return self::SUCCESS;
        }

        if (! $identityExists) {
            $identity->save();
        }

        $token = $identity->createToken($name, $abilities, now()->addDays($days));

        $this->warn('Copy this token now. It will not be stored or shown again:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
