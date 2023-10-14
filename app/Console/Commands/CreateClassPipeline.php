<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class CreateClassPipelineCommand extends Command
{
    protected $signature = 'make:pipeline {pipe?} {--model=}';

    protected $description = 'Create a new Pipeline Class';

    protected static $path;

    public function handle()
    {
        self::$path = app_path("Pipelines/");

        $pipe = $this->argument("pipe");
        $model = $this->option("model");
        $namespace = 'App\Pipelines';

        if(str_contains($pipe, "/")){

            $pipes = explode("/", $pipe);

            if(count($pipes) > 2) {

                foreach ($pipes as $value) {

                    if($value !== $pipes[array_key_last($pipes)])
                        $namespace = sprintf("%s%s%s", $namespace , "\\", $value);

                }

            }else {

                $namespace .= sprintf("%s%s", "\\", $pipes[array_key_first($pipes)]);

            }

            $classPipe = $pipes[array_key_last($pipes)];

        }

        $createFile = self::storage()->put( sprintf("%sPipeline.php",$pipe), $this->stub($classPipe, $namespace, $model));

        if($createFile) {

            $this->info("Pipeline created with success!");

            return;
        }

        $this->error("Failed to create pipeline.");
    }

    public function stub($pipe, $namespace = null, $model = null)
    {
        $model = !empty($model) ? $model : '';

        return "<?php\n\nnamespace {$namespace};\n\nuse Closure;\nuse Illuminate\Http\Request;\n\nclass {$pipe}Pipeline {\n
\tpublic function __construct( protected Request \$request )\n\t{\n\n\t}\n
\tpublic function handle({$model} \$content, Closure \$next )\n\t{\n
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
