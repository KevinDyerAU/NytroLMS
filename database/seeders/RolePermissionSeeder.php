<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'view permissions']);
        Permission::firstOrCreate(['name' => 'create permissions']);
        Permission::firstOrCreate(['name' => 'update permissions']);
        Permission::firstOrCreate(['name' => 'delete permissions']);

        Permission::firstOrCreate(['name' => 'view roles']);
        Permission::firstOrCreate(['name' => 'create roles']);
        Permission::firstOrCreate(['name' => 'update roles']);
        Permission::firstOrCreate(['name' => 'delete roles']);

        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'update users']);
        Permission::firstOrCreate(['name' => 'delete users']);

        Permission::firstOrCreate(['name' => 'view companies']);
        Permission::firstOrCreate(['name' => 'create companies']);
        Permission::firstOrCreate(['name' => 'update companies']);
        Permission::firstOrCreate(['name' => 'delete companies']);
        Permission::firstOrCreate(['name' => 'assign companies']);
        Permission::firstOrCreate(['name' => 'manage company poc']);
        Permission::firstOrCreate(['name' => 'manage company bm']);

        Permission::firstOrCreate(['name' => 'view leaders']);
        Permission::firstOrCreate(['name' => 'create leaders']);
        Permission::firstOrCreate(['name' => 'update leaders']);
        Permission::firstOrCreate(['name' => 'delete leaders']);
        Permission::firstOrCreate(['name' => 'assign leaders']);

        Permission::firstOrCreate(['name' => 'view trainers']);
        Permission::firstOrCreate(['name' => 'create trainers']);
        Permission::firstOrCreate(['name' => 'update trainers']);
        Permission::firstOrCreate(['name' => 'delete trainers']);
        Permission::firstOrCreate(['name' => 'assign trainers']);

        Permission::firstOrCreate(['name' => 'view students']);
        Permission::firstOrCreate(['name' => 'create students']);
        Permission::firstOrCreate(['name' => 'update students']);
        Permission::firstOrCreate(['name' => 'delete students']);
        Permission::firstOrCreate(['name' => 'manage students']);
        Permission::firstOrCreate(['name' => 'view students related']);
        Permission::firstOrCreate(['name' => 'update student progress']);
        Permission::firstOrCreate(['name' => 'course backdate reg']);
        Permission::firstOrCreate(['name' => 'allow semester only']);
        Permission::firstOrCreate(['name' => 'show course expiry']);
        Permission::firstOrCreate(['name' => 'unlock lessons']);

        Permission::firstOrCreate(['name' => 'update settings']);
        Permission::firstOrCreate(['name' => 'menu settings']);
        Permission::firstOrCreate(['name' => 'site settings']);
        Permission::firstOrCreate(['name' => 'user settings']);
        Permission::firstOrCreate(['name' => 'account settings']);
        Permission::firstOrCreate(['name' => 'profile settings']);
        Permission::firstOrCreate(['name' => 'system log viewer']);

        Permission::firstOrCreate(['name' => 'view dashboard']);
        Permission::firstOrCreate(['name' => 'manage roles']);
        Permission::firstOrCreate(['name' => 'manage users']);
        Permission::firstOrCreate(['name' => 'manage accounts']);
        Permission::firstOrCreate(['name' => 'show course flyer']);

        Permission::firstOrCreate(['name' => 'view frontend']);
        Permission::firstOrCreate(['name' => 'view course_listing']);
        Permission::firstOrCreate(['name' => 'view course']);
        Permission::firstOrCreate(['name' => 'view result']);
        Permission::firstOrCreate(['name' => 'submit quiz']);
        Permission::firstOrCreate(['name' => 'communicate']);

        Permission::firstOrCreate(['name' => 'manage lms']);
        Permission::firstOrCreate(['name' => 'view lms']);
        Permission::firstOrCreate(['name' => 'create lms']);
        Permission::firstOrCreate(['name' => 'update lms']);
        Permission::firstOrCreate(['name' => 'delete lms']);
        Permission::firstOrCreate(['name' => 'published courses status']);
        Permission::firstOrCreate(['name' => 'set course restriction']);
        Permission::firstOrCreate(['name' => 'manage course version']);

        Permission::firstOrCreate(['name' => 'view notes']);
        Permission::firstOrCreate(['name' => 'create notes']);
        Permission::firstOrCreate(['name' => 'create bulk notes']);

        Permission::firstOrCreate(['name' => 'update notes']);
        Permission::firstOrCreate(['name' => 'delete notes']);
        Permission::firstOrCreate(['name' => 'pin notes']);

        Permission::firstOrCreate(['name' => 'view work placements']);
        Permission::firstOrCreate(['name' => 'create work placements']);
        Permission::firstOrCreate(['name' => 'update work placements']);
        Permission::firstOrCreate(['name' => 'delete work placements']);

        Permission::firstOrCreate(['name' => 'view documents']);
        Permission::firstOrCreate(['name' => 'upload documents']);
        Permission::firstOrCreate(['name' => 'delete documents']);

        Permission::firstOrCreate(['name' => 'mark complete']);
        Permission::firstOrCreate(['name' => 'mark assessments']);
        Permission::firstOrCreate(['name' => 'view assessments']);
        Permission::firstOrCreate(['name' => 'view student activities']);
        Permission::firstOrCreate(['name' => 'mark work placement']);
        Permission::firstOrCreate(['name' => 'upload checklist']);
        Permission::firstOrCreate(['name' => 'mark competency']);
        Permission::firstOrCreate(['name' => 'view competency']);
        Permission::firstOrCreate(['name' => 'issue certificate']);

        Permission::firstOrCreate(['name' => 'view reports']);
        Permission::firstOrCreate(['name' => 'view admin reports']);
        Permission::firstOrCreate(['name' => 'view enrolment reports']);
        Permission::firstOrCreate(['name' => 'view competency reports']);
        Permission::firstOrCreate(['name' => 'view work placement reports']);
        Permission::firstOrCreate(['name' => 'download reports']);
        Permission::firstOrCreate(['name' => 'view reports special columns']);

        Permission::firstOrCreate(['name' => 'widget registered_students']);
        Permission::firstOrCreate(['name' => 'widget daily_enrolments']);
        Permission::firstOrCreate(['name' => 'widget active_students']);
        Permission::firstOrCreate(['name' => 'widget inactive_students']);
        Permission::firstOrCreate(['name' => 'widget total_assessments']);
        Permission::firstOrCreate(['name' => 'widget pending_assessments']);
        Permission::firstOrCreate(['name' => 'widget engaged_students']);
        Permission::firstOrCreate(['name' => 'widget disengaged_students']);
        Permission::firstOrCreate(['name' => 'widget leader companies']);
        Permission::firstOrCreate(['name' => 'widget course flyer']);
        Permission::firstOrCreate(['name' => 'widget non_commenced students']);
        Permission::firstOrCreate(['name' => 'widget competency']);

        Permission::firstOrCreate(['name' => 'access admin tools']);

        $root = Role::firstOrCreate(['name' => 'Root']);

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::where('name', '!=', 'update settings')->where('name', 'not like', '%permissions')->get());

        $moderator = Role::firstOrCreate(['name' => 'Moderator']);
        $moderator->givePermissionTo(['view dashboard', 'manage lms', 'view lms', 'create lms', 'update lms']);

        $leader = Role::firstOrCreate(['name' => 'Leader']);
        $leader->givePermissionTo(['view dashboard', 'manage accounts', 'view students', 'create students', 'update students', 'view assessments']);

        $trainer = Role::firstOrCreate(['name' => 'Trainer']);
        $trainer->givePermissionTo(['view dashboard', 'manage accounts', 'view students', 'mark assessments', 'view assessments']);

        $trainer = Role::firstOrCreate(['name' => 'Student']);
        $trainer->givePermissionTo(['view frontend', 'view course_listing', 'view course', 'view result', 'submit quiz', 'communicate']);
    }
}
