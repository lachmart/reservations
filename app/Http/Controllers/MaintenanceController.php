<?php

namespace App\Http\Controllers;

use App\Support\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * POST /_maintenance/{task} with header X-Maintenance-Token: <MAINTENANCE_TOKEN>.
 * Used by deploy-ftp.sh after uploading. Answers 404 when no token is configured,
 * so the endpoint doesn't exist on servers that don't need it.
 */
class MaintenanceController extends Controller
{
    public function __invoke(Request $request, Maintenance $maintenance, string $task): Response
    {
        $expected = (string) config('app.maintenance_token');

        abort_if($expected === '', 404);
        abort_unless(hash_equals($expected, (string) $request->header('X-Maintenance-Token')), 403);

        $result = $maintenance->run($task);

        return response($result['output']."\n", $result['ok'] ? 200 : 500)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }
}
