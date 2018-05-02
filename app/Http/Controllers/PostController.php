<?php

namespace App\Http\Controllers;

use App\Like;
use App\Post;
use App\PostImage;
use App\PostPermition;
use App\Teacher;
use App\User;
use App\PushAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('block');
    }

    public function createPost(Request $re)
    {
        //echo "<pre>";print_r($re->all());die();
        $user = Auth::user();
        $post = new Post();
        $permition = new PostPermition;

        $check = true;

        if($user->role->name == 'teacher' && $re->classId)
        {
            $teacher=Teacher::where('user_id',$user->id)->get();
            $classesIDs = array();
            foreach ($teacher as $item)
                $classesIDs[] = $item->class_id;
            $classId = $re->classId;
            if(!in_array($classId,$classesIDs))
                $check = false;
        }
        $posted = "";

        if($check)
        {
            if(Auth::user()->role->name == 'teacher' || Auth::user()->role->name == 'principle')
            {
                
                if($re->content)
                {
                    $posted = $post->create([
                        'user_id' => Auth::user()->id,
                        'post_id' => 0,
                        'content' => $re->content,
                    ]);     
                }
                else
                {
                    $errmsg = "Bad Request, you should write something.";
                    if($re->wantsJson())
                        return response()->json([
                            'status_code' => 400,
                            'message' => $errmsg,
                            'data' => []
                        ],200);
                }
            }
            
        }
        else
        {
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthenticated to post to this class.',
                    'data' => []
                ],200);
        }

        if($posted)
        {

            $classId = 0;
            $parents = $staff = 1;
            
            switch($re->vtype){
                case 'parent':
                $parents = 1;
                $classId = 0;
                $staff = 0;
                break;
                case 'staff':
                $parents = 0;
                $classId = 0;
                $staff = 1;
                break;
                case 'class':
                $parents = 0;
                $classId = $re->classId;
                $staff = 0;
                break;
            }

            $permited = $permition->create([
                'post_id' => $posted->id,
                'parents' => $parents,
                'staff' => $staff,
                'class_id' => $classId,
            ]);
            

            if($re->hasfile('image')){

                $extensions = ['jpeg','jpg','png'];
                $ext = $re->image->extension();

                $destinationPath = base_path('images/posts');
                $file = strtolower(rand(999,99999).uniqid().'.'.$ext);
                $re->image->move($destinationPath, $file);

                $postImg = new PostImage;
                $postImg->post_id = $posted->id;
                $postImg->image = $file;

                $postImg->save();
            }

            $last = Post::where('user_id',Auth::user()->id)
                        ->orderBy('created_at','DESC')->first();

            $last['userName']= Auth::user()->name;
            $last['userImage']= Auth::user()->image;
            $postimg = PostImage::where('post_id',$posted->id)->first();
            if($postimg)
                $last['postImage']= $postimg->image;
            
            $last['permition']= $last->permition;

            if($re->wantsJson())
                return response()->json([
                    'status_code' => 201,
                    'message' => 'Adding process succeed.',
                    'data' => $last
                ],200);

            session()->flash('message','Post Created Successfully'); 
        }
        else
        {
            $errmsg = "Unauthenticated.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 401,
                    'message' => $errmsg,
                    'data' => []
                ],200);
        }

        return redirect()->route('home');
    }

    public function delete(Request $re)
    {
        $postId = $re->post_id;
        $post = Post::where('id',$postId)->first();

        $postPermition = PostPermition::where('post_id',$postId)->first(); 
        $postImage = PostImage::where('post_id',$postId)->first();

        if($post)
        {
            if(Auth::user()->id != $post->user->id)
            {
            $errmsg = "Unauthenticated.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => $errmsg,
                    'data' => []
                ],200);

            return redirect()->back();
            }
        }
        else
        {
            $errmsg = "Post not found.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => $errmsg,
                    'data' => []
                ],200);

            return redirect()->back();
        }
        
        if($post->delete())
        {
            if($postPermition && $postImage)
            {
                $postPermition->delete();
                $postImage->delete();
                $image = 'images/posts/'.$postImage->image;
                File::delete($image);
            }

            if($re->wantsJson())
                return response()->json([
                    'status_code' => 204,
                    'message' => 'Delete process succeed.',
                    'data' => []
                ],200);

            session()->flash('message','Post Deleted Successfully');
        }  

        return redirect()->route('home');
    }

    public function editPost(Request $re)
    {

        $postId = (int)$re['postId'];
        $postBody = $re['body'];

        $post = Post::find($postId);

        if($post)
        {
            if($postBody)
            {
                $post->content = $postBody;
                $updated = $post->update();

                if($re->wantsJson())
                    return response()->json([
                        'status_code' => 201,
                        'message' => 'Update process succeed.',
                        'data' => $post
                    ],200);
            }
            else
            {
                $errmsg = "Bad Request, you should write something.";
                if($re->wantsJson())
                    return response()->json([
                        'status_code' => 400,
                        'message' => $errmsg,
                        'data' => []
                    ],200);
            }
        }
        else
        {
            $errmsg = "Post not found!";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => $errmsg,
                    'data' => []
                ],200);
        }

        

        return response()->json([
            'new-body' => $postBody
        ],200);
    }

    public function likePost(Request $re)
    {

        $postId = $re->postId;
        //Tarek => isLike is received as a string not boolean

        $user = Auth::user();
        $post = Post::find($postId);

        if(!$post)
        {
            $errmsg = "post not found.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => $errmsg,
                    'data' => []
                ],200);

            return null;
        }

        $like = $user->likes()->where('post_id',$postId)->first();
        
        if($like)
        {
            $like->delete();

            $allLikes = count($post->likes);

            $isLiked = 0;
            $checkLike=Like::where([
                ['post_id','=',$post->id],
                ['user_id','=',$user->id]
            ])->first(); 
            if($checkLike)
               $isLiked = 1;

            if($re->wantsJson())
                return response()->json([
                    'status_code' => 200,
                    'message' => 'Request process succeed.',
                    'data' => [
                        'Likes' => $allLikes,
                        'isLiked' => $isLiked
                    ]
                ],200);

            return response()->json(['likes'=>$allLikes]); 
        }
        else{
        
            $like = new Like();
            $like->like = true;
            $like->user_id = $user->id;
            $like->post_id = $postId;

            $like->save();
            
            $postOwner = User::find($post->user_id);
            if($postOwner)
            {
            	$tokens= $postOwner->firbase_token;
	        $title = "Like received";
	        $body  = $user->name." Likes your post";
	        $type  = "like";
	        $data  = ['post_id'=>$postId];

	    	$push = new PushAlert();
	    	$push->send_notification($tokens, $title, $body, $type, $data);
            }
            
            
            
	}
        

        $allLikes = count($post->likes);
        
        $isLiked = 0;
        $checkLike=Like::where([
            ['post_id','=',$post->id],
            ['user_id','=',$user->id]
        ])->first(); 
        if($checkLike)
           $isLiked = 1;

        if($re->wantsJson())
            return response()->json([
                'status_code' => 200,
                'message' => 'Request process succeed.',
                'data' => [
                    'Likes' => $allLikes,
                    'isLiked' => $isLiked
                ]
            ],200);

        return response()->json(['likes'=>$allLikes]);   
    }

    public function commentPost(Request $re)
    { 
    	$user = Auth::user();
        $post = new Post();
        $postId = (int)$re->postId;
        $content = $re->content;
        $posted = "";
        
        $parentPost = Post::find($postId);

        if($parentPost)
        {
           if($content)
           {
               $this->Validate($re,[
                'content' => 'required|max:1000'
                ]);

                $posted = $post->create([
                    'user_id' => $user->id,
                    'post_id' => $postId,
                    'content' => $content,
                ]);
                
                $postOwner = User::find($parentPost->user_id);
                if($postOwner)
            	{
	            	$tokens= $postOwner->firbase_token;
		        $title = "Comment received";
		        $body  = $user->name." Comment on your post";
		        $type  = "comment";
		        $data  = ['post_id'=>$postId];
	
		    	$push = new PushAlert();
		    	$push->send_notification($tokens, $title, $body, $type, $data);
            	}
            
           }
           else
           {
                $errmsg = "Bad Request, you should write something.";
                if($re->wantsJson())
                    return response()->json([
                        'status_code' => 400,
                        'message' => $errmsg,
                        'data' => []
                    ],200);
           }
        }
        else
        {
            $errmsg = "post not found.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 404,
                    'message' => $errmsg,
                    'data' => []
                ],200);
        }
        

        if($posted)
        {
            $last = Post::where([
                ['user_id','=',Auth::user()->id],
                ['post_id','=',$postId]
            ])->orderBy('created_at','DESC')->first();

            if($re->wantsJson())
                return response()->json([
                    'status_code' => 201,
                    'message' => 'Adding process succeed.',
                    'data' => $last
                ],200);

            return response()->json(['feedback'=>'success'],201);
        }
        else
        {
            $errmsg = "error not created.";
            if($re->wantsJson())
                return response()->json([
                    'status_code' => 400,
                    'message' => 'Bad Request.',
                    'data' => []
                ],200);

            return response()->json(['feedback'=>'faield'],200);
        }
    }

    


}
