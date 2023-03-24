<?php
namespace Translator;

abstract class TranslationProject
{
	protected ?string $dir;

	public function __construct()
	{
	}

	public function setBaseDirectory(string $dir): void
	{
		$this->dir = $dir;
	}

	public function getBaseDirectory(): ?string
	{
		return $this->dir;
	}

	public function getTranslationOutputs(): array
	{
		return [];
	}

	public function getTranslationConfigs(): array
	{
		return [];
	}

	public function getMessagePatterns(): MessagePatterns
	{
		$patterns = new MessagePatterns();

		$patterns->addPatterns('js',
		[
			'#(toastError|toastErrorRetry)\(tr\((?P<quote>(\'|"))(?P<msg>([^\2\\\\]|\\\\.)*?)(?P=quote)\)#',
			'#\s+tr\((?P<quote>(`|\'|"))(?P<msg>([^\1\\\\]|\\\\.)*?)(?P=quote)#',
		]);

		$patterns->addPatterns('jsx',
		[
			'#(?<!at)tr\((?P<quote>(`|\'|"))(?P<msg>([^\1\\\\]|\\\\.)*?)(?P=quote)(, {)?#',
			'#(?<!at)tr\((?P<quote>(`|\'|"))(?P<msg>.*?)(?P=quote)(, {)?#',
			'#(title|placeholder|action|successMessage|errorMessage)=(?P<quote>(`|\'|"))(?P<msg>([^\2\\\\]|\\\\.)*?)(?P=quote)#',
		]);

		$patterns->addPatterns('php',
		[
			'#(tr|messageResponse)\((?P<quote>(\'|"))(?P<msg>([^\2\\\\]|\\\\.)*?)(?P=quote)(, [0-9]{3})?#',
		]);

		return $patterns;
	}

	public function createTranslationConfig(): TranslationConfig
	{
		return new TranslationConfig();
	}
}
