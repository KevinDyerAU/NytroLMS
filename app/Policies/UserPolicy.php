<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('manage users');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, User $model)
    {
        $userRole = $user->roles()->first();
        $role = $model->roles()->first();
        $superiors = $user->getSuperiorRoles();

        //        $superiors = [...$superiors,...[$userRole->name]];
        //        ddd($superiors, $role->name, in_array($role->name, $superiors));
        return (($user->id !== $model->id) && !in_array($role->name, $superiors))
            ? Response::allow()
            : Response::deny('You can not view this role.');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create users');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, User $model)
    {
        $userRole = $user->roles()->first();
        $role = $model->roles()->first();

        return (($userRole->id !== $role->id) && !in_array($role->name, $user->getSuperiorRoles()))
            ? Response::allow()
            : Response::deny('You can not update this role.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, User $model)
    {
        $userRole = $user->roles()->first();
        $role = $model->roles()->first();

        return ($role->name !== 'Root' && ($userRole->id < $role->id))
            ? Response::allow()
            : Response::deny('You can not update this role.');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, User $model)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        return false;
    }
}
