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
<script type="text/html" id="tmpl-bp-activity-entry">
	<article class="{{data.activity_class}}" id="activity-{{data.id}}" data-bp-{{data.id_attribute}}-id="{{data.id}}" data-bp-timestamp="{{data.timestamp}}">
		<header class="activity-header item-header">
			<div class="activity-avatar item-avatar">
				<a href="{{{data.author_link}}}">
					<# if ( data.user_avatar ) { #>
						<img loading="lazy" src="{{{data.user_avatar.thumb}}}" class="avatar user-{{data.user_id}}-avatar avatar-50 photo" width="50" alt="{{data.altAvatar}}">
					<# } else { #>
						<div class="avatar user-{{data.user_id}}-avatar avatar-50 photo">&nbsp;</div>
					<# } #>
				</a>
			</div>
			<div class="activity-title item-title">
				<p>{{{data.title}}} <a href="{{{data.link}}}" class="activity-time-since"><span class="time-since">{{data.timediff}}</span></a></p>
			</div>
		</header>

		<div class="activity-content">
			<# if ( 'rendered' in data.content && !! data.content.rendered ) { #>
				<div class="activity-inner">{{{data.content.rendered}}}</div>
			<# } #>
		</div>

		<footer class="activity-footer item-footer">
		</footer>
	</article>
</script>
