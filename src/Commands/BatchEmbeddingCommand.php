<?php

namespace Subhendu\Recommender\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Subhendu\Recommender\Services\BatchEmbeddingService;

class BatchEmbeddingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embedding:batch {modelName}';
//    protected $signature = 'embedding:batch {--chunk=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a JSONL file for batch embedding of JobVerified models';
    private $disk;

    public function __construct()
    {
        parent::__construct();
        $this->disk = Storage::disk('local');
    }


    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modelClass = $this->argument('modelName');

        $batchEmbeddingService = app(BatchEmbeddingService::class, [
            'embeddableModelName' => $modelClass
        ]);

        $batchEmbeddingService->generateJsonLFile(500);

        $this->info('file generated success');

        try {
            $files = $this->disk->files($batchEmbeddingService::inputFileDirectory);
            $this->info("Files found: " . json_encode($files));

            foreach ($files as $file) {
                $response = $batchEmbeddingService->uploadFileForBatchEmbedding($this->disk->path($file));

                $this->info("File uploaded successfully for batch embedding. batch created!");
                $this->info("File ID: " . $response->id);
                $this->info("Array Response: " . json_encode($response->toArray()));
            }
        } catch (\Exception $e) {
            $this->error("Error occurred while uploading file for batch embedding: " . $e->getMessage());
        }

    }

}
