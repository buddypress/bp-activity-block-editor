/**
 * WordPress dependencies.
 */
import {
	Button,
	Icon,
	Popover,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const ActivitySettingsIcon = () => <Icon icon="admin-generic" />;

const SettingsPopover = () => {
	const [ isVisible, setIsVisible ] = useState( false );
	const toggleVisibitity = () => {
        setIsVisible( ( state ) => ! state );
    };

	return (
		<div className="activity-editor-header__action-buttons">
			<Button
				icon={ ActivitySettingsIcon }
				label={ __( 'Settings', 'bp-activity' ) }
				onClick={ toggleVisibitity }
			>
				{ isVisible && (
					<Popover>{ __( 'Activity settings TBD', 'bp-activity' ) }</Popover>
				) }
			</Button>
		</div>
	)
}

export default SettingsPopover;
