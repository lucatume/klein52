<?php

class ObjectOne {

	public function __construct() {
		global $constructed;
		$constructed[] = __CLASS__;
	}
}

class AppServiceTest extends PHPUnit_Framework_TestCase {

	/**
	 * It should allow storing an object instance in the app service locator
	 *
	 * @test
	 */
	public function should_allow_storing_an_object_instance_in_the_app_service_locator() {
		$app = new _App();

		$app->objectOne = new ObjectOne();

		$this->assertInstanceOf( ObjectOne::class, $app->objectOne );
	}

	/**
	 * It should lazily build registered services
	 *
	 * @test
	 */
	public function should_lazily_build_registered_services() {
		$app = new _App();

		$app->register( 'objectOne', function () {
			return new ObjectOne();
		} );

		global $constructed;
		$this->assertEmpty( $constructed );

		$this->assertInstanceOf( ObjectOne::class, $app->objectOne );
		$this->assertContains( ObjectOne::class, $constructed );
	}

	/**
	 * It should return the same instance every time
	 *
	 * @test
	 */
	public function should_return_the_same_instance_every_time() {
		$app = new _App();

		$app->objectOne = new ObjectOne();

		$this->assertSame( $app->objectOne, $app->objectOne );
	}

	/**
	 * It should return always the same instance when registering a service
	 *
	 * @test
	 */
	public function should_return_always_the_same_instance_when_registering_a_service() {

		$app = new _App();

		$app->register( 'objectOne', function () {
			return new ObjectOne();
		} );

		$this->assertSame( $app->objectOne, $app->objectOne );
	}

	/**
	 * It should allow adding methods to the app service
	 *
	 * @test
	 */
	public function should_allow_adding_methods_to_the_app_service() {
		$app = new _App();

		$app->strToUpper = function ( $arg ) {
			return strtoupper( $arg );
		};

		$this->assertEquals( 'FOO', $app->strToUpper( 'foo' ) );
	}
}
