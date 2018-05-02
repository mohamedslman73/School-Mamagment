<?php

namespace App\Http\Controllers;

use App\Like;
use App\Level;
use App\Sort;
use App\Post;
use App\PostImage;
use App\Subject;
use App\Teacher;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('block');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $re)
    {
        DB::table('visitors')->insert([
            'ip' => $re->ip(),
            'date' => date("Y-m-d h:i:s")
        ]);

        $posts = array();
        $user = Auth::user();
        $id = $user->id;
        if(Auth::user()->role->name == 'teacher')
        {
            $allPosts = Post::select('posts.*','u.name as userName','u.image as userImage','pi.image as postImage')
                ->leftJoin('users as u', 'u.id', '=', 'posts.user_id')
                ->leftJoin('post_images as pi', 'pi.post_id', '=', 'posts.id')
                ->leftJoin('likes as l', 'l.post_id', '=', 'posts.id')
                ->where('posts.post_id',0)
                ->orderBy('posts.created_at','DESC')
                ->get();

            foreach ($allPosts as $Post) {
                if($Post['postImage'] == null)
                    $Post['postImage'] = "";
            }

            foreach ($allPosts as $item) 
            {
                if($item->permition->staff||($item->user_id == $id) )
                {
                    $isLiked = 0;
                    $checkLike=Like::where([
                        ['post_id','=',$item->id],
                        ['user_id','=',$user->id]
                    ])->first(); 
                    if($checkLike)
                       $isLiked = 1;
                     
                    $likes = Like::where('post_id',$item->id)->count();
                    $item['isLiked'] = $isLiked;
                    $item['likes'] = $likes;
                    $posts[] = $item;
                }  
            }
        }

        elseif (Auth::user()->role->name == 'admin' || Auth::user()->role->name == 'principle')
        {

            $posts = Post::select('posts.*','u.name as userName','u.image as userImage','pi.image as postImage')
                ->leftJoin('users as u', 'u.id', '=', 'posts.user_id')
                ->leftJoin('post_images as pi', 'pi.post_id', '=', 'posts.id')
                ->leftJoin('likes as l', 'l.post_id', '=', 'posts.id')
                ->where('posts.post_id',0)
                ->orderBy('created_at','DESC')->get();


            foreach ($posts as $Post) {
                if($Post['postImage'] == null)
                    $Post['postImage'] = "";
            }

                foreach ($posts as $item) 
                {
                    $likes = Like::where('post_id',$item->id)->count();
                    $item['likes'] = $likes;
                }
        }

        elseif (Auth::user()->role->name == 'parent')
        {
            $classes = $this->sonsClasses();
            $teachers = $this->sonsTeachers();
            $teachersIDs = $posts = array();
            foreach ($teachers as $teacher) {
                $teachersIDs[] = $teacher->id;
            }
            $allPosts = Post::select('posts.*','u.name as userName','u.image as userImage','pi.image as postImage')
                ->leftJoin('users as u', 'u.id', '=', 'posts.user_id')
                ->leftJoin('post_images as pi', 'pi.post_id', '=', 'posts.id')
                ->leftJoin('likes as l', 'l.post_id', '=', 'posts.id')
                ->where('posts.post_id',0)
                ->whereIn('posts.user_id',$teachersIDs)
                ->orderBy('created_at','DESC')->get();

            foreach ($allPosts as $Post) {
                if($Post['postImage'] == null)
                    $Post['postImage'] = "";
            }

                        
            foreach ($allPosts as $item) 
            {
                if($item->permition->parents||in_array($item->permition->class_id,$classes))
                {
                    $likes = Like::where('post_id',$item->id)->count();
                    $item['likes'] = $likes;
                    $posts[] = $item;
                }  
            }
        }

        if($re->wantsJson())
            return response()->json([
                'status_code' => 200,
                'message' => 'Request process succeed.',
                'data' => $posts
            ],200);

        $classes = $this->teacherClasses();
        return view('home')->with([
            'posts' => $posts,
            'classes' => $classes
        ]);
    }    

    public function sonsTeachers()
    {
        $children = Auth::user()->children;
        $teachersIDs = $classesIDs = array();
        foreach ($children as $child) {
            $classesIDs[] = $child->class_id;
        }
        if($classesIDs)
        {
            $teachers = Teacher::whereIn('class_id',$classesIDs)->get();
            foreach ($teachers as $teacher) {
                $teachersIDs[] = $teacher->user_id;
            }

        }
            

        return User::whereIn('id',$teachersIDs)->get();
    }

    public function sonsClasses()
    {
        $children = Auth::user()->children;
        $classesIDs = array();
        foreach ($children as $child) {
            $classesIDs[] = $child->class_id;
        }
        return $classesIDs;
    }

    public function teacherClasses()
    {
        $id = Auth::user()->id;
        $classesIDs = array();

        $teachers = Teacher::where('user_id',$id)->get();

        foreach ($teachers as $teacher) {
            $classesIDs[] = $teacher->class_id;
        }

        $classes = array();
        if(Auth::user()->role_id == 2)
            $classes = Sort::whereIn('id',$classesIDs)->get();
        elseif(Auth::user()->role_id == 3)
            $classes = Sort::all();
        return $classes;
    }
    


}
