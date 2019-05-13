<?php
declare( strict_types = 1 );

namespace Parsoid\Utils;

use Parsoid\Config\SiteConfig;

class Title {

	/** @var int */
	private $namespaceId;

	/** @var string */
	private $namespaceName;

	/** @var string */
	private $dbkey;

	/** @var string|null */
	private $fragment;

	/** @var TitleNamespace */
	private $namespace;

	/**
	 * @param string $key Page DBkey (with underscores, not spaces)
	 * @param int|TitleNamespace $ns
	 * @param SiteConfig $siteConfig
	 * @param string|null $fragment
	 */
	public function __construct( string $key, $ns, SiteConfig $siteConfig, ?string $fragment = null ) {
		$this->dbkey = $key;
		if ( $ns instanceof TitleNamespace ) {
			$this->namespaceId = $ns->getId();
			$this->namespace = $ns;
		} else {
			$this->namespaceId = (int)$ns;
			$this->namespace = new TitleNamespace( $this->namespaceId, $siteConfig );
		}
		$this->namespaceName = $siteConfig->namespaceName( $this->namespaceId );
		$this->fragment = $fragment;
	}

	/**
	 * Sanitize an IP.
	 * @todo Librarize MediaWiki core's IP class and use that instead of this
	 *  code that derived from it via PHP→JS→PHP translation.
	 * @param string $ip
	 * @return string
	 */
	private static function sanitizeIP( string $ip ): string {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		static $ipStringRegex = '/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?(?:\/(12[0-8]|1[01][0-9]|[1-9]?\d))?$/';
		// phpcs:ignore Generic.Files.LineLength.TooLong
		static $ipv4StringRegex = '/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])(?:\/(3[0-2]|[12]?\d))?$/';

		$ip = trim( $ip );

		// If not an IP, just return trimmed value, since sanitizeIP() is called
		// in a number of contexts where usernames are supplied as input.
		if ( !preg_match( $ipStringRegex, $ip ) ) {
			return $ip;
		}

		if ( preg_match( $ipv4StringRegex, $ip ) ) {
			// Remove leading 0's from octet representation of IPv4 address
			$ip = preg_replace( '!(?:^|(?<=\.))0+(?=[1-9]|0[./]|0$)!', '', $ip );
			return $ip;
		}

		$ip = strtoupper( $ip );
		// Expand zero abbreviations
		$abbrevPos = strpos( $ip, '::' );
		if ( $abbrevPos !== false ) {
			// We know this is valid IPv6. Find the last index of the
			// address before any CIDR number (e.g. "a:b:c::/24").
			$CIDRStart = strpos( $ip, '/' );
			$addressEnd = $CIDRStart !== false ? $CIDRStart - 1 : strlen( $ip ) - 1;
			if ( $abbrevPos === 0 ) {
				// If the '::' is at the beginning...
				$repeat = '0:';
				$extra = $ip === '::' ? '0' : ''; // for the address '::'
				$pad = 9; // 7+2 (due to '::')
			// If the '::' is at the end...
			} elseif ( $abbrevPos === $addressEnd - 1 ) {
				$repeat = ':0';
				$extra = '';
				$pad = 9; // 7+2 (due to '::')
				// If the '::' is in the middle...
			} else {
				$repeat = ':0';
				$extra = ':';
				$pad = 8; // 6+2 (due to '::')
			}
			$ip = strtr( $ip, [ '::' => str_repeat( $repeat, $pad - substr_count( $ip, ':' ) ) . $extra ] );
		}
		// Remove leading zeros from each bloc as needed
		return preg_replace( '/(^|:)0+([0-9A-Fa-f]{1,4})/', '$1$2', $ip );
	}

