<?php
/**
 * Server-side rendering of the `core/latest-posts` block.
 *
 * @package WordPress
 */

/**
 * The excerpt length set by the Latest Posts core block
 * set at render time and used by the block itself.
 *
 * @var int
 */
$block_core_latest_posts_excerpt_length = 0;

/**
 * Callback for the excerpt_length filter used by
 * the Latest Posts block at render time.
 *
 * @return int Returns the global $block_core_latest_posts_excerpt_length variable
 *             to allow the excerpt_length filter respect the Latest Block setting.
 */
function block_core_latest_posts_get_excerpt_length() {
	global $block_core_latest_posts_excerpt_length;
	return $block_core_latest_posts_excerpt_length;
}

/**
 * Renders the `core/latest-posts` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with latest posts added.
 */
function render_block_core_latest_posts( $attributes ) {
	global $post, $block_core_latest_posts_excerpt_length;

	$args = array(
		'posts_per_page'   => $attributes['postsToShow'],
		'post_status'      => 'publish',
		'order'            => $attributes['order'],
		'orderby'          => $attributes['orderBy'],
		'suppress_filters' => false,
	);

	$block_core_latest_posts_excerpt_length = $attributes['excerptLength'];
	add_filter( 'excerpt_length', 'block_core_latest_posts_get_excerpt_length', 20 );

	if ( isset( $attributes['categories'] ) ) {
		$args['category__in'] = array_column( $attributes['categories'], 'id' );
	}

	$recent_posts = get_posts( $args );

	$list_items_markup = '';

	foreach ( $recent_posts as $post ) {
		$template              = '<div class="%1$s">%2$s</div>';
		$template              = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'FeaturedImage'
		);
		$featured_image_markup = '';
		if ( $attributes['displayFeaturedImage'] && has_post_thumbnail( $post ) ) {
			$image_style = '';
			if ( isset( $attributes['featuredImageSizeWidth'] ) ) {
				$image_style .= sprintf( 'max-width:%spx;', $attributes['featuredImageSizeWidth'] );
			}
			if ( isset( $attributes['featuredImageSizeHeight'] ) ) {
				$image_style .= sprintf( 'max-height:%spx;', $attributes['featuredImageSizeHeight'] );
			}

			$image_classes = 'wp-block-latest-posts__featured-image';
			if ( isset( $attributes['featuredImageAlign'] ) ) {
				$image_classes .= ' align' . $attributes['featuredImageAlign'];
			}

			$featured_image_markup = sprintf(
				$template,
				$image_classes,
				get_the_post_thumbnail(
					$post,
					$attributes['featuredImageSizeSlug'],
					array(
						'style' => $image_style,
					)
				)
			);
		}

		$template = '<a href="%1$s">%2$s</a>';
		$template = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'Title'
		);
		$title    = get_the_title( $post );
		if ( ! $title ) {
			$title = __( '(no title)' );
		}
		$title_markup = sprintf(
			$template,
			esc_url( get_permalink( $post ) ),
			$title
		);

		$template = '<time datetime="%1$s" class="wp-block-latest-posts__post-date">%2$s</time>';
		$template = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'PostDate'
		);

		$post_date_markup = '';
		if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
			$post_date_markup = sprintf(
				'<time datetime="%1$s" class="wp-block-latest-posts__post-date">%2$s</time>',
				esc_attr( get_the_date( 'c', $post ) ),
				esc_html( get_the_date( '', $post ) )
			);
		}

		$template = '<div class="wp-block-latest-posts__post-excerpt">%1$s</div>';
		$template = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'PostContent_Excerpt'
		);

		$post_content_excerpt_markup = '';
		if ( isset( $attributes['displayPostContent'] ) && $attributes['displayPostContent']
			&& isset( $attributes['displayPostContentRadio'] ) && 'excerpt' === $attributes['displayPostContentRadio']
		) {

			$trimmed_excerpt = get_the_excerpt( $post );

			$post_content_excerpt_markup = sprintf(
				$template,
				$trimmed_excerpt
			);
		}

		$template = '<div class="wp-block-latest-posts__post-excerpt">%1$s</div>';
		$template = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'PostContent_FullPost'
		);

		$post_content_full_post_markup = '';
		if ( isset( $attributes['displayPostContent'] ) && $attributes['displayPostContent']
			&& isset( $attributes['displayPostContentRadio'] ) && 'full_post' === $attributes['displayPostContentRadio']
		) {
			$post_content_full_post_markup = sprintf(
				$template,
				wp_kses_post( html_entity_decode( $post->post_content, ENT_QUOTES, get_option( 'blog_charset' ) ) )
			);
		}

		$template          .= '<li>%1$s%2$s</li>';
		$template           = apply_filters(
			'gutenberg-element-template',
			$template,
			'core/latest-posts',
			'PostItem'
		);
		$list_items_markup .= sprintf(
			$template,
			$featured_image_markup,
			$title_markup,
			$post_date_markup,
			$post_content_excerpt_markup,
			$post_content_full_post_markup
		);

	}

	remove_filter( 'excerpt_length', 'block_core_latest_posts_get_excerpt_length', 20 );

	$class = 'wp-block-latest-posts wp-block-latest-posts__list';
	if ( isset( $attributes['align'] ) ) {
		$class .= ' align' . $attributes['align'];
	}

	if ( isset( $attributes['postLayout'] ) && 'grid' === $attributes['postLayout'] ) {
		$class .= ' is-grid';
	}

	if ( isset( $attributes['columns'] ) && 'grid' === $attributes['postLayout'] ) {
		$class .= ' columns-' . $attributes['columns'];
	}

	if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
		$class .= ' has-dates';
	}

	if ( isset( $attributes['className'] ) ) {
		$class .= ' ' . $attributes['className'];
	}

	return sprintf(
		'<ul class="%1$s">%2$s</ul>',
		esc_attr( $class ),
		$list_items_markup
	);
}

/**
 * Registers the `core/latest-posts` block on server.
 */
function register_block_core_latest_posts() {
	$path     = __DIR__ . '/latest-posts/block.json';
	$metadata = json_decode( file_get_contents( $path ), true );

	register_block_type(
		$metadata['name'],
		array_merge(
			$metadata,
			array(
				'render_callback' => 'render_block_core_latest_posts',
			)
		)
	);
}
add_action( 'init', 'register_block_core_latest_posts' );
