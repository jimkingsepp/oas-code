<?php

namespace App\CodeGenerator;

class Server implements Collectable
{
	private $url;
	private $description;
	private $variables;

	/**
	 * Get the value of url
	 */
	public function getUrl() : string
	{
		return $this->url;
	}

	/**
	 * Set the value of url
	 *
	 * @return  self
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * Get the value of description
	 */
	public function getDescription() : string
	{
		return $this->description;
	}

	/**
	 * Set the value of description
	 *
	 * @return  self
	 */
	public function setDescription(string $description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get the value of variables
	 */
	public function getVariables() : \stdClass
	{
		return $this->variables;
	}

	/**
	 * Set the value of variables
	 *
	 * @return  self
	 */
	public function setVariables(\stdClass $variables)
	{
		$this->variables = $variables;

		return $this;
	}

	public function toArray() : array
	{
		return get_object_vars($this);
	}
}
