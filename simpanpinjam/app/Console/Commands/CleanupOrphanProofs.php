<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\ModelPayment;

class CleanupOrphanProofs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-orphan-proofs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle()
    {
        $files = Storage::disk('public')->files('proofs');
        $deleted = 0;

        foreach ($files as $file) {
            $exists = ModelPayment::where('proof', $file)->exists();
            if (!$exists) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        $this->info("Cleanup selesai. File dihapus: {$deleted}");
    }

}
