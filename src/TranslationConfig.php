<?php
namespace Translator;

class TranslationConfig
{
	protected string $name = 'messages';
	protected ?string $projectDirectory = null;
	protected array $branches = [];
	protected array $includes = [];
	protected array $excludes = [];
	protected array $lists = [];
	protected array $pots = [];
	protected array $outputs = [];
	protected array $hooks = [];
	protected ?array $patterns = null;
	protected bool $withReverse = false;
	protected array $translations = [];

	public function name($name): static
	{
		$this->name = $name;
		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function projectDirectory($dir): static
	{
		$this->projectDirectory = $dir;
		return $this;
	}

	public function getProjectDirectory(): ?string
	{
		return $this->projectDirectory;
	}

	public function include($pattern): static
	{
		if(is_array($pattern))
		{
			$this->includes = array_merge($this->includes, $pattern);
		}
		else
		{
			$this->includes[] = $pattern;
		}

		return $this;
	}

	public function getIncludes(): array
	{
		return $this->includes;
	}

	public function exclude($pattern): static
	{
		if(is_array($pattern))
		{
			$this->excludes = array_merge($this->excludes, $pattern);
		}
		else
		{
			$this->excludes[] = $pattern;
		}

		return $this;
	}

	public function getExcludes(): array
	{
		return $this->excludes;
	}

	public function list($file): static
	{
		if(is_array($file))
		{
			$this->lists = array_merge($this->lists, $file);
		}
		else
		{
			$this->lists[] = $file;
		}

		return $this;
	}

	public function getLists(): array
	{
		return $this->lists;
	}

	public function pot($file): static
	{
		if(is_array($file))
		{
			$this->pots = array_merge($this->pots, $file);
		}
		else
		{
			$this->pots[] = $file;
		}

		return $this;
	}

	public function getPots(): array
	{
		return $this->pots;
	}

	public function hook($which, $fn): static
	{
		$this->hooks[$which][] = $fn;
		return $this;
	}

	public function getHooks(): array
	{
		return $this->hooks;
	}

	public function branch($branch): static
	{
		if(is_array($branch))
		{
			$this->branches = array_merge($this->branches, $branch);
		}
		else
		{
			$this->branches[] = $branch;
		}

		return $this;
	}

	public function getBranches(): array
	{
		return $this->branches;
	}

	public function output($out): static
	{
		if(is_array($out))
		{
			$this->outputs = array_merge($this->outputs, $out);
		}
		else
		{
			$this->outputs[] = $out;
		}

		return $this;
	}

	public function getOutputs(): array
	{
		return $this->outputs;
	}

	public function patterns($patterns): static
	{
		$this->patterns = $patterns;
		return $this;
	}

	public function getPatterns(): ?array
	{
		return $this->patterns;
	}

	public function withReverse($a): static
	{
		$this->withReverse = $a;
		return $this;
	}

	public function isWithReverse(): bool
	{
		return $this->withReverse;
	}

	public function addTranslation($tr): void
	{
		$this->translations[] = $tr;
	}

	public function getTranslations(): array
	{
		return $this->translations;
	}
}
