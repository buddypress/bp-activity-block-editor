/**
 * WordPress dependencies.
 */
const {
	components: {
		Button,
		Dashicon,
		ExternalLink,
		Panel,
		PanelBody,
		PanelRow,
		SelectControl,
	},
	data: {
		useSelect,
		useDispatch,
	},
	element: {
		createElement,
		Fragment,
		useState,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * BuddyPress dependencies.
 */
const {
	blockComponents: {
		AutoCompleter,
	},
} = bp;

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const GROUP_STATI = {
	public: __( 'Public', 'bp-activity-block-editor' ),
	private: __( 'Private', 'bp-activity-block-editor' ),
	hidden: __( 'Hidden', 'bp-activity-block-editor' ),
};

const getSlugValue = ( item ) => {
	if ( item && item.status && GROUP_STATI[ item.status ] ) {
		return GROUP_STATI[ item.status ];
	}

	return null;
};

const ActivitySidebar = () => {
	const { isGroupsActive, userGroups, group, user } = useSelect( ( select ) => {
		const store = select( BP_ACTIVITY_STORE_KEY );
		const activeComponents = store.getActiveComponents();
		const isGroupsActive = -1 !== activeComponents.indexOf( 'groups' );

		return {
			isGroupsActive: isGroupsActive,
			userGroups: isGroupsActive ? store.getUserGroups() : [],
			group: store.getActivityGroup(),
			user: store.getCurrentUser(),
		};
	}, [] );
	const [ component, onSelect ] = useState( !! group ? 'groups' : 'activity' );

	const {
		setActivityGroup,
		resetActivityGroup,
	} = useDispatch( BP_ACTIVITY_STORE_KEY );

	const cancelSelectedGroup = () => {
		if ( 'groups' !== component ) {
			onSelect( 'groups' );
		}

		resetActivityGroup();
	}

	let postInOptions = [
		{ label: __( 'my Profile', 'bp-activity-block-editor' ), value: 'activity' },
	];

	if ( isGroupsActive && 0 !== userGroups.length ) {
		postInOptions.push( { label: __( 'a Group', 'bp-activity-block-editor' ), value: 'groups' } );
	}

	return (
		<Panel>
			<PanelBody
				className="activity-editor-sidebar__sharing-preferences-panel"
				title={ __( 'Sharing preferences', 'bp-activity-block-editor' ) }
				opened={ true }
			>
				{ ! group && (
					<PanelRow className="activity-editor-sidebar__sharing-preferences-row">
						<span>{ __( 'Post in', 'bp-activity-block-editor' ) }</span>
						<SelectControl
							value={ component }
							options={ postInOptions }
							onChange={ ( component ) => onSelect( component ) }
						/>
					</PanelRow>
				) }

				{ 'activity' === component && (
					<PanelRow className="activity-editor-sidebar__sharing-preferences-help-row">
						<p className="description">
							{ __( 'Posting an activity in your Profile will make it available from your Activity Profile page and more widely into the Activity directory.', 'bp-activity-block-editor' ) }
						</p>
					</PanelRow>
				) }

				{ 'groups' === component && ! group && (
					<Fragment>
						<PanelRow className="activity-editor-sidebar__group-selection-help-row">
							<p className="description">
								{ __( 'Start typing the name of the group you want to post your activity into.', 'bp-activity-block-editor' ) }
							</p>
						</PanelRow>
						<PanelRow className="activity-editor-sidebar__group-selection-input-row">
							<AutoCompleter
								component="groups"
								objectQueryArgs={ { 'show_hidden': true, 'user_id': user.id } }
								slugValue={ getSlugValue }
								ariaLabel={ __( 'Group\'s name', 'bp-activity-block-editor' ) }
								placeholder={ __( 'Enter Group\'s name here…', 'bp-activity-block-editor' ) }
								onSelectItem={ ( { itemID } ) => setActivityGroup( itemID ) }
								useAvatar={ true }
							/>
							<Button
								className="activity-editor-sidebar__group-selection-cancel"
								onClick={ () => onSelect( 'activity' ) }
								isLink
							>
								<Dashicon icon="dismiss" />
								<span className="screen-reader-text">{ __( 'Dismiss group search', 'bp-activity-block-editor' ) }</span>
							</Button>
						</PanelRow>
					</Fragment>
				) }

				{ !! group && !! group.id && (
					<PanelRow className="activity-editor-sidebar__selected-group">
						<span>{ __( 'Post in', 'bp-activity-block-editor' ) }</span>
						<ExternalLink href={ group.link }>{ group.name }</ExternalLink>
						<Button
							className="activity-editor-sidebar__selected-group-cancel"
							onClick={ () => cancelSelectedGroup() }
							isLink
						>
							<Dashicon icon="dismiss" />
							<span className="screen-reader-text">{ __( 'Remove Group', 'bp-activity-block-editor' ) }</span>
						</Button>
					</PanelRow>
				) }

				{ 'groups' === component && (
					<PanelRow className="activity-editor-sidebar__sharing-preferences-help-row">
						<p className="description">
							{ __( 'Posting an activity in a Group you are a member of will make it available from the Group’s activity page. It will also be reachable from your Activity Profile page and more widely into the Activity directory.', 'bp-activity-block-editor' ) }
							<br/>
							{ __( 'NB: Into the Activity directory, use the "My Groups" tab to see activities shared into Private or hidden groups.', 'bp-activity-block-editor' ) }
						</p>
					</PanelRow>
				) }
			</PanelBody>
		</Panel>
	);
};

export default ActivitySidebar;
