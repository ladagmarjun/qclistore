<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Create the customer and admin roles, then move each user's role
     * from the users.role column into the model_has_roles table.
     */
    public function up(): void
    {
        [$roles, $modelHasRoles, $roleKey, $modelKey] = $this->names();

        $roleIds = collect(['customer', 'admin'])->mapWithKeys(fn (string $name) => [
            $name => DB::table($roles)->insertGetId([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);

        DB::table('users')->select(['id', 'role'])->orderBy('id')->each(function (object $user) use ($roleIds, $modelHasRoles, $roleKey, $modelKey) {
            DB::table($modelHasRoles)->insert([
                $roleKey => $roleIds[$user->role] ?? $roleIds['customer'],
                'model_type' => User::class,
                $modelKey => $user->id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Put the users.role column back, filled from the assigned roles.
     */
    public function down(): void
    {
        [$roles, $modelHasRoles, $roleKey, $modelKey] = $this->names();

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['customer', 'admin'])->default('customer')->after('phone');
        });

        $adminIds = DB::table($modelHasRoles)
            ->join($roles, "{$roles}.id", '=', "{$modelHasRoles}.{$roleKey}")
            ->where("{$roles}.name", 'admin')
            ->where('model_type', User::class)
            ->pluck($modelKey);

        DB::table('users')->whereIn('id', $adminIds)->update(['role' => 'admin']);

        DB::table($modelHasRoles)->where('model_type', User::class)->delete();
        DB::table($roles)->whereIn('name', ['customer', 'admin'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{string, string, string, string}
     */
    private function names(): array
    {
        return [
            config('permission.table_names.roles'),
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key') ?? 'role_id',
            config('permission.column_names.model_morph_key'),
        ];
    }
};
