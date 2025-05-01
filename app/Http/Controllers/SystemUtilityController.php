<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate; // If using Gates
use Illuminate\Support\Facades\App; // To check environment


class SystemUtilityController extends Controller
{
    /**
     * Executes composer dump-autoload.
     * PROTECTED METHOD - Ensure proper authorization is in place via routes/middleware.
     *
     * @param Request $request
     * @param string $secret_token // Matches the route parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function dumpAutoload(Request $request)
    {
        // --- SECURITY CHECKS ---

        // 1. Environment Check (Strongly Recommended)
        // Prevent running on production unless absolutely necessary and secured.
        // if (App::environment('production')) {
        //      Log::warning('Attempted to run dump-autoload via HTTP on production environment.');
        //      abort(403, 'Operation not permitted in this environment.');
        // }

        $secret_token = $request->input('secret_token');
        // 2. Secret Token Check (Example)
        // Compare the token from the URL with one stored securely (e.g., in .env)
        $expectedToken = config('app.dump_autoload_secret'); // Add DUMP_AUTOLOAD_SECRET to your .env
        if (!$expectedToken || $secret_token !== $expectedToken) {
            Log::warning('Invalid secret token attempt for dump-autoload endpoint.');
            abort(403, 'Invalid secret token.');
        }

        // 3. Authorization Check (Redundant if using Gate middleware, but good practice)
        // Ensure the Gate middleware already ran, or explicitly check here.
        // if (!Gate::allows('run-system-commands')) {
        //     abort(403, 'You are not authorized to perform this action.');
        // }


        // --- EXECUTE THE COMMAND ---

        try {
            // Get the directory containing the PHP executable
            $phpDir = '/usr/local/bin'; // Based on 'which php' output
            $currentPath = getenv('PATH');
            $newPath = $phpDir . PATH_SEPARATOR . $currentPath;

            Log::info("Attempting composer dump-autoload with modified PATH: " . $newPath);

            // Run the command from the base path with the modified PATH environment
            $result = Process::path(base_path())
                         ->env(['PATH' => $newPath]) // Set the PATH for the command
                         ->run('composer dump-autoload -o --apcu'); // Removed --no-dev flag

            if ($result->successful()) {
                Log::info('composer dump-autoload executed successfully via endpoint.');
                return response()->json([
                    'message' => 'composer dump-autoload executed successfully.',
                    'output' => $result->output(),
                ]);
            } else {
                Log::error('composer dump-autoload failed via endpoint.', [
                    'exit_code' => $result->exitCode(),
                    'output' => $result->output(),
                    'error_output' => $result->errorOutput(),
                ]);
                return response()->json([
                    'message' => 'composer dump-autoload failed.',
                    'error' => $result->errorOutput(),
                    'output' => $result->output(),
                ], 500); // Internal Server Error
            }
        } catch (\Throwable $e) {
            Log::critical('Exception caught while trying to run composer dump-autoload via endpoint.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
