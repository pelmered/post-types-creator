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
