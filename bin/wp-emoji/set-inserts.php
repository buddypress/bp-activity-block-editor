<?php
/**
 * Loop into Twemojis to create SQL insert instructions.
 */
$emojis = file_get_contents( dirname( __FILE__, 1 ) . '/wp-emojis.json' );
$emojis = json_decode( $emojis );

$content = '<?php return array(' . "\n";

foreach ( $emojis as $emoji ) {
	$content .= "\t" . 'sprintf( "INSERT INTO {$prefix}bp_emojis ( `emoji_id`, `name`, `char`, `category` ) VALUES ( \'' . $emoji->id . '\', \'%s\', \'' . $emoji->char . '\', \'%s\' );", __( \'' . str_replace( array( '-', '_' ), ' ', $emoji->name ) . '\', \'bp-activity-block-editor\' ), __( \'' . $emoji->category . '\', \'bp-activity-block-editor\' ) ),' . "\n";
}
$content .= ');';

$wp_inserts_file = dirname( __FILE__, 3 ) . '/inc/inserts.php';

if ( file_exists( $wp_inserts_file ) ) {
	unlink( $wp_inserts_file );
}
file_put_contents( $wp_inserts_file, $content );

echo "\n" . count( $emojis ) . ' SQL insert instructions written.' . "\n";
