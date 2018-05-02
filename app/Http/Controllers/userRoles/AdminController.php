<?php

namespace App\Http\Controllers\userRoles;

use App\BlockedUser;
use App\Chat;
use App\Http\Controllers\Controller;
use App\Level;
use App\Like;
use App\Post;
use App\PostImage;
use App\PostPermition;
use App\Sort;
use App\Student;
use App\StudentSubject;
use App\Subject;
use App\Teacher;
use App\Url;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	*/
	public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('admin');
	}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
    */

    public function index()
    {	
    	return view('userRoles.admin.home');
    }

    public function teachers(Request $re)
    {
    	$teachers = $schedule = array();
	$isOnline = 0;
	$allteachers = User::where('role_id',2)->get();
	foreach ($allteachers as $item) {
	    if($item->isOnline())
	        $isOnline = 1;
	    $item['isOnline'] = $isOnline;
	    if($item->last_login)
	    	$item['lastSeen'] = $item->last_login->diffForHumans();
	    else
	    	$item['lastSeen'] = "";
	    	
	    $duties = Teacher::where('user_id',$item->id)->get();
            if($duties)
            {
                foreach($duties as $value)
                {
                    $schedule[] = $value;
                }
            }
            foreach($schedule as $sc)
            {
                unset($sc['id']);
                unset($sc['user_id']);
                unset($sc['created_at']);
                unset($sc['updated_at']);

                $sub = Subject::find($sc->subject_id);
                $lev = Level::find($sc->level_id);
                $cla = Sort::find($sc->class_id);

                $sc['subjectName'] = $sub->name;
                $sc['levelName'] = $lev->name;
                $sc['className'] = $cla->name;
            }
            

            $item['schedule'] = $schedule;
            
            
	    $teachers[] = $item;
	}
	
	if($re->wantsJson())
	    return response()->json([
	        'status_code' => 200,
	        'message' => 'Request process succeed.',
	        'data' => $teachers
	    ],200);
		
	

    	return view('userRoles.admin.teachers',with([
    		'teachers' => $teachers
    	]));            
    }

    public function editTeacherView(Request $re)
    {
    	$teacherId = $re->tid;

    	$teacher = User::where('id',$teacherId)->first();

    	$teacherClasses = $teacher->classes->toArray();
    	$teacherLevels = $teacher->levels->toArray();
    	$teacherSubjects = $teacher->subjects->toArray();

    	$tclasses = $this->ClassesLevelsToString($teacherClasses);
    	$tlevels = $this->ClassesLevelsToString($teacherLevels);
    	$tsubjects = $this->ClassesLevelsToString($teacherSubjects);

    	$alllevels = Level::all();
    	$allclasses = Sort::orderBy('level_id')->get();
    	$alllsubjects = Subject::all();

        //return $tclasses;    
    	return view('userRoles.admin.editteacher',with([
    		'teacher' => $teacher,
    		'tclasses' => $tclasses,
    		'tlevels' => $tlevels,
    		'tsubjects' => $tsubjects,
    		'editclasses' => $teacherClasses,
    		'editsubjects' => $teacherSubjects,
    		'classes' => $allclasses,
    		'levels' => $alllevels,
    		'subjects' => $alllsubjects

    	]));
    }

    public function editTeacher(Request $re)
    {

    	$teacher = new Teacher();
    	$tid = $re->tid;
    	$user = User::find($tid);
    	if($user)
    	{
    		$ifexisted = Teacher::where([
    			['user_id','=',$tid],
    			['level_id','=',$re->level],
    			['class_id','=',$re->class],
    			['subject_id','=',$re->subject],
    		])->first();
    		if($ifexisted)
    		{
    			$errMsg = "Bad Request, Teacher already teach this class with this subject.";
                if($re->wantsJson())
                    return response()->json([
                        'status_code'=> 400,
                        'message'=>$errMsg,
                        'data'=>[]
                    ],200);
    		}
    		else
    		{
    			$teacher->user_id = $tid;
    			$teacher->level_id = $re->level;
    			$teacher->class_id = $re->class;
    			$teacher->subject_id = $re->subject;

    			$added = $teacher->save();
                $last = Teacher::where('user_id',$tid)
                                ->orderBy('created_at','DESC')
                                ->first();

                if($re->wantsJson())
                    return response()->json([
                        'status_code'=> 201,
                        'message'=>'Adding process succeed.',
                        'data'=>$last
                    ],200);
    		}
    	}
    	else
        {
    		$errMsg = "User not found.";
            if($re->wantsJson())
            return response()->json([
                'status_code'=> 404,
                'message'=>$errMsg,
                'data'=>[]
            ],200);
    	}

    	return redirect()->back();
    }
    
    public function unassignclass(Request $re)
    {
    	$teacherId = $re->tid;
    	$classId = $re->id;
    	$deleted = array();
        $user = User::find($teacherId);
    	$teacher = Teacher::where([
            ['user_id','=',$teacherId],
            ['class_id','=',$classId]
        ])->first();
        $tclasses = Teacher::where([
    		['user_id','=',$teacherId],
    		['class_id','=',$classId]
    	])->get();

    	if($teacher)
    	{
    		foreach ($tclasses as $item){
    	    	$deleted[] = $item->delete();
    	    }
            $classes = $user->classes;
            if($re->wantsJson())
                return response()->json([
                    'status_code'=> 204,
                    'message'=>'Delete process succeed.',
                    'data'=>$classes
                ],200);
    	}
    	else
    	{
			$errMsg = "Teacher not found.";
            if($re->wantsJson())
                return response()->json([
                    'status_code'=> 404,
                    'message'=>$errMsg,
                    'data'=>[]
                ],200);
    	}
    	return redirect()->back();
    }

	public function addTeacherView()
	{

		return view('userRoles.admin.addteacher');
	}

	public function addTeacher(Request $re)
	{
		$schedules = $re->schedule;
	        $class = $re->class;
	        $subject = $re->subject;

		$phone = '00966'.$re->phone;
	        $re->merge(['phone' => $phone]);
	        $last = User::where('role_id',2)
	                      ->orderBy('created_at','DESC')->first();

		 $check = $this->validator($re);
	        if($check)
	        {
	            if($re->wantsJson())
	                return response()->json([
	                    'status_code' => 400,
	                    'message' => "Bad Request.",
	                    'data' => $check
	                ],200);
	        }

		$user = User::create([
			'name' => $re->name,         
			'phone' => $re->phone,
			'image' => 'avatar.jpg',
			'role_id' => 2,
			'password' => bcrypt($re->password),
		]);

	        if($user)
	        {
		        if(count($schedules) > 0){
		            foreach ($schedules as $schedule) {
		                    $teacher = new Teacher();
		                    $teacher->user_id = $user->id;
		                    $teacher->level_id = $schedule['level_id'];
		                    $teacher->class_id = $schedule['class_id'];
		                    $teacher->subject_id = $schedule['subject_id'];
		
		                    $added = $teacher->save();
		            }
		        }
	
	            $subjects = $user->subjects->toArray();
	            if($user['subjects'])
	                unset($user['subjects']);
	
	            $names = array();
	            foreach ($subjects as $item) {
	                $names[] = $item['name'];
	            }
	            $names = array_unique($names);
	            $user['subjects'] = $names;
	
	            return response()->json([
	                'status_code'=> 201,
	                'message'=>'Adding process succeed.',
	                'data'=>$user
	            ],200);
	        }
		

		return redirect()->route('admins.edit.teacher.view',['tid'=>$user->id]);   
	}

	public function parents(Request $re)
	{
		$parents = User::where('role_id',1)->get();

        if($re->wantsJson())
            return response()->json([
                'status_code'=> 200,
                'message'=>'Request process succeed.',
                'data'=>$parents
            ],200);
		

		return view('userRoles.admin.parents',with([
			'parents' => $parents
		]));
	}

	public function viewParent(Request $re)
	{
		$parentId = $re->pid;
		$parent = User::find($parentId);
        $children = array();
        if($parent)
        {
            $children = $parent->children;
            if($re->wantsJson())
                return response()->json([
                    'status_code'=> 200,
                    'message'=>'Request process succeed.',
                    'data'=>$parent
                ],200);
        }
        else
        {
            if($re->wantsJson())
                return response()->json([
                    'status_code'=> 404,
                    'message'=>'User not found.',
                    'data'=>[]
                ],200);

            return redirect()->back();
        }
		
		

		return view('userRoles.admin.viewparent',with([
			'parent' => $parent,
			'children' => $children
		]));
	}

	public function contactus(Request $re)
	{
		$admins = User::whereIn('role_id',[3,4])->pluck('id');
                
                $parentId = Auth::user()->id;
                $msgs = array();
                $allMsgs = Chat::whereIn('to_id',$admins)->get();
                foreach ($allMsgs as $msg) {
                      $sender = User::find($msg->from_id); 
                      $msg['senderName'] = $sender->name;
                      $msg['senderImage'] = $sender->image;

                     $msgs[] = $msg;
                }

        if($re->wantsJson())
            return response()->json([
                'status_code'=> 200,
                'message'=>'Request process succeed.',
                'data'=>$msgs
            ],200);

		return view('userRoles.admin.contactus',with([
			'msgs' => $msgs
		]));
	}

	public function schoolUrlsView()
	{
		$urls = Url::first();
		return view('userRoles.admin.schoolurls',compact('urls'));
	}

	public function editSchoolUrls(Request $re)
	{
		$urls = Url::first();

		$images = $urls->images;
		$videos = $urls->videos;
		$location = $urls->location;

		if($re->images)
			$images = 'https://'.$re->images;

		if($re->videos)
					$videos = 'https://'.$re->videos;

		if($re->location)
			$location = 'https://'.$re->location;

		$urls->images = $images;
		$urls->videos = $videos;
		$urls->location = $location;

		$updated = $urls->update();

        if($updated)
        {
            if($re->wantsJson())
            return response()->json([
                'status_code'=> 201,
                'message'=>'Update process succeed.',
                'data'=>$urls
            ],200);
        }
        else
        {
           if($re->wantsJson())
            return response()->json([
                'status_code'=> 400,
                'message'=>'Bad Request, not updated.',
                'data'=>[]
            ],200); 
        }


		return redirect()->back();
	}

	public function ClassesLevelsToString($arr)
	{
		$names = array();
		foreach ($arr as $item) {
			$names[] = $item['name'];
		}
		$names = array_unique($names);
		$str = implode(" - ", $names);
		return rtrim($str,", ");
	} 

    public function blockParent(Request $request)
    {
        
        $parentId = $request->pid;
        $parent = User::find($parentId);
        if($parent)
        {
            if($parent->role_id == 1)
            {
                $check=BlockedUser::where('user_id',$parentId)->first();
                if($check)
                {
                    if($request->wantsJson())
                    return response()->json([
                        'status_code' => 400,
                        'message' => "Bad Request, this parent is already blocked.",
                        'data' => []
                    ],200);
                }
                else
                {
                    $blcok = new BlockedUser();
                    $blcok->user_id = $parentId;

                    $blcok->save();

                    if($request->wantsJson())
                    return response()->json([
                        'status_code' => 200,
                        'message' => "Request process succeed.",
                        'data' => []
                    ],200);
                }
            }
            else
            {
                if($request->wantsJson())
                return response()->json([
                    'status_code' => 400,
                    'message' => "Bad Request, this user isn't parent.",
                    'data' => []
                ],200);
            }
        }
        else
        {
            if($request->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => "User not found.",
                    'data' => []
                ],200);
        }
        return redirect()->back();
 
    }
    
    public function unBlockParent(Request $request)
    {
        $parentId = $request->pid;
        $parent = User::find($parentId);
        if($parent)
        {
            
            $blocked=BlockedUser::where('user_id',$parentId)->first();
            if($blocked)
            {
                $blocked->delete();
                if($request->wantsJson())
                    return response()->json([
                        'status_code' => 200,
                        'message' => "Request process succeed.",
                        'data' => []
                    ],200);
            }
            else
            {
                if($request->wantsJson())
                return response()->json([
                    'status_code' => 400,
                    'message' => "Bad Request, this parent is already unblocked.",
                    'data' => []
                ],200);
            } 
        }
        else
        {
            if($request->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => "User not found.",
                    'data' => []
                ],200);
        }
        return redirect()->back();
    }
    

    public function resetNewYear(Request $request)
    {
        $images = array();

        Like::truncate();
        Post::truncate();
        PostPermition::truncate();

        $postImages = PostImage::all();
        foreach ($postImages as $postImage){
            $image = base_path().'/images/posts/'.$parent->image;
            if(file_exists($image))
                unlink($image);
        }
        PostImage::truncate();

        Chat::truncate();
        Student::truncate();
        Teacher::truncate();
        StudentSubject::truncate();
        BlockedUser::truncate();

        $parents = User::where('role_id',1)->get();
        foreach ($parents as $parent) {
            if($parent->image != "avatar.jpg")
            {
              $image = base_path().'/images/users/'.$parent->image;
              //$images[] = $image;
              if(file_exists($image))
                unlink($image);
            }
            //return $images;
            
            $parent->delete();
        }

        if($request->wantsJson())
            return response()->json([
                'status_code' => 200,
                'message' => 'Request process succeed.',
                'data' => []
            ],200);

        return redirect()->route('home');
    }


    public function blockedParents(Request $request)
    {
        $parents = User::where('role_id',1)->get();
        $blocked = array();
        foreach ($parents as $parent) {
            if($parent->isBlocked())
                $blocked[] = $parent;
        }
        if($request->wantsJson())
            return response()->json([
                'status_code' => 200,
                'message' => 'Request process succeed.',
                'data' => $blocked
            ],200);  
    }
    
    protected function validator(Request $re)
    {

        $validationErrors = array();

        $nameValidator = Validator::make($re->only('name'), 
            ['name' => 'required|string|max:255|min:3']);

        if($nameValidator->fails())
        {
            $errors = $nameValidator->errors()->toArray();
            $validationErrors['name']=$errors['name'];
        }
        /* ============ password ========== */
        $passvalidator = Validator::make($re->only('password'),
            ['password' => 'required|string|min:4']);

        if($passvalidator->fails())
        {
            $errors=$passvalidator->errors()->toArray();
            $validationErrors['password']=$errors['password'];
        }
        /* ============ Phone ========== */
        $phonevalidator = Validator::make($re->only('phone'), 
            ['phone' => 'digits:14|unique:users,phone,$parent->id']);

        if($phonevalidator->fails())
        {
            $errors=$phonevalidator->errors()->toArray();
            $validationErrors['phone']=$errors['phone']; 
        }

        return $validationErrors;


        /*return Validator::make($data, [
            'name' => 'required|string|max:255',
            'phone' => 'required|digits:14|unique:users',
            'password' => 'required|string|min:4',
        ]);*/
    }



}
