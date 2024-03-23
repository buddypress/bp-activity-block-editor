<?php
// Set time limit.
set_time_limit( 1000 );

/**
 * Loop into Twemojis to build WP Emojis data.
 */
$emoji_all = file_get_contents( 'https://raw.githubusercontent.com/iamcal/emoji-data/v15.1.2/emoji.json' );
$emoji_all = json_decode( $emoji_all );
$wp_list_emoji = array();

function emoji_sort( $a, $b ) {
	return (int) $b->sort_order < (int) $a->sort_order ? 1 : 0;
}

uasort( $emoji_all, 'emoji_sort' );

foreach ( $emoji_all as $emoji ) {
	if ( ! isset( $emoji->has_img_twitter ) || ! $emoji->has_img_twitter ) {
		continue;
	}

	if ( isset( $emoji->image ) && $emoji->image ) {
		$image_file    = str_replace( '.png', '.svg', $emoji->image );
		$wordpress_src = 'https://s.w.org/images/core/emoji/15.0.3/svg/' . $image_file;
		$wordpress_svg = file_get_contents( $wordpress_src );

		if ( false !== $wordpress_svg && 0 === strpos( $wordpress_svg, '<svg' ) ) {
			$wp_list_emoji[] = (object) array(
				'id'       => $emoji->unified,
				'name'     => strtolower( $emoji->short_name ),
				'char'     => '&#x' . str_replace( '-', ';&#x', $emoji->unified ) . ';',
				'src'      => $wordpress_src,
				'category' => strtolower( str_replace( array( ' & ', ' ' ), '-', $emoji->category ) ),
			);
		}
	}
}

$wp_data_file = 'wp-emojis.json';
if ( file_exists( $wp_data_file ) ) {
	unlink( $wp_data_file );
}
file_put_contents( $wp_data_file, json_encode( $wp_list_emoji ) );

echo "\n" . count( $wp_list_emoji ) . ' emojis imported.' . "\n";
