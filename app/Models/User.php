<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Notifiables\CronJobNotifier;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SlackAlertNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $appends = ['displayable_active'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'userable_id',
        'userable_type',
        'email_verified_at',
        'study_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setUsernameAttribute($value) {
        $firstName = $this->attributes['first_name'];
        $lastName = $this->attributes['last_name'];

        $username = $this->cleanUserName($firstName . $lastName[0]);

        $i = 0;
        while (User::whereUsername($username)->exists()) {
            $i++;
            $username = $this->cleanUserName($firstName . $lastName[0] . $i);
        }

        $this->attributes['username'] = Str::slug(trim($username), '_');
    }

    public function cleanUserName($string): string {
        return preg_replace(
            '/[\s\.\W]/i',
            '_',
            strtolower($string)
        );
    }

    public function isActive() {
        return (bool)$this->is_active;
    }

    public function getDisplayableActiveAttribute() {
        return $this->is_active ? 'Active' : 'In Active';
    }

    public function scopeNotRoot($query) {
        return $query->where('id', '>', 1);
    }

    public function scopeNotRole(Builder $query, $roles, $guard = null): Builder {
        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(
            function ($role) use ($guard) {
                if ($role instanceof Role) {
                    return $role;
                }

                $method = is_numeric($role) ? 'findById' : 'findByName';
                $guard = $guard ?: $this->getDefaultGuardName();
                $roleClass = $this->getRoleClass();

                return $roleClass::{$method}($role, $guard);
            },
            $roles
        );

        return $query->whereHas(
            'roles',
            function ($query) use ($roles) {
                $query->where(
                    function ($query) use ($roles) {
                        foreach ($roles as $role) {
                            $query->where(config('permission.table_names.roles') . '.id', '!=', $role->id);
                        }
                    }
                );
            }
        );
    }

    public function getSuperiorRoles() {
        $role = $this->role()->name;
        $roles = ['Root', 'Admin'];
        if ($role === 'Root') {
            $roles = [];
        } elseif ($role === 'Admin') {
            $roles = ['Root'];
        }

        return $roles;
    }

    public function role() {
        return $this->roles()->first();
    }

    public function roleName() {
        return $this->role()->name;
    }

    public function getNameAttribute() {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies() {
        //        return $this->belongsToMany(Company::class,'user_company');

        return $this->morphedByMany(Company::class, 'attachable', 'user_has_attachables');
    }

    /**
     * User relation to detail model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function detail() {
        return $this->hasOne(UserDetail::class);
    }

    public function userProfile() {
        return $this->detail();
    }

    public function avatar() {
        return $this->detail->avatar;
    }

    public function userable() {
        return $this->morphTo();
    }

    public function scopeOnlyStudents($query) {
        return $query->whereHas(
            'roles',
            function ($query) {
                $query->where('roles.name', '=', 'Student');
            }
        );
    }

    public function scopeOnlyActive($query) {
        return $query->where('is_active', 1);
    }

    public function scopeActive($query) {
        return $query->whereHas(
            'detail',
            function ($query) {
                $query->where('status', '!=', 'INACTIVE');
            }
        );
    }

    public function scopeInactive($query) {
        return $query->whereHas(
            'detail',
            function ($query) {
                $query->where('status', '=', 'INACTIVE');
            }
        );
    }

    public function trainers() {
        return $this->morphedByMany(Trainer::class, 'attachable', 'user_has_attachables');
    }

    public function scopeOnlyTrainers($query) {
        return $query->where('is_active', 1)->whereHas(
            'roles',
            function ($query) {
                $query->where('roles.name', '=', 'Trainer');
            }
        );
    }

    public function scopeIsRelatedTrainer($query) {
        return $query->whereHas(
            'trainers',
            function (Builder $query) {
                $query->where('id', '=', auth()->user()->id);
            }
        );
    }

    public function scopeIsRelatedLeader($query) {
        return $query->whereHas(
            'leaders',
            function (Builder $query) {
                $query->where('id', '=', auth()->user()->id);
            }
        );
    }

    public function scopeIsRelatedCompany($query) {
        $companies = auth()->user()->companies?->pluck('id');
        if (!empty($companies)) {
            return $query->whereHas(
                'companies',
                function (Builder $query) use ($companies) {
                    return $query->whereIn('id', $companies);
                }
            );
        }

        return $query;
    }

    public function scopeHasAnyLeader($query) {
        $companies = auth()->user()->companies;
        $company_leaders = [];
        foreach ($companies as $company) {
            $company_leaders[] = $company->leaders->pluck('id');
        }
        $company_leaders = \Arr::flatten($company_leaders);
        $company_leaders = array_unique($company_leaders);

        return $query->whereHas(
            'leaders',
            function (Builder $query) use ($company_leaders) {
                $query->whereIn('id', $company_leaders);
            }
        );
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\MorphOne {
        return $this->morphOne(User::class, 'userable');
    }

    public function leaders() {
        return $this->morphedByMany(Leader::class, 'attachable', 'user_has_attachables');
    }

    public function scopeOnlyLeaders($query) {
        return $query->where('is_active', 1)->whereHas(
            'roles',
            function ($query) {
                $query->where('roles.name', '=', 'Leader');
            }
        );
    }

    public function scopeOnlyCompanyLeaders($query, $company_id) {
        return $query->where('is_active', 1)->whereHas('roles', function ($query) {
            $query->where('roles.name', '=', 'Leader');
        })->whereHas('companies', function ($query) use ($company_id) {
            $query->where('companies.id', $company_id);
        });
    }

    public function enrolments() {
        //        return $this->morphedByMany(Enrolment::class, 'attachable', 'user_has_attachables');
        return $this->hasMany(Enrolment::class, 'user_id');
    }

    public function studentActivities() {
        return $this->hasMany(StudentActivity::class, 'user_id');
    }

    public function adminReports() {
        return $this->hasMany(AdminReport::class, 'student_id');
    }

    public function onboardDetailsArray($dotkey = null)
    {
        // Get active enrolment (including re-enrollments with keys like onboard2, onboard3, etc.)
        $result = $this->enrolments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->first()?->enrolment_value->toArray();

        if ($dotkey && !empty($result)) {
            $result = \Arr::dot($result);
            if (!empty($result[$dotkey])) {
                return $result[$dotkey];
            }

            return '';
        }

        return $result;
    }

    public function courseEnrolments(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(StudentCourseEnrolment::class, 'user_id')->where('status', '!=', 'DELIST');
    }

    public function allCourseEnrolments(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(StudentCourseEnrolment::class, 'user_id');
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token) {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param \Illuminate\Notifications\Notification $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification) {
        if (!empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return [$this->email => $this->name];
        }

        \Log::warning("Email notification skipped: Invalid or missing email for user ID {$this->id} ({$this->email}");
        // Send Slack notification via CronJobNotifier
        (new CronJobNotifier())->notify(new SlackAlertNotification(
            message: 'Invalid or missing email address detected for user',
            fields: [
                'User ID' => $this->id ?? 'Unknown',
                'Name' => $this->name ?? 'Unknown',
                'Email' => $this->email ?? 'None',
                'Environment' => config('app.env', 'unknown'),
            ],
            level: 'error'
        ));

        // Returning null or empty string stops mail from sending
        return;
    }

    public function isRoot() {
        return $this->hasRole('Root');
    }

    public function isAdmin() {
        return $this->hasRole('Admin');
    }

    public function isLeader() {
        return $this->hasRole('Leader');
    }

    public function isTrainer() {
        return $this->hasRole('Trainer');
    }

    public function isStudent() {
        return $this->hasRole('Student');
    }

    public function isModerator() {
        return $this->hasRole('Moderator');
    }

    public function transformDate($attribute) {
        $input = $this->attributes[$attribute];
        if (Carbon::parse($input)->greaterThan(Carbon::parse('30-08-2023'))) {
            return Carbon::parse($input)->format('j F, Y');
        } else {
            return Carbon::parse($input)->timezone(Helper::getTimeZone())->format('j F, Y');
        }
    }

    /**
     * Check if a user ID exists in the database.
     *
     * @param int|string $userId The user ID to check
     * @return bool True if user exists, false otherwise
     */
    public static function userIdExists($userId): bool {
        return self::where('id', $userId)->exists();
    }

    /**
     * Check if a user ID exists and is active.
     *
     * @param int|string $userId The user ID to check
     * @return bool True if user exists and is active, false otherwise
     */
    public static function activeUserIdExists($userId): bool {
        return self::where('id', $userId)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get user by ID if it exists, otherwise return null.
     *
     * @param int|string $userId The user ID to find
     * @return User|null The user model if found, null otherwise
     */
    public static function findUserById($userId): ?User {
        return self::find($userId);
    }
}
