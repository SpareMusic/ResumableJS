<?php

namespace SpareMusic\ResumableJS\Commands;

use Illuminate\Console\GeneratorCommand;

class ResumableRequestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:resumable-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resumable request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resumable request';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/resumable-request.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\ResumableRequests';
    }
}
