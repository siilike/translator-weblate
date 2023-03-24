<?php
namespace Translator;

class MessagePatterns
{
	protected array $patterns = [];

	public function addPatterns($ext, $patterns): void
	{
		if(is_array($ext))
		{
			foreach($ext as $a)
			{
				$this->addPatterns($a, $patterns);
			}

			return;
		}

		if(!isset($this->patterns[$ext]))
		{
			$this->patterns[$ext] = [];
		}

		if(!is_array($patterns))
		{
			$patterns = [ $patterns ];
		}

		$this->patterns[$ext] = array_merge($this->patterns[$ext], $patterns);
	}

	public function getPatterns($ext): array
	{
		return $this->patterns[$ext] ?? [];
	}
}
