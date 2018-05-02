<?php

namespace App\Http\Controllers;

use App\Level;
use App\Sort;
use App\Subject;
use Illuminate\Http\Request;

class SchoolController extends Controller
{

	public function __construct()
	{
		//$this->middleware('auth');
		//$this->middleware('block');
	}

	public function classes(Request $request)
	{
		$classes = Sort::all();
		if($request->wantsJson())
			return response()->json([
				'status_code' => 200,
				'message' => 'Request process succeed.',
				'data' => $classes
			],200);
	} 
	public function levels(Request $request)
	{
		$levels = Level::all();
		if($request->wantsJson())
			return response()->json([
				'status_code' => 200,
				'message' => 'Request process succeed.',
				'data' => $levels
			],200);
	}
	
	
    public function subjects(Request $re)
    {
        $subjects = Subject::all();
        if($subjects)
            foreach ($subjects as $subject){
                unset($subject['created_at']);
                unset($subject['updated_at']);
            }
        if($re->wantsJson())
            return response()->json([
                'status_code' => 200,
                'message' => "Request process succeed.",
                'data' => $subjects
            ],200);
    }
    
    public function classesOfLevel(Request $re)
    {
    	$levelId = $re->level;
    	$check = Level::find($levelId);
    	if($check)
    	{
    	   $classes = Sort::where('level_id',$levelId)->get();
    	   if($re->wantsJson())
    	   	return response()->json([
    	   		'status_code' => 200,
    	   		'message' => 'Request Process succeed.',
    	   		'data' => $classes
    	   	]);
    	}
    	else
    	{
    	     if($re->wantsJson())
    	   	return response()->json([
    	   		'status_code' => 404,
    	   		'message' => 'Level not found.',
    	   		'data' => []
    	   	]);
    	}
    	
    	
    	
    	
    }
	
	
	
	
	
}
