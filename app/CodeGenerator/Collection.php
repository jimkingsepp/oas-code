<?php

namespace App\CodeGenerator;

class Collection implements \Countable, \Iterator
{
	private $collectables = [];
	private $current_index = 0;

	public function toArray() : array
	{
		return array_map(function (Collectable $c) {
			return $c->toArray();
		}, $this->collectables);
	}

	public function add(Collectable $c)
	{
		$this->collectables[] = $c;
	}

	// Countable & Iterator methods that must be implemented
	public function count(): int
	{
		return count($this->collectables);
	}

	public function current(): Collectable
	{
		return $this->collectables[$this->current_index];
	}

	public function key(): int
	{
		return $this->current_index;
	}

	public function next() : void
	{
		$this->current_index++;
	}

	public function rewind() : Collection
	{
		$this->current_index = 0;

		return $this;
	}

	public function valid(): bool
	{
		return isset($this->collectables[$this->current_index]);
	}
}
