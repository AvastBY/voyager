<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearThumbsCommand extends Command
{
    protected $signature = 'voyager:clear-thumbs {--all : Clear all thumbnails including placeholders}';
    protected $description = 'Clear all generated thumbnails';

    public function handle()
    {
        $this->info('Clearing thumbnails...');
        
        $thumbsPath = '_thumbs';
        
        if ($this->option('all')) {
            // Удаляем все thumbnails включая placeholders
            if (Storage::disk('public')->exists($thumbsPath)) {
                Storage::disk('public')->deleteDirectory($thumbsPath);
                $this->info('All thumbnails and placeholders cleared successfully!');
            } else {
                $this->info('No thumbnails directory found.');
            }
        } else {
            // Удаляем только thumbnails, оставляем placeholders
            $directories = Storage::disk('public')->directories($thumbsPath);
            $clearedCount = 0;
            
            foreach ($directories as $directory) {
                if (basename($directory) !== 'placeholders') {
                    Storage::disk('public')->deleteDirectory($directory);
                    $clearedCount++;
                }
            }
            
            $this->info("Cleared {$clearedCount} thumbnail directories successfully!");
            $this->info('Placeholders were preserved. Use --all option to clear everything.');
        }
        
        $this->info('Thumbnails cleared successfully!');
    }
}
