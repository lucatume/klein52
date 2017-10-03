<?php
class TestClass2 {
	static function GET($r, $m, $a) {
		echo 'ok';
	}
}

class WPRoutesTest extends PHPUnit_Framework_TestCase {

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

	protected function assertOutputSame($expected, $callback, $message = '') {
	    ob_start();
	    call_user_func($callback);
	    $out = ob_get_contents();
	    ob_end_clean();
	    $this->assertSame($expected, $out, $message);
	}

	protected function loadExternalRoutes() {
		$route_directory = __DIR__ . '/routes/';
		$route_files = scandir( $route_directory );
		$route_namespaces = array();

		foreach( $route_files as $file ) {
			if ( is_file( $route_directory . $file ) ) {
				$route_namespace = '/' . basename( $file, '.php' );
				$route_namespaces[] = $route_namespace;

				klein_with( $route_namespace, $route_directory . $file );
			}
		}

		return $route_namespaces;
	}

	public function testBasic() {
		$this->expectOutputString( 'x' );

		respond( '/', function(){ echo 'x'; });
		respond( '/something', function(){ echo 'y'; });
		klein_dispatch( '/' );
	}

	public function testCallable() {
		$this->expectOutputString( 'okok' );
		respond( '/', array('TestClass', 'GET'));
		respond( '/', 'TestClass::GET');
		klein_dispatch( '/' );
	}

	public function testAppReference() {
		$this->expectOutputString( 'ab' );
		respond( '/', function($r, $m ,$a){ $a->state = 'a'; });
		respond( '/', function($r, $m ,$a){ $a->state .= 'b'; });
		respond( '/', function($r, $m ,$a){ print $a->state; });
		klein_dispatch( '/' );
	}

	public function testCatchallImplicit() {
		$this->expectOutputString( 'b' );

		respond( '/one', function(){ echo 'a'; });
		respond( function(){ echo 'b'; });
		respond( '/two', function(){ } );
		respond( '/three', function(){ echo 'c'; } );
		klein_dispatch( '/two' );
	}

	public function testCatchallAsterisk() {
		$this->expectOutputString( 'b' );

		respond( '/one', function(){ echo 'a'; } );
		respond( '*', function(){ echo 'b'; } );
		respond( '/two', function(){ } );
		respond( '/three', function(){ echo 'c'; } );
		klein_dispatch( '/two' );
	}

	public function testCatchallImplicitTriggers404() {
		$this->expectOutputString("b404\n");

		respond( function(){ echo 'b'; });
		respond( 404, function(){ echo "404\n"; } );
		klein_dispatch( '/' );
	}

	public function testRegex() {
		$this->expectOutputString( 'z' );

		respond( '@/bar', function(){ echo 'z'; });
		klein_dispatch( '/bar' );
	}

	public function testRegexNegate() {
		$this->expectOutputString( "y" );

		respond( '!@/foo', function(){ echo 'y'; });
		klein_dispatch( '/bar' );
	}

	public function test404() {
		$this->expectOutputString("404\n");

		respond( '/', function(){ echo 'a'; } );
		respond( 404, function(){ echo "404\n"; } );
		klein_dispatch( '/foo' );
	}

	public function testParamsBasic() {
		$this->expectOutputString( 'blue' );

		respond( '/[:color]', function($request){ echo $request->param('color'); });
		klein_dispatch( '/blue' );
	}

	public function testParamsIntegerSuccess() {
		$this->expectOutputString( "987" );

		respond( '/[i:age]', function($request){ echo $request->param('age'); });
		klein_dispatch( '/987' );
	}

	public function testParamsIntegerFail() {
		$this->expectOutputString( '404 Code' );

		respond( '/[i:age]', function($request){ echo $request->param('age'); });
		respond( '404', function(){ echo '404 Code'; } );
		klein_dispatch( '/blue' );
	}

	public function testParamsAlphaNum() {
		respond( '/[a:audible]', function($request){ echo $request->param('audible'); });

		$this->assertOutputSame( 'blue42',  function(){ klein_dispatch('/blue42'); });
		$this->assertOutputSame( '',        function(){ klein_dispatch('/texas-29'); });
		$this->assertOutputSame( '',        function(){ klein_dispatch('/texas29!'); });
	}

	public function testParamsHex() {
		respond( '/[h:hexcolor]', function($request){ echo $request->param('hexcolor'); });

		$this->assertOutputSame( '00f',     function(){ klein_dispatch('/00f'); });
		$this->assertOutputSame( 'abc123',  function(){ klein_dispatch('/abc123'); });
		$this->assertOutputSame( '',        function(){ klein_dispatch('/876zih'); });
		$this->assertOutputSame( '',        function(){ klein_dispatch('/00g'); });
		$this->assertOutputSame( '',        function(){ klein_dispatch('/hi23'); });
	}

	public function test404TriggersOnce() {
		$this->expectOutputString( 'd404 Code' );

		respond( function(){ echo "d"; } );
		respond( '404', function(){ echo '404 Code'; } );
		klein_dispatch( '/notroute' );
	}

	public function testMethodCatchAll() {
		$this->expectOutputString( 'yup!123' );

		respond( 'POST', null, function($request){ echo 'yup!'; });
		respond( 'POST', '*', function($request){ echo '1'; });
		respond( 'POST', '/', function($request){ echo '2'; });
		respond( function($request){ echo '3'; });
		klein_dispatch( '/', 'POST' );
	}

