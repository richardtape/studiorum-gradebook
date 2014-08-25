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

			// Register the metabox call
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes__registerMetaBox' ) );

			// When a post is saved, save the grade too
			add_action( 'save_post', array( $this, 'save_post__saveGradebookData' ) );

		}/* __construct() */


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

			$screens = array( 'lectio-submission' );

			$screens = apply_filters( 'studiorum_gradebook_metabox_screens', $screens );

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
			$screens = array( 'lectio-submission' );
			$screens = apply_filters( 'studiorum_gradebook_metabox_screens', $screens );

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

			// Update the meta field in the database.
			update_post_meta( $post_id, 'studiorum_grade', $value );

		}/* save_post__saveGradebookData() */


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