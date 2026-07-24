<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enum\Gender;
use App\Models\Port;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Create port')]
class CreatePort extends BaseTool
{
    public function description(): string
    {
        return 'Create a new port. Gender must be "male" or "female".';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('name')->description('Port name.')->required()
            ->string('gender')->description('Port gender: "male" or "female".')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', new Enum(Gender::class)],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $port = Port::create([
            'name' => $arguments['name'],
            'gender' => $arguments['gender'],
        ]);

        return ToolResult::json([
            'created' => true,
            'id' => $port->id,
            'name' => $port->name,
            'gender' => $port->gender?->value,
        ]);
    }
}
