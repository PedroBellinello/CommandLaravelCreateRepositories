<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class CreateClassPipeline extends Command
{
    protected $signature = 'make:pipeline {pipe?} {--model=}';

    protected $description = 'Create a new Pipeline Class';

    protected static $path;

    public function handle()
    {
        self::$path = app_path("Pipelines/");

        $pipe = $this->argument("pipe");
        $model = $this->option("model");


        $createFile = self::storage()->put(
            sprintf("%sPipeline.php",$pipe),
            $this->stub(
                $this->getPipeNamespace($pipe),
                $this->getModelNamespace($model)
            )
        );

        if($createFile) {

            $this->info("Pipeline created with success!");

            return;
        }

        $this->error("Failed to create pipeline.");
    }
    public function getPipeNamespace($pipe)
    {
        $namespace = 'App\Pipelines';

        if(str_contains($pipe, "/")){

            $pipes = explode("/", $pipe);

            foreach ($pipes as $value) {

                if($value !== $pipes[array_key_last($pipes)])
                    $namespace = sprintf("%s%s%s", $namespace , "\\", $value);

            }

            $classPipe = $pipes[array_key_last($pipes)];

        }else{
            $classPipe = $pipe;
        }

        return [
            'class' => $classPipe,
            'namespace' => $namespace
        ];
    }


    public function getModelNamespace($model = null)
    {
        if(empty($model)) {
            return [
                'class' => '',
                'namespace' => ''
            ];
        }

        $namespace = 'App\Models';

        if(str_contains($model, "/")){

            $pathModel = explode("/", $model);

            foreach ($pathModel as $value) {

                $namespace = sprintf("%s%s%s", $namespace, "\\", $value);

            }

            $classModel = $pathModel[array_key_last($pathModel)];

        }else{
            $classModel = $model;
            $namespace =  sprintf("%s%s%s", $namespace, "\\", $model);
        }

        return [
          'class' => $classModel,
          'namespace' => "use $namespace;"
        ];
    }

    public function stub($pipe, array $model)
    {
        $modelName = $model['class'];
        $modelNamespace = $model["namespace"];

        $pipeName = $pipe['class'];
        $namespace = $pipe['namespace'];

        return "<?php\n\nnamespace {$namespace};\n\nuse Closure;\nuse Illuminate\Http\Request;\n{$modelNamespace}\n
class {$pipeName}Pipeline {\n
\tpublic function __construct(protected Request \$request)\n\t{\n\n\t}\n
\tpublic function handle({$modelName} \$content, Closure \$next)\n\t{\n
\t\treturn \$next(\$content);\n
\t}\n
}";
    }

    public static function storage()
    {
        $path = self::$path;
        // Make sure the storage path exists and writeable
        if (!is_writable($path)) {

            if(!is_dir($path)) {
                Process::run(sprintf("sudo mkdir %s && sudo chown %s %s/",
                    $path, '$USER:$USER', $path));

                (new crontab())->log()->info("Created Pipelines folder successfully");
            }

            if(!is_dir($path)){
                (new crontab())->log()->error("Failed to create Pipelines folder");
                return new \Exception("Failed to create Pipelines folder");
            }

        }

        return Storage::createLocalDriver(["root" => $path]);
    }

}
