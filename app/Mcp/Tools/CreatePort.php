<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enum\Gender;
use App\Models\Port;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Create port')]
class CreatePort extends BaseTool
{
    public function description(): string
    {
        return 'Create a new port. Gender must be "male" or "female".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Port name.')
                ->required(),
            'gender' => $schema->string()
                ->description('Port gender: "male" or "female".')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', new Enum(Gender::class)],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $port = Port::create([
            'name' => $arguments['name'],
            'gender' => $arguments['gender'],
        ]);

        return Response::json([
            'created' => true,
            'id' => $port->id,
            'name' => $port->name,
            'gender' => $port->gender?->value,
        ]);
    }
}
