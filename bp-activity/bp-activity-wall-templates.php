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

			<# if ( !! data.edit_link || !! data.can_delete ) { #>
				<div class="activity-major-actions">
					<button popovertarget="activity-major-actions-{{data.id}}" popovertargetaction="toggle">
						<span class="dashicons dashicons-ellipsis"></span>
					</button>
					<div popover="auto" id="activity-major-actions-{{data.id}}" role="tooltip" tabindex="-1" class="activity-major-actions-popover">
						<ul class="activity-major-action-links">
							<# if ( !! data.edit_link ) { #>
								<li><span class="dashicons dashicons-edit"></span><a href="{{{data.edit_link}}}" class="bp-activity-edit" role="button"><?php esc_html_e( 'Edit', 'bp-activity-block-editor' ); ?></a></li>
							<# } #>
							<# if ( !! data.can_delete ) { #>
								<li><span class="dashicons dashicons-trash"></span><a href="#delete" class="bp-activity-delete delete-activity submitdelete deletion" role="button"><?php esc_html_e( 'Delete', 'bp-activity-block-editor' ); ?></a></li>
							<# } #>
						</ul>
					</div>
				</div>
			<# } #>
		</header>

		<div class="activity-content">
			<# if ( 'rendered' in data.content && !! data.content.rendered ) { #>
				<div class="activity-inner">{{{data.content.rendered}}}</div>
			<# } #>
		</div>

		<footer class="activity-footer item-footer">
			<ul class="activity-action-buttons">
				<# if ( !! data.can_comment ) { #>
					<li>
						<a href="{{{data.link}}}" class="button bp-activity-comment bp-primary-action button-primary" role="button">
							<?php esc_html_e( 'Comment', 'bp-activity-block-editor' ); ?>
							<# if ( !! data.comment_count && data.comment_count > 0 ) { #>
								({{ data.comment_count }})
							<# } #>
						</a>
					</li>
				<# } #>
				<# if ( !! data.can_favorite ) { #>
					<li>
						<a href="#favorite" class="button bp-activity-favorite bp-secondary-action button-secondary" role="button">
						<# if ( ! data.favorited ) { #>
							<?php esc_html_e( 'Favorite', 'bp-activity-block-editor' ); ?>
						<# } else { #>
							<?php esc_html_e( 'Remove Favorite', 'bp-activity-block-editor' ); ?>
						<# } #>
						</a>
					</li>
				<# } #>
			</ul>
		</footer>
	</article>
</script>
