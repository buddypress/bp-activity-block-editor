<?php
/**
 * @group activity
 */
#[AllowDynamicProperties]
class BP_Tests_Activity_Functions extends BP_UnitTestCase {
	/**
	 * @group bp_register_activity_type
	 * @group activity_type
	 */
	public function test_bp_register_activity_type_no_args() {
		$type = bp_register_activity_type( 'greeting' );

		$this->assertInstanceOf( 'BP_Activity_Type', $type );
		$this->assertSame( 'Greeting', $type->labels->front_filter );

		bp_unregister_activity_type( 'greeting' );
	}

	/**
	 * @group bp_register_activity_type
	 * @group activity_type
	 * @expectedIncorrectUsage bp_register_activity_type
	 */
	public function test_bp_register_activity_type_empty_key() {
		$type = bp_register_activity_type( '' );

		$this->assertInstanceOf( 'WP_Error', $type );
	}

	/**
	 * @group bp_register_activity_type
	 * @group activity_type
	 */
	public function test_bp_register_activity_type_support_numeric_keys() {
		$type = bp_register_activity_type(
			'greetings_support',
			array(
				'supports' => array( 'comments', 'likes' ),
			)
		);

		$this->assertTrue( bp_activity_type_supports( 'greetings_support', 'comments' ) );
		$this->assertTrue( bp_activity_type_supports( 'greetings_support', 'likes' ) );

		bp_unregister_activity_type( 'greetings_support' );
	}

	/**
	 * @group bp_unregister_activity_type
	 * @group activity_type
	 */
	public function test_bp_unregister_activity_type() {
		bp_register_activity_type( 'greeting' );
		$type = bp_unregister_activity_type( 'greeting' );

		$this->assertTrue( $type );
	}

	/**
	 * @group bp_unregister_activity_type
	 * @group activity_type
	 */
	public function test_bp_unregister_activity_type_invalid() {
		$type = bp_unregister_activity_type( 'greeting' );

		$this->assertInstanceOf( 'WP_Error', $type );
	}

	/**
	 * @group bp_get_activity_types_for_role
	 * @group activity_type
	 */
	public function test_bp_get_activity_types_for_role_reaction() {
		$this->assertTrue( in_array( 'activity_comment', bp_get_activity_types_for_role( 'reaction' ), true ) );
	}

	/**
	 * @group bp_activity_add_reaction
	 * @group activity_type
	 */
	function test_bp_activity_add_like_reaction() {
		$u1 = self::factory()->user->create();
		$a1 = self::factory()->activity->create(
			array(
				'user_id'   => $u1,
				'component' => 'activity',
				'type'      => 'activity_update',
			)
		);

		$r1 = bp_activity_add_reaction(
			array(
				'user_id'  => $u1,
				'activity' => $a1,
			)
		);

		$this->assertTrue( ! is_wp_error( $r1 ) );

		$reaction = bp_activity_get(
			array(
				'in'               => array( $r1 ),
				'display_comments' => 'stream',
			)
		);

		$activity_like = wp_list_pluck( $reaction['activities'], 'type' );
		$this->assertEquals( 'activity_like', $activity_like[0] );
	}

	/**
	 * @group bp_activity_add_reaction
	 * @group activity_type
	 */
	function test_bp_activity_add_like_reaction_unexisting() {
		$u1 = self::factory()->user->create();
		$a1 = self::factory()->activity->create(
			array(
				'user_id'   => $u1,
				'component' => 'activity',
				'type'      => 'activity_update',
			)
		);

		bp_activity_delete( array( 'id' => $a1 ) );

		$r1 = bp_activity_add_reaction(
			array(
				'user_id'     => $u1,
				'activity_id' => $a1,
			)
		);

		$this->assertTrue( is_wp_error( $r1 ) );
	}
}
