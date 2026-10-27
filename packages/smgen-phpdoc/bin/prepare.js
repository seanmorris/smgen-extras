#!/usr/bin/env node

import {runPrepareHook} from '../src/plugin-hook.js';

try
{
	await runPrepareHook();
}
catch(error)
{
	const message = error instanceof Error ? error.message : String(error);
	process.stderr.write(`smgen-phpdoc: ${message}\n`);
	process.exitCode = 1;
}
