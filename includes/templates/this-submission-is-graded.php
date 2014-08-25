<?php

	/**
	 * Template for the message shown when someone is visiting a submisison that is graded (and is able to see the grade)
	 *
	 * @since 0.1
	 * @var array $data 				- The grade for this post
	 *
	 */

?>

	<div class="pre-form-message has-been-graded">

		<p class="grade-details">
			<span class="grade-note"><?php _e( 'Your Grade', 'studiorum-gradebook' ); ?></span>
			<span class="actual-grade"><?php echo esc_html( $data['grade'] ); ?></span>
		</p>

	</div><!-- .pre-form-message -->