	/**
	 * @param string $title
	 * @param SiteConfig $siteConfig
	 * @param int|TitleNamespace $defaultNs
	 * @return Title
	 */
	public static function newFromText(
		string $title, SiteConfig $siteConfig, $defaultNs = 0
	): Title {
		$origTitle = $title;

		if ( !mb_check_encoding( $title, 'UTF-8' ) ) {
			throw new TitleException( "Bad UTF-8 in title \"$title\"", 'title-invalid-utf8', $title );
		}

		// Strip Unicode bidi override characters.
		$title = preg_replace( '/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $title );
		// Clean up whitespace
		$title = preg_replace(
			'/[ _\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u',
			'_', $title
		);
		// Trim _ from beginning and end
		$title = trim( $title, '_' );

		if ( strpos( $title, \UtfNormal\Constants::UTF8_REPLACEMENT ) !== false ) {
			throw new TitleException( "Bad UTF-8 in title \"$title\"", 'title-invalid-utf8', $title );
		}

		// Initial colon indicates main namespace rather than specified default
		// but should not create invalid {ns,title} pairs such as {0,Project:Foo}
		if ( $title !== '' && $title[0] === ':' ) {
			$title = ltrim( substr( $title, 1 ), '_' );
			$defaultNs = 0;
		}

		if ( $title === '' ) {
			throw new TitleException( 'Empty title', 'title-invalid-empty', $title );
		}

		if ( $defaultNs instanceof TitleNamespace ) {
			$defaultNs = $defaultNs->getId();
		}

		// phpcs:ignore MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		if ( preg_match( '/^(.+?)_*:_*(.*)$/', $title, $m ) &&
			( $nsId = $siteConfig->namespaceId( $m[1] ) ) !== null
		) {
			$ns = $nsId;
			$title = $m[2];
		} else {
			$ns = $defaultNs;
		}

		// Disallow Talk:File:x type titles.
		if ( $ns === $siteConfig->canonicalNamespaceId( 'talk' ) &&
			preg_match( '/^(.+?)_*:_*(.*)$/', $title, $m ) &&
			$siteConfig->namespaceId( $m[1] ) !== null
		) {
			throw new TitleException(
				"Invalid Talk namespace title \"$origTitle\"", 'title-invalid-talk-namespace', $title
			);
		}

		$fragment = null;
		$fragmentIndex = strpos( $title, '#' );
		if ( $fragmentIndex !== false ) {
			$fragment = substr( $title, $fragmentIndex + 1 );
			$title = rtrim( substr( $title, 0, $fragmentIndex ), '_' );
		}

		$illegalCharsRe = '/[^' . $siteConfig->legalTitleChars() . ']'
			// URL percent encoding sequences interfere with the ability
			// to round-trip titles -- you can't link to them consistently.
			. '|%[0-9A-Fa-f]{2}'
			// XML/HTML character references produce similar issues.
			. '|&[A-Za-z0-9\x80-\xff]+;'
			. '|&#[0-9]+;'
			. '|&#x[0-9A-Fa-f]+;/';
		if ( preg_match( $illegalCharsRe, $title ) ) {
			throw new TitleException(
				"Invalid characters in title \"$origTitle\"", 'title-invalid-characters', $title
			);
		}

		// Pages with "/./" or "/../" appearing in the URLs will often be
		// unreachable due to the way web browsers deal with 'relative' URLs.
		// Also, they conflict with subpage syntax. Forbid them explicitly.
		if ( strpos( $title, '.' ) !== false && (
			$title === '.' || $title === '..' ||
			strpos( $title, './' ) === 0 ||
			strpos( $title, '../' ) === 0 ||
			strpos( $title, '/./' ) !== false ||
			strpos( $title, '/../' ) !== false ||
			substr( $title, -2 ) === '/.' ||
			substr( $title, -3 ) === '/..'
		) ) {
			throw new TitleException(
				"Title \"$origTitle\" contains relative path components", 'title-invalid-relative', $title
			);
		}

		// Magic tilde sequences? Nu-uh!
		if ( strpos( $title, '~~~' ) !== false ) {
			throw new TitleException(
				"Title \"$origTitle\" contains ~~~", 'title-invalid-magic-tilde', $title
			);
		}

		$maxLength = $ns === $siteConfig->canonicalNamespaceId( 'special' ) ? 512 : 255;
		if ( strlen( $title ) > $maxLength ) {
			throw new TitleException(
				"Title \"$origTitle\" is too long", 'title-invalid-too-long', $title
			);
		}

		if ( $siteConfig->namespaceCase( $ns ) === 'first-letter' ) {
			$title = $siteConfig->ucfirst( $title );
		}

		// Allow "#foo" as a title, which comes in as namespace 0.
		// TODO: But should this exclude "_#foo" and the like?
		if ( $title === '' && $ns !== $siteConfig->canonicalNamespaceId( '' ) ) {
			throw new TitleException( 'Empty title', 'title-invalid-empty', $title );
		}

		if ( $ns === $siteConfig->canonicalNamespaceId( 'user' ) ||
			$ns === $siteConfig->canonicalNamespaceId( 'user_talk' )
		) {
			$title = self::sanitizeIP( $title );
		}

		if ( $ns === $siteConfig->canonicalNamespaceId( 'special' ) ) {
			$parts = explode( '/', $title, 2 );
			$specialName = $siteConfig->canonicalSpecialPageName( $parts[0] );
			if ( $specialName !== null ) {
				$parts[0] = $specialName;
				$title = implode( '/', $parts );
			}
		}

		return new self( $title, $ns, $siteConfig, $fragment );
	}

	/**
	 * Get the DBkey
	 * @return string
	 */
	public function getKey(): string {
		return $this->dbkey;
	}

	/**
	 * Get the prefixed DBkey
	 * @return string
	 */
	public function getPrefixedDBKey(): string {
		if ( $this->namespaceName === '' ) {
			return $this->dbkey;
		}
		return strtr( $this->namespaceName, ' ', '_' ) . ':' . $this->dbkey;
	}

	/**
	 * Get the prefixed text
	 * @return string
	 */
	public function getPrefixedText(): string {
		$ret = strtr( $this->dbkey, '_', ' ' );
		if ( $this->namespaceName !== '' ) {
			$ret = $this->namespaceName . ':' . $ret;
		}
		return $ret;
	}

	/**
	 * Get the fragment, if any
	 * @return string|null
	 */
	public function getFragment(): ?string {
		return $this->fragment;
	}

	/**
	 * @deprecated Use namespace IDs and SiteConfig methods instead.
	 * @return TitleNamespace
	 */
	public function getNamespace(): TitleNamespace {
		return $this->namespace;
	}

	/**
	 * Get the namespace ID
	 * @return int
	 */
	public function getNamespaceId(): int {
		return $this->namespaceId;
	}

}
