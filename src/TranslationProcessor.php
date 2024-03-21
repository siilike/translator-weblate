<?php
namespace Translator;

use DirectoryIterator;
use Exception;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Gettext\Translations;
use Magneds\MessageFormat\MessageFormatter;

class TranslationProcessor
{
	protected TranslationProject $proj;
	protected CLI $cli;

	public function __construct(TranslationProject $proj, ?CLI $cli = null)
	{
		$this->proj = $proj;
		$this->cli = $cli;
	}

	public function debug($msg): void
	{
		if(!empty($this->cli))
		{
			$this->cli->debug($msg);
		}
	}

	public function info($msg): void
	{
		if(!empty($this->cli))
		{
			$this->cli->info($msg);
		}
	}

	public function warn($msg): void
	{
		if(!empty($this->cli))
		{
			$this->cli->warning($msg);
		}
	}

	public function error($msg): void
	{
		if(!empty($this->cli))
		{
			$this->cli->error($msg);
		}
	}

	public static function ensureFullLocale($input, $separator = '-')
	{
		if(strlen($input) === 2)
		{
			$countries =
			[
				'af' => 'ZA', // ZA NA
				'ar' => 'AR', // AR MA SA
				'ay' => 'BO',
				'az' => 'AZ',
				'be' => 'BY',
				'bg' => 'BG',
				'bn' => 'IN', // IN BD
				'bs' => 'BA',
				'ca' => 'ES',
				'ck' => 'US',
				'cs' => 'CZ',
				'cy' => 'GB',
				'da' => 'DK',
				'de' => 'DE', // DE AT CH
				'el' => 'GR',
				'en' => 'GB', // GB AU CA IE IN PI UD US ZA
				'eo' => 'EO',
				'es' => 'ES', // ES AR 419 CL CO EC LA NI MX US VE
				'et' => 'EE',
				'eu' => 'ES',
				'fa' => 'IR',
				'fi' => 'FI',
				'fo' => 'FO',
				'fr' => 'FR', // FR CA BE CH
				'fy' => 'NL',
				'ga' => 'IE',
				'gl' => 'ES',
				'gn' => 'PY',
				'gu' => 'IN',
				'gx' => 'GR',
				'he' => 'IL',
				'hi' => 'IN',
				'hr' => 'HR',
				'hu' => 'HU',
				'hy' => 'AM',
				'id' => 'ID',
				'is' => 'IS',
				'it' => 'IT',
				'ja' => 'JP',
				'jv' => 'ID',
				'ka' => 'GE',
				'kk' => 'KZ',
				'km' => 'KH',
				'kn' => 'IN',
				'ko' => 'KR',
				'ku' => 'TR',
				'la' => 'VA',
				'li' => 'NL',
				'lt' => 'LT',
				'lv' => 'LV',
				'mg' => 'MG',
				'mk' => 'MK',
				'ml' => 'IN',
				'mn' => 'MN',
				'mr' => 'IN',
				'ms' => 'MY',
				'mt' => 'MT',
				'nb' => 'NO',
				'ne' => 'NP',
				'nl' => 'NL', // NL BE
				'nn' => 'NO',
				'or' => 'IN',
				'pa' => 'IN',
				'pl' => 'PL',
				'ps' => 'AF',
				'pt' => 'PT', // PT BR
				'qu' => 'PE',
				'rm' => 'CH',
				'ro' => 'RO',
				'ru' => 'RU',
				'sa' => 'IN',
				'se' => 'NO',
				'si' => 'LK',
				'sk' => 'SK',
				'sl' => 'SI',
				'so' => 'SO',
				'sq' => 'AL',
				'sr' => 'RS',
				'sv' => 'SE',
				'sw' => 'KE',
				'ta' => 'IN',
				'te' => 'IN',
				'tg' => 'TJ',
				'th' => 'TH',
				'tl' => 'PH',
				'tr' => 'TR',
				'tt' => 'RU',
				'uk' => 'UA',
				'ur' => 'PK',
				'uz' => 'UZ',
				'vi' => 'VN',
				'xh' => 'ZA',
				'yi' => 'DE',
				'zh' => 'Hans', // CN Hans Hant HK SG TW
				'zu' => 'ZA',
				'lo' => 'LA',

				'xx' => 'XX', // "unset"
			];

			return $input.$separator.$countries[$input];
		}

		return str_replace([ '-', '_' ], $separator, $input);
	}

