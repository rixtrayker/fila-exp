<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAllPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:add-all {--force : Force the operation without confirmation} {--rollback : Rollback to previous state if available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all permissions and roles by running the necessary seeders';

    /**
     * Backup data for rollback
     */
    private array $backupData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        if (!$this->option('force')) {
            if (!$this->confirm('This will clear existing permissions and roles. Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Starting to add all permissions and roles...');

        try {
            // Validate required tables exist
            if (!$this->validateTables()) {
                return Command::FAILURE;
            }

            // Create backup before making changes
            $this->createBackup();

            // Disable foreign key checks to avoid constraint issues
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            // Clear existing permissions and roles (but keep users)
            $this->info('Clearing existing permissions and roles...');
            $this->clearExistingData();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            // Clear permission cache
            $this->info('Clearing permission cache...');
            Artisan::call('permission:cache-reset');

            // Define the seeders to run in order
            $seeders = [
                'RolesAndPermissionsSeeder',
                'MedicalRepRole',
                'DistrictManagerRole',
                'AreaManagerRole',
                'CountryManagerRole',
            ];

            // Validate seeders exist
            if (!$this->validateSeeders($seeders)) {
                $this->rollback();
                return Command::FAILURE;
            }

            // Run each seeder with progress tracking
            $progressBar = $this->output->createProgressBar(count($seeders));
            $progressBar->start();

            foreach ($seeders as $seeder) {
                $progressBar->setMessage("Running {$seeder}...");

                try {
                    $result = Artisan::call('db:seed', ['--class' => $seeder]);

                    if ($result !== 0) {
                        throw new \Exception("Seeder {$seeder} failed with exit code {$result}");
                    }

                    $this->info("\n✓ {$seeder} completed successfully");
                } catch (\Exception $e) {
                    $this->error("\n✗ {$seeder} failed: " . $e->getMessage());
                    $this->rollback();
                    return Command::FAILURE;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Clear permission cache again after seeding
            $this->info('Clearing permission cache after seeding...');
            Artisan::call('permission:cache-reset');

            $this->info('All permissions and roles have been added successfully!');

            // Show summary
            $this->showSummary();

            // Clear backup data since operation was successful
            $this->backupData = [];

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred while adding permissions: ' . $e->getMessage());
            $this->rollback();
            return Command::FAILURE;
        }
    }

    /**
     * Validate that required tables exist
     */
    private function validateTables(): bool
    {
        $requiredTables = ['permissions', 'roles', 'role_has_permissions', 'model_has_roles', 'model_has_permissions'];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table '{$table}' does not exist. Please run migrations first.");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that all seeders exist
     */
    private function validateSeeders(array $seeders): bool
    {
        foreach ($seeders as $seeder) {
            $seederClass = "Database\\Seeders\\{$seeder}";
            if (!class_exists($seederClass)) {
                $this->error("Seeder class '{$seederClass}' does not exist.");
                return false;
            }
        }

        return true;
    }

    /**
     * Create backup of existing data
     */
    private function createBackup(): void
    {
        $this->info('Creating backup of existing data...');

        $this->backupData = [
            'permissions' => DB::table('permissions')->get()->toArray(),
            'roles' => DB::table('roles')->get()->toArray(),
            'role_has_permissions' => DB::table('role_has_permissions')->get()->toArray(),
            'model_has_roles' => DB::table('model_has_roles')->get()->toArray(),
            'model_has_permissions' => DB::table('model_has_permissions')->get()->toArray(),
        ];

        $this->info('Backup created successfully.');
    }

    /**
     * Clear existing permission data
     */
    private function clearExistingData(): void
    {
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
    }

    /**
     * Rollback to previous state
     */
    private function rollback(): int
    {
        if (empty($this->backupData)) {
            $this->error('No backup data available for rollback.');
            return Command::FAILURE;
        }

        $this->info('Rolling back to previous state...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            // Clear current data
            $this->clearExistingData();

            // Restore backup data
            foreach ($this->backupData as $table => $data) {
                if (!empty($data)) {
                    DB::table($table)->insert($data);
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            // Clear permission cache
            Artisan::call('permission:cache-reset');

            $this->info('Rollback completed successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show a summary of what was created
     */
    private function showSummary()
    {
        $this->newLine();
        $this->info('Summary:');

        $permissionCount = DB::table('permissions')->count();
        $roleCount = DB::table('roles')->count();
        $userCount = DB::table('users')->count();

        $this->line("• {$permissionCount} permissions created");
        $this->line("• {$roleCount} roles created");
        $this->line("• {$userCount} users exist");

        $this->newLine();
        $this->info('Available roles:');
        $roles = DB::table('roles')->select('name', 'display_name')->get();
        foreach ($roles as $role) {
            $this->line("• {$role->display_name} ({$role->name})");
        }

        $this->newLine();
        $this->info('Default users (if they exist):');
        $defaultUsers = [
            'amr@super-admin.com' => 'Super Admin',
            'amr@admin.com' => 'Moderator',
            'amr@developer.com' => 'Developer',
            'medical-rep@admin.com' => 'Medical Rep'
        ];

        foreach ($defaultUsers as $email => $role) {
            $user = DB::table('users')->where('email', $email)->first();
            if ($user) {
                $this->line("• {$user->name} ({$email}) - {$role}");
            }
        }

        $this->newLine();
        $this->info('Command completed successfully! You can now use the application with the new permissions system.');
    }
}
