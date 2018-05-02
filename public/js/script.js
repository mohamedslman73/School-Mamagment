




/*======= Send Message =========*/

var msgFrom = 0;
var msgTo = 0;


function sendMsg(event,from,to){
	event.preventDefault();
	msgFrom = from;
	msgTo = to;
	$('#write-msg').modal();
}

$('#send-msg').on('click',function(){
	var msg = $('#msg-body').val();
	$('#write-msg').modal('hide');
	$.ajax({
		method:'POST',
		url:sendMsgUrl,
		data:{msgFrom:msgFrom, msgTo:msgTo, msg:msg, _token:token},
		error: function(){
			alert("You didn't type any message!");
		}
	})
	.done(function(msg){

		//alert(JSON.stringify(msg['data']));
		alert("Your Message has been sent Successfully.");
		$('#edit_modal').modal('hide');
	});

});


/* ============ Edit Post =========== */
var postId = 0;
var postBodyElement = null;

$('.post').find('.interaction').find('.edit').on('click',function(event){
	event.preventDefault();
	postBodyElement = event.target.parentNode.parentNode.childNodes[6];
	var postBody = postBodyElement.textContent;
	postId = $(this).attr('data-postId');
	$('#post-body').val(postBody);
	$('#edit_modal').modal();
});

$('#save-editing').on('click',function(){

	$.ajax({
		method: 'POST',
		url: urlEditPost,
		data: { body: $('#post-body').val(), postId: postId, _token: token }
	})
	.done(function(msg){
		$(postBodyElement).text(msg['new-body']);
		$('#edit_modal').modal('hide');
	});
});

/* ============ Like and Dislike Post =========== */
$('.like').on('click',function(event){
	event.preventDefault();
	
	postId = $(this).attr('data-postId');
	// if you want to add dislike option
	//var isLike = event.target.previousElementSibling == null;
	$.ajax({
		method:'POST',
		url:urlLikePost,
		data:{postId:postId, _token: token}
	})
	.done(function(feedback){
		$('.allLikes').text(feedback['likes']);
	});
	var check = $(this).attr('data-val');
	if(check == "l")
	{
		$(this).attr('data-val','d');
		check = "d";
		$(this).html('Dislike');
	}
	else if(check == "d")
	{
		$(this).attr('data-val','l');
		check = "l";
		$(this).html('Like');   
	}
});

/* ============ Comment on Post =========== */

$('.comment').on('click',function(event){
	event.preventDefault();
	postId = $(this).attr('data-postId');
	$('#comment_modal').modal();
});

$('#save-comment').on('click',function(){
	$.ajax({
		method: 'POST',
		url: urlWriteComment,
		data: { content: $('#comment-body').val(), postId: postId, _token: token }
	})
	.done(function(msg){
		//console.log(msg['feedback']);
		$('#comment_modal').modal('hide');
	});
});









