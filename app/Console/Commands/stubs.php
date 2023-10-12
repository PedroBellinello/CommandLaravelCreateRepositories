<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class stubs extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:stub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $path;

    protected $runStubs;

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function __construct()
    {
        parent::__construct();
        $this->path['Stubs'] = app_path("Stubs/");

        $this->runStubs = [
            "Provide"=>$this->BindServiceProvider(),
            "Contract"=>$this->Contract(),
            "DefaultRepository"=>$this->DefaultRepository(),
            "DefaultRepositoryInterface"=>$this->DefaultRepositoryInterface(),
            "Repository"=>$this->Repository(),
            "RepositoryNoContract"=>$this->RepositoryNoContract(),
            "Service"=>$this->Service(),
            "Controller"=>$this->Controller(),
            "ControllerApi"=>$this->ControllerApi(),
        ];

    }

    public function handle()
    {
        //$name = $this->argument("service");
        $show = false;
        foreach ($this->runStubs as $stub) {
            if(!file_exists($this->path['Stubs'].$stub['file'].".stub")) {
                if(!$show){
                    $this->info("Creating stubs.");
                    $show = true;
                }
                $this->create($stub);
            }
        }
        if($show)$this->info("Finish!");

    }

    private function BindServiceProvider(): array
    {
        $content = "<?php\n\nnamespace App\Providers;\n\nuse Illuminate\Support\Facades\App;\nuse Illuminate\Support\ServiceProvider;
\nclass BindServiceProvider extends ServiceProvider\n{\n\t/**\n\t * Register services.\n\t *\n\t * @return void\n\t */
\n\tpublic function register(){\n\n\t}\n\n\t/**\n\t * Bootstrap services.\n\t *\n\t * @return void\n\t */
\n\tpublic function boot()\n\t{\n\n\t}\n}";
        return ["file"=>"BindServiceProvider", 'content'=>$content];
    }

    private function Contract(): array
    {
        $content = "<?php\n\nnamespace App\Contracts;\n\ninterface {\$name}RepositoryInterface\n{\n\tpublic function getAll();\n\tpublic function find(\$id);
\tpublic function create(\$data);\n\tpublic function update(\$data, \$id);\n\tpublic function destroy(\$id);\n}";
        return ["file"=>"Contract", 'content'=>$content];
    }

    private function DefaultRepository(): array
    {
        $content = "<?php\n\nnamespace App\Repositories;\n\nuse App\Contracts\RepositoryInterface;\n\nclass AbstractRepository implements RepositoryInterface\n{
\n\tpublic function getAll()\n\t{\n\t\t// TODO: Implement getAll() method.\n\t}
\n\tpublic function find(\$id)\n\t{\n\t\t// TODO: Implement find() method.\n\t}
\n\tpublic function create(\$data)\n\t{\n\t\t// TODO: Implement create() method.\n\t}
\n\tpublic function update(\$data, \$id)\n\t{\n\t\t// TODO: Implement update() method.\n\t}
\n\tpublic function destroy(\$id)\n\t{\n\t\t// TODO: Implement destroy() method.\n\t}\n}";
        return ["file"=>"DefaultRepository", 'content'=>$content];
    }

    private function DefaultRepositoryInterface(): array
    {
        $content = "<?php\n\nnamespace App\Contracts;\n\ninterface RepositoryInterface\n{
\tpublic function getAll();\n\tpublic function find(\$id);\n\tpublic function create(\$data);\n\tpublic function update(\$data, \$id);
\tpublic function destroy(\$id);\n}";
        return ["file"=>"DefaultRepositoryInterface", 'content'=>$content];
    }

    private function Repository(): array
    {
        $content = "<?php\n
namespace App\Repositories;\n\nuse App\Contracts\{\$name}RepositoryInterface;\nuse App\Models\{\$name};\n
class {\$name}Repository implements {\$name}RepositoryInterface\n{\n\tprotected {\$name} \$model;\n
\tpublic function __construct({\$name} \$model)\n\t{\n\t\t\$this->model = \$model;\n\t}\n
\tpublic function getAll(): \Illuminate\Http\JsonResponse\n\t{
\t\t\$all = \$this->model->all();\n\t\treturn response()->json([\"status\" => \$all->count() != 0, \"data\" => \$all->toArray()]);\n\t}\n
\tpublic function find(\$id): \Illuminate\Http\JsonResponse\n\t{
\t\t\$ids = explode(\",\", \$id);\n\t\t\$find = \$this->model->whereIn('id', \$ids);
\t\treturn response()->json([\"status\" => \$find->exists(), \"ids\" => \$ids, \"data\" => \$find->get()]);\n\t}\n
\tpublic function create(\$data): \Illuminate\Http\JsonResponse\n\t{
\t\t\$create = \$this->model->create(\$data);\n\t\treturn response()->json([\"status\" => \$create !== false, \"data\" => \$create]);\n\t}\n
\tpublic function update(\$data, \$id): \Illuminate\Http\JsonResponse\n\t{
\t\t\$update = \$this->model->where('id', \$id)->update(\$data);
\t\treturn response()->json([\"status\" => \$update != 0, \"id\" => \$update != 0 ? \$id : null, \"data\" => \$update != 0 ? \$data : null]);\n\t}\n
\tpublic function destroy(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\t\$destroy = \$this->model->destroy(\$id);
\t\treturn response()->json([\"status\" => \$destroy !== 0, \"result\" => \$destroy]);\n\t}\n}";
        return ["file"=>"Repository", 'content'=>$content];
    }

    private function RepositoryNoContract(): array
    {
        $content = "<?php\n
namespace App\Repositories;\n\nuse App\Contracts\{\$name}RepositoryInterface;\nuse App\Models\{\$name};\n
class {\$name}Repository\n{\n\tprotected {\$name} \$model;\n
\tpublic function __construct({\$name} \$model)\n\t{\n\t\t\$this->model = \$model;\n\t}\n
\tpublic function getAll(): \Illuminate\Http\JsonResponse\n\t{
\t\t\$all = \$this->model->all();\n\t\treturn response()->json([\"status\" => \$all->count() != 0, \"data\" => \$all->toArray()]);\n\t}\n
\tpublic function find(\$id): \Illuminate\Http\JsonResponse\n\t{
\t\t\$ids = explode(\",\", \$id);\n\t\t\$find = \$this->model->whereIn('id', \$ids);
\t\treturn response()->json([\"status\" => \$find->exists(), \"ids\" => \$ids, \"data\" => \$find->get()]);\n\t}\n
\tpublic function create(\$data): \Illuminate\Http\JsonResponse\n\t{
\t\t\$create = \$this->model->create(\$data);\n\t\treturn response()->json([\"status\" => \$create !== false, \"data\" => \$create]);\n\t}\n
\tpublic function update(\$data, \$id): \Illuminate\Http\JsonResponse\n\t{
\t\t\$update = \$this->model->where('id', \$id)->update(\$data);
\t\treturn response()->json([\"status\" => \$update != 0, \"id\" => \$update != 0 ? \$id : null, \"data\" => \$update != 0 ? \$data : null]);\n\t}\n
\tpublic function destroy(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\t\$destroy = \$this->model->destroy(\$id);
\t\treturn response()->json([\"status\" => \$destroy !== 0, \"result\" => \$destroy]);\n\t}\n}";
        return ["file"=>"RepositoryNoContract", 'content'=>$content];
    }

    private function Service(): array
    {
        $content = "<?php\n\nnamespace App\Services;\n\n\nuse App\Repositories\{\$name}Repository;\n
class {\$name}Service\n{\n\tprotected {\$name}Repository \$repos;\n\n
\tpublic function __construct({\$name}Repository \$repos)\n\t{\n\t\t\$this->repos = \$repos;\n\t}\n
\tpublic function getAll(): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->repos->getAll();\n\t}\n
\tpublic function find(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->repos->find(\$id);\n\t}\n
\tpublic function create(\$data): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->repos->create(\$data);\n\t}\n
\tpublic function update(\$data, \$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->repos->update(\$data, \$id);\n\t}\n
\tpublic function destroy(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->repos->destroy(\$id);\n\t}\n}";
        return ["file"=>"Service", 'content'=>$content];
    }

    private function Controller(): array
    {
        $content = "<?php\n\nnamespace App\Http\Controllers;\n\nuse App\Services\{\$name}Service;\nuse Illuminate\Http\Request;\n
class {\$name}Controller extends Controller\n{\n\tpublic {\$name}Service \$service;\n\n\tpublic function __construct({\$name}Service \$service)\n\t{\n\t\t\$this->service = \$service;\n\t}
\tpublic function getAll(): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->getAll();\n\t}
\tpublic function find(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->find(\$id);\n\t}
\tpublic function create(Request \$request): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->create(\$request->all());\n\t}
\tpublic function update(Request \$request, \$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->update(\$request->all(), \$id);\n\t}
\tpublic function destroy(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->destroy(\$id);\n\t}\n}";
        return ["file"=>"Controller", 'content'=>$content];
    }

    private function ControllerApi(): array
    {
        $content = "<?php\n\nnamespace App\Http\Controllers\api;\n\nuse App\Http\Controllers\Controller;\nuse App\Services\{\$name}Service;\nuse Illuminate\Http\Request;\n
class {\$name}Controller extends Controller\n{\n\tpublic {\$name}Service \$service;\n\n\tpublic function __construct({\$name}Service \$service)\n\t{\n\t\t\$this->service = \$service;\n\t}
\tpublic function getAll(): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->getAll();\n\t}
\tpublic function find(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->find(\$id);\n\t}
\tpublic function create(Request \$request): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->create(\$request->all());\n\t}
\tpublic function update(Request \$request, \$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->update(\$request->all(), \$id);\n\t}
\tpublic function destroy(\$id): \Illuminate\Http\JsonResponse\n\t{\n\t\treturn \$this->service->destroy(\$id);\n\t}\n}";
        return ["file"=>"ControllerApi", 'content'=>$content];
    }

    private function create($stub)
    {
        $fileStub = $this->path['Stubs'].$stub['file'].".stub";
        if(!file_exists($fileStub)) {
            $fileCreate = fopen($fileStub, 'wb+');
            $this->comment("Creating stub: " . $stub['file']);
            if (fwrite($fileCreate, $stub['content'])) {
                $this->info("Stub " . $stub['file'] . " created in: " . $fileStub);
                fclose($fileCreate);
            }
        }
    }





}
