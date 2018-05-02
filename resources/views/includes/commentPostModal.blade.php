<div class="modal fade" tabindex="-1" role="dialog" id="comment_modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">@yield('modal_title')</h4>
			</div>
			<div class="modal-body">
				<form>
					<label for="Comment-post">Write Comment</label>
					<textarea class="form-control" name="body" id="comment-body" rows="5" placeholder="What do you want to say?"></textarea>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" id="save-comment">Save changes</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->