<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class service extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {service?} {--mode=} {--contract=} {--add=} {--model=}';

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
            $this->error("Insert Contract, the default is --contract=false or setter --contract=true for enable create interface");
            return false;
        }

        if(strtolower(trim($this->option("mode"))) === "api") {
            $this->set['Controllers']['stub'] = "ControllerApi";
            $this->path['Controllers'] = app_path("Http\Controllers\api/");
        }
        if(!$this->option("contract") || strtolower(trim($this->option("contract"))) === 'false') {
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
                $this->info("INFO  Finish!");
                return true;
            }

            if($this->create($name)){
                if(strtolower(trim($this->option("contract"))) !== 'false') {
                    if ($this->setBind("App\\Contracts\\{$name}RepositoryInterface", "App\\Repositories\\{$name}Repository")) {
                        $this->info("INFO  Finish!");
                    }
                }else{
                    $this->info("INFO  Finish!");
                }

            }

        }
        return true;
    }

    public function add()
    {
        $add = $this->option("add");
        $name = $this->argument("service");
        $nameModel = $this->option("model") ?? $name;

//        dd($name, $add, $nameModel);

        if($this->option("add") === "*"){
            Artisan::call(sprintf('make:model %s --all', $nameModel));
            $this->artisanOutput();
        }

        if(str_contains($add, 'm')){
            Artisan::call(sprintf('make:model %s', $nameModel));
            $this->artisanOutput();

            if(substr_count($this->option("add"), 'm') > 1 || str_contains($add, '?mg')){
                Artisan::call(sprintf('make:migration %s%s%s', "Create",$name,"_table"));
                $this->artisanOutput();
            }

            if(substr_count($this->option("add"), 'm') > 2 || str_contains($add, '?md')){
                Artisan::call(sprintf('make:middleware %s', $name));
                $this->artisanOutput();
            }
        }

        if(str_contains($add, 's')){
            Artisan::call(sprintf('make:seeder %s%s', ucfirst($name),"Seeder"));
            $this->artisanOutput();
        }

        if(str_contains($add, 'v')){
            Artisan::call(sprintf('make:view %s', strtolower(Str::slug($name))));
            $this->artisanOutput();
        }

        if(str_contains($add, 'r')){
            Artisan::call(sprintf('make:request %s%s', ucfirst($name),"Request"));
            $this->artisanOutput();

            if(substr_count($this->option("add"), 'r') > 1 || str_contains($add, '?r')){
                Artisan::call(sprintf('make:resource %s%s', ucfirst($name),"Resource"));
                $this->artisanOutput();
            }
        }

    }

    public function artisanOutput()
    {
        $artisanOutput = Artisan::output();

        $color = str_contains($artisanOutput, "INFO") ? "<fg=green>" : "<fg=red>";
        $artisanOutput = str_replace("

   ", "", $artisanOutput);

        $this->line($color.trim($artisanOutput)."</>");
    }

    public function Provider(): bool
    {
        $fileProvider = $this->path['Providers']."BindServiceProvider.php";
        if(!file_exists($fileProvider)){

            if($this->typeMakeProvider === "shell") {
                $this->comment("INFO  Creating provider: BindServiceProvider");
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
                        $this->info("INFO  Provider published in: {$fileConfigApp}");
                        fclose($openFile);
                        return true;
                    }

                }

                $this->info("INFO  Provider created in: {$fileProvider}");
            }
        }
        return file_exists($fileProvider);
    }

    public function setBind($interface, $class)
    {
        if(!$this->option("contract") || strtolower(trim($this->option("contract"))) === 'false')
            return true;

        $provider = $this->path['Providers']."BindServiceProvider.php";

        $bind = "App::bind('{$interface}','{$class}');";

        $providerRead = file_get_contents($provider);
        $bindPublished = str_replace($this->register, $this->register."\n\t\t".$bind, $providerRead);

        if(file_exists($provider) && !str_contains($providerRead, $bind)){
            $this->comment("INFO  Publishing bind interface: {$interface}");
            $openFile = fopen($provider, 'wb+');
            if(fwrite($openFile, $bindPublished)) {
                $this->info("INFO  Bind published in: {$provider}");
                fclose($openFile);
                return true;
            }
        }else{
            $this->line("<fg=red>ERROR  The bind is already published on the provider: {$interface}.</>");
            return true;
        }
    }

    public function createDefault($path, $name, $title, $stub): bool
    {
        $fileDefault = $path."{$name}.php";
        if(!file_exists($fileDefault)){

            $this->comment("INFO  Creating {$title}: {$name}");
            $openFile = fopen($fileDefault, 'wb+');
            $readStubsInterface = file_get_contents($this->path['Stubs']."{$stub}.stub");
            if(fwrite($openFile, $readStubsInterface)) {
                $this->info("INFO  {$title} created in: {$fileDefault}");
                fclose($openFile);
            }
        }
        return file_exists($fileDefault);
    }

    public function create($name): bool
    {
        $nameModel = $this->option("model") ?? $name;

        if($this->option("add")) $this->add();

        foreach ($this->set as $key => $parameters) {
            $prefix = ($parameters['prefix'] ?? $key);
            $path = isset($parameters['path']) ? $this->path[@$parameters['path']] : $this->path[$key];

            $fileCreatePath = "{$path}{$name}{$prefix}.php";

            if (!file_exists($fileCreatePath)) {

                $readStubs = file_get_contents($this->path['Stubs'] . ($parameters['stub'] ?? $key).".stub");
                $replacesVariableInStubs = str_replace(['{$name}', '{$nameModel}'], [$name, $nameModel], $readStubs);

                $openFile = fopen($fileCreatePath, 'wb+');
                if (fwrite($openFile, $replacesVariableInStubs)) {
                    $this->line("<fg=green>INFO  {$key} created in: {$fileCreatePath}.</>");
                    fclose($openFile);
                }else{
                    $this->line("<fg=red>ERROR  Failed to create the {$key} in: {$fileCreatePath}</>");
                    return false;
                }

            }else{
                $this->line("<fg=red>ERROR  {$name}{$prefix} is already created.</>");
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
                    $this->line("<fg=green>INFO  Creating folders.</>");
                    $show = true;
                }
                mkdir($path);
            }
        }
        if($show) {
            $this->info("INFO  Finish!");
        }
    }

}
