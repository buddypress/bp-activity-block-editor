/**
 * WordPress dependencies.
 */
import {
	Button,
	Icon,
	Popover,
	Panel,
	PanelBody,
	PanelRow,
	CheckboxControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import {
	useSelect,
	useDispatch,
} from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependency.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const ActivitySettingsIcon = () => <Icon icon="admin-generic" />;

const SettingsPopover = () => {
	const [ popoverAnchor, setPopoverAnchor ] = useState();
	const [ isVisible, setIsVisible ] = useState( false );
	const toggleVisibitity = () => {
        setIsVisible( ( state ) => ! state );
    };
	const { setActivityReplies } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const canReply = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).canReplytoActivity();
	}, [] );
	const [ isChecked, setIsChecked ] = useState( canReply );

	const setCanReply = () => {
		const checked = ! isChecked;
		setIsChecked( checked );
		setActivityReplies( checked );
	}

	return (
		<div className="activity-editor-header__action-buttons">
			<Button
				icon={ ActivitySettingsIcon }
				label={ __( 'Settings', 'bp-activity' ) }
				onClick={ toggleVisibitity }
				ref={ setPopoverAnchor }
			/>
			{ isVisible && (
				<Popover
					anchor={ popoverAnchor }
					onFocusOutside={ toggleVisibitity }
					offset={ 5 }
					placement="bottom-end"
				>
					<Panel className="activity-editor-header__settings_panel">
						<PanelBody title={ __( 'Replies', 'bp-activity' ) } initialOpen={ true }>
							<PanelRow>
								<CheckboxControl
									label={ __( 'Allow replies', 'bp-activity' ) }
									help={ __( 'Deactivating this setting will prevent other community members to comment your update.', 'bp-activity' ) }
									checked={ isChecked }
									onChange={ setCanReply }
								/>
							</PanelRow>
						</PanelBody>
						<PanelBody title={ __( 'Target', 'bp-activity' ) } initialOpen={ false }>
							{ __( 'All community members - Specific group members', 'bp-activity' ) }
						</PanelBody>
					</Panel>
				</Popover>
			) }
		</div>
	)
}

export default SettingsPopover;
