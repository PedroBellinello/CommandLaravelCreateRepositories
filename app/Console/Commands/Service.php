<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

class service extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {service?} {--mode=} {--contract=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected string $register = "public function register(){";

    protected array $path = [];

    protected array $set = [
        "Repositories" => ["prefix"=>"Repository", "stub"=>"Repository"],
        "Services" => ["prefix"=>"Service", "stub"=>"Service"],
        "Contracts" => ["prefix"=>"RepositoryInterface", "stub"=>"Contract"],
        "Controllers" => ["prefix"=>"Controller", "stub"=>"Controller"]
    ];

    protected string $typeMakeProvider = "stub"; // stub or shell;

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function __construct()
    {
        parent::__construct();
        $this->path['Configs'] = config_path('/');
        $this->path['Providers'] = app_path("Providers/");
        $this->path['Contracts'] = app_path("Contracts/");
        $this->path['Services'] = app_path("Services/");
        $this->path['Repositories'] = app_path("Repositories/");
        $this->path['Controllers'] = app_path("Http/Controllers/");
        $this->path['Stubs'] = app_path("Stubs/");

    }

    public function handle()
    {

        $name = $this->argument("service");
        $mode = $this->option("mode");
        $contract = $this->option("contract");
        if($mode !== null && ($mode !== "controller" && $mode !== "api")){
            $this->error("Insert mode, the default is --mode=controller or setter --mode=api for create controller api");
            return false;
        }
        if($contract !== null && ($contract !== "false" && $contract !== "true")){
            $this->error("Insert Contract, the default is --contract=true or setter --contract=false for disable create interface");
            return false;
        }

        if(strtolower(trim($this->option("mode"))) === "api") {
            $this->set['Controllers']['stub'] = "ControllerApi";
            $this->path['Controllers'] = app_path("Http\Controllers\api/");
        }
        if(strtolower(trim($this->option("contract"))) === 'false') {
            $this->set['Repositories']['stub'] = "RepositoryNoContract";
            unset($this->set['Contracts']);
        }

        $this->makeDirIfNotExists();

        $this->call("make:stub");
        if($this->Provider()){

            $this->createDefault($this->path['Repositories'], "AbstractRepository", "Default Repository", "DefaultRepository");
            $this->createDefault($this->path['Contracts'], "RepositoryInterface", "Default Contract", "DefaultRepositoryInterface");
            $this->setBind("App\\Contracts\\RepositoryInterface", "App\\Repositories\\AbstractRepository");

            if(empty(trim($name)) || $name === "default"){
                $this->info("Finish!");
                return true;
            }

            if($this->create($name)){
                if(strtolower(trim($this->option("contract"))) !== 'false') {
                    if ($this->setBind("App\\Contracts\\{$name}RepositoryInterface", "App\\Repositories\\{$name}Repository")) {
                        $this->info("Finish!");
                    }
                }else{
                    $this->info("Finish!");
                }

            }

        }
        return true;
    }

    public function Provider(): bool
    {
        $fileProvider = $this->path['Providers']."BindServiceProvider.php";
        if(!file_exists($fileProvider)){

            if($this->typeMakeProvider === "shell") {
                $this->comment("Creating provider: BindServiceProvider");
                $makeProvider = shell_exec("php artisan make:provider BindServiceProvider");
            }else{
                $makeProvider = $this->createDefault($this->path['Providers'], "BindServiceProvider", "provider", "BindServiceProvider");
            }

            if($makeProvider!== false) {

                $fileConfigApp = $this->path['Configs']."app.php";
                $ConfigRead = file_get_contents($fileConfigApp);

                $openFile = fopen($fileConfigApp, 'wb+');
                $publishAfter = "App\Providers\RouteServiceProvider::class,";
                $publishBefore = "App\Providers\BindServiceProvider::class,";

                if(file_exists($fileConfigApp) && !str_contains($ConfigRead, $publishBefore)) {
                    $configPublishProvider = $this->concat($publishAfter, $publishBefore, $ConfigRead);
                    if (fwrite($openFile, $configPublishProvider)) {
                        $this->info("Provider published in: {$fileConfigApp}");
                        fclose($openFile);
                        return true;
                    }

                }

                $this->info("Provider created in: {$fileProvider}");
            }
        }
        return file_exists($fileProvider);
    }

    public function setBind($interface, $class)
    {
        $provider = $this->path['Providers']."BindServiceProvider.php";

        $bind = "App::bind('{$interface}','{$class}');";

        $providerRead = file_get_contents($provider);
        $bindPublished = str_replace($this->register, $this->register."\n\t\t".$bind, $providerRead);

        if(file_exists($provider) && !str_contains($providerRead, $bind)){
            $this->comment("Publishing bind interface: {$interface}");
            $openFile = fopen($provider, 'wb+');
            if(fwrite($openFile, $bindPublished)) {
                $this->info("Bind published in: {$provider}");
                fclose($openFile);
                return true;
            }
        }else{
            $this->comment("The bind is already published on the provider: {$interface}");
            return true;
        }
    }

    public function createDefault($path, $name, $title, $stub): bool
    {
        $fileDefault = $path."{$name}.php";
        if(!file_exists($fileDefault)){

            $this->comment("Creating {$title}: {$name}");
            $openFile = fopen($fileDefault, 'wb+');
            $readStubsInterface = file_get_contents($this->path['Stubs']."{$stub}.stub");
            if(fwrite($openFile, $readStubsInterface)) {
                $this->info("{$title} created in: {$fileDefault}");
                fclose($openFile);
            }
        }
        return file_exists($fileDefault);
    }

    public function create($name): bool
    {
        foreach ($this->set as $key => $parameters) {
            $prefix = ($parameters['prefix'] ?? $key);
            $path = isset($parameters['path']) ? $this->path[@$parameters['path']] : $this->path[$key];

            $fileCreatePath = "{$path}{$name}{$prefix}.php";

            if (!file_exists($fileCreatePath)) {

                $this->comment("Creating {$key}: {$name}{$prefix}");

                $readStubs = file_get_contents($this->path['Stubs'] . ($parameters['stub'] ?? $key).".stub");
                $replacesVariableInStubs = str_replace('{$name}', $name, $readStubs);

                $openFile = fopen($fileCreatePath, 'wb+');
                if (fwrite($openFile, $replacesVariableInStubs)) {
                    $this->info("{$key} created in: {$fileCreatePath}");
                    fclose($openFile);
                }else{
                    $this->error("Failed to create the {$key} in: {$fileCreatePath}");
                    return false;
                }

            }else{
                $this->comment("{$name}{$prefix} is already created.");
            }
        }
        return true;
    }

    public function concat($strPrimary, $strSecond, $string)
    {
        return str_replace($strPrimary, $strPrimary."\n\t\t".$strSecond, $string);

    }

    public function makeDirIfNotExists(): void
    {
        $show = false;
        foreach ($this->path as $path) {
            if(!is_dir($path)){
                if(!$show){
                    $this->info("Creating folders.");
                    $show = true;
                }
                mkdir($path);
            }
        }
        if($show) {
            $this->info("Finish!");
        }
    }

}
