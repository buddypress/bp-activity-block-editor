<?php
/**
 * BuddyPress Activity Wall templates.
 *
 * @package bp-activity-block-editor\bp-activity
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<template id="bp-activity-entry-template">
	<article class="" id="" data-bp-object-id="" data-bp-timestamp="">
		<header class="activity-header item-header">
			<div class="activity-avatar item-avatar">
				<a class="activity-avatar-link" href=""></a>
			</div>
			<div class="activity-title item-title">
				<p><span class="activity-title-text"></span> <a href="" class="activity-time-since"><span class="time-since"></span></a></p>
			</div>
			<div class="activity-major-actions"></div>
		</header>
		<div class="activity-content">
			<div class="activity-inner"></div>
		</div>
		<footer class="activity-footer item-footer">
			<ul class="activity-action-buttons"></ul>
		</footer>
		<dialog id="activity-error">
			<button autofocus><?php esc_html_e( 'Close', 'bp-activity' ); ?></button>
			<p>
				<?php esc_html_e( 'Ouch, there was an unexpected error.', 'bp-activity' ); ?>
			</p>
		</dialog>
	</article>
</template>
<template id="bp-activity-entry-major-actions-template">
	<button popovertarget="activity-major-actions" popovertargetaction="toggle">
		<span class="dashicons dashicons-ellipsis"></span>
	</button>
	<div popover="auto" id="activity-major-actions" role="tooltip" tabindex="-1" class="activity-major-actions-popover">
		<ul class="activity-major-action-links"></ul>
	</div>
</template>
<template id="bp-activity-entry-actions-template">
	<li><a href="" class="button" role="button"></a></li>
</template>
