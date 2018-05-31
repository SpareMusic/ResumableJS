<?php
namespace SpareMusic\ResumableJS\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\OutputInterface;

class ClearChunksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunks:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the chunks upload directory.';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        parent::__construct();
    }

    /**
     * Clears the chunks upload directory
     */
    public function handle()
    {
        $verbose = OutputInterface::VERBOSITY_VERBOSE;

        // try to get the old chunk directories
        $oldChunks = $this->oldChunkFiles();

        if ($oldChunks->isEmpty()) {
            $this->warn("Chunks: no old files");
            return;
        }

        $this->info(sprintf("Found %d chunk files", $oldChunks->count()), $verbose);
        $deleted = 0;

        foreach ($oldChunks as $chunk) {
            // debug the file info
            $this->comment("> ".$chunk, $verbose);
            // delete the file
            if ($this->filesystem->deleteDirectory($chunk)) {
                $deleted++;
            } else {
                $this->error("> chunk not deleted: ".$chunk);
            }
        }
        $this->info("Chunks: cleared ".$deleted." ".Str::plural("chunk", $deleted));
    }

    /**
     * Returns the old chunk files
     *
     * @return Collection
     */
    public function oldChunkFiles()
    {
        $directories = $this->directories();

        // if there are no files, lets return the empty collection
        if ($directories->isEmpty()) {
            return $directories;
        }
        // build the timestamp
        $timeToCheck = strtotime(config('resumable-js.timestamp'));
        $collection = new Collection();

        $directories->each(function ($directory) use ($timeToCheck, $collection) {
            // get the last modified time to check if the chunk is not new
            $modified = $this->filesystem->lastModified($directory);
            // delete only old chunk
            if ($modified < $timeToCheck) {
                $collection->push($directory);
            }
        });

        return $collection;
    }

    /**
     * Returns an array of directories in the chunks directory
     *
     * @return Collection
     */
    public function directories()
    {
        $directories = config('resumable-js.chunks');

        $chunks = new Collection();

        foreach ($directories as $directory) {
            $directory = Storage::disk('local')->path($directory);

            if (!$this->filesystem->exists($directory)) {
                continue;
            }

            $chunkDirectories = $this->filesystem->directories($directory);

            foreach ($chunkDirectories as $chunkDirectory) {
                $chunks->push($chunkDirectory);
            }
        }

        return $chunks;
    }
}