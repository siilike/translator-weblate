<?php
namespace Translator;

class TranslationOutput
{
	protected array $names;
	protected string $type;
	protected string $dir;
	protected string $projectId;
	protected ?string $javaPackageId;

	protected array $sourceDirs = [];

	public function __construct($names, string $type, string $projectId, string $dir, ?string $javaPackageId = null)
	{
		$this->names = is_array($names) ? $names : [ $names ];
		$this->type = $type;
		$this->projectId = $projectId;
		$this->dir = $dir;
		$this->javaPackageId = $javaPackageId;
	}

	public function getName(): string
	{
		return $this->names[0];
	}

	public function getNames(): array
	{
		return $this->names;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getDirectory(): string
	{
		return $this->dir;
	}

	public function getProjectId(): string
	{
		return $this->projectId;
	}

	public function getJavaPackageId(): ?string
	{
		return $this->javaPackageId;
	}

	public function addSourceDirectory(string $dir): void
	{
		$this->sourceDirs[] = $dir;
	}

	public function getSourceDirectories(): array
	{
		return $this->sourceDirs;
	}

	public function is(string $which): bool
	{
		return in_array($which, $this->names);
	}
}
