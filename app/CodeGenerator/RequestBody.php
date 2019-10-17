<?php

namespace App\CodeGenerator;

class RequestBody
{
	private $content;
	private $required;

	public function __construct(\stdClass $request_data)
	{
        $this->content = new RequestBodyContent($request_data->content);
		$this->required = $request_data->required;
	}

	public function toArray() : array
	{
		return [
			'required' => $this->required,
			'content' => $this->content->toArray()
		];
    }

    public function getContent() : RequestBodyContent
    {
        return $this->content;
    }
}
