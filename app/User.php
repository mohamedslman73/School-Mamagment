<?php

namespace App;

use App\BlockedUser;
use App\Chat;
use App\Level;
use App\Like;
use App\Post;
use App\Role;
use App\Sort;
use App\Student;
use App\Subject;
use App\Teacher;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Keygen\Keygen;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{

    use Notifiable,HasApiTokens;

    public function role()
    {

        return $this->belongsTo(Role::class);
    }

    public function children()
    {

        return $this->hasMany(Student::class,'parent_id');
    }

    public function posts()
    {

        return $this->hasMany(Post::class,'user_id');
    }

    public function likes()
    {

        return $this->hasMany(Like::class);
    }

    public function subjects()
    {

        return $this->belongsToMany(Subject::class,'teachers');
    }

    public function levels()
    {

        return $this->belongsToMany(Level::class,'teachers');
    }

    public function classes()
    {

        return $this->belongsToMany(Sort::class,'teachers','user_id','class_id');
    }

    public function messages()
    {
        return $this->hasMany(Chat::class,'from_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'name', 'image', 'phone', 'password','role_id','firebase_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

        'password', 'remember_token','firebase_token',
    ];

    /**
     * The attributes that will be treated as Carbon objcects.
     *
     * @var array
     */
    protected $dates = [

        'last_login', 'created_at',
    ];

    public function isOnline()
    {
        return Cache::has('user-online-'.$this->id);
    }

    public function isBlocked()
    {
        
        return BlockedUser::where('user_id',$this->id)->first();
    }

    public function followers($id)
    {
        $followers = array();
        $user = $this->find($id);
        $userId = (int)$id;
        if($user)
        {
            if($user->role_id == 1)
            {
                $children = $user->children;
                $classes = array();
                foreach ($children as $child){
                    $classes[] = $child->class->id;
                }
                $classes = array_unique($classes);
                $teachers = Teacher::distinct('user_id')
                                ->whereIn('class_id',$classes)
                                ->get();
                $followers = count($teachers);
            }
            elseif($user->role_id == 2)
            {
                $colleagues = $this->whereIn('role_id',[2,3])->get();

                $classes=Teacher::distinct('class_id')
                    ->where('user_id',$userId)->pluck('class_id')->toArray();
                $students = $parents = array();
                $students = Student::whereIn('class_id',$classes)->get();

                foreach ($students as $student) {
                    if($student->parent)
                        $parents[] = $student->parent->id;
                }
                $parents = array_unique($parents);

                //-1 because function count user itself;
                $followers = count($colleagues)+count($parents)-1;
            }
            elseif($user->role_id == 3)
            {
                $users=$this->whereIn('role_id',[1,2,3])->get();
                //-1 because function count user itself;
                $followers = count($users)-1;
            }
            elseif($user->role_id == 3)
            {
                $followers = 0;
            }
            
        }

        return $followers;
    }

}