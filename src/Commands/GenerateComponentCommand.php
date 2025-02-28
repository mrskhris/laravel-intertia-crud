<?php

namespace Tareq1988\InertiaCrud\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class GenerateComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inertia:make-component {name : The name of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Inertia React components for CRUD operations';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->argument('name');
        $fields = $this->getFieldsFromUser();

        // Create types
        //$this->createTypes($modelName, $fields);

        // Create components
        $this->createComponent($modelName, 'react/index.stub', 'Index.tsx', $fields);
        $this->createComponent($modelName, 'react/show.stub', 'Show.tsx', $fields);
        $this->createComponent($modelName, 'react/form.stub', 'Form.tsx', $fields);
        $this->copyPaginationComponent();

        $this->info('Components created successfully.');
    }

    protected function getFieldsFromUser()
    {
        $fields = [];
        $this->info('Enter field details:');

        while (true) {
            $name = text('Field name (db column name)');
            if (empty($name)) break;

            $type = select('Field type', [
                'string' => 'String (short text)',
                'number' => 'Number',
                'text' => 'Text (long text)',
                'date' => 'Date',
                'boolean' => 'Boolean',
                'email' => 'Email',
                'select' => 'Select (dropdown)',
            ]);

            $label = text('Field label', default: Str::title($name));

            $options = multiselect(
                'Select field options',
                [
                    'required'    => 'Required in forms',
                    'showInTable' => 'Show in table listing',
                    'sortable'    => 'Sortable in table',
                ],
                default: ['showInTable']
            );

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'label' => $label,
                'required' => in_array('required', $options),
                'showInTable' => in_array('showInTable', $options),
                'sortable' => in_array('sortable', $options),
            ];

            if (!confirm('Add another field?', default: true)) {
                break;
            }
        }

        return $fields;
    }

    protected function createTypes($modelName, $fields)
    {
        $stub = $this->getStub('types.stub');

        $typeFields = collect($fields)->map(function ($field) {
            $type = match ($field['type']) {
                'number' => 'number',
                'date' => 'string',
                default => 'string'
            };
            return "  {$field['name']}: {$type};";
        })->join("\n");

        $content = str_replace(
            ['{{ model }}', '{{ fields }}'],
            [$modelName, $typeFields],
            $stub
        );

        $path = base_path('resources/js/types/index.d.ts');
        $this->ensureDirectoryExists(dirname($path));

        if (file_exists($path)) {
            $existing = $this->files->get($path);

            if ($this->interfaceExists($existing, $modelName)) {
                $content = $this->replaceInterface($existing, $content);
            } else {
                $content = $existing . "\n\n" . $content;
            }

            if (!$this->interfaceExists($existing, 'PaginatedResource')) {
                $content = $content . "\n\n" . $this->generatePaginatedResourceInterface();
            }

            $this->files->put($path, $content);
        } else {
            $this->files->put($path, $content);
        }
    }

    protected function createComponent($modelName, $subPath, $fileName, $fields)
    {
        $stub          = $this->getStub($subPath);
        $pluralModel   = Str::plural($modelName);
        $lowerModel    = Str::lower($modelName);
        $lowerPlural   = Str::lower($pluralModel);
        $formFields    = $this->generateFormFields($fields, Str::lower($modelName));
        $tableColumns  = $this->generateTableColumns($fields);
        $tableRows     = $this->generateTableRows($fields, $modelName);
        $displayFields = $this->generateDisplayFields($fields, $lowerModel);

        $content = str_replace(
            [
                '{{ model }}',
                '{{ modelLower }}',
                '{{ modelLowerPlural }}',
                '{{ tableColumns }}',
                '{{ tableRows }}',
                '{{ displayFields }}',
                '{{ formInitialState }}',
                '{{ formFields }}',
                '{{ formEditingState }}',
            ],
            [
                $modelName,
                Str::camel($modelName),
                $lowerPlural,
                $tableColumns,
                $tableRows,
                $displayFields,
                $formFields['formInitialState'],
                $formFields['formFields'],
                $formFields['editingState'],
            ],
            $stub
        );

        $path = resource_path("js/Pages/{$pluralModel}/{$fileName}");
        $this->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);
    }

    protected function getStub($file)
    {
        $stubPath = base_path('stubs/inertia-crud/' . $file);
        if (!$this->files->exists($stubPath)) {
            $stubPath = __DIR__ . '/../../stubs/' . $file;
        }

        return $this->files->get($stubPath);
    }

    protected function ensureDirectoryExists($path)
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true);
        }
    }

    protected function interfaceExists($content, $modelName)
    {
        return Str::contains($content, "export interface {$modelName}");
    }

    protected function replaceInterface($content, $interface)
    {
        return preg_replace('/export interface (\w+) {[^}]+}/', $interface, $content);
    }

    /**
     * Generate the PaginatedResource interface.
     *
     * @return string
     */
    protected function generatePaginatedResourceInterface(): string
    {
        return <<<TS
export interface PaginatedResource<T> {
  data: T[];
  total: number;
  per_page: number;
  current_page: number;
  first_page_url: string;
  last_page_url: string;
  last_page: number;
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
}

TS;
    }

    protected function generateTableColumns($fields)
    {
        // First add the linked title column
        $columns = collect($fields)
            ->filter(fn($field) => $field['showInTable'])
            ->map(function ($field) {
                return "{
                    key: '{$field['name']}',
                    label: '{$field['label']}',
                    sortable: " . ($field['sortable'] ? 'true' : 'false') . ",
                }";
            })->toArray();

        return implode(",\n", $columns);
    }

    protected function generateFormFields($fields, $modelName)
    {
        return collect($fields)->map(function ($field) {
            return [
                'label' => $field['label'],
                'name' => $field['name'],
                'type' => $field['type'],
                'required' => $field['required'],
                'default' => match ($field['type']) {
                    'number' => '0',
                    'boolean' => 'false',
                    default => "''",
                }
            ];
        })->pipe(function ($fields) use ($modelName) {
            // Generate the form initial state
            $initialState = $fields->map(
                fn($field) =>
                "    {$field['name']}: {$field['default']}"
            )->join(",\n");

            $editingState = $fields->map(
                fn($field) =>
                "    {$field['name']}: {$modelName}?.{$field['name']} || ''"
            )->join(",\n");

            // Generate the form fields JSX
            $formFields = $fields->map(function ($field) {
                $required = $field['required'] ? ' required' : '';
                return match ($field['type']) {
                    'text' => "<Textarea\n      label='{$field['label']}'\n      value={form.data.{$field['name']}}\n      onChange={(value) => form.setData('{$field['name']}', value)}\n      {$required}\n      error={form.errors.{$field['name']}}\n    />",
                    default => "<TextField\n      label='{$field['label']}'\n      value={form.data.{$field['name']}}\n      onChange={(value) => form.setData('{$field['name']}', value)}\n      {$required}\n      error={form.errors.{$field['name']}}\n    />"
                };
            })->join("\n");

            return [
                'formInitialState' => $initialState,
                'editingState' => $editingState,
                'formFields' => $formFields
            ];
        });
    }

    protected function generateTableRows($fields, $modelName)
    {
        $model = Str::lower($modelName);

        return collect($fields)
            ->filter(fn($field) => $field['showInTable'])
            ->map(function ($field) use ($model) {
                return "<td className=\"whitespace-nowrap px-3 py-3.5 text-sm text-gray-500\">
                {{$model}.{$field['name']}}
            </td>";
            })->join("\n");
    }

    protected function generateDisplayFields($fields, $modelName)
    {
        return collect($fields)->map(function ($field) use ($modelName) {
            return "<div className=\"mb-4\">
                <h3 className=\"text-sm font-medium text-gray-500 mb-2\">{$field['label']}</h3>
                <p className=\"text-sm\">{{$modelName}.{$field['name']}}</p>
            </div>";
        })->join("\n");
    }

    protected function copyPaginationComponent()
    {
        $stub = $this->getStub('react/pagination.stub');
        $path = resource_path('js/Components/Pagination.tsx');

        if ($this->files->exists($path)) {
            return;
        }

        $this->files->put($path, $stub);
    }
}
