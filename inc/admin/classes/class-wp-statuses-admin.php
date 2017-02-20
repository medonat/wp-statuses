<?php
/**
 * WP Statuses Admin Class.
 *
 * @package WP Statuses\admin\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WP_Statuses_Admin {
	public $post_type = '';
	public $post_type_object = null;
	public $post_type_capability = 'publish_posts';

	public function __construct() {
		//$this->setup_globals();
		//$this->includes();
		$this->hooks();
	}

	/**
	 * Starts the Admin class
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		if ( ! is_admin() ) {
			return;
		}

		$wp_statuses = wp_statuses();

		if ( empty( $wp_statuses->admin ) ) {
			$wp_statuses->admin = new self;
		}

		return $wp_statuses->admin;
	}

	/**
	 * Setups the action and filters to hook to
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_script' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
	}

	public function register_script() {
		wp_register_script(
			'wp-statuses',
			sprintf( '%1$sscript%2$s.js', wp_statuses_js_url(), wp_statuses_min_suffix() ),
			array( 'jquery' ),
			wp_statuses_version(),
			true
		);

		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && in_array( $current_screen->id, array( 'page', 'post' ), true ) ) {
			wp_add_inline_style( 'edit', '
				#wp-statuses-publish-box .inside {
					margin: 0;
					padding: 0;
				}

				#wp-statuses-dropdown {
					width: calc( 100% - 29px );
				}

				#misc-publishing-actions .misc-pub-section span.dashicons {
					vertical-align: middle;
					color: #82878c;
					padding-right: 3px;
				}
			' );
		}
	}

	public function add_meta_box( $post_type, $post ) {
		add_meta_box(
			'wp-statuses-publish-box',
			__( 'Publishing', 'wp-statuses' ),
			array( $this, 'publishing_box' ),
			$post_type,
			'side',
			'high'
		);

		// Validate the post type.
		$this->post_type_object = get_post_type_object( $post_type );

		if ( is_a( $this->post_type_object, 'WP_Post_Type' ) ) {
			$this->post_type            = $post_type;
			$this->post_type_capability = $this->post_type_object->cap->publish_posts;
		}
	}

	public function publishing_box( $post = null ) {
		if ( empty( $post->post_type ) ) {
			return;
		}

		// Load script for the metabox.
		wp_enqueue_script( 'wp-statuses' );

		$status = $post->post_status;
		if ( 'auto-draft' === $status ) {
			$status = 'draft';
		}
		?>
		<div class="submitbox" id="submitpost">
			<?php
			/**
			 * Split parts for a better visibility.
			 */
			$this->get_minor_publishing_div( $post, $status );
			$this->get_major_publishing_div( $post, $status ); ?>
		</div><!-- #submitpost -->
		<?php
	}

	public function get_minor_publishing_div( $post = null, $status = '' ) {
		if ( empty( $post->post_type ) || empty( $status ) ) {
			return;
		}
		?>
		<div id="minor-publishing">

			<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
			<div style="display:none;">
				<?php submit_button( __( 'Save' ), '', 'save' ); ?>
			</div>

			<div id="minor-publishing-actions">
				<div id="save-action">

					<?php if ( 'draft' === $status ) : ?>

						<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft', 'wp-statuses' ); ?>" class="button" />

					<?php elseif ( 'pending' === $status && current_user_can( $this->post_type_capability ) ) : ?>

						<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save as Pending', 'wp-statuses' ); ?>" class="button" />

					<?php endif ; ?>

					<span class="spinner"></span>

				</div>

				<?php if ( is_post_type_viewable( $this->post_type_object ) ) : ?>

					<div id="preview-action">
						<?php printf( '<a class="preview button" href="%1$s" target="wp-preview-%2$s" id="post-preview">%3$s</a>',
							esc_url( get_preview_post_link( $post ) ),
							(int) $post->ID,
							'draft' === $status ? esc_html__( 'Preview', 'wp-statuses' ) : esc_html__( 'Preview Changes', 'wp-statuses' )
						); ?>
						<input type="hidden" name="wp-preview" id="wp-preview" value="" />
					</div>

				<?php endif;

				/**
				 * Fires before the post time/date setting in the Publish meta box.
				 *
				 * @since 4.4.0
				 *
				 * @param WP_Post $post WP_Post object for the current post.
				 */
				do_action( 'post_submitbox_minor_actions', $post ); ?>

				<div class="clear"></div>
			</div><!-- #minor-publishing-actions -->
		</div><!-- #minor-publishing -->
		<?php
	}

	public function get_major_publishing_div( $post = null, $current = '' ) {
		if ( empty( $post->post_type ) || empty( $current ) ) {
			return;
		}

		$statuses = wp_statuses_get_metabox_statuses( $post->post_type );

		$options  = array();
		$dashicon = 'dashicons-post-status';

		foreach ( $statuses as $status ) {
			$selected = selected( $current, $status->name, false );

			if ( $selected ) {
				$dashicon = $status->dashicon;
			}

			$options[] = '<option value="' . esc_attr( $status->name ) .'" ' . $selected . ' data-dashicon="' . esc_attr( $status->dashicon ) . '">' . esc_html( $status->labels['metabox_dropdown'] ) . '</option>';
		}
		?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">

				<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( $current ); ?>" />
				<label for="post_status" class="screen-reader-text"><?php esc_html_e( 'Set status', 'wp-statuses' ); ?></label>
				<?php printf(
					'<span class="dashicons %1$s"></span> <select name="post_status" id="wp-statuses-dropdown">%2$s</select>',
					sanitize_html_class( $dashicon ),
					join( "\n", $options )
				); ?>

			</div><!-- .misc-pub-section -->
		</div><!-- #misc-publishing-actions -->
		<?php
	}
}
