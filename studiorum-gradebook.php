<?php
	/*
	 * Plugin Name: Studiorum Grade Book
	 * Description: Provides grade book functionality to Studiorum
	 * Version:     0.1
	 * Plugin URI:  #
	 * Author:      UBC, CTLT, Richard Tape
	 * Author URI:  http://ubc.ca/
	 * Text Domain: studiorum-gradebook
	 * License:     GPL v2 or later
	 * Domain Path: languages
	 *
	 * studiorum-grade-book is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 2 of the License, or
	 * any later version.
	 *
	 * studiorum-grade-book is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with studiorum-grade-book. If not, see <http://www.gnu.org/licenses/>.
	 *
	 * @package Grade Book
	 * @category Core
	 * @author Richard Tape
	 * @version 0.1.0
	 */

	if( !defined( 'ABSPATH' ) ){
		die( '-1' );
	}

	if( !defined( 'STUDIORUM_GRADE_BOOK_DIR' ) ){
		define( 'STUDIORUM_GRADE_BOOK_DIR', plugin_dir_path( __FILE__ ) );
	}

	if( !defined( 'STUDIORUM_GRADE_BOOK_URL' ) ){
		define( 'STUDIORUM_GRADE_BOOK_URL', plugin_dir_url( __FILE__ ) );
	}

	class Studiorum_Grade_Book
	{

		// The screens on which we wish the metabox to appear
		static $screens = array();

		/**
		 * Actions and filters
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function __construct()
		{

			// Load our necessary files
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme__includes' ), 1 );

			// Set up the screens for the metabox
			add_action( 'init', array( $this, 'init__setDefaultScreens' ) );

			// Register the metabox call
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes__registerMetaBox' ) );

			// When a post is saved, save the grade too
			add_action( 'save_post', array( $this, 'save_post__saveGradebookData' ) );

			// Custom column for the gradebook if active
			add_filter( 'manage_edit-lectio-submission_columns', array( $this, 'manage_edit_columns__addGradebookColumn' ) );
			add_action( 'manage_lectio-submission_posts_custom_column' , array( $this, 'manage_posts_custom_column__addDateToGradebookColumn' ), 10, 2 );

			// Add grade to single submission view on front-end
			add_filter( 'the_content', array( $this, 'the_content__addGradeAboveSubmission' ) );

			// Some extra styles for warning messages etc.
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts__frontEndStyles' ) );

			// Register ourself as an addon
			add_filter( 'studiorum_modules', array( $this, 'studiorum_modules__registerAsModule' ) );

		}/* __construct() */


		/**
		 * Load our includes
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public static function after_setup_theme__includes()
		{

			require_once( trailingslashit( STUDIORUM_GRADE_BOOK_DIR ) . 'includes/class-studiorum-gradebook-utils.php' );

		}/* after_setup_theme__includes() */


		/**
		 * Add default screens for where to show the metabox
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function init__setDefaultScreens()
		{

			$screens = array( 'lectio-submission' );

			static::$screens = apply_filters( 'studiorum_gradebook_metabox_screens', $screens );

		}/* init__setDefaultScreens() */


		/**
		 * Add the metabox 
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function add_meta_boxes__registerMetaBox()
		{

			$screens = static::$screens;

			if( !$screens || !is_array( $screens ) || empty( $screens )  ){
				return;
			}
			foreach( $screens as $key => $postType ){
				add_meta_box( 'studiorum_gradebook_details', __( 'Evaluation', 'studiorum-gradebook' ), array( $this, 'add_meta_box_callback__gradebookFields' ), $postType, 'side', 'default' );
			}

		}/* add_meta_boxes__registerMetaBox() */


		/**
		 * The fields for the gradebook metabox
		 *
		 * @since 0.1
		 *
		 * @param object $post The post object
		 * @return null
		 */

		public function add_meta_box_callback__gradebookFields( $post )
		{

			// Always have a nonce
			wp_nonce_field( 'studiorum_gradebook_details', 'studiorum_gradebook_details_nonce' );

			// Existing data
			$value = get_post_meta( $post->ID, 'studiorum_grade', true );

			echo '<label for="studiorum_grade">';
				_e( 'Grade', 'myplugin_textdomain' );
			echo '</label> ';
	
			echo '<input type="text" id="studiorum_grade" name="studiorum_grade" value="' . esc_attr( $value ) . '" size="25" />';

		}/* add_meta_box_callback__gradebookFields() */


		/**
		 * When a post is updated, ensure we save the metadata too
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		public function save_post__saveGradebookData( $post_id = false )
		{

			// Check if our nonce is set.
			if( !isset( $_POST['studiorum_gradebook_details_nonce'] ) ) {
				return;
			}

			// Verify that the nonce is valid.
			if( !wp_verify_nonce( $_POST['studiorum_gradebook_details_nonce'], 'studiorum_gradebook_details' ) ) {
				return;
			}

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check we're on a relevant post type
			$screens = static::$screens;

			if( !$screens || !is_array( $screens ) || empty( $screens )  ){
				return;
			}

			if( isset( $_POST['post_type'] ) && !in_array( $_POST['post_type'], array_values( $screens ) ) ){
				return;
			}

			// Make sure that it is set.
			if( !isset( $_POST['studiorum_grade'] ) ){
				return;
			}

			// Sanitize user input.
			$value = sanitize_text_field( $_POST['studiorum_grade'] );

			do_action( 'studiorum_gradebook_before_save_grade', $value, $post_id );

			// Update the meta field in the database.
			update_post_meta( $post_id, 'studiorum_grade', $value );

			do_action( 'studiorum_gradebook_after_save_grade', $value, $post_id );

		}/* save_post__saveGradebookData() */


		/**
		 * Add the gradebook column should the gradebook add-on be activate
		 *
		 * @since 0.1
		 *
		 * @param array $columns - The predefined columns for the post type
		 * @return array - modified columns with our custom column should we need to add it
		 */

		public function manage_edit_columns__addGradebookColumn( $columns )
		{

			// Is gradebook active?
			if( class_exists( 'Studiorum_Lectio_Utils' ) && !Studiorum_Lectio_Utils::gradebookIsActive() ){
				return $columns;
			}

			// It's active, so let's add the column
			$columns['grade'] = __( 'Grade', 'studiorum-lectio' );

			// ship
			return $columns;

		}/* manage_edit_columns__addGradebookColumn */


		/**
		 * Add data to the gradebook column should the gradebook add-on be active
		 *
		 *
		 * @since 0.1
		 *
		 * @param array $column - columns being shown on the admin screen
		 * @param int $post_id - the ID for each row of posts on the admin screen
		 * @return null
		 */

		public function manage_posts_custom_column__addDateToGradebookColumn( $column, $post_id )
		{

			// Ensure we have the gradebook active
			if( class_exists( 'Studiorum_Lectio_Utils' ) && !Studiorum_Lectio_Utils::gradebookIsActive() ){
				return;
			}

			// Only add data to the grade column
			if( $column != 'grade' ){
				return;
			}

			// Get the data for this post
			$value = get_post_meta( $post_id, 'studiorum_grade', true );

			if( !$value || $value == '' )
			{
			
				_e( 'Ungraded', 'studiorum-lectio' );
				return;

			}

			echo esc_html( $value );

		}/* manage_posts_custom_column__addDateToGradebookColumn */


		/**
		 * If a submission has been graded then we need to add a note with the grade above the submission
		 * This should only be for the author of the submission and the admin
		 *
		 * @since 0.1
		 *
		 * @param string $content - the existing post content
		 * @return string $content - modified post content
		 */

		public function the_content__addGradeAboveSubmission( $content )
		{

			// Not logged in, or we're on the back-end? No dice.
			if( !is_user_logged_in() || is_admin() ){
				return $content;
			}

			// First check we're on the right post type
			$postID = get_the_ID();

			// Screens (which doubles as post-types)
			$screens = static::$screens;
			
			if( !isset( $screens ) || !is_array( $screens ) || empty( $screens ) ){
				return $content;
			}

			if( !in_array( get_post_type( $postID ), array_values( $screens ) ) ){
				return $content;
			}

			$currentUserID 		= get_current_user_id();
			$userCanSeeGrade 	= Studiorum_Grade_Book_Utils::userCanSeeGrade( $currentUserID, $postID );

			if( !$userCanSeeGrade ){
				return $content;
			}

			// Fetch the grade
			$grade = get_post_meta( $postID, 'studiorum_grade', true );

			if( !$grade || $grade == '' ){
				return $content;
			}
			
			$hasGradeTemplate = apply_filters( 'studiorum_gradebook_has_grade_template_path', Studiorum_Utils::locateTemplateInPlugin( STUDIORUM_GRADE_BOOK_DIR, 'includes/templates/this-submission-is-graded.php' ) );
			
			if( !empty( $hasGradeTemplate ) ){
				$prefix = Studiorum_Utils::fetchTemplatePart( $hasGradeTemplate, array( 'grade' => $grade ) );
			}

			return $prefix . $content;

		}/* the_content__addGradeAboveSubmission() */


		/**
		 * Enqueue some front end styles
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function wp_enqueue_scripts__frontEndStyles()
		{

			// First check we're on a page with a valid gForm (set in options)
			if( !class_exists( 'Studiorum_Lectio_Utils' ) ){
				return false;
			}

			if( !is_singular( Studiorum_Lectio_Utils::$postTypeSlug ) ){
				return false;
			}

			wp_enqueue_style( 'studiorum-grade-book-front-end-styles', trailingslashit( STUDIORUM_GRADE_BOOK_URL ) . 'includes/assets/css/studiorum-grade-book.css' );

		}/* wp_enqueue_scripts__frontEndStyles() */


		/**
		 * Register ourself as a studiorum addon, so it's available in the main studiorum page
		 *
		 * @since 0.1
		 *
		 * @param array $modules Currently registered modules
		 * @return array $modules modified list of modules
		 */

		public function studiorum_modules__registerAsModule( $modules )
		{

			if( !$modules || !is_array( $modules ) ){
				$modules = array();
			}

			$modules['studiorum-gradebook'] = array(
				'id' 				=> 'gradebook',
				'plugin_slug'		=> 'studiorum-gradebook',
				'title' 			=> __( 'Grade Book', 'studiorum' ),
				'icon' 				=> 'yes', // dashicons-#
				'excerpt' 			=> __( 'Give feedback to your students. View statistics across all users and allow students to see their own statistics.', 'studiorum' ),
				'image' 			=> 'http://dummyimage.com/310/162',
				'link' 				=> 'http://code.ubc.ca/studiorum/gradebook',
				'content' 			=> __( '<p>Information here about what gradebook does</p>', 'studiorum' ),
				'content_sidebar' 	=> 'http://dummyimage.com/300x150',
				'date'				=> '2014-10-01',
				'coming_soon'		=> true
			);

			return $modules;

		}/* studiorum_modules__registerAsModule() */

	}/* class Studiorum_Grade_Book */


	/**
	 *
	 * Instantiate the gradebook
	 *
	 * @since 0.1.0
	 * @return null
	 */

	function Studiorum_Grade_Book()
	{

		$Studiorum_Grade_Book = new Studiorum_Grade_Book;

		$GLOBALS['studiorum_addons'][] = $Studiorum_Grade_Book;

	}/* Studiorum_Grade_Book() */

	// Get Studiorum_Grade_Book Running
	add_action( 'plugins_loaded', 'Studiorum_Grade_Book', 5 );