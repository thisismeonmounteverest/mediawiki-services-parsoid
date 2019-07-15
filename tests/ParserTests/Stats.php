<?php
declare( strict_types = 1 );

namespace Parsoid\Tests\ParserTests;

class Stats {
	/** @var Stats[] */
	public $modes;

	/** @var int */
	public $passedTests = 0;

	/** @var int */
	public $passedTestsUnexpected = 0;

	/** @var int */
	public $failedTests = 0;

	/** @var int */
	public $failedTestsUnexpected = 0;

	/** @var int */
	public $loggedErrorCount = 0;

	/** @var int */
	public $failures = 0;

	/** @var array Array of elements representing test failures */
	public $failList;

	/** @var string result */
	public $result;

	public function allFailures(): int {
		$this->failures = $this->passedTestsUnexpected +
			$this->failedTestsUnexpected + $this->loggedErrorCount;

		return $this->failures;
	}

	public function accum( Stats $other ) {
		$this->passedTests += $other->passedTests;
		$this->passedTestsUnexpected += $other->passedTestsUnexpected;
		$this->failedTests += $other->failedTests;
		$this->failedTestsUnexpected += $other->failedTestsUnexpected;
		$this->loggedErrorCount += $other->loggedErrorCount;
		$this->failures += $other->failures;
	}
}
