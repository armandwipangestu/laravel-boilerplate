<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;

class ClearExpiredRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:clear-refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired and revoked refresh tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = RefreshToken::whereNotNull('revoked_at')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Successfully cleared {$count} expired/revoked refresh tokens.");
    }
}
