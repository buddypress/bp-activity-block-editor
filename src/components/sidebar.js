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
	public: __( 'Public', 'bp-gutenberg' ),
	private: __( 'Private', 'bp-gutenberg' ),
	hidden: __( 'Hidden', 'bp-gutenberg' ),
};

const getSlugValue = ( item ) => {
	if ( item && item.status && GROUP_STATI[ item.status ] ) {
		return GROUP_STATI[ item.status ];
	}

	return null;
};

const ActivitySidebar = () => {
	const [ component, onSelect ] = useState( 'activity' );
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

	const {
		setActivityGroup,
		resetActivityGroup,
	} = useDispatch( BP_ACTIVITY_STORE_KEY );

	let postInOptions = [
		{ label: __( 'my Profile', 'bp-gutenberg' ), value: 'activity' },
	];

	if ( isGroupsActive && 0 !== userGroups.length ) {
		postInOptions.push( { label: __( 'a Group', 'bp-gutenberg' ), value: 'groups' } );
	}

	return (
		<Panel>
			<PanelBody
				className="activity-editor-sidebar__sharing-preferences-panel"
				title={ __( 'Sharing preferences', 'bp-gutenberg' ) }
				opened={ true }
			>
				{ ! group && (
					<PanelRow className="activity-editor-sidebar__sharing-preferences-row">
						<span>{ __( 'Post in', 'bp-gutenberg' ) }</span>
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
							{ __( 'Posting an activity in your Profile will make it available from your Activity Profile page and more widely into the Activity directory.', 'bp-gutenberg' ) }
						</p>
					</PanelRow>
				) }

				{ 'groups' === component && ! group && (
					<Fragment>
						<PanelRow className="activity-editor-sidebar__group-selection-help-row">
							<p className="description">
								{ __( 'Start typing the name of the group you want to post your activity into.', 'bp-gutenberg' ) }
							</p>
						</PanelRow>
						<PanelRow className="activity-editor-sidebar__group-selection-input-row">
							<AutoCompleter
								component="groups"
								objectQueryArgs={ { 'show_hidden': true, 'user_id': user.id } }
								slugValue={ getSlugValue }
								ariaLabel={ __( 'Group\'s name', 'bp-gutenberg' ) }
								placeholder={ __( 'Enter Group\'s name here…', 'bp-gutenberg' ) }
								onSelectItem={ ( { itemID } ) => setActivityGroup( itemID ) }
								useAvatar={ true }
							/>
							<Button
								className="activity-editor-sidebar__group-selection-cancel"
								onClick={ () => onSelect( 'activity' ) }
								isLink
							>
								<Dashicon icon="dismiss" />
								<span className="screen-reader-text">{ __( 'Dismiss group search', 'bp-gutenberg' ) }</span>
							</Button>
						</PanelRow>
					</Fragment>
				) }

				{ !! group && !! group.id && (
					<PanelRow className="activity-editor-sidebar__selected-group">
						<span>{ __( 'Post in', 'bp-gutenberg' ) }</span>
						<ExternalLink href={ group.link }>{ group.name }</ExternalLink>
						<Button
							className="activity-editor-sidebar__selected-group-cancel"
							onClick={ () => resetActivityGroup() }
							isLink
						>
							<Dashicon icon="dismiss" />
							<span className="screen-reader-text">{ __( 'Remove Group', 'bp-gutenberg' ) }</span>
						</Button>
					</PanelRow>
				) }

				{ 'groups' === component && (
					<PanelRow className="activity-editor-sidebar__sharing-preferences-help-row">
						<p className="description">
							{ __( 'Posting an activity in a Group you are a member of will make it available from the Group’s activity page. It will also be reachable from your Activity Profile page and more widely into the Activity directory.', 'bp-gutenberg' ) }
							<br/>
							{ __( 'NB: Into the Activity directory, use the "My Groups" tab to see activities shared into Private or hidden groups.', 'bp-gutenberg' ) }
						</p>
					</PanelRow>
				) }
			</PanelBody>
		</Panel>
	);
};

export default ActivitySidebar;
