<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Template extends BP_UnitTestCase {
	/**
	 * @group likes
	 * @group bp_activity_is_liked
	 */
	public function test_bp_activity_is_liked() {
		$a1 = self::factory()->activity->create();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$r1 = bp_activity_add_reaction(
			array(
				'user_id'  => $u1,
				'activity' => $a1,
			)
		);

		$r2 = bp_activity_add_reaction(
			array(
				'user_id'  => $u2,
				'activity' => $a1,
			)
		);

		$this->assertTrue( bp_activity_is_liked( $u2, $a1 ) );
	}

	/**
	 * @group likes
	 * @group bp_activity_get_like_count
	 */
	public function test_bp_activity_get_like_count() {
		$a1 = self::factory()->activity->create();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$r1 = bp_activity_add_reaction(
			array(
				'user_id'  => $u1,
				'activity' => $a1,
			)
		);

		$r2 = bp_activity_add_reaction(
			array(
				'user_id'  => $u2,
				'activity' => $a1,
			)
		);

		$this->assertTrue( 2 === bp_activity_get_like_count( $a1 ) );
	}
}
