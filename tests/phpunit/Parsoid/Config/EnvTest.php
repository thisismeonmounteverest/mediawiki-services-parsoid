<?php

namespace Test\Parsoid\Config;

use Parsoid\Tests\MockEnv;
use Parsoid\Tests\MockPageConfig;
use Parsoid\Tests\MockSiteConfig;

/**
 * @covers \Parsoid\Config\Env
 */
class EnvTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideResolveTitle
	 */
	public function testResolveTitle( $args, $expect, $ns = 4, $title = 'Wikipedia:Foo/bar/baz' ) {
		$pageConfig = $this->getMockBuilder( MockPageConfig::class )
			->setConstructorArgs( [ [], null ] )
			->setMethods( [ 'getTitle', 'getNs' ] )
			->getMock();
		$pageConfig->method( 'getTitle' )->willReturn( $title );
		$pageConfig->method( 'getNs' )->willReturn( $ns );

		$siteConfig = $this->getMockBuilder( MockSiteConfig::class )
			->setConstructorArgs( [ [] ] )
			->setMethods( [ 'namespaceHasSubpages' ] )
			->getMock();
		$siteConfig->method( 'namespaceHasSubpages' )->willReturnCallback( function ( $ns ) {
			return $ns !== 0;
		} );

		$env = new MockEnv( [ 'pageConfig' => $pageConfig, 'siteConfig' => $siteConfig ] );
		$this->assertSame( $expect, $env->resolveTitle( ...$args ) );
	}

	public function provideResolveTitle() {
		return [
			[ [ ' xxx ' ], 'xxx' ],
			[ [ '#fragment' ], 'Wikipedia:Foo/bar/baz#fragment' ],
			[ [ ':xxx' ], 'xxx' ],
			[ [ '/abc' ], 'Wikipedia:Foo/bar/baz/abc' ],
			[ [ '../abc' ], 'Wikipedia:Foo/bar/abc' ],
			[ [ '../../abc' ], 'Wikipedia:Foo/abc' ],
			[ [ '../../../abc' ], '../../../abc' ],

			[ [ ':xxx' ], 'xxx', 0, 'Foo/bar/baz' ],
			[ [ 'xxx///' ], 'xxx///', 0, 'Foo/bar/baz' ],
			[ [ '/abc' ], '/abc', 0, 'Foo/bar/baz' ],
			[ [ '../abc' ], '../abc', 0, 'Foo/bar/baz' ],
			[ [ '../../abc' ], '../../abc', 0, 'Foo/bar/baz' ],
			[ [ '../../../abc' ], '../../../abc', 0, 'Foo/bar/baz' ],

			[ [ ':xxx', true ], ':xxx' ],
			[ [ 'xxx///', true ], 'xxx///' ],
			[ [ '/abc', true ], '/abc' ],
			[ [ '../abc', true ], '../abc' ],
			[ [ '../../abc', true ], '../../abc' ],
			[ [ '../../../abc', true ], '../../../abc' ],

			[ [ 'xxx///' ], 'xxx///' ], // Is this right?
			[ [ '/xxx///' ], 'Wikipedia:Foo/bar/baz/xxx' ],
			[ [ '../xxx///' ], 'Wikipedia:Foo/bar/xxx' ],

			[ [ 'xxx///' ], 'xxx///', 0, 'Foo/bar/baz' ],
			[ [ '/xxx///' ], '/xxx///', 0, 'Foo/bar/baz' ],
			[ [ '../xxx///' ], '../xxx///', 0, 'Foo/bar/baz' ],

			[ [ 'xxx///', true ], 'xxx///' ],
			[ [ '/xxx///', true ], '/xxx///' ],
			[ [ '../xxx///', true ], '../xxx///' ],
		];
	}

}