	public static function exec(string $cmd, string $cwd = '.', array $env = []): string
	{
		$result = self::exec0($cmd, $cwd, $env);

		if($result['code'] != 0)
		{
			echo $result['stderr'] . "\n";

			throw new Exception("Command $cmd in $cwd failed with code {$result['code']}");
		}

		return $result['stdout'];
	}

	public static function exec0(string $cmd, string $cwd = '.', array $env = []): array
	{
		$proc = proc_open($cmd,
		[
			0 => [ "pipe", "r" ],
			1 => [ "pipe", "w" ],
			2 => [ "pipe", "w" ],
		], $pipes, $cwd, array_merge(
		[
			'PATH' => getenv('PATH'),
			'HOME' => getenv('HOME'),
			'LANG' => 'C.UTF-8',
		], $env));

		if(is_resource($proc))
		{
			fclose($pipes[0]);

			$stdout = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[2]);

			$code = proc_close($proc);

			$ret =
			[
				'cmd' => $cmd,
				'stdout' => $stdout,
				'stderr' => $stderr,
				'code' => $code,
			];
		}
		else
		{
			$ret =
			[
				'cmd' => $cmd,
				'code' => -1
			];
		}

		return $ret;
	}

	public function getConfig(): TranslationProject
	{
		return $this->proj;
	}

	public function writeSources(): void
	{
		$this->writePots($this->process());
	}

	public function writePots($results): void
	{
		$dir = $this->proj->getBaseDirectory();

		$g = new PoGenerator();
		foreach($results as $n => $r)
		{
			$g->generateFile($r, "$dir/$n.pot");
		}
	}

	public function writeTranslations(string $projectId, string $outputBaseDir, bool $validOnly = false, bool $includeFuzzy = false): void
	{
		$out = $this->proj->getTranslationOutputs();

		$configsByName = [];
		foreach($this->proj->getTranslationConfigs() as $a)
		{
			$configsByName[$a->getName()] = $a;
		}

		$didWork = false;
		foreach($out as $a)
		{
			if($a->getProjectId() !== $projectId)
			{
				$this->debug("Ignoring non-matching project ".$a->getProjectId());
				continue;
			}

			$config = @$configsByName[$a->getName()];

			if(!$config)
			{
				$this->warn("Config ".$a->getName()." not found");
			}

			$d = [];

			$srcDirs = array_merge([ $this->proj->getBaseDirectory().'/'.$a->getName() ], $a->getSourceDirectories());

			foreach($srcDirs as $srcDir)
			{
				if(!file_exists($srcDir))
				{
					$this->error("Ignoring non-existent directory ".$srcDir);
					continue;
				}

				foreach(new DirectoryIterator($srcDir) as $file)
				{
					if($file->isDot())
					{
						continue;
					}

					if(!preg_match('#\.po$#', $file->getFilename()))
					{
						$this->warn("Ignoring non-po file ".$file->getFilename());
						continue;
					}

					$locale = static::ensureFullLocale($file->getBasename('.po'));

					if(!array_key_exists($locale, $d[$a->getName()] ?? []))
					{
						$d[$a->getName()][$locale] = (new PoLoader())->loadFile($file->getPathname());
					}
					else
					{
						$translations = $d[$a->getName()][$locale];

						foreach((new PoLoader())->loadFile($file->getPathname()) as $t)
						{
							if(!empty($t->getTranslation()))
							{
								$translations->add($t);
							}
						}
					}

					if($validOnly)
					{
						$d[$a->getName()][$locale] = $this->filterValidTranslations($locale, $d[$a->getName()][$locale]);
					}

					if(!$includeFuzzy)
					{
						$d[$a->getName()][$locale] = $this->filterFuzzyTranslations($d[$a->getName()][$locale]);
					}

					if($config && $config->isWithReverse())
					{
						if(!empty($d[$a->getName().'reverse'][$locale]))
						{
							throw new Exception("Multi-source reverse translations not implemented");
						}

						$translations = (new PoLoader())->loadFile($file->getPathname());

						$d[$a->getName().'reverse'][$locale] = $this->flip($translations);
					}
				}
			}

			foreach($d as $name => $locales)
			{
				foreach($locales as $locale => $translations)
				{
					$this->writeTranslations0($projectId, $outputBaseDir, $a, $locale, $name, $translations);

					$didWork = true;
				}
			}
		}

		if(empty($didWork))
		{
			throw new Exception("Project $projectId not found or did not write any translations!");
		}
	}

	public function writeTranslations0(string $projectId, string $outputBaseDir, TranslationOutput $output, string $locale, string $name, Translations $translations): void
	{
		$this->info("Writing ".$locale." / ".$name);

		if($output->getType() == "mo")
		{
			$outDir = $outputBaseDir.'/'.$output->getDirectory().'/'.str_replace('-', '_', $locale).'/LC_MESSAGES/';
			$outFile = $outDir.$name.'.mo';

			@mkdir($outDir, 0755, true);

			$this->debug("Writing to ".$outFile);

			(new MoGenerator())->generateFile($translations, $outFile);
		}
		else if($output->getType() == "js")
		{
			$outDir = $outputBaseDir.'/'.$output->getDirectory();

			@mkdir($outDir, 0755, true);

			$ret = [];
			foreach($translations as $t)
			{
				$msgid = $t->getOriginal();
				$msgstr = $t->getTranslation();

				if(!empty($msgid) && $msgstr !== "" && $msgstr !== null)
				{
					$ret[$msgid] = $msgstr;
				}
			}

			$json = json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			$json = str_replace('\\\n', '\n', $json);

			$outFile = $outDir.'/'.$name.'_'.str_replace('-', '_', $locale).'.js';

			$this->debug("Writing to ".$outFile);

			file_put_contents($outFile, 'export default '.$json);
		}
		else if($output->getType() == "java")
		{
			$outDir = $outputBaseDir.'/'.$output->getDirectory();
			$tmpDir = '/tmp/translator/';
			$tmpFile = $tmpDir.'/tmp.po';

			$this->debug("Writing to ".$outDir);

			self::exec("rm -rf ".escapeshellarg($tmpDir));

			@mkdir($outDir, 0755, true);
			@mkdir($tmpDir, 0755, true);

			(new PoGenerator())->generateFile($translations, $tmpFile);

			self::exec("msgfmt --java2 -d ".escapeshellarg($tmpDir)." -r ".escapeshellarg($output->getJavaPackageId().".".$name)." -l ".escapeshellarg(str_replace('-', '_', $locale))." ".escapeshellarg($tmpFile));

			self::exec("find ".escapeshellarg($tmpDir)." -name *.class -exec cp \"{}\" ".escapeshellarg($outDir)." \\;");

			self::exec("rm -rf ".escapeshellarg($tmpDir));
		}
		else
		{
			throw new Exception("Unknown type: ".$output->getType());
		}
	}

	protected function findVariableNames(string $source): array
	{
		$matches = [];
		preg_match_all('#({\s*([[:alnum:]]+)\s*,([^{}]*|{[^{}]*})*}|{([[:alnum:]]+)})#', $source, $matches);

		$ret = $matches[4];

		foreach($matches[1] as $a)
		{
			$m = [];
			preg_match('#{\s*([[:alnum:]]+)\s*,#', $a, $m);

			if(!empty($m))
			{
				$ret[] = $m[1];
			}
		}

		return $ret;
	}

	public function validateTranslations(): int
	{
		$names = array_unique(array_map(fn($a) => $a->getName(), $this->proj->getTranslationConfigs()));

		$errors = 0;

		foreach($names as $name)
		{
			$poDir = $this->proj->getBaseDirectory().'/'.$name;

			if(!file_exists($poDir))
			{
				$this->debug("Ignoring non-existent directory ".$poDir);
				continue;
			}

			foreach(new DirectoryIterator($poDir) as $a)
			{
				if($a->isDot()) continue;

				$this->info("Validating ".$name."/".$a);

				$trs = (new PoLoader())->loadFile($a->getPathname(), null);

				foreach($trs->getTranslations() as $t)
				{
					if($this->isValid($a, $t) === false)
					{
						$errors++;
					}
				}
			}
		}

		return $errors;
	}

	public function isValid(string $locale, Translation $t): ?bool
	{
		if($t->getTranslation() === "")
		{
			return null;
		}

		$originalVariableNames = $this->findVariableNames($t->getOriginal());
		$translationVariableNames = $this->findVariableNames($t->getTranslation());
		$matching = array_intersect($originalVariableNames, $translationVariableNames);

		if(count($matching) !== count($originalVariableNames))
		{
			$this->debug('Missing variables: ' . join(", ", array_diff($originalVariableNames, $matching)));
			$this->error("Found invalid translation for {$locale}: {$t->getOriginal()} ==> {$t->getTranslation()}");
			return false;
		}

		$values = array_map(fn($a) => 1, array_flip($originalVariableNames));

		try
		{
			$f = new MessageFormatter($locale, $t->getTranslation());

			if($f->format($values) === false)
			{
				throw new Exception();
			}
		}
		catch(Exception $e)
		{
			$this->debug("Got error: ".$e->getMessage());

			$this->error("Found invalid translation for {$locale}: {$t->getOriginal()} ==> {$t->getTranslation()}");
			return false;
		}

		return true;
	}

	public function filterValidTranslations(string $locale, Translations $translations): Translations
	{
		$ret = clone $translations;

		foreach($ret->getTranslations() as $a)
		{
			if(!$this->isValid($locale, $a))
			{
				$ret->remove($a);
			}
		}

		return $ret;
	}

	public function filterFuzzyTranslations(Translations $translations): Translations
	{
		$ret = clone $translations;

		foreach($ret->getTranslations() as $a)
		{
			if($a->getFlags()->has('fuzzy'))
			{
				$ret->remove($a);
			}
		}

		return $ret;
	}

	public function process(): array
	{
		$results = [];

		$configs = $this->proj->getTranslationConfigs();

		foreach($configs as $conf)
		{
			$this->processTranslationConfig($conf);
		}

		foreach($configs as $conf)
		{
			$trs = @$results[$conf->getName()];

			if(!$trs)
			{
				$trs = Translations::create($conf->getName(), null);
				$trs->getHeaders()->set('Content-Type', 'text/plain; charset=UTF-8');

				$results[$conf->getName()] = $trs;
			}

			foreach($conf->getTranslations() as $a)
			{
				$trs->addOrMerge($a);
			}
		}

		return $results;
	}

	public function processTranslationConfig(TranslationConfig $conf): void
	{
		$this->runHooks($conf, 'pre');

		$projectDir = $conf->getProjectDirectory();
		$branches = null;

		if(!empty($projectDir))
		{
			if(!str_starts_with($projectDir, '/'))
			{
				$projectDir = $this->proj->getBaseDirectory() . '/' . $projectDir;
			}

			$branches = $conf->getBranches();

			if(empty($branches))
			{
				$branches = array_filter(array_map('trim', explode("\n", self::exec('git branch -a -r', $projectDir))), fn($a) => !empty($a) && !str_contains($a, 'origin/HEAD'));
			}

			// FIXME: using local branches when remote not available
			if(empty($branches))
			{
				$branches = array_filter(array_map('trim', explode("\n", str_replace('*', '', self::exec('git branch -a', $projectDir)))), fn($a) => !empty($a) && !str_contains($a, 'origin/HEAD'));
			}

			foreach($branches as $branch)
			{
				$files = array_filter(explode("\n", self::exec("git ls-tree $branch -r --name-only", $projectDir)), function($a) use($conf)
				{
					foreach($conf->getExcludes() as $b)
					{
						if(preg_match('#'.$b.'#', $a))
						{
							return false;
						}
					}

					if(!empty($conf->getIncludes()))
					{
						foreach($conf->getIncludes() as $b)
						{
							if(preg_match('#'.$b.'#', $a))
							{
								return true;
							}
						}

						return false;
					}

					return true;
				});

				foreach($files as $f)
				{
					$d = self::exec("git show $branch:'./$f'", $projectDir);

					$this->processFile($conf, $branch, $f, $d);
				}
			}
		}

		foreach($conf->getLists() as $l)
		{
			if(!str_starts_with($l, '/'))
			{
				$l = $this->proj->getBaseDirectory() . '/' . $l;
			}

			if(strpos($l, '.json') === strlen($l)-5)
			{
				$d = json_decode(file_get_contents($l));
			}
			else
			{
				$d = array_filter(explode("\n", file_get_contents($l)));
			}

			foreach($d as $m)
			{
				if(empty($m)) continue;

				$tr = Translation::create(null, $m);
				$tr->getReferences()->add($l);

				$conf->addTranslation($tr);
			}
		}

		foreach($conf->getPots() as $l)
		{
			if(!str_starts_with($l, '/'))
			{
				$l = $this->proj->getBaseDirectory() . '/' . $l;
			}

			$trs = (new PoLoader())->loadFile($l, null);

			foreach($trs as $tr)
			{
				$conf->addTranslation($tr);
			}
		}

		$this->runHooks($conf, 'post',
		[
			'branches' => $branches,
			'projectDir' => $projectDir,
		]);
	}

	public function processFile(TranslationConfig $conf, string $branch, string $file, string $data): void
	{
		$patterns = $conf->getPatterns();

		if(empty($patterns))
		{
			$patterns = $this->proj->getMessagePatterns();
		}

		$ext = pathinfo($file, PATHINFO_EXTENSION);

		foreach($patterns->getPatterns($ext) as $pattern)
		{
			$matches = [];
			$x = preg_match_all($pattern, $data, $matches);

			if($x === false)
			{
				throw new Exception("preg_match_all returned false for pattern $pattern");
			}

			if(!empty($matches['msg']))
			{
				foreach($matches['msg'] as $i => $m)
				{
					$q = $matches['quote'][$i];

					if($q == '"')
					{
						$m = str_replace('\\"', '"', $m);
					}
					else if($q == "'")
					{
						$m = str_replace("\\'", "'", $m);
					}

					if(empty($m)) continue;

					$tr = Translation::create(null, $m);
					$tr->getReferences()->add($branch.' '.$file);

					$conf->addTranslation($tr);
				}
			}
		}
	}

	public function flip(Translations $translations): Translations
	{
		$t = Translations::create($translations->getDomain(), $translations->getLanguage());
		$t->getHeaders()->set("Content-Type", "text/plain; charset=UTF-8");

		foreach($translations->getTranslations() as $a)
		{
			if(!$a->isTranslated())
			{
				$this->error("No translation for ".$a->getOriginal());
				continue;
			}

			$b = Translation::create($a->getContext(), mb_strtolower($a->getTranslation()));
			$b->translate($a->getOriginal());

			$t->addOrMerge($b);
		}

		return $t;
	}

	protected function runHooks(TranslationConfig $conf, string $which, array $args = []): void
	{
		$hooks = @$conf->getHooks()[$which];

		if(!empty($hooks))
		{
			foreach($hooks as $a)
			{
				$a($conf, $args);
			}
		}
	}
}
