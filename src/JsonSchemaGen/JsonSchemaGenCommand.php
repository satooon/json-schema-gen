<?php namespace Satooon\JsonSchemaGen;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class JsonSchemaGenCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:JsonSchemaGen';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'JSON Schema generator.';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        JsonSchemaGen::make()
            ->setOption($this->option())
            ->setUrl($this->argument('url'))
            ->run()
            ->finish(function () {
                // $this->info("\xf0\x9f\x8d\xba");
            });
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'API url'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['headers', 'H', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Custom header to pass to server (H)', []],
            ['request', 'X', InputOption::VALUE_OPTIONAL, 'Specify request command to use', 'GET'],
            ['data', 'd', InputOption::VALUE_OPTIONAL, 'HTTP POST data (H)', '{}'],
        ];
    }
}
