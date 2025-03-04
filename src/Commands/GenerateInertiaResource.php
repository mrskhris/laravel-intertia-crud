<?php

namespace Tareq1988\InertiaCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateInertiaResource extends Command
{
    protected $signature = 'inertia:make-resource {name : The name of the resource}';
    protected $description = 'Create Inertia resource with model, controller, and views';

    public function handle()
    {
        $name = $this->argument('name');

        $fields = $this->getFieldsFromUser();

        // Create Model
        $this->createModel($name, $fields);

        // Create Controller
        $this->createController($name, $fields);

        // Add Routes
        $this->addRoutes($name);

        $this->info('Resource created successfully!');
    }

    protected function getFieldsFromUser()
    {
        $fields = [];

        while (true) {
            $name = \Laravel\Prompts\text('Field name');
            if (empty($name)) break;

            $type = \Laravel\Prompts\select('Field type', [
                'string'  => 'String',
                'integer' => 'Integer',
                'text'    => 'Text',
                'date'    => 'Date',
                'boolean' => 'Boolean',
                'email'   => 'Email'
            ]);

            $validation = \Laravel\Prompts\multiselect(
                'Validation rules',
                [
                    'required' => 'Required',
                    // 'unique'   => 'Unique',
                    'nullable' => 'Nullable',
                    'max:255'  => 'Max 255 chars',
                    'email'    => 'Email format',
                    'numeric'  => 'Numeric',
                    'boolean'  => 'Boolean'
                ]
            );

            $searchable = \Laravel\Prompts\confirm('Searchable?', true);

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'validation' => $validation,
                'searchable' => $searchable
            ];

            if (!\Laravel\Prompts\confirm('Add another field?', true)) break;
        }

        return $fields;
    }

    protected function createModel($name, $fields)
    {
        $fillable = collect($fields)
            ->pluck('name')
            ->map(fn($field) => "        '{$field}'")
            ->join(",\n");

        $stubPath = base_path('stubs/inertia-crud/model.stub');
        if (!File::exists($stubPath)) {
            $stubPath = __DIR__ . '/../../stubs/model.stub';
        }
        $stub = File::get($stubPath);
        $content = str_replace(
            ['{{ modelName }}', '{{ fillable }}'],
            [$name, $fillable],
            $stub
        );

        $path = app_path("Models/{$name}.php");

        if (!File::exists($path)) {
            File::put($path, $content);
        } else {
            $this->error('Model already exists!');
        }
    }

    protected function createController($name, $fields)
    {
        $searchFields = collect($fields)
            ->filter(fn($field) => $field['searchable'])
            ->map(fn($field) => "->orWhere('{$field['name']}', 'like', \"%{\$searchTerm}%\")")
            ->join("\n                  ");

        $validationRules = collect($fields)
            ->map(fn($field) => "            '{$field['name']}' => [" . implode(', ', array_map(fn($rule) => "'{$rule}'", $field['validation'])) . "]")
            ->join(",\n");

        $stubPath = base_path('stubs/inertia-crud/controller.inertia.stub');
        if (!File::exists($stubPath)) {
            $stubPath = __DIR__ . '/../../stubs/controller.inertia.stub';
        }

        $stub = File::get($stubPath);
        $content = str_replace(
            [
                '{{namespace}}',
                '{{modelNamespace}}',
                '{{controllerName}}',
                '{{modelName}}',
                '{{modelVariable}}',
                '{{routePrefix}}',
                '{{viewPrefix}}',
                '{{searchableFields}}',
                '{{validationRules}}'
            ],
            [
                'App\\Http\\Controllers',
                'App\\Models\\' . $name,
                $name . 'Controller',
                $name,
                lcfirst($name),
                Str::plural(Str::kebab($name)),
                Str::plural($name),
                $searchFields,
                $validationRules
            ],
            $stub
        );

        $path = app_path("Http/Controllers/{$name}Controller.php");
        if(File::exists($path)){
            $path = preg_replace("/.php$/", "Crud.php", $path);
        }
        if (!File::exists($path)) {
            File::put($path, $content);
        } else {
            $this->error('Controller already exists!');
        }
    }

    protected function addRoutes($name)
    {
        $routePrefix = Str::plural(Str::kebab($name));
        $routes = "\nRoute::resource('{$routePrefix}', {$name}Controller::class)->except(['create', 'edit']);";
        $routes .= "\nRoute::delete('/bulk/{$routePrefix}', [{$name}Controller::class, 'bulkDelete'])->name('{$routePrefix}.bulk-delete');\n";

        File::append(
            base_path('routes/web.php'),
            $routes
        );

        // Add the controller import
        $this->replaceInFile(
            base_path('routes/web.php'),
            'use Illuminate\Support\Facades\Route;',
            "use Illuminate\Support\Facades\Route;\nuse App\Http\Controllers\\{$name}Controller;"
        );
    }

    protected function replaceInFile($path, $search, $replace)
    {
        $content = File::get($path);
        $content = str_replace($search, $replace, $content);

        File::put($path, $content);
    }
}
