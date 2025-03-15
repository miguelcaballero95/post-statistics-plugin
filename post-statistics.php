<?php

/*
	Plugin Name: Post Statitics
	Description: Show your post statistics in single page.
	Version: 1.0
	Author: Miguel Caballero
	Text Domain: wcpdomain
	Domain Path: /languages
*/

class Post_Statistics {

	/**
	 * Initializes the plugin by adding hooks for admin settings and content filtering.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_init', [ $this, 'add_settings' ] );
		add_filter( 'the_content', [ $this, 'add_statistics_to_content' ] );
		add_action( 'init', [ $this, 'languages' ] );
	}

	/**
	 * Adds a settings page under the "Settings" menu in the WordPress admin panel.
	 * The page will allow the user to configure the plugin's settings.
	 * @return void
	 */
	public function add_admin_page(): void {
		add_options_page(
			'Word Count Settings',
			esc_html__( 'Word Count', 'wcpdomain' ),
			'manage_options',
			'word-count-settings-page',
			[ $this, 'page_html' ]
		);
	}

	/**
	 * Registers settings fields for configuring the plugin's display options.
	 * The settings include the display location, headline text, and options for displaying word count, character count, and read time.
	 * @return void
	 */
	public function add_settings(): void {
		add_settings_section(
			'wcp_first_section',
			null,
			"__return_false",
			'word-count-settings-page'
		);

		// Location
		add_settings_field(
			'wcp_location',
			'Display Location',
			[ $this, 'location_html' ],
			'word-count-settings-page',
			'wcp_first_section'
		);
		register_setting(
			'wordcountplugin',
			'wcp_location',
			[ 
				'sanitize_callback' => [ $this, 'sanitize_location' ],
				'default' => '0'
			]
		);

		// Headline
		add_settings_field(
			'wcp_headline',
			'Headline Text',
			[ $this, 'headline_html' ],
			'word-count-settings-page',
			'wcp_first_section'
		);
		register_setting(
			'wordcountplugin',
			'wcp_headline',
			[ 
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'Post Statistics'
			]
		);

		// Word Count
		add_settings_field(
			'wcp_wordcount',
			'Word Count',
			[ $this, 'checkbox_html' ],
			'word-count-settings-page',
			'wcp_first_section',
			[ 'name' => 'wcp_wordcount' ]
		);
		register_setting(
			'wordcountplugin',
			'wcp_wordcount',
			[ 
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '1'
			]
		);

		// Character Count
		add_settings_field(
			'wcp_charactercount',
			'Character Count',
			[ $this, 'checkbox_html' ],
			'word-count-settings-page',
			'wcp_first_section',
			[ 'name' => 'wcp_charactercount' ]
		);
		register_setting(
			'wordcountplugin',
			'wcp_charactercount',
			[ 
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '1'
			]
		);

		// Read Time
		add_settings_field(
			'wcp_readtime',
			'Read Time',
			[ $this, 'checkbox_html' ],
			'word-count-settings-page',
			'wcp_first_section',
			[ 'name' => 'wcp_readtime' ]
		);
		register_setting(
			'wordcountplugin',
			'wcp_readtime',
			[ 
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '1'
			]
		);
	}

	/**
	 * Filters the post content and appends or prepends post statistics if enabled.
	 * 
	 * @param string $content The original post content.
	 * @return string Modified content with statistics.
	 */
	public function add_statistics_to_content( string $content ): string {
		if (
			is_main_query()
			&& is_single()
			&& (
				get_option( 'wcp_wordcount', '1' )
				|| get_option( 'wcp_charactercount', '1' )
				|| get_option( 'wcp_readtime', '1' )
			)
		) {
			return $this->create_post_html( $content );
		}
		return $content;
	}

	/**
	 * Generates the HTML markup for post statistics.
	 * 
	 * @param string $content The original post content.
	 * @return string HTML output with statistics.
	 */
	public function create_post_html( string $content ): string {
		$html = '<h3>' . esc_html( get_option( 'wcp_headline', 'Post Statistics' ) ) . '</h3><p>';

		if ( get_option( 'wcp_wordcount', '1' ) || get_option( 'wcp_readtime', '1' ) ) {
			$wordCount = str_word_count( strip_tags( $content ) );
		}

		if ( get_option( 'wcp_wordcount', '1' ) ) {
			$html .= esc_html__( 'This post has', 'wcpdomain' ) . ' ' . $wordCount . ' ' . esc_html__( 'words', 'wcpdomain' ) . '<br>';
		}

		if ( get_option( 'wcp_charactercount', '1' ) ) {
			$html .= 'This post has ' . strlen( strip_tags( $content ) ) . ' characters. <br>';
		}

		if ( get_option( 'wcp_readtime', '1' ) ) {
			$html .= 'This will take about ' . round( $wordCount / 225 ) . ' minute(s) to read. <br>';
		}

		$html .= '</p>';

		return get_option( 'wcp_location', '0' ) === '0' ? "$html $content" : "$content $html";
	}

	/**
	 * Validates and sanitizes the display location input.
	 * 
	 * @param string $input The user-provided input value.
	 * @return string The sanitized location value.
	 */
	public function sanitize_location( string $input ): string {
		if ( $input !== '0' && $input !== '1' ) {
			add_settings_error( 'wcp_location', 'wcp_location_error', 'Display location must be either beginning or end' );
			return get_option( 'wcp_location' );
		}

		return $input;
	}

	/**
	 * Renders a checkbox field for plugin settings.
	 * 
	 * @param array $args Arguments containing the setting name.
	 * @return void
	 */
	public function checkbox_html( array $args ): void { ?>
		<input type="checkbox" name="<?= $args['name']; ?>" value="1" <?php checked( get_option( $args['name'] ), '1' ) ?>>
		<?php
	}

	/**
	 * Renders the text input field for the headline setting.
	 * 
	 * @return void
	 */
	public function headline_html(): void { ?>
		<input type="text" name="wcp_headline" value="<?= esc_attr( get_option( 'wcp_headline' ) ); ?>">
		<?php
	}

	/**
	 * Renders the dropdown menu for selecting the display location.
	 * 
	 * @return void
	 */
	public function location_html(): void { ?>
		<select name="wcp_location">
			<option value="0" <?php selected( get_option( 'wcp_location' ), '0' ) ?>>Beginning of post</option>
			<option value="1" <?php selected( get_option( 'wcp_location' ), '1' ) ?>>End of post</option>
		</select>
		<?php
	}

	/**
	 * Renders the settings page HTML.
	 * 
	 * @return void
	 */
	public function page_html(): void { ?>
		<div class="wrap">
			<h1>Word Count Settings</h1>
			<form action="options.php" method="POST">
				<?php
				settings_fields( 'wordcountplugin' );
				do_settings_sections( 'word-count-settings-page' );
				submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Loads the plugin's translation files.
	 * 
	 * @return void
	 */
	public function languages(): void {
		load_plugin_textdomain(
			'wcpdomain',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
}

$post_statistics = new Post_Statistics();