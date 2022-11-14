<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Generators\FileGenerator;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateMRC extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'create:MRC';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Model & repo & contract (only for Module) create:MRC --type all(m,r,c,rc) ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');
        $type = $type? strtolower($type):'all';
        switch($type){
            case 'all':
                $this->model();
                $this->repo();
                $this->contract();
                break;
            case 'm':
                $this->model();
                break;
            case 'r':
                $this->repo();
                break;
            case 'c':
                $this->contract();
                break;
            case 'rc':
                $this->repo();
                $this->contract();
                break;
        }
        $this->info("Done");
    }

    public function model(){
        //model
        $model_path = str_replace('\\', '/', $this->getDestinationFilePath('model'));
        if (!$this->laravel['files']->isDirectory($dir = dirname($model_path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }
        $contents_model = $this->getTemplateContents('model');
        $this->writefile($model_path, $contents_model);
    }
    public function repo(){
        //repo
        $repo_path = str_replace('\\', '/', $this->getDestinationFilePath('repo'));
        if (!$this->laravel['files']->isDirectory($dir = dirname($repo_path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }
        $contents_repo = $this->getTemplateContents('repo');
        $this->writefile($repo_path, $contents_repo);
    }
    public function contract(){
        //contract
        $contract_path = str_replace('\\', '/', $this->getDestinationFilePath('contract'));
        if (!$this->laravel['files']->isDirectory($dir = dirname($contract_path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }
        $contents_contract = $this->getTemplateContents('contract');
        $this->writefile($contract_path, $contents_contract);
    }



    /**
     * Get module name
     *
     * @return string
     */
    public function getModuleName()
    {
        $module = $this->argument('module') ?: app('modules')->getUsedNow();
        $module = app('modules')->findOrFail($module);
        return $module->getStudlyName();
    }

    /**
     * writefile
     *
     * @param $path
     * @param $contents
     * @return string
     */
    public function writefile($path, $contents)
    {
        try {
            $overwriteFile = $this->hasOption('force') ? $this->option('force') : false;
            (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->error("File : {$path} already exists.");
        }
    }

    /**
     * Get class namespace.
     *
     * @param \Nwidart\Modules\Module $module
     * @param $type
     * @return string
     */
    public function getClassNamespace($module, $type)
    {
        $extra = str_replace($this->getClass(), '', $this->getMRCName());

        $extra     = str_replace('/', '\\', $extra);
        $namespace = $this->laravel['modules']->config('namespace');

        $namespace .= '\\' . $module->getStudlyName();

        $namespace .= '\\' . $this->getDefaultNamespace($type);

        $namespace .= '\\' . $extra;

        $namespace = str_replace('/', '\\', $namespace);

        return trim($namespace, '\\');
    }


    public function getDefaultNamespace($type): string
    {
        switch ($type) {
            case 'model':
                return $this->laravel['modules']->config('paths.generator.model.path', 'Entities');
            case 'repo':
                return "Repositories";
            case 'contract':
                return "Contracts";
        }
        return '';
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        return str::studly(class_basename($this->argument('name')));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of model will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, 'Create type default all', null]
        ];
    }
    /**
     * @return mixed
     */
    protected function getDestinationFilePath($type)
    {
        $path      = $this->laravel['modules']->getModulePath($this->getModuleName());
        $file_path = '';
        switch ($type) {
            case 'model':
                $modelPath = GenerateConfigReader::read('model');
                $file_path = $path . $modelPath->getPath() . '/' . $this->getMRCName() . '.php';
                break;
            case 'repo':
                $file_path = $path . 'Repositories/' . $this->getMRCName() . 'Repository.php';
                break;
            case 'contract':
                $file_path = $path . 'Contracts/' . $this->getMRCName() . 'Contract.php';
            // no break
            default:
                break;
        }
        return $file_path;
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents($type)
    {
        switch ($type) {
            case 'model':
                $module = $this->laravel['modules']->findOrFail($this->getModuleName());
                $stub   = new Stub('/MyModel.stub', [
                    'MODEL_NAME'       => $this->getClass(),
                    'NAMESPACE'        => $this->getClassNamespace($module, 'model'),
                    'MODULE_NAMESPACE' => $this->laravel['modules']->config('namespace'),
                ]);
                $stub->setBasePath(__DIR__ . '/../Commands/stubs');
                break;
            case 'repo':
                $module = $this->laravel['modules']->findOrFail($this->getModuleName());
                $stub   = new Stub('/MyRepository.stub', [
                    'MODEL_NAME'       => $this->getClass(),
                    'MODEL'            => str_replace('/', '\\', $this->getMRCName()),
                    'CONTRACT'         => str_replace('/', '\\', $this->getMRCName()),
                    'NAMESPACE'        => $this->getClassNamespace($module,'repo'),
                    'MODULES_NAME'     => $this->getModuleName(),
                    'MODULE_NAMESPACE' => $this->laravel['modules']->config('namespace'),
                ]);
                $stub->setBasePath(__DIR__ . '/../Commands/stubs');
                break;
            case 'contract':
                $module = $this->laravel['modules']->findOrFail($this->getModuleName());
                $stub   = new Stub('/MyContract.stub', [
                    'MODEL_NAME'       => $this->getClass(),
                    'NAMESPACE'        => $this->getClassNamespace($module,'contract'),
                    'MODULE_NAMESPACE' => $this->laravel['modules']->config('namespace'),
                ]);
                $stub->setBasePath(__DIR__ . '/../Commands/stubs');
                break;
            default:
                break;
        }

        return $stub->render();
    }

    /**
     * @return mixed|string
     */
    private function getMRCName()
    {
        $words    = explode('/', $this->argument('name'));
        $str_name = '';
        foreach ($words as $item => $value) {
            $str_name .= Str::studly($words[$item]) . ' ';
        }
        $str_name = str_replace(' ', '/', trim($str_name));
        return $str_name;
    }
}
