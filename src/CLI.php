<?php
namespace Translator;

use splitbrain\phpcli\Options;

class CLI extends \splitbrain\phpcli\CLI
{
	protected $logdefault = 'debug';

	protected function setup(Options $options)
	{
		$options->setHelp('A command-line interface to manage translations.');

		$options->registerCommand('generate', 'Generate source files');

		$options->registerCommand('write', 'Write translations');
		$options->registerCommand('validate', 'Validate translations');

		$options->registerOption('basedir', 'Base directory', 'b', 'directory');
		$options->registerOption('version', 'Version', 'v', 'version');

		$options->registerOption('config', 'Configuration file', 'c', 'file');
		$options->registerOption('project', 'Project to process', 'p', 'id', 'write');
		$options->registerOption('project-dir', 'Project directory to write outputs', 'd', 'directory', 'write');
		$options->registerOption('valid-only', 'Only valid translations', 'x', false, 'write');
	}

	protected function main(Options $options)
	{
		$cmd = $options->getCmd();

		if(!in_array($cmd, [ 'generate', 'write', 'validate' ]))
		{
			echo $options->help();
			exit(1);
		}

		$dir = $options->getOpt('basedir') ?: '.';
		$config = $options->getOpt('config') ?: 'config.php';

		if(!file_exists($dir.'/'.$config))
		{
			$this->error("$config not found in ".$dir);
			exit(1);
		}

		require($dir . '/' . $config);

		$conf = new \Project();
		$conf->setBaseDirectory($dir);

		$proc = new TranslationProcessor($conf, $this);

		if($cmd === 'generate')
		{
			$this->info('Generating source files');

			$proc->writeSources();

			exit(0);
		}
		else if($cmd === 'write')
		{
			$project = $options->getOpt('project');
			$projectDir = $options->getOpt('project-dir');

			if(!$project)
			{
				$this->error('Project not specified');
				exit(1);
			}

			if(!$projectDir)
			{
				$this->error('Project directory not specified');
				exit(1);
			}

			if(!file_exists($projectDir))
			{
				$this->error('Project directory does not exist');
				exit(1);
			}

			$validOnly = !!$options->getOpt('valid-only');

			$this->info($validOnly ? 'Writing valid translations' : 'Writing translations');

			$proc->writeTranslations($project, $projectDir, $validOnly);

			exit(0);
		}
		else if($cmd === 'validate')
		{
			$this->info('Validating translations');

			$errors = $proc->validateTranslations();

			$this->info("Found $errors errors");

			exit($errors > 0 ? 1 : 0);
		}

		$this->error('Unknown command '.$cmd);
		exit(1);
	}
}
