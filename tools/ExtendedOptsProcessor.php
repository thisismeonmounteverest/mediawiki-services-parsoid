<?php

namespace Parsoid\Tools;

trait ExtendedOptsProcessor {
	private $optionDefaults = [];

	public function setOptionDefault( string $name, $default ) {
		$this->optionDefaults[$name] = $default;
	}

	public function getOption( $name, $default = null ) {
		return parent::getOption(
			$name,
			$default ?? $this->optionDefaults[$name] ?? null
		);
	}

	/**
	 * Return all known CLI options in an associative array
	 * @return array
	 */
	public function optionsToArray(): array {
		$options = [];
		// Set CLI args
		foreach ( $this->options as $name => $value ) {
			$options[$name] = $value;
		}
		// Add in defaults
		foreach ( $this->optionDefaults as $name => $value ) {
			if ( !isset( $options[$name] ) ) {
				$options[$name] = $value;
			}
		}
		return $options;
	}
}
