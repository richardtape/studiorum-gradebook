<?php 

	/**
	 * Gradebook Utility functions
	 *
	 * @package     Studiorum Gradebook Utils
	 * @subpackage  Studiorum/Gradebook/Utils
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       0.1.0
	 */

	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ){
		exit;
	}

	class Studiorum_Grade_Book_Utils
	{

		/**
		 * Determine whether the passed user ID can see the grade associated with the passed Post ID
		 * If the user is the post author, true
		 * if the user is a teacher, true
		 * if the user is an admin, true
		 *
		 * @since 0.1
		 *
		 * @param int $userID The ID of the user to check
		 * @param int $postID The ID of the post to check
		 * @return bool
		 */
		public static function userCanSeeGrade( $userID, $postID )
		{

			// Admins can
			if( user_can( $userID, 'manage_options' ) ){
				return true;
			}

			// Post author can
			$postObject 	= get_post( $postID );
			$postAuthorID 	= $postObject->post_author;
			
			if( $postAuthorID == $userID ){
				return true;
			}

			// Teachers can
			$userObject = get_user_by( 'id', $userID );
			if( Studiorum_Utils::usersRoleIs( 'studiorum_educator', $userObject ) ){
				return true;
			}

			// Default to say no
			return false;

		}/* userCanSeeGrade() */

	}/* class Studiorum_Grade_Book_Utils */