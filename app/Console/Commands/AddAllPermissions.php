<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AddAllPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:add-all {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all permissions and roles by running the necessary seeders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will clear existing permissions and roles. Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Starting to add all permissions and roles...');

        try {
            // Disable foreign key checks to avoid constraint issues
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            // Clear existing permissions and roles (but keep users)
            $this->info('Clearing existing permissions and roles...');
            DB::table('permissions')->truncate();
            DB::table('roles')->truncate();
            DB::table('role_has_permissions')->truncate();
            DB::table('model_has_roles')->truncate();
            DB::table('model_has_permissions')->truncate();

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

            // Run each seeder
            foreach ($seeders as $seeder) {
                $this->info("Running {$seeder}...");
                Artisan::call('db:seed', ['--class' => $seeder]);
                $this->info("✓ {$seeder} completed successfully");
            }

            // Clear permission cache again after seeding
            $this->info('Clearing permission cache after seeding...');
            Artisan::call('permission:cache-reset');

            $this->info('All permissions and roles have been added successfully!');

            // Show summary
            $this->showSummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred while adding permissions: ' . $e->getMessage());
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
    }
}