	public function testLazyTrailingMatch() {
		$this->expectOutputString( 'this-is-a-title-123' );

		respond( '/posts/[*:title][i:id]', function($request){
			echo $request->param('title')
				. $request->param('id');
		});
		klein_dispatch( '/posts/this-is-a-title-123' );
	}

	public function testFormatMatch() {
		$this->expectOutputString( 'xml' );

		respond( '/output.[xml|json:format]', function($request){
			echo $request->param('format');
		});
		klein_dispatch( '/output.xml' );
	}

	public function testDotSeparator() {
		$this->expectOutputString( 'matchA:slug=ABCD_E--matchB:slug=ABCD_E--' );

		respond('/[*:cpath]/[:slug].[:format]',   function($rq){ echo 'matchA:slug='.$rq->param("slug").'--';});
		respond('/[*:cpath]/[:slug].[:format]?',  function($rq){ echo 'matchB:slug='.$rq->param("slug").'--';});
		respond('/[*:cpath]/[a:slug].[:format]?', function($rq){ echo 'matchC:slug='.$rq->param("slug").'--';});
		klein_dispatch("/category1/categoryX/ABCD_E.php");

		$this->assertOutputSame(
			'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
			function(){klein_dispatch( '/category1/categoryX/ABCD_E.php' );}
		);
		$this->assertOutputSame(
			'matchB:slug=ABCD_E--',
			function(){klein_dispatch( '/category1/categoryX/ABCD_E' );}
		);
	}

	public function testControllerActionStyleRouteMatch() {
		$this->expectOutputString( 'donkey-kick' );

		respond( '/[:controller]?/[:action]?', function($request){
			echo $request->param('controller')
				. '-' . $request->param('action');
		});
		klein_dispatch( '/donkey/kick' );
	}

	public function testRespondArgumentOrder() {
		$this->expectOutputString( 'abcdef' );

		respond( function(){ echo 'a'; });
		respond( null, function(){ echo 'b'; });
		respond( '/endpoint', function(){ echo 'c'; });
		respond( 'GET', null, function(){ echo 'd'; });
		respond( array( 'GET', 'POST' ), null, function(){ echo 'e'; });
		respond( array( 'GET', 'POST' ), '/endpoint', function(){ echo 'f'; });
		klein_dispatch( '/endpoint' );
	}

	public function testTrailingMatch() {
		respond( '/?[*:trailing]/dog/?', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/cat/dog'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/cat/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/cat/ball/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('cat/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('cat/ball/cheese/dog'); });
	}

	public function testTrailingPossessiveMatch() {
		respond( '/sub-dir/[**:trailing]', function($request){ echo 'yup'; });

		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/sub-dir/dog'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/sub-dir/cheese/dog'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/sub-dir/ball/cheese/dog/'); });
		$this->assertOutputSame( 'yup', function(){ klein_dispatch('/sub-dir/ball/cheese/dog'); });
	}

	public function testNSDispatch() {
		klein_with('/u', function () {
			respond('GET', '/?',     function ($request, $response) { echo "slash";   });
			respond('GET', '/[:id]', function ($request, $response) { echo "id"; });
		});
		klein_respond(404, function ($request, $response) { echo "404"; });

		$this->assertOutputSame("slash",          function(){klein_dispatch("/u");});
		$this->assertOutputSame("slash",          function(){klein_dispatch("/u/");});
		$this->assertOutputSame("id",             function(){klein_dispatch("/u/35");});
		$this->assertOutputSame("404",             function(){klein_dispatch("/35");});
	}

	public function testNSDispatchExternal() {
		$ext_namespaces = $this->loadExternalRoutes();

		klein_respond(404, function ($request, $response) { echo "404"; });

		foreach ( $ext_namespaces as $namespace ) {
			$this->assertOutputSame('yup',  function() use ( $namespace ) { klein_dispatch( $namespace . '/' ); });
			$this->assertOutputSame('yup',  function() use ( $namespace ) { klein_dispatch( $namespace . '/testing/' ); });
		}
	}

	public function testNSDispatchExternalRerequired() {
		$ext_namespaces = $this->loadExternalRoutes();

		klein_respond(404, function ($request, $response) { echo "404"; });

		foreach ( $ext_namespaces as $namespace ) {
			$this->assertOutputSame('yup',  function() use ( $namespace ) { klein_dispatch( $namespace . '/' ); });
			$this->assertOutputSame('yup',  function() use ( $namespace ) { klein_dispatch( $namespace . '/testing/' ); });
		}
	}

	public function test405Routes() {
		$resultArray = array();

		$this->expectOutputString( '_' );

		klein_respond( function(){ echo '_'; });
		klein_respond( 'GET', null, function(){ echo 'fail'; });
		klein_respond( array( 'GET', 'POST' ), null, function(){ echo 'fail'; });
		klein_respond( 405, function($a,$b,$c,$d,$methods) use ( &$resultArray ) {
			$resultArray = $methods;
		});
		klein_dispatch( '/sure', 'DELETE' );

		$this->assertCount( 2, $resultArray );
		$this->assertContains( 'GET', $resultArray );
		$this->assertContains( 'POST', $resultArray );
	}

}
