<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Vertices;

class ResetVertexStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vertex:reset-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the status of vertices after one hour';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $vertices = Vertices::where('is_busy', true)
            ->where('busy_at', '<=', Carbon::now()->subHour())
            ->get();

        foreach ($vertices as $vertex) {
            $vertex->is_busy = false;
            $vertex->busy_at = null;
            $vertex->save();
        }
    }
}
