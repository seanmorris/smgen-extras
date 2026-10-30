#!/usr/bin/env node
// Scaffold an SMGen site from an official theme. See lib/main.js for the flow.

import {run} from '../lib/main.js';

process.exitCode = await run(process.argv.slice(2), {
	stdout: process.stdout,
	stderr: process.stderr,
	stdin: process.stdin,
	cwd: process.cwd(),
});
