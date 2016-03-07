<?php
/**
 * Plugin Name: Post types creator
 * Plugin URI:
 * Description: Helper plugin for easily creating localize-ready custom post types and custom taxonomies with extra functionality in WordPress
 * Version:     0.2.0
 * Author:      Peter Elmered
 * Text Domain: post-type-creator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace PE;


class Post_Types_Creator {

	private $text_domain = 'post-type-creator';

	public $post_types = [];
	public $taxonomies = [];

	public $taxonomy_filters = [];

	public $use_acf = false;


	/**
	 * Post_Type_Creator constructor. Should be called on plugins_loaded action
	 * @param array $options
	 */
	public function __construct( $options = [] ) {

		if ( isset( $options['text_domain'] ) ) {
			$this->text_domain = $options['text_domain'];
		}
		if ( isset( $options['use_acf'] ) && $options['use_acf'] ) {
			$this->use_acf = true;
		}

		$this->load_plugin_textdomain();
	}

	/**
	 * Initialize the plugin. Should be called at init action
	 */
	public function init() {

		add_action( 'wp_ajax_pe_ptc_sort_posts', [ $this, 'sortable_ajax_handler' ] );

		$this->register_post_types();
		$this->register_taxonomies();

		add_action( 'save_post', [ $this, 'save_post' ], 10, 3 );

		$force_reint = filter_input( INPUT_GET, 'ptc-reinit', FILTER_SANITIZE_STRING );

		if ( is_admin() && ! empty( $force_reint ) ) {
			$this->force_reinitialize();
		}

		// Sort posts
		add_filter( 'pre_get_posts', [ $this, 'sort_admin_post_list' ] );

		// Restrict posts based on taxonomy filters
		add_action( 'restrict_manage_posts', [ $this, 'restrict_admin_posts_by_taxonomy' ] );
		add_filter( 'parse_query', [ $this, 'add_terms_filter_to_query' ] );

		// Sort terms
		add_filter( 'get_terms_orderby', [ $this, 'sort_get_terms' ], 10, 3 );
	}

	/**
	 * Reinitialize the plugins. Flush rewrite rules and set sort meta value for sortable post types to make the sorting query work.
	 */
	public function force_reinitialize() {

		global $wp_rewrite;

		$wp_rewrite->flush_rules();

		foreach ( $this->post_types as $post_slug => $post_args ) {
			if ( isset( $post_args['sortable'] ) && $post_args['sortable'] ) {
				$sort_meta_key = $this->get_sort_meta_key( $post_slug );

				$args = [
					'posts_per_page'   => -1,
					'post_type'        => $post_slug,
					'post_status'      => 'publish',
				];
				$posts = get_posts( $args );

				$sort_value = 1;

				foreach ( $posts as $post ) {
					$current = get_post_meta( $post->ID, $sort_meta_key, true );

					if ( empty( $current ) || ! is_numeric( $current ) ) {
						delete_post_meta( $post->ID, $sort_meta_key );
						update_post_meta( $post->ID, $sort_meta_key, $sort_value++ );
					}
				}
			}
		}
	}

	/**
	 * Public interface for seting post type data
	 *
	 * @param $post_types
	 */
	public function set_post_types( $post_types ) {

		$parsed_post_types = [];

		foreach ( $post_types as $slug => $post_type ) {
			$parsed_post_types[ $slug ] = $this->parse_post_type_args( $slug, $post_type );
		}

		$this->post_types = $parsed_post_types;
	}

	/**
	 * Public interface for seting taxonomy data
	 *
	 * @param $taxonomies
	 */
	public function set_taxonomies( $taxonomies ) {

		$parsed_taxonomies = [];

		foreach ( $taxonomies as $slug => $post_type ) {
			$parsed_taxonomies[ $slug ] = $this->parse_taxonomy_args( $slug, $post_type );
		}

		$this->taxonomies = $parsed_taxonomies;
	}

	/**
	 * @param $post_type
	 * @return mixed|null|void
	 */
	private function get_post_type_labels( $post_slug, $post_type ) {

		$labels = [
			'name'                  => _x( $post_type['plural_label_ucf'], 'Post Type General Name', $this->text_domain ),
			'singular_name'         => _x( $post_type['singular_label_ucf'], 'Post Type Singular Name', $this->text_domain ),
			'menu_name'             => __( $post_type['plural_label_ucf'], $this->text_domain ),
			'parent'                => sprintf( __( 'Parent %s', $this->text_domain ), $post_type['singular_label'] ),
			'parent_item_colon'     => sprintf( __( 'Parent %s:', $this->text_domain ), $post_type['singular_label'] ),
			'all_items'             => sprintf( __( 'All %s', $this->text_domain ), $post_type['plural_label'] ),
			'view'                  => sprintf( __( 'View %s', $this->text_domain ), $post_type['singular_label'] ),
			'view_item'             => sprintf( __( 'View %s', $this->text_domain ), $post_type['singular_label'] ),
			'add_new'               => sprintf( __( 'Add %s', $this->text_domain ), $post_type['singular_label'] ),
			'add_new_item'          => sprintf( __( 'Add new %s', $this->text_domain ), $post_type['singular_label'] ),
			'edit'                  => __( 'Edit', $this->text_domain ),
			'edit_item'             => sprintf( __( 'Edit %s', $this->text_domain ), $post_type['singular_label'] ),
			'update_item'           => sprintf( __( 'Update %s', $this->text_domain ), $post_type['singular_label'] ),
			'search_items'          => sprintf( __( 'Search %s', $this->text_domain ), $post_type['plural_label'] ),
			'not_found'             => sprintf( __( 'No %s found', $this->text_domain ), $post_type['plural_label'] ),
			'not_found_in_trash'    => sprintf( __( 'No %s found in trash', $this->text_domain ), $post_type['plural_label'] ),
		];

		$labels = apply_filters( 'ptc_post_type_labels', $labels, $post_slug, $post_type );
		$labels = apply_filters( 'ptc_post_type_labels_'.$post_slug, $labels, $post_type );

		return $labels;
	}

	/**
	 * Generates all post type labels and merges the labels and default values with the values passed to the plugin
	 *
	 * @param $post_slug - Post type slug
	 * @param $post_type - Post type arguments/settings
	 * @return array - Generated arguments for passing to register_post_type()
	 */
	private function parse_post_type_args( $post_slug, $post_type ) {

		$post_type['singular_label_ucf'] = ucfirst( $post_type['singular_label'] );
		$post_type['plural_label_ucf'] = ucfirst( $post_type['plural_label'] );

		$generated_args = [
			'label'               => __( $post_slug, $this->text_domain ),
			'description'         => __( $post_type['plural_label_ucf'], $this->text_domain ),
			'labels'              => $this->get_post_type_labels( $post_slug, $post_type ),
		];

		$default_args = [
			// Override some defaults to cover most cases out of the box
			'supports'              => [ 'title', 'editor', 'thumbnail' ],
			'taxonomies'            => [],
			'public'                => true,
			'menu_position'         => 6,   //Below posts
			'has_archive'           => true,

			//Custom
			'admin_columns'         => [],
			'sortable'              => false,
		];

		return wp_parse_args( array_merge( $generated_args, $post_type ), $default_args );
	}

	private function get_taxonomy_labels( $taxonomy_slug, $taxonomy ) {

		$labels = [
			'name'                  => _x( $taxonomy['plural_label_ucf'], 'Taxonomy General Name', $this->text_domain ),
			'singular_name'         => _x( $taxonomy['singular_label_ucf'], 'Taxonomy Singular Name', $this->text_domain ),
			'menu_name'             => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
			'parent'                => sprintf( __( 'Parent %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'parent_item'           => sprintf( __( 'Parent %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'parent_item_colon'     => sprintf( __( 'Parent %s:', $this->text_domain ), $taxonomy['singular_label'] ),
			'new_item_name'         => sprintf( __( 'Add new %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'add_new_item'          => sprintf( __( 'Add new %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'edit'                  => __( 'Edit', $this->text_domain ),
			'edit_item'             => sprintf( __( 'Edit %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'update_item'           => sprintf( __( 'Update %s', $this->text_domain ), $taxonomy['singular_label'] ),
			'separate_items_with_commas' => __( 'Separate items with commas', $this->text_domain ),
			'search_items'          => sprintf( __( 'Search %s', $this->text_domain ), $taxonomy['plural_label'] ),
			'add_or_remove_items'   => __( 'Add or remove %s', $taxonomy['plural_label'] ),
			'choose_from_most_used' => __( 'Choose from the most used items', $this->text_domain ),
			'not_found'             => sprintf( __( 'No %s found', $this->text_domain ), $taxonomy['plural_label'] ),
		];

		$labels = apply_filters( 'ptc_taxonomy_labels', $labels, $taxonomy_slug, $taxonomy );
		$labels = apply_filters( 'ptc_taxonomy_labels_'.$taxonomy_slug, $labels, $taxonomy );

		return $labels;
	}

	/**
	 * Generates all taxonomy labels and merges the labels and default values with the values passed to the plugin
	 *
	 * @param $taxonomy_slug - Taxonomy slug
	 * @param $taxonomy - Taxonomy arguments/settings
	 * @return array - Generated arguments for passing to register_taxonomy()
	 */
	private function parse_taxonomy_args( $taxonomy_slug, $taxonomy ) {

		$taxonomy['singular_label_ucf'] = ucfirst( $taxonomy['singular_label'] );
		$taxonomy['plural_label_ucf'] = ucfirst( $taxonomy['plural_label'] );

		$generated_args = [
			'label'               => __( $taxonomy_slug, $this->text_domain ),
			'description'         => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
			'labels'              => $this->get_taxonomy_labels( $taxonomy_slug, $taxonomy ),
		];

		$default_args = [
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,

			//Custom
			'sortable'              => false,

			// register_taxonomy overrides
			// https://codex.wordpress.org/Function_Reference/register_taxonomy
			'rewrite' => [
				'slug' => sanitize_title( $taxonomy['plural_label'] ),
			],
		];

		return wp_parse_args( array_merge( $generated_args, $taxonomy ), $default_args );
	}

	/**
	 * Register the post types passed to the plugin and adds extra features depending on passed options
	 */
	private function register_post_types() {

		$post_types = $this->post_types;

		foreach ($post_types as $slug => $post_args ) {

			$post_args = apply_filters( 'ptc_post_type_args', $post_args, $slug );
			$post_args = apply_filters( 'ptc_post_type_args_'.$slug, $post_args );

			register_post_type( $slug, $post_args );

			$this->register_post_statuses( $post_args );

			if ( is_admin() ) {

				add_action( 'admin_footer-post.php', [ $this, 'append_post_status_list' ] );

				$this->add_taxonomy_filters( $slug, $post_args );

				$this->register_admin_columns( $slug, $post_args );

			}
		}

		$this->register_sortable( $slug, $post_args );
	}

	private function register_post_statuses( $post_args ) {

		if ( empty( $post_args['post_statuses'] ) || ! is_array( $post_args['post_statuses'] ) ) {
			return null;
		}

		foreach ($post_args['post_statuses'] as $post_status_slug => $post_status_args ) {

			$post_status_args['label'] = $post_status_args['singular_label'];
			$post_status_args['label_count'] = _n_noop(
				$post_status_args['singular_label'].' <span class="count">(%s)</span>',
				$post_status_args['plural_label'].' <span class="count">(%s)</span>',
				$this->text_domain
			);

			register_post_status( $post_status_slug, $post_status_args );
		}
	}

	private function register_admin_columns( $slug, $post_args ) {

		if ( isset( $post_args['admin_columns'] ) ) {
			foreach ( $post_args['admin_columns'] as $column ) {
				add_filter( 'manage_posts_columns' , [ $this, 'add_admin_column' ], 10, 2 );

				add_action( 'manage_'.$slug.'_posts_custom_column' , [ $this, 'add_admin_column_content' ], 10, 2 );
			}
		}
	}

	private function add_taxonomy_filters( $slug, $post_args ) {

		if ( isset( $post_args['taxonomy_filters'] ) ) {

			if ( $post_args['taxonomy_filters'] === true ) {
				$this->taxonomy_filters[ $slug ] = $post_args['taxonomies'];
			}
			elseif (is_array( $post_args['taxonomy_filters'] ) && ! empty( $post_args['taxonomy_filters'] ) ) {
				$this->taxonomy_filters[ $slug ] = $post_args['taxonomy_filters'];
			}
		}
	}

	private function register_sortable( $slug, $args, $taxonomy = false ) {

		if ( ! $args['sortable'] ) {
			return;
		}

		if ( $taxonomy ) {
			$current = $this->get_current_taxonomy( $args['post_type'] );

			if ( $args['sortable'] &&
				isset( $this->taxonomies[ $current ]['sortable'] ) &&
				$this->taxonomies[ $current ]['sortable'] == true
			) {

				// Show all terms on the same page. Needed for drag and drop sorting to work.
				// TODO: Better solution if there are many terms needed.
				add_filter( 'edit_' . $slug . '_per_page', function() {
					return 5000;
				} );

				$this->enqueue_sortable_scripts();
			} else {
				$this->taxonomies[ $slug ]['sortable'] = false;
			}
		} else {
			$current = $this->get_current_post_type();

			if ( $args['sortable'] &&
				isset( $this->post_types[ $current ]['sortable'] ) &&
				$this->post_types[ $current ]['sortable'] == true
			) {

				if ( $taxonomy ) {

				}
			} else {
				$this->post_types[ $slug ]['sortable'] = false;
			}
		}

		if ( $args['sortable'] &&
			isset( $this->post_types[ $current ]['sortable'] ) &&
			$this->post_types[ $current ]['sortable'] == true
		) {

			if ( $taxonomy ) {

			}
		}
		else {
			$this->post_types[ $slug ]['sortable'] = false;
		}

	}

	private function enqueue_sortable_scripts() {

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script( 'pe-post-type-creator-sortable', plugins_url( '', __FILE__ ) . '/assets/js/sortable.js', [ 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ] );
		wp_enqueue_style( 'pe-post-type-creator-sortable', plugins_url( '', __FILE__ ) . '/assets/css/sortable.css', [] );

		wp_localize_script( 'pe-post-type-creator-sortable', 'PEPTCSortable', array(
			'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
			'peptcSortNonce'	=> wp_create_nonce( 'peptc-sorting-nonce' ),
		) );
	}

	/**
	 * Register the taxonomies passed to the plugin and adds extra features depending on passed options
	 */
	private function register_taxonomies() {

		$taxonomies = $this->taxonomies;

		foreach ($taxonomies as $slug => $taxonomy_args ) {
			register_taxonomy( $slug, $taxonomy_args['post_type'], $taxonomy_args );

			if ( is_admin() ) {

				$this->register_sortable( $slug, $taxonomy_args, true );

			}
		}

	}

	/**
	 * Returns meta sort key for post type
	 *
	 * @param $post_type - Slug of post type
	 * @return String - Meta key for sorting post type
	 */
	private function get_sort_meta_key( $post_type ) {

		return apply_filters( 'pe_ptc_sort_meta_key', 'sort', $post_type );
	}

	/**
	 * Sets ORDER BY in the get_terms() query which is used in both admin and in themes to get terms
	 *
	 * @param string $orderby
	 * @param type $args
	 * @param type $taxonomies
	 * @return string
	 */
	public function sort_get_terms( $orderby, $args, $taxonomies ) {

		$taxonomy = $taxonomies[0];

		if (array_key_exists( $taxonomy, $this->taxonomies ) && isset( $this->taxonomies[ $taxonomy ]['sortable'] ) ) {
			$order = get_option( 'taxonomy_order_'.$taxonomy, [] );

			if ( ! empty( $order ) ) {
				$orderby = 'FIELD(t.term_id, ' . implode( ',', $order ) . ')';
				return $orderby;
			}
		}

		return $orderby;
	}

	/**
	 * Set default sort meta for new posts. Hooked into save_post
	 * Reference: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 */
	public function save_post( $post_id, $post, $update ) {

		if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

		if ( in_array( $post->post_type , $this->post_types ) && $this->post_types[ $post->post_type ]['sortable'] ) {
			$current = get_post_meta( $post->ID, $this->get_sort_meta_key( $post->post_type ), true );

			if ( empty( $current ) || ! is_numeric( $current ) ) {
				$sort_value = apply_filters( 'pe_ptc_sort_default', 0, $post_id, $post, $update );

				$sort_meta_key = $this->get_sort_meta_key( $post->post_type );

				if ( $this->use_acf ) {
					update_field( $sort_meta_key, $sort_value, $post_id );
				}
				else {
					update_post_meta( $post_id, $sort_meta_key, $sort_value );
				}
			}
		}
	}

	/**
	 * Adds order by query to all post queries
	 *
	 * @param $wp_query
	 * @return mixed
	 */
	public function sort_admin_post_list( $wp_query ) {

		if ( ! isset( $wp_query->query_vars ) || ! isset( $wp_query->query_vars['post_type'] ) || is_array( $wp_query->query_vars['post_type'] ) ) {
			return $wp_query;
		}

		if (
			isset( $wp_query->query_vars['post_type'] ) &&
			isset( $this->post_types[ $wp_query->query_vars['post_type'] ] ) &&
			$this->post_types[ $wp_query->query_vars['post_type'] ]['sortable']
		) {
			$wp_query->set( 'orderby', 'meta_value_num' );
			$wp_query->set( 'meta_key', $this->get_sort_meta_key( $wp_query->query_vars['post_type'] ) );
			$wp_query->set( 'order', 'ASC' );
		}

		return $wp_query;
	}

	public function restrict_admin_posts_by_taxonomy() {

		global $typenow;

		$post_type = $this->get_current_post_type();

		if ( isset( $this->taxonomy_filters[ $post_type ] ) && is_array( $this->taxonomy_filters[ $post_type ] ) ) {
			foreach ( $this->taxonomy_filters[ $post_type ] as $taxonomy ) {
				$selected_taxonomy = filter_input( INPUT_GET, $taxonomy, FILTER_SANITIZE_STRING );

				$selected = isset( $selected_taxonomy ) ? $selected_taxonomy : '';

				$info_taxonomy = get_taxonomy( $taxonomy );
				wp_dropdown_categories([
					'show_option_all' => __( "Show All {$info_taxonomy->label}" ),
					'taxonomy' => $taxonomy,
					'name' => $taxonomy,
					'orderby' => 'name',
					'selected' => $selected,
					'show_count' => true,
					'hide_empty' => true,
				]);
			}
		}
	}

	public function add_terms_filter_to_query( $query ) {
		global $pagenow;

		if ($pagenow != 'edit.php' ) {
			return $query;
		}

		$post_type = $this->get_current_post_type();

		if ( isset( $this->taxonomy_filters[ $post_type ] ) && is_array( $this->taxonomy_filters[ $post_type ] ) ) {
			foreach ( $this->taxonomy_filters[ $post_type ] as $taxonomy ) {
				$query_vars = $query->query_vars;

				if (
					isset( $query_vars['post_type'] ) &&
					$query_vars['post_type'] == $post_type &&
					isset( $query_vars[ $taxonomy ] ) &&
					is_numeric( $query_vars[ $taxonomy ] )
					&& $query_vars[ $taxonomy ] != 0
				) {
					$term = get_term_by( 'id', $query_vars[ $taxonomy ], $taxonomy );
					$query->query_vars[ $taxonomy ] = $term->slug;
				}
			}
		}

		return $query;
	}


	/**
	 * Gets slug of current post type in admin views
	 * Reference: https://gist.github.com/mjangda/476964
	 *
	 * @return string - Slug of current post type or null
	 */
	private function get_current_post_type() {

		global $post, $typenow, $current_screen;

		if ( $post && $post->post_type ) {
			return $post->post_type;
		}
		elseif ( $typenow ) {
			return $typenow;
		}
		elseif ( $current_screen && isset( $current_screen->post_type ) ) {
			return $current_screen->post_type;
		}
		else {
			$post_type = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );

			if ( ! empty( $post_type ) ) {
				return sanitize_key( $post_type );
			}

			$post_type = filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_STRING );

			if ( ! empty( $post_type ) ) {
				return sanitize_key( $post_type );
			}

			return null;
		}
	}

	/**
	 *
	 * Gets slug of current taxonomy in admin views
	 *
	 * @param string $post_type - (optional) Get only for specific post type (post slug)
	 * @return string - Slug of current taxonomy or null
	 */
	private function get_current_taxonomy( $post_type = '' ) {

		$current_taxonomy = filter_input( INPUT_GET, 'taxonomy', FILTER_SANITIZE_STRING );
		$current_post_type = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );

		if (
			isset( $current_taxonomy ) &&
			( empty( $post_type ) ||
			(is_array( $post_type ) ? in_array( $current_post_type, $post_type ) : $current_post_type == $post_type ) )
		) {
			return sanitize_key( $current_taxonomy );
		}
		else {
			return null;
		}
	}

	/**
	 * Hack for adding custom post statuses to the select box. There is currently not a better is not in WP core.
	 * Related trac ticket: https://core.trac.wordpress.org/ticket/12706
	 */
	public function append_post_status_list() {

		global $post;

		if ( isset( $this->post_types[ $post->post_type ]['post_statuses'] ) && is_array( $this->post_types[ $post->post_type ]['post_statuses'] ) ) {
			echo '<script>';
			echo 'jQuery(document).ready(function($) {';

			foreach ( $this->post_types[ $post->post_type ]['post_statuses'] as $post_status_slug => $post_status ) {
				$label = $post_status['singular_label'];

				if ( $post->post_status == $post_status_slug && in_array( $post->post_status, array_keys( $this->post_types[ $post->post_type ]['post_statuses'] ) ) ) {
					$selected = ' selected="selected"';
					?>
					$(".misc-pub-section label").append(" <?php echo $label; ?>");
					<?php
				}
				else {
					$selected = '';
				}
				?>
				$("select#post_status").append('<option value="<?php echo $post_status_slug; ?>" <?php echo $selected; ?>><?php echo $label ?></option>');
				<?php
			}

			echo '});';
			echo '</script>';

		}
	}

	/**
	 * AJAX handler/callback for drag-and-drop sorting
	 */
	public function sortable_ajax_handler() {

		$nonce = filter_input( INPUT_POST, 'peptc_sort_nonce', FILTER_SANITIZE_STRING );
		$nonce = $_POST['peptc_sort_nonce'];

		//var_dump('sadasd');
		//die();

		//wp_send_json( [ 'status' => 'auth_failed' ] );

		if ( ! wp_verify_nonce( $nonce, 'peptc-sorting-nonce' ) || ! current_user_can( 'edit_posts' ) ) {
			//todo
			wp_send_json( [ 'status' => 'auth_failed' ] );
		}

		parse_str( filter_input( INPUT_POST, 'post_data', FILTER_SANITIZE_STRING ), $post_data );

		var_dump($post_data);

		if ( isset( $post_data['post'] ) && is_array( $post_data['post'] ) ) {
			$this->update_post_sort( $post_data['post'] );
		}
		if ( isset( $post_data['tag'] ) && is_array( $post_data['tag'] ) ) {

			// TODO
			$this->update_term_sort( $post_data['tag'] );


			/*
			$taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );

			if ( ! empty( $taxonomy ) && ! empty( $post_data['tag'] ) ) {
				update_option( 'taxonomy_order_'.$taxonomy , $post_data['tag'] );
			}
			*/
		}



		wp_send_json( [ 'status' => 'ok' ] );

		//wp_die();
	}

	private function update_post_sort( $post_data ) {

		$post_type = filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_STRING );
		$sort_meta_key = $this->get_sort_meta_key( $post_type );
		$i = 1;

		foreach ( $post_data as $post_id ) {
			$this->update_post_meta( $sort_meta_key, $i++, $post_id );
		}

	}

	function update_post_meta( $meta_key, $meta_value, $post_id ) {
		if ( $this->use_acf ) {
			update_field( $meta_key, $meta_value, $post_id );
		}
		else {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}
	}

	private function update_term_sort( $term_data ) {

		$taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );
		$sort_meta_key = $this->get_sort_meta_key( $taxonomy );
		$i = 1;

		global $wp_version;

		if ( version_compare( $wp_version, 4.4, '>=' ) || $this->use_acf ) {

			foreach ( $term_data as $term_id ) {
				$this->update_post_meta( $sort_meta_key, $i++, $term_id );
			}

		}
		else {
			if ( ! empty( $taxonomy ) && ! empty( $term_data ) ) {
				update_option( 'taxonomy_order_'.$taxonomy, $term_data );
			}
		}

	}

	function update_term_meta( $meta_key, $meta_value, $term_id ) {

		$taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );

		if ( $this->use_acf ) {

			$term = get_term( $term_id, $taxonomy );

			update_field( $meta_key, $meta_value, $term );
		}
		else {
			update_term_meta( $term_id, $meta_key, $meta_value );
    	}
	}



	public function add_admin_column( $columns, $post_type ) {

		if ( ! isset( $this->post_types[ $post_type ] ) ) {
			return $columns;
		}

		$options = $this->post_types[ $post_type ];

		foreach ($options['admin_columns'] as $slug => $data ) {

			if ( isset( $data['location'] ) && is_int( $data['location'] ) ) {
				$columns = array_slice( $columns, 0, $data['location'], true ) +
					[ $slug => $data['label'] ] +
					array_slice( $columns, $data['location'], count( $columns ) -$data['location'], true );
			}
			else {
				$columns[ $slug ] = $data['label'];
			}
		}

		return $columns;
	}

	public function add_admin_column_content( $column_name, $post_id ) {

		// No query is executed and no performance penalty as this is already cached internaly in WP
		$post = get_post( $post_id );

		if (isset( $this->post_types[ $post->post_type ] ) ) {
			$options = $this->post_types[ $post->post_type ];

			if ( isset( $options['admin_columns'][ $column_name ]['cb'] )  && is_callable( $options['admin_columns'][ $column_name ]['cb'] ) ) {
				call_user_func_array( $options['admin_columns'][ $column_name ]['cb'], [ $post_id ] );
			}
		}
	}




	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Translations / Locales are loaded from:
	 * 	 - WP_LANG_DIR/post-type-creator/post-type-creator-LOCALE.mo (first prority)
	 * 	 - [path to this plugin]/languages/post-type-creator-LOCALE.mo (Loaded if the file above does not exist)
	 *
	 */
	public function load_plugin_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), $this->text_domain );

		load_textdomain( $this->text_domain, WP_LANG_DIR . '/' . $this->text_domain . '/' . $this->text_domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $this->text_domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}
}
