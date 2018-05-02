<?php

namespace App\Http\Controllers\userRoles;

use App\BlockedUser;
use App\Chat;
use App\Http\Controllers\Controller;
use App\Subject;
use App\Level;
use App\Sort;
use App\Teacher;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PrincipleController extends Controller
{

	/**
	* Create a new controller instance.
	*
	* @return void
	*/
	public function __construct()
	{
	$this->middleware('auth');
	$this->middleware('principle');
	}
	
	/**
	* Show the application dashboard.
	*
	* @return \Illuminate\Http\Response
	*/
	public function index()
	{
	return view('userRoles.principle.home');
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
		
		return view('userRoles.principle.teachers',with([
		    'teachers' => $teachers
		]));
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
	
	
	/*return view('userRoles.admin.parents',with([
	    'parents' => $parents
	]));*/
	}
	
	public function contactus(Request $re)
	{
	$adPrincs = User::whereIn('role_id',[3,4])->pluck('id');
	
	$msgs = array();
	$allMsgs = Chat::whereIn('to_id',$adPrincs)->get();
	foreach ($allMsgs as $msg){
	       $sender = User::find($msg->from_id); 
	       $msg['senderName'] = $sender->name;
	       $msg['senderImage'] = $sender->image;
	
	       $msgs[] = $msg;
	}
	
	
	if($re->wantsJson())
	    return response()->json([
	        'status_code' => 200,
	        'message' => 'Request process succeed.',
	        'data' => $msgs
	    ],200);
	
	return view('userRoles.principle.contactus',with([
	    'msgs' => $msgs
	]));    
	}
	
	public function sendView()
	{
	return view('userRoles.principle.send');
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
		

		//return redirect()->route('admins.edit.teacher.view',['tid'=>$user->id]);   
	}
	
	
	
	public function onlineTeachers(Request $request)
	{
	$teachers = User::where('role_id',2)->get();
	$online = array();
	foreach ($teachers as $teacher){
	    if($teacher->isOnline())
	        $online[] = $teacher;
	}
	
	if($request->wantsJson())
	    return response()->json([
	        'status_code' => 200,
	        'message' => 'Request process succeed.',
	        'data' => $online
	    ],200);
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
