<?php
class WPTest52Class {

	static function GET( $r, $m, $a ) {
		echo 'ok';
	}
}

class WPRoutes52Test extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		klein_Request::$_headers->silent( true );
		global $__klein_routes;
		$__klein_routes = array();

		global $__klein_namespace;
		$__klein_namespace = null;

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
	}

	protected function tearDown() {
		klein_Request::$_headers->silent( false );
	}

	protected function assertOutputSame( $expected, $callback, $message = '' ) {
		ob_start();
		call_user_func( $callback );
		$out = ob_get_contents();
		ob_end_clean();
		$this->assertSame( $expected, $out, $message );
	}

	protected function loadExternalRoutes() {
		$route_directory  = __DIR__ . '/routes/';
		$route_files      = scandir( $route_directory );
		$route_namespaces = array();

		foreach ( $route_files as $file ) {
			if ( is_file( $route_directory . $file ) ) {
				$route_namespace    = '/' . basename( $file, '.php' );
				$route_namespaces[] = $route_namespace;

				inNamespace( $route_namespace, $route_directory . $file );
			}
		}

		return $route_namespaces;
	}

	public function testBasic() {
		$this->expectOutputString( 'x' );
		klein_respond( '/', 'closure1' );
		klein_respond( '/something', 'closure2' );
		klein_dispatch( '/' );
	}

	public function testCallable() {
		$this->expectOutputString( 'okok' );
		klein_respond( '/', array('WPTest52Class', 'GET' ));
		klein_respond( '/', 'WPTest52Class::GET' );
		klein_dispatch( '/' );
	}

	public function testAppReference() {
		$this->expectOutputString( 'ab' );
		klein_respond( '/', 'closure3' );
		klein_respond( '/', 'closure4' );
		klein_respond( '/', 'closure5' );
		klein_dispatch( '/' );
	}

	public function testCatchallImplicit() {
		$this->expectOutputString( 'b' );

		klein_respond( '/one', 'closure6' );
		klein_respond( 'closure7' );
		klein_respond( '/two', 'closure8' );
		klein_respond( '/three', 'closure9' );
		klein_dispatch( '/two' );
	}

	public function testCatchallAsterisk() {
		$this->expectOutputString( 'b' );
		klein_respond( '/one', 'closure10' );
		klein_respond( '*', 'closure11' );
		klein_respond( '/two', 'closure12' );
		klein_respond( '/three', 'closure13' );
		klein_dispatch( '/two' );
	}

	public function testCatchallImplicitTriggers404() {
		$this->expectOutputString( "b404\n" );;
		klein_respond( 'closure14' );
		klein_respond( 404, 'closure15' );
		klein_dispatch( '/' );
	}

	public function testRegex() {
		$this->expectOutputString( 'z' );

		klein_respond( '@/bar', 'closure16' );
		klein_dispatch( '/bar' );
	}

	public function testRegexNegate() {
		$this->expectOutputString( "y" );

		klein_respond( '!@/foo', 'closure17' );
		klein_dispatch( '/bar' );
	}

	public function test404() {
		$this->expectOutputString( "404\n" );

		klein_respond( '/', 'closure18' );
		klein_respond( 404, 'closure19' );
		klein_dispatch( '/foo' );
	}

	public function testParamsBasic() {
		$this->expectOutputString( 'blue' );

		klein_respond( '/[:color]', 'closure20' );
		klein_dispatch( '/blue' );
	}

	public function testParamsIntegerSuccess() {
		$this->expectOutputString( "987" );

		klein_respond( '/[i:age]', 'closure21' );
		klein_dispatch( '/987' );
	}

	public function testParamsIntegerFail() {
		$this->expectOutputString( '404 Code' );

		klein_respond( '/[i:age]', 'closure22' );
		klein_respond( '404', 'closure23' );
		klein_dispatch( '/blue' );
	}

	public function testParamsAlphaNum() {
		klein_respond( '/[a:audible]', 'closure24' );

		$this->assertOutputSame( 'blue42', 'closure25' );
		$this->assertOutputSame( '', 'closure26' );
		$this->assertOutputSame( '', 'closure27' );
	}

	public function testParamsHex() {
		klein_respond( '/[h:hexcolor]', 'closure28' );

		$this->assertOutputSame( '00f', 'closure29' );
		$this->assertOutputSame( 'abc123', 'closure30' );
		$this->assertOutputSame( '', 'closure31' );
		$this->assertOutputSame( '', 'closure32' );
		$this->assertOutputSame( '', 'closure33' );
	}

	public function test404TriggersOnce() {
		$this->expectOutputString( '404 Code' );

		klein_respond( '404', 'closure35' );
		klein_dispatch( '/notroute' );
	}

	public function testMethodCatchAll() {
		$this->expectOutputString( 'yup!123' );

		klein_respond( 'POST', null, 'closure36' );
		klein_respond( 'POST', '*', 'closure37' );
		klein_respond( 'POST', '/', 'closure38' );
		klein_respond( 'closure39' );
		klein_dispatch( '/', 'POST' );
	}

	public function testLazyTrailingMatch() {
		$this->expectOutputString( 'this-is-a-title-123' );

		klein_respond( '/posts/[*:title][i:id]', 'closure40' );
		klein_dispatch( '/posts/this-is-a-title-123' );
	}

	public function testFormatMatch() {
		$this->expectOutputString( 'xml' );

		klein_respond( '/output.[xml|json:format]', 'closure41' );
		klein_dispatch( '/output.xml' );
	}

	public function testDotSeparator() {
		$this->expectOutputString( 'matchA:slug=ABCD_E--matchB:slug=ABCD_E--' );

		klein_respond( '/[*:cpath]/[:slug].[:format]', 'closure42' );
		klein_respond( '/[*:cpath]/[:slug].[:format]?', 'closure43' );
		klein_respond( '/[*:cpath]/[a:slug].[:format]?', 'closure44' );
		klein_dispatch( "/category1/categoryX/ABCD_E.php" );
		$this->assertOutputSame(
			'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
			'closure45'
		);
		$this->assertOutputSame(
			'matchB:slug=ABCD_E--',
			'closure46'
		);
	}

	public function testControllerActionStyleRouteMatch() {
		$this->expectOutputString( 'donkey-kick' );

		klein_respond( '/[:controller]?/[:action]?', 'closure47' );
		klein_dispatch( '/donkey/kick' );
	}

	public function testRespondArgumentOrder() {
		$this->expectOutputString( 'abcdef' );

		klein_respond( 'closure48' );
		klein_respond( null, 'closure49' );
		klein_respond( '/endpoint', 'closure50' );
		klein_respond( 'GET', null, 'closure51' );
		klein_respond( array( 'GET', 'POST' ), null, 'closure52' );
		klein_respond( array( 'GET', 'POST' ), '/endpoint', 'closure53' );
		klein_dispatch( '/endpoint' );
	}

	public function testTrailingMatch() {
		klein_respond( '/?[*:trailing]/dog/?', 'closure54' );

		$this->assertOutputSame( 'yup', 'closure55' );
		$this->assertOutputSame( 'yup', 'closure56' );
		$this->assertOutputSame( 'yup', 'closure57' );
		$this->assertOutputSame( 'yup', 'closure58' );
		$this->assertOutputSame( 'yup', 'closure59' );
		$this->assertOutputSame( 'yup', 'closure60' );
	}

	public function testTrailingPossessiveMatch() {
		klein_respond( '/sub-dir/[**:trailing]', 'closure61' );

		$this->assertOutputSame( 'yup', 'closure62' );
		$this->assertOutputSame( 'yup', 'closure63' );
		$this->assertOutputSame( 'yup', 'closure64' );
		$this->assertOutputSame( 'yup', 'closure65' );
	}

	public function testNSDispatch() {
		inNamespace( '/u', 'closure80' );
		klein_respond( 404, 'closure68' );

		$this->assertOutputSame( "slash", 'closure69' );
		$this->assertOutputSame( "slash", 'closure70' );
		$this->assertOutputSame( "id", 'closure71' );
		$this->assertOutputSame( "404", 'closure72' );
	}
}